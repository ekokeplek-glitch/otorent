<?php
/**
 * Test Suite for Atomic Double-Booking Prevention & Critical Lock Points (TASK-017)
 *
 * Verifies:
 * - Atomic motor locking wrapper `ryokourent_with_motor_lock()` execution and release.
 * - Point 1 (Online Form Submit): Simulation of 2 concurrent booking requests on a unit
 *   with remaining quota = 1. Exactly one request must succeed, and the second must be rejected.
 * - Point 2 (Operator Confirmation): Rejection of status transition to 'status_dikonfirmasi'
 *   if the fleet quota is already exhausted by other confirmed bookings.
 * - Guaranteed release of locks in finally block to prevent deadlock.
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

// Define ABSPATH and plugin constants for mock environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}
if (!defined('RYOKOURENT_PLUGIN_DIR')) {
    define('RYOKOURENT_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('RYOKOURENT_TIMEZONE')) {
    define('RYOKOURENT_TIMEZONE', 'Asia/Jakarta');
}

// Global mock bookings database for testing
$GLOBALS['mock_bookings'] = array();
$GLOBALS['mock_transients'] = array();

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        return $number == 1 ? $single : $plural;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($data) {
        return $data;
    }
}
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int) $maybeint);
    }
}
if (!function_exists('get_transient')) {
    function get_transient($key) {
        return isset($GLOBALS['mock_transients'][$key]) ? $GLOBALS['mock_transients'][$key] : false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0) {
        $GLOBALS['mock_transients'][$key] = $value;
        return true;
    }
}
if (!function_exists('delete_transient')) {
    function delete_transient($key) {
        unset($GLOBALS['mock_transients'][$key]);
        return true;
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        // Motor 2: Honda Trail CRF 150L (Only 1 physical unit in fleet)
        if ($post_id === 2 && $key === '_ryokou_physical_stock') {
            return 1;
        }
        // Booking metadata mock
        if (isset($GLOBALS['mock_bookings'][$post_id])) {
            $booking = $GLOBALS['mock_bookings'][$post_id];
            if ($key === '_ryokou_booking_motor_id') return $booking['motor_id'];
            if ($key === '_ryokou_booking_start_datetime') return $booking['start'];
            if ($key === '_ryokou_booking_end_datetime') return $booking['end'];
        }
        return '';
    }
}
if (!function_exists('wp_update_post')) {
    function wp_update_post($postarr) {
        $id = isset($postarr['ID']) ? $postarr['ID'] : 0;
        if (isset($GLOBALS['mock_bookings'][$id])) {
            if (isset($postarr['post_status'])) {
                $GLOBALS['mock_bookings'][$id]['status'] = $postarr['post_status'];
            }
            return $id;
        }
        return 0;
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return is_a($thing, 'WP_Error');
    }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $code;
        public $message;
        public function __construct($code = '', $message = '') {
            $this->code = $code;
            $this->message = $message;
        }
        public function get_error_message() {
            return $this->message;
        }
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Mock WP_Query
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = array();
        public function __construct($args = array()) {
            $matched_ids = array();
            $target_motor = null;
            $req_end = null;
            $req_start = null;

            if (isset($args['meta_query'])) {
                foreach ($args['meta_query'] as $clause) {
                    if (is_array($clause) && isset($clause['key'])) {
                        if ($clause['key'] === '_ryokou_booking_motor_id') {
                            $target_motor = $clause['value'];
                        } elseif ($clause['key'] === '_ryokou_booking_start_datetime') {
                            $req_end = $clause['value'];
                        } elseif ($clause['key'] === '_ryokou_booking_end_datetime') {
                            $req_start = $clause['value'];
                        }
                    }
                }
            }

            $allowed_statuses = isset($args['post_status']) ? (array) $args['post_status'] : array();
            $excluded = isset($args['post__not_in']) ? (array) $args['post__not_in'] : array();

            foreach ($GLOBALS['mock_bookings'] as $id => $b) {
                if (in_array($id, $excluded, true)) {
                    continue;
                }
                if ($target_motor !== null && $b['motor_id'] !== $target_motor) {
                    continue;
                }
                if (!empty($allowed_statuses) && !in_array($b['status'], $allowed_statuses, true)) {
                    continue;
                }
                // Overlap: StartBooking < req_end AND EndBooking > req_start
                if ($req_end !== null && $req_start !== null) {
                    $b_start = $b['start'];
                    $b_end   = $b['end'];
                    if ($b_start < $req_end && $b_end > $req_start) {
                        $matched_ids[] = $id;
                    }
                } else {
                    $matched_ids[] = $id;
                }
            }
            $this->posts = $matched_ids;
        }
    }
}

// Load helpers and availability module
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/availability.php';

// Test runner
$test_count = 0;
$pass_count = 0;

function run_test($description, $assertion) {
    global $test_count, $pass_count;
    $test_count++;
    if ($assertion) {
        $pass_count++;
        echo "[PASS] " . $description . PHP_EOL;
    } else {
        echo "[FAIL] " . $description . PHP_EOL;
    }
}

echo "=== Menjalankan Unit Test Pencegahan Double Booking Atomik (TASK-017) ===" . PHP_EOL . PHP_EOL;

// 1. Test Lock Wrapper Basic Functionality
$lock_test = ryokourent_with_motor_lock(2, function () {
    return 'lock_success';
});
run_test("Fungsi ryokourent_with_motor_lock mengeksekusi callback dengan sukses", $lock_test === 'lock_success');
run_test("Lock otomatis dilepas setelah callback selesai (no leftover lock)", empty($GLOBALS['mock_transients']['ryokou_lock_2']));

// 2. Test Lock Guaranteed Release on Exception / Error
try {
    ryokourent_with_motor_lock(2, function () {
        throw new Exception("Simulasi error dalam callback");
    });
} catch (Exception $e) {
    // Expected exception
}
run_test("Lock dilepas via blok finally meskipun callback melempar exception", empty($GLOBALS['mock_transients']['ryokou_lock_2']));

// 3. TASK-017 Acceptance Scenario:
// "Tes dua request bersamaan pada unit dengan sisa kuota 1; pastikan hanya satu yang lolos."
// Motor 2 has physical stock = 1.
$sched_start = '2026-10-05 08:30:00';
$sched_end   = '2026-10-07 17:00:00';

$request_1_result = null;
$request_2_result = null;

// Simulate Request A arriving first
$request_1_result = ryokourent_with_motor_lock(2, function () use ($sched_start, $sched_end) {
    $available = ryokourent_check_availability(2, $sched_start, $sched_end);
    if ($available) {
        // Reserve the single remaining unit
        $GLOBALS['mock_bookings'][201] = array(
            'motor_id' => 2,
            'start'    => $sched_start,
            'end'      => $sched_end,
            'status'   => 'status_dikonfirmasi',
        );
        return 'booked_successfully';
    }
    return 'quota_exhausted';
});

// Simulate Request B arriving concurrently for the same unit & schedule
$request_2_result = ryokourent_with_motor_lock(2, function () use ($sched_start, $sched_end) {
    $available = ryokourent_check_availability(2, $sched_start, $sched_end);
    if ($available) {
        $GLOBALS['mock_bookings'][202] = array(
            'motor_id' => 2,
            'start'    => $sched_start,
            'end'      => $sched_end,
            'status'   => 'status_dikonfirmasi',
        );
        return 'booked_successfully';
    }
    return 'quota_exhausted';
});

run_test("Request 1 berhasil memesan unit terakhir (booked_successfully)", $request_1_result === 'booked_successfully');
run_test("Request 2 bersamaan ditolak karena kuota telah habis (quota_exhausted)", $request_2_result === 'quota_exhausted');

// 4. Point 2 Critical Checkpoint: Operator Confirmation Protection
// Simulate pending booking 203 waiting for confirmation
$GLOBALS['mock_bookings'][203] = array(
    'motor_id' => 2,
    'start'    => $sched_start,
    'end'      => $sched_end,
    'status'   => 'status_menunggu',
);

// Operator tries to confirm booking 203, but Motor 2 is already at 100% capacity with booking 201
$confirm_res = ryokourent_confirm_booking(203);
run_test("Konfirmasi operator diblokir saat kuota penuh (success = false)", $confirm_res['success'] === false);
run_test("Kode error konfirmasi adalah quota_full", $confirm_res['code'] === 'quota_full');
run_test("Status booking 203 tetap status_menunggu (tidak berubah ke status_dikonfirmasi)", $GLOBALS['mock_bookings'][203]['status'] === 'status_menunggu');

// Free the slot by completing booking 201
$GLOBALS['mock_bookings'][201]['status'] = 'status_selesai';

// Now operator re-confirms booking 203 with available slot
$confirm_res_2 = ryokourent_confirm_booking(203);
run_test("Konfirmasi operator berhasil setelah slot kembali tersedia (success = true)", $confirm_res_2['success'] === true);
run_test("Status booking 203 berhasil diperbarui menjadi status_dikonfirmasi", $GLOBALS['mock_bookings'][203]['status'] === 'status_dikonfirmasi');

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
