<?php
/**
 * Test Suite for Rental Pricing Engine & Best-Rate Combinations (TASK-014)
 *
 * Verifies:
 * - Daily tariff calculation (24-hour cycle).
 * - Weekly package rate (7 days discount).
 * - Monthly package rate (30 days discount).
 * - Automated cheapest-combination rate calculation for:
 *   - 1 day sewa (Rp 85.000)
 *   - 3 hari sewa (Rp 255.000)
 *   - 7 hari sewa (Rp 500.000 - paket mingguan)
 *   - 35 hari sewa (Rp 2.025.000 - 1 bulan + 5 hari)
 * - Handling of placeholder / empty price (requires WhatsApp consultation).
 * - Integration with schedule duration and server-side quote generation.
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
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        // Mock motor 1: Honda BeAT Deluxe
        if ($post_id === 1) {
            $meta = array(
                '_ryokou_price_daily'   => 85000,
                '_ryokou_price_weekly'  => 500000,
                '_ryokou_price_monthly' => 1600000,
            );
            return isset($meta[$key]) ? $meta[$key] : '';
        }
        // Mock motor 2: Honda Trail CRF 150L
        if ($post_id === 2) {
            $meta = array(
                '_ryokou_price_daily'   => 200000,
                '_ryokou_price_weekly'  => 1250000,
                '_ryokou_price_monthly' => 3800000,
            );
            return isset($meta[$key]) ? $meta[$key] : '';
        }
        // Mock motor 99: Custom / Placeholder motor (no price set)
        if ($post_id === 99) {
            return '';
        }
        return '';
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Load helpers, booking, and pricing modules
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/booking.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/pricing.php';

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

echo "=== Menjalankan Unit Test Kalkulasi Harga Paket Sewa (TASK-014) ===" . PHP_EOL . PHP_EOL;

// Standard rates for Honda BeAT: 85k / day, 500k / week (saves 95k), 1.6M / month (saves 950k)
$daily_85k   = 85000;
$weekly_500k = 500000;
$monthly_16m = 1600000;

// 1. Test 1 Day Rental
$res_1d = ryokourent_calculate_optimal_rental_price(1, $daily_85k, $weekly_500k, $monthly_16m);
run_test("Sewa 1 hari: Total harga Rp 85.000", $res_1d['total_price'] === 85000);
run_test("Sewa 1 hari: Breakdown harian = 1, mingguan = 0, bulanan = 0", $res_1d['breakdown']['daily_count'] === 1 && $res_1d['breakdown']['weekly_count'] === 0 && $res_1d['breakdown']['monthly_count'] === 0);
run_test("Sewa 1 hari: Format harga 'Rp 85.000'", $res_1d['formatted_price'] === 'Rp 85.000');

// 2. Test 3 Days Rental
$res_3d = ryokourent_calculate_optimal_rental_price(3, $daily_85k, $weekly_500k, $monthly_16m);
run_test("Sewa 3 hari: Total harga Rp 255.000 (3 x 85.000)", $res_3d['total_price'] === 255000);
run_test("Sewa 3 hari: Breakdown harian = 3, mingguan = 0, bulanan = 0", $res_3d['breakdown']['daily_count'] === 3 && $res_3d['breakdown']['weekly_count'] === 0);
run_test("Sewa 3 hari: Format harga 'Rp 255.000'", $res_3d['formatted_price'] === 'Rp 255.000');

// 3. Test 7 Days Rental (Weekly Package)
$res_7d = ryokourent_calculate_optimal_rental_price(7, $daily_85k, $weekly_500k, $monthly_16m);
run_test("Sewa 7 hari: Total harga Rp 500.000 (Paket Mingguan)", $res_7d['total_price'] === 500000);
run_test("Sewa 7 hari: Breakdown mingguan = 1, harian = 0", $res_7d['breakdown']['weekly_count'] === 1 && $res_7d['breakdown']['daily_count'] === 0);
run_test("Sewa 7 hari: Format harga 'Rp 500.000'", $res_7d['formatted_price'] === 'Rp 500.000');

// 4. Test 35 Days Rental (1 Month + 5 Days)
$res_35d = ryokourent_calculate_optimal_rental_price(35, $daily_85k, $weekly_500k, $monthly_16m);
// 1 bulan (1.600.000) + 5 hari (5 x 85.000 = 425.000) = 2.025.000
run_test("Sewa 35 hari: Total harga Rp 2.025.000 (1 Bulan + 5 Hari)", $res_35d['total_price'] === 2025000);
run_test("Sewa 35 hari: Breakdown bulanan = 1, mingguan = 0, harian = 5", $res_35d['breakdown']['monthly_count'] === 1 && $res_35d['breakdown']['weekly_count'] === 0 && $res_35d['breakdown']['daily_count'] === 5);
run_test("Sewa 35 hari: Format harga 'Rp 2.025.000'", $res_35d['formatted_price'] === 'Rp 2.025.000');
run_test("Sewa 35 hari: Deskripsi paket memuat '1 Paket Bulanan' dan '5 Hari'", strpos($res_35d['breakdown']['description'], '1 Paket Bulanan') !== false && strpos($res_35d['breakdown']['description'], '5 Hari') !== false);

// 5. Test Best-Rate Guarantee (e.g. 6 days where 1 week is cheaper than 6 individual days)
// 6 days * 85k = 510.000, while 1 week package = 500.000!
$res_6d = ryokourent_calculate_optimal_rental_price(6, $daily_85k, $weekly_500k, $monthly_16m);
run_test("Sewa 6 hari: Memilih paket mingguan Rp 500.000 karena lebih murah dari 6 x 85.000 (Rp 510.000)", $res_6d['total_price'] === 500000);
run_test("Sewa 6 hari: Breakdown otomatis memilih 1 Paket Mingguan", $res_6d['breakdown']['weekly_count'] === 1);

// 6. Test Handling of Placeholder / Empty Price (Requires Consultation)
$res_empty_price = ryokourent_calculate_optimal_rental_price(3, 0, 0, 0);
run_test("Tarif motor kosong / 0 ditolak dari booking instan", $res_empty_price['success'] === false);
run_test("Tarif motor kosong menyalakan flag requires_consultation = true", $res_empty_price['requires_consultation'] === true);
run_test("Tarif motor kosong menampilkan label 'Konsultasi Admin WA'", $res_empty_price['formatted_price'] === 'Konsultasi Admin WA');

// 7. Test Integration with ryokourent_calculate_booking_quote()
// Motor 1 (BeAT): Start 02/10/2026 08:30 s/d 04/10/2026 17:00 (56.5h = 3 billable days)
$quote_beat = ryokourent_calculate_booking_quote(1, '2026-10-02 08:30', '2026-10-04 17:00');
run_test("Quote sewa Motor 1 selama 56.5 jam menghasilkan 3 Hari", $quote_beat['billable_days'] === 3);
run_test("Quote sewa Motor 1 selama 3 hari menghasilkan total harga Rp 255.000", $quote_beat['total_price'] === 255000);
run_test("Quote sewa Motor 1 tidak membutuhkan konsultasi khusus", $quote_beat['requires_consultation'] === false);

// Motor 99 (Unpriced / Custom):
$quote_unpriced = ryokourent_calculate_booking_quote(99, '2026-10-02 08:30', '2026-10-04 17:00');
run_test("Quote motor tanpa harga mengaktifkan requires_consultation = true", $quote_unpriced['requires_consultation'] === true);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
