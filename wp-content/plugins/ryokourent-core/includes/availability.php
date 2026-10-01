<?php
/**
 * Availability Engine & Atomic Double-Booking Prevention for Ryokourent
 *
 * Implements:
 * - Real-time physical fleet quota verification (Internal only, strictly zero public leak).
 * - Multi-criteria date overlapping query for active bookings (status_dikonfirmasi, status_berjalan).
 * - Two critical lock checkpoints (Point 1: Online Form Submit; Point 2: Admin Confirmation).
 * - Atomic MySQL GET_LOCK / RELEASE_LOCK wrapper with guaranteed finally release.
 * - Public AJAX endpoint returning strictly boolean availability without exposing internal stock numbers.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/includes
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve total physical stock units for a motorcycle model.
 *
 * STRICTLY INTERNAL OPERATOR METRIC:
 * This value must NEVER be sent to public API responses or client-side markup.
 *
 * @since 1.0.0
 * @param int $motor_id CPT 'motor' post ID.
 * @return int Total physical stock units. Default 0.
 */
function ryokourent_get_motor_physical_stock($motor_id) {
    $motor_id = absint($motor_id);
    if ($motor_id <= 0) {
        return 0;
    }

    $stock = get_post_meta($motor_id, '_ryokou_physical_stock', true);
    if ($stock !== '' && is_numeric($stock)) {
        return max(0, (int) $stock);
    }

    // Fallback to blueprint catalog if available in mock/demo environment
    if (function_exists('ryokourent_get_blueprint_default_fleet')) {
        $blueprint = ryokourent_get_blueprint_default_fleet();
        $idx = $motor_id - 1;
        if (isset($blueprint[$idx]['physical_stock'])) {
            return max(0, (int) $blueprint[$idx]['physical_stock']);
        }
    }

    return 0;
}

/**
 * Count active bookings that overlap with a requested schedule for a motorcycle.
 *
 * An active booking overlaps if:
 * (StartBooking < RequestedEnd) AND (EndBooking > RequestedStart)
 *
 * Only bookings with statuses that consume quota (status_dikonfirmasi, status_berjalan)
 * are counted against the physical quota.
 *
 * @since 1.0.0
 * @param int    $motor_id           CPT 'motor' post ID.
 * @param string $start_datetime     Requested start datetime string (Y-m-d H:i).
 * @param string $end_datetime       Requested end datetime string (Y-m-d H:i).
 * @param int    $exclude_booking_id Optional booking post ID to exclude (e.g. during confirmation edit).
 * @param array  $statuses           Array of post statuses that consume quota.
 * @return int Total overlapping active bookings count.
 */
function ryokourent_count_overlapping_bookings($motor_id, $start_datetime, $end_datetime, $exclude_booking_id = 0, $statuses = array('status_dikonfirmasi', 'status_berjalan')) {
    global $wpdb;

    $motor_id = absint($motor_id);
    if ($motor_id <= 0 || empty($start_datetime) || empty($end_datetime)) {
        return 0;
    }

    $tz = function_exists('ryokourent_get_timezone') ? ryokourent_get_timezone() : new DateTimeZone('Asia/Jakarta');

    try {
        $start_dt = new DateTime($start_datetime, $tz);
        $end_dt   = new DateTime($end_datetime, $tz);
    } catch (Exception $e) {
        return 0;
    }

    $req_start = $start_dt->format('Y-m-d H:i:s');
    $req_end   = $end_dt->format('Y-m-d H:i:s');

    // Use WP_Query with efficient fields => 'ids'
    $query_args = array(
        'post_type'      => 'penyewaan',
        'post_status'    => !empty($statuses) ? $statuses : array('status_dikonfirmasi', 'status_berjalan'),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_ryokou_booking_motor_id',
                'value'   => $motor_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => '_ryokou_booking_start_datetime',
                'value'   => $req_end,
                'compare' => '<',
                'type'    => 'DATETIME',
            ),
            array(
                'key'     => '_ryokou_booking_end_datetime',
                'value'   => $req_start,
                'compare' => '>',
                'type'    => 'DATETIME',
            ),
        ),
    );

    if ($exclude_booking_id > 0) {
        $query_args['post__not_in'] = array(absint($exclude_booking_id));
    }

    $query = new WP_Query($query_args);

    if (isset($query->posts) && is_array($query->posts)) {
        return count($query->posts);
    }

    return 0;
}

/**
 * Check whether a motorcycle model has available units for a requested schedule.
 *
 * Formula:
 * Available = (PhysicalStock - ActiveOverlappingBookings) > 0
 *
 * @since 1.0.0
 * @param int    $motor_id           CPT 'motor' post ID.
 * @param string $start_datetime     Requested start datetime string.
 * @param string $end_datetime       Requested end datetime string.
 * @param int    $exclude_booking_id Optional booking post ID to exclude.
 * @param array  $statuses           Array of post statuses that consume quota.
 * @return bool True if at least 1 unit is available, false if fully booked.
 */
function ryokourent_check_availability($motor_id, $start_datetime, $end_datetime, $exclude_booking_id = 0, $statuses = array('status_dikonfirmasi', 'status_berjalan')) {
    $motor_id = absint($motor_id);
    if ($motor_id <= 0) {
        return false;
    }

    $physical_stock = ryokourent_get_motor_physical_stock($motor_id);

    // If no physical stock is assigned, unit is not available for online booking
    if ($physical_stock <= 0) {
        return false;
    }

    $active_bookings = ryokourent_count_overlapping_bookings(
        $motor_id,
        $start_datetime,
        $end_datetime,
        $exclude_booking_id,
        $statuses
    );

    $remaining_stock = $physical_stock - $active_bookings;

    return ($remaining_stock > 0);
}

/**
 * Execute a critical callback within an atomic motor-level lock.
 *
 * Uses MySQL GET_LOCK() when available on the active database connection,
 * with guaranteed RELEASE_LOCK() in the finally block to prevent deadlocks.
 * Automatically falls back to transient-based locking in non-MySQL environments.
 *
 * @since 1.0.0
 * @param int      $motor_id        Motor post ID to lock.
 * @param callable $callback        Function to execute inside atomic lock.
 * @param int      $timeout_seconds Maximum seconds to wait for lock acquisition. Default 10.
 * @return mixed Return value of the callback, or WP_Error on lock failure.
 */
function ryokourent_with_motor_lock($motor_id, $callback, $timeout_seconds = 10) {
    global $wpdb;

    $motor_id = absint($motor_id);
    if ($motor_id <= 0 || !is_callable($callback)) {
        return new WP_Error('invalid_lock_parameters', __('Parameter penguncian armada tidak valid.', 'ryokourent'));
    }

    $lock_name    = 'ryokourent_lock_motor_' . $motor_id;
    $has_mysql    = is_object($wpdb) && method_exists($wpdb, 'get_var');
    $lock_acquired = false;

    // 1. Acquire Lock
    if ($has_mysql) {
        try {
            $lock_res = $wpdb->get_var(
                $wpdb->prepare("SELECT GET_LOCK(%s, %d)", $lock_name, $timeout_seconds)
            );
            $lock_acquired = ($lock_res == 1 || $lock_res === '1');
        } catch (Exception $e) {
            $lock_acquired = false;
        }
    }

    // Fallback to transient lock if MySQL GET_LOCK unavailable
    if (!$lock_acquired) {
        $transient_key = 'ryokou_lock_' . $motor_id;
        $start_time = time();
        while ((time() - $start_time) < $timeout_seconds) {
            if (!get_transient($transient_key)) {
                set_transient($transient_key, 1, max(5, $timeout_seconds));
                $lock_acquired = true;
                break;
            }
            usleep(100000); // 100ms
        }
    }

    if (!$lock_acquired) {
        return new WP_Error(
            'lock_acquisition_timeout',
            __('Sistem sedang memproses pemesanan lain untuk armada ini. Silakan coba sesaat lagi.', 'ryokourent')
        );
    }

    // 2. Execute Callback with Guaranteed Release
    try {
        $result = call_user_func($callback);
        return $result;
    } finally {
        // 3. Always Release Lock
        if ($has_mysql) {
            try {
                $wpdb->query(
                    $wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name)
                );
            } catch (Exception $e) {
                // Ignore release errors to prevent throwing from finally
            }
        }
        delete_transient('ryokou_lock_' . $motor_id);
    }
}

/**
 * Programmatically confirm a booking with atomic double-booking protection.
 *
 * Point 2 Critical Checkpoint: Ensures quota is still available before transitioning
 * a booking to 'status_dikonfirmasi'.
 *
 * @since 1.0.0
 * @param int $booking_id CPT 'penyewaan' post ID.
 * @return array Array with keys 'success' (bool), 'code' (string), and 'message' (string).
 */
function ryokourent_confirm_booking($booking_id) {
    $booking_id = absint($booking_id);
    if ($booking_id <= 0) {
        return array(
            'success' => false,
            'code'    => 'invalid_booking_id',
            'message' => __('ID Pemesanan tidak valid.', 'ryokourent'),
        );
    }

    $motor_id = (int) get_post_meta($booking_id, '_ryokou_booking_motor_id', true);
    $start    = (string) get_post_meta($booking_id, '_ryokou_booking_start_datetime', true);
    $end      = (string) get_post_meta($booking_id, '_ryokou_booking_end_datetime', true);

    if ($motor_id <= 0 || empty($start) || empty($end)) {
        return array(
            'success' => false,
            'code'    => 'missing_booking_meta',
            'message' => __('Data armada atau jadwal pemesanan tidak lengkap.', 'ryokourent'),
        );
    }

    // Execute within atomic lock
    return ryokourent_with_motor_lock($motor_id, function () use ($booking_id, $motor_id, $start, $end) {
        // Check availability excluding current booking
        $is_available = ryokourent_check_availability($motor_id, $start, $end, $booking_id);

        if (!$is_available) {
            return array(
                'success' => false,
                'code'    => 'quota_full',
                'message' => __('Gagal mengonfirmasi: Kuota armada motor telah penuh pada jadwal sewa tersebut.', 'ryokourent'),
            );
        }

        // Transition status to status_dikonfirmasi
        $updated = wp_update_post(array(
            'ID'          => $booking_id,
            'post_status' => 'status_dikonfirmasi',
        ));

        if (is_wp_error($updated) || !$updated) {
            return array(
                'success' => false,
                'code'    => 'update_failed',
                'message' => __('Gagal memperbarui status pemesanan di database.', 'ryokourent'),
            );
        }

        return array(
            'success' => true,
            'code'    => 'confirmed',
            'message' => __('Pemesanan berhasil dikonfirmasi dan kuota unit telah terkunci.', 'ryokourent'),
        );
    });
}

/**
 * Intercept WordPress post status transitions on CPT 'penyewaan'.
 *
 * Point 2 Critical Checkpoint: Prevents operators from confirming an order if the fleet
 * has run out of physical quota for the requested schedule.
 *
 * @since 1.0.0
 * @param string  $new_status New post status slug.
 * @param string  $old_status Old post status slug.
 * @param WP_Post $post       Post object.
 * @return void
 */
function ryokourent_intercept_booking_confirmation($new_status, $old_status, $post) {
    if (!$post || $post->post_type !== 'penyewaan') {
        return;
    }

    // Only intercept when moving TO 'status_dikonfirmasi' from another status
    if ($new_status !== 'status_dikonfirmasi' || $old_status === 'status_dikonfirmasi') {
        return;
    }

    // Prevent recursive loop
    static $is_processing = false;
    if ($is_processing) {
        return;
    }

    $motor_id = (int) get_post_meta($post->ID, '_ryokou_booking_motor_id', true);
    $start    = (string) get_post_meta($post->ID, '_ryokou_booking_start_datetime', true);
    $end      = (string) get_post_meta($post->ID, '_ryokou_booking_end_datetime', true);

    if ($motor_id <= 0 || empty($start) || empty($end)) {
        return;
    }

    $is_processing = true;

    try {
        ryokourent_with_motor_lock($motor_id, function () use ($post, $old_status, $motor_id, $start, $end) {
            $is_available = ryokourent_check_availability($motor_id, $start, $end, $post->ID);

            if (!$is_available) {
                // Revert status to old status
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    array('post_status' => $old_status),
                    array('ID' => $post->ID)
                );
                clean_post_cache($post->ID);

                // Set admin flash message
                $user_id = function_exists('get_current_user_id') ? get_current_user_id() : 0;
                if ($user_id > 0) {
                    set_transient(
                        'ryokourent_admin_error_' . $user_id,
                        __('Gagal mengonfirmasi pesanan: Kuota armada motor telah penuh terisi booking lain pada jadwal tersebut.', 'ryokourent'),
                        45
                    );
                }
            }
        });
    } finally {
        $is_processing = false;
    }
}
add_action('transition_post_status', 'ryokourent_intercept_booking_confirmation', 10, 3);

/**
 * Display flash error message in WordPress admin when confirmation fails.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_display_admin_booking_notices() {
    if (!function_exists('get_current_user_id')) {
        return;
    }

    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return;
    }

    $notice = get_transient('ryokourent_admin_error_' . $user_id);
    if ($notice) {
        delete_transient('ryokourent_admin_error_' . $user_id);
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>⚠️ <?php echo esc_html($notice); ?></strong></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'ryokourent_display_admin_booking_notices');

/**
 * Public AJAX Handler for Checking Unit Availability.
 *
 * Hook: 'ryokourent_check_unit_availability'
 *
 * PRIVACY RULE: Returns strictly boolean available: true/false.
 * Physical stock numbers are NEVER exposed to the client.
 *
 * @since 1.0.0
 * @return void Sends JSON response and exits.
 */
function ryokourent_ajax_check_unit_availability() {
    $motor_id = isset($_POST['motor_id']) ? absint(wp_unslash($_POST['motor_id'])) : 0;
    $start    = isset($_POST['start_datetime']) ? sanitize_text_field(wp_unslash($_POST['start_datetime'])) : '';
    $end      = isset($_POST['end_datetime']) ? sanitize_text_field(wp_unslash($_POST['end_datetime'])) : '';

    if ($motor_id <= 0 || empty($start) || empty($end)) {
        wp_send_json_error(array(
            'available' => false,
            'message'   => __('Parameter pemeriksaan ketersediaan tidak lengkap.', 'ryokourent'),
        ), 400);
    }

    $is_available = ryokourent_check_availability($motor_id, $start, $end);

    if ($is_available) {
        wp_send_json_success(array(
            'available' => true,
            'message'   => __('Unit armada tersedia untuk jadwal yang dipilih.', 'ryokourent'),
        ), 200);
    } else {
        wp_send_json_success(array(
            'available' => false,
            'message'   => __('Armada ini telah terpesan penuh pada jadwal tersebut. Silakan pilih armada lain atau sesuaikan jadwal Anda.', 'ryokourent'),
        ), 200);
    }
}
add_action('wp_ajax_ryokourent_check_unit_availability', 'ryokourent_ajax_check_unit_availability');
add_action('wp_ajax_nopriv_ryokourent_check_unit_availability', 'ryokourent_ajax_check_unit_availability');
