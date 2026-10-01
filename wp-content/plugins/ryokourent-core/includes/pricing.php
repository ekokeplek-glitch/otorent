<?php
/**
 * Pricing Engine & Multi-Tier Rental Tariff Calculation for Ryokourent
 *
 * Implements server-side pricing logic for:
 * - 24-hour daily rates with 2-hour overtime tolerance grace period.
 * - 7-day weekly package discount pricing.
 * - 30-day monthly package discount pricing.
 * - Dynamic cheapest-combination rate calculation (best-rate guarantee).
 * - Detection and handling of custom/placeholder pricing (redirect to WhatsApp consultation).
 * - Complete independence from client-submitted price values (tamper-proof).
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

// -----------------------------------------------------------------------------
// Pricing Constants
// -----------------------------------------------------------------------------
if (!defined('RYOKOURENT_DAYS_IN_WEEK')) {
    define('RYOKOURENT_DAYS_IN_WEEK', 7);
}
if (!defined('RYOKOURENT_DAYS_IN_MONTH')) {
    define('RYOKOURENT_DAYS_IN_MONTH', 30);
}

/**
 * Fetch official tariff rates for a motorcycle from database or fallback catalog.
 *
 * @since 1.0.0
 * @param int $motor_id Post ID of CPT 'motor'.
 * @return array Array containing 'daily', 'weekly', 'monthly' (int) and 'is_custom' (bool).
 */
function ryokourent_get_motor_pricing($motor_id) {
    $motor_id = absint($motor_id);

    if ($motor_id <= 0) {
        return array(
            'motor_id'  => 0,
            'daily'     => 0,
            'weekly'    => 0,
            'monthly'   => 0,
            'is_custom' => true,
        );
    }

    $daily   = (int) get_post_meta($motor_id, '_ryokou_price_daily', true);
    $weekly  = (int) get_post_meta($motor_id, '_ryokou_price_weekly', true);
    $monthly = (int) get_post_meta($motor_id, '_ryokou_price_monthly', true);

    // Fallback if post meta is empty (e.g. in fresh install or blueprint test environment)
    if ($daily <= 0 && function_exists('ryokourent_get_blueprint_default_fleet')) {
        $blueprint = ryokourent_get_blueprint_default_fleet();
        // Check if ID matches blueprint 1-based index
        $index = $motor_id - 1;
        if (isset($blueprint[$index])) {
            $daily   = (int) $blueprint[$index]['price_daily'];
            $weekly  = (int) $blueprint[$index]['price_weekly'];
            $monthly = (int) $blueprint[$index]['price_monthly'];
        }
    }

    return array(
        'motor_id'  => $motor_id,
        'daily'     => $daily,
        'weekly'    => $weekly,
        'monthly'   => $monthly,
        'is_custom' => ($daily <= 0),
    );
}

/**
 * Calculate the cheapest combination of daily, weekly, and monthly packages.
 *
 * Algorithm evaluates combinations of months (30d), weeks (7d), and single days
 * that cover at least $billable_days to guarantee the customer the absolute best rate.
 *
 * @since 1.0.0
 * @param int $billable_days Total billable rental days (positive integer).
 * @param int $daily_price   Daily rate in IDR (must be > 0 for instant calculation).
 * @param int $weekly_price  Weekly package rate in IDR (optional).
 * @param int $monthly_price Monthly package rate in IDR (optional).
 * @return array Array with calculation status, total_price, breakdown, and consultation flag.
 */
function ryokourent_calculate_optimal_rental_price($billable_days, $daily_price, $weekly_price = 0, $monthly_price = 0) {
    $billable_days = (int) $billable_days;
    $daily_price   = (int) $daily_price;
    $weekly_price  = (int) $weekly_price;
    $monthly_price = (int) $monthly_price;

    // Check for custom/placeholder pricing (requires admin WhatsApp consultation)
    if ($daily_price <= 0) {
        return array(
            'success'               => false,
            'requires_consultation' => true,
            'code'                  => 'custom_pricing_required',
            'billable_days'         => $billable_days,
            'total_price'           => 0,
            'formatted_price'       => __('Konsultasi Admin WA', 'ryokourent'),
            'message'               => __('Model motor ini belum memiliki tarif pasti atau merupakan unit custom yang memerlukan konsultasi via WhatsApp.', 'ryokourent'),
            'breakdown'             => array(),
        );
    }

    if ($billable_days <= 0) {
        return array(
            'success'               => false,
            'requires_consultation' => false,
            'code'                  => 'invalid_days',
            'billable_days'         => 0,
            'total_price'           => 0,
            'formatted_price'       => 'Rp 0',
            'message'               => __('Jumlah hari sewa tidak valid.', 'ryokourent'),
            'breakdown'             => array(),
        );
    }

    // Optimization: find minimum cost by evaluating (m months, w weeks, d days) combinations
    $max_m = (int) ceil($billable_days / 30);
    $min_cost = PHP_INT_MAX;
    $best_combo = array(
        'monthly' => 0,
        'weekly'  => 0,
        'daily'   => $billable_days,
    );

    for ($m = 0; $m <= $max_m; $m++) {
        $cost_m = ($monthly_price > 0) ? ($m * $monthly_price) : ($m * 30 * $daily_price);
        $rem_after_m = max(0, $billable_days - ($m * 30));
        $max_w = (int) ceil($rem_after_m / 7);

        for ($w = 0; $w <= $max_w; $w++) {
            $cost_w = ($weekly_price > 0) ? ($w * $weekly_price) : ($w * 7 * $daily_price);
            $rem_d = max(0, $rem_after_m - ($w * 7));
            $cost_d = $rem_d * $daily_price;

            $total_cost = $cost_m + $cost_w + $cost_d;

            if ($total_cost < $min_cost) {
                $min_cost = $total_cost;
                $best_combo = array(
                    'monthly' => $m,
                    'weekly'  => $w,
                    'daily'   => $rem_d,
                );
            }
        }
    }

    // Build human-readable breakdown description
    $parts = array();
    if ($best_combo['monthly'] > 0) {
        $parts[] = sprintf(
            /* translators: %d: Monthly packages count */
            _n('%d Paket Bulanan (30 Hari)', '%d Paket Bulanan (30 Hari)', $best_combo['monthly'], 'ryokourent'),
            $best_combo['monthly']
        );
    }
    if ($best_combo['weekly'] > 0) {
        $parts[] = sprintf(
            /* translators: %d: Weekly packages count */
            _n('%d Paket Mingguan (7 Hari)', '%d Paket Mingguan (7 Hari)', $best_combo['weekly'], 'ryokourent'),
            $best_combo['weekly']
        );
    }
    if ($best_combo['daily'] > 0) {
        $parts[] = sprintf(
            /* translators: %d: Daily count */
            _n('%d Hari', '%d Hari', $best_combo['daily'], 'ryokourent'),
            $best_combo['daily']
        );
    }

    $description = !empty($parts) ? implode(' + ', $parts) : sprintf(__('%d Hari Sewa', 'ryokourent'), $billable_days);

    return array(
        'success'               => true,
        'requires_consultation' => false,
        'code'                  => 'price_calculated',
        'billable_days'         => $billable_days,
        'total_price'           => $min_cost,
        'formatted_price'       => 'Rp ' . number_format($min_cost, 0, ',', '.'),
        'breakdown'             => array(
            'monthly_count'    => $best_combo['monthly'],
            'monthly_subtotal' => ($monthly_price > 0 ? $best_combo['monthly'] * $monthly_price : 0),
            'weekly_count'     => $best_combo['weekly'],
            'weekly_subtotal'  => ($weekly_price > 0 ? $best_combo['weekly'] * $weekly_price : 0),
            'daily_count'      => $best_combo['daily'],
            'daily_subtotal'   => $best_combo['daily'] * $daily_price,
            'description'      => $description,
        ),
    );
}

/**
 * Generate full authoritative booking price quote.
 *
 * Validates the rental schedule, calculates billable days with 2-hour overtime tolerance,
 * and applies the optimal combination pricing algorithm.
 *
 * @since 1.0.0
 * @param int    $motor_id        CPT 'motor' post ID.
 * @param string $start_datetime  Start datetime (Y-m-d H:i or ISO string).
 * @param string $end_datetime    End datetime (Y-m-d H:i or ISO string).
 * @param int    $tolerance_hours Free grace period in hours. Default 2.
 * @return array Complete quote object.
 */
function ryokourent_calculate_booking_quote($motor_id, $start_datetime, $end_datetime, $tolerance_hours = 2) {
    // 1. Validate schedule
    if (function_exists('ryokourent_validate_rental_schedule')) {
        $schedule = ryokourent_validate_rental_schedule($start_datetime, $end_datetime, $tolerance_hours);
        if (!$schedule['is_valid']) {
            return array(
                'success'               => false,
                'requires_consultation' => false,
                'code'                  => 'invalid_schedule',
                'message'               => reset($schedule['errors']),
                'errors'                => $schedule['errors'],
                'total_price'           => 0,
                'formatted_price'       => 'Rp 0',
                'billable_days'         => 0,
            );
        }
        $billable_days  = $schedule['billable_days'];
        $duration_hours = $schedule['duration_hours'];
        $duration_label = $schedule['summary_label'];
    } else {
        // Fallback calculation
        $billable_days = 1;
        $duration_hours = 24.0;
        $duration_label = '1 Hari (~24 Jam)';
    }

    // 2. Fetch motor rates
    $rates = ryokourent_get_motor_pricing($motor_id);

    // 3. Compute optimal price
    $price_result = ryokourent_calculate_optimal_rental_price(
        $billable_days,
        $rates['daily'],
        $rates['weekly'],
        $rates['monthly']
    );

    // 4. Merge schedule metadata
    $price_result['motor_id']       = $motor_id;
    $price_result['duration_hours'] = $duration_hours;
    $price_result['duration_label'] = $duration_label;

    return $price_result;
}

/**
 * AJAX Handler for Instant Server-Authoritative Price Quote.
 *
 * Action: 'ryokourent_get_price_quote'
 *
 * @since 1.0.0
 * @return void Sends JSON response and exits.
 */
function ryokourent_ajax_get_price_quote() {
    $motor_id = isset($_POST['motor_id']) ? absint(wp_unslash($_POST['motor_id'])) : 0;
    $start    = isset($_POST['start_datetime']) ? sanitize_text_field(wp_unslash($_POST['start_datetime'])) : '';
    $end      = isset($_POST['end_datetime']) ? sanitize_text_field(wp_unslash($_POST['end_datetime'])) : '';

    if ($motor_id <= 0) {
        wp_send_json_error(array(
            'message' => __('Pilihan armada motor belum ditentukan.', 'ryokourent'),
        ), 400);
    }

    $quote = ryokourent_calculate_booking_quote($motor_id, $start, $end);

    if (!$quote['success'] && empty($quote['requires_consultation'])) {
        wp_send_json_error(array(
            'message' => isset($quote['message']) ? $quote['message'] : __('Gagal menghitung tarif sewa.', 'ryokourent'),
            'errors'  => isset($quote['errors']) ? $quote['errors'] : array(),
        ), 400);
    }

    wp_send_json_success(array(
        'total_price'           => $quote['total_price'],
        'formatted_price'       => $quote['formatted_price'],
        'billable_days'         => $quote['billable_days'],
        'duration_hours'        => $quote['duration_hours'],
        'duration_label'        => $quote['duration_label'],
        'breakdown'             => isset($quote['breakdown']) ? $quote['breakdown'] : array(),
        'requires_consultation' => !empty($quote['requires_consultation']),
        'message'               => isset($quote['message']) ? $quote['message'] : '',
    ), 200);
}
add_action('wp_ajax_ryokourent_get_price_quote', 'ryokourent_ajax_get_price_quote');
add_action('wp_ajax_nopriv_ryokourent_get_price_quote', 'ryokourent_ajax_get_price_quote');
