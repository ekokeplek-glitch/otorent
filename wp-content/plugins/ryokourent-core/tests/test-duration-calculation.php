<?php
/**
 * Test Suite for Rental Duration Calculation & Operating Hours (TASK-013)
 *
 * Verifies:
 * - Real-time duration calculation between start and end datetime in Asia/Jakarta (WIB).
 * - Exact task acceptance scenario: 02/10/2026 08:30 to 04/10/2026 17:00 = 3 Days (~56.5 Hours).
 * - 2-hour overtime tolerance grace period rules (24h => 1 day, 26h => 1 day, 26.5h => 2 days, 50h => 2 days).
 * - Operating hours enforcement (07:00 – 23:00 WIB).
 * - Rejection of backwards / negative durations (end <= start) and sub-1-hour rentals.
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

// Mock minimal WordPress functions
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
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Load helpers and booking module
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/booking.php';

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

echo "=== Menjalankan Unit Test Kalkulasi Durasi Sewa & Jam Operasional (TASK-013) ===" . PHP_EOL . PHP_EOL;

// 1. Exact Acceptance Scenario from TASKS.md:
// Start: 2026-10-02 08:30 | End: 2026-10-04 17:00
// Duration = 56.5 Hours | Billable Days with 2h tolerance = 3 Days
$start_test = '2026-10-02 08:30';
$end_test   = '2026-10-04 17:00';

$hours_calc = ryokourent_calculate_duration_hours($start_test, $end_test);
run_test("Kalkulasi jam tepat 56.5 jam (02/10 08:30 s/d 04/10 17:00)", abs($hours_calc - 56.5) < 0.01);

$days_calc = ryokourent_calculate_rental_days($start_test, $end_test, 2);
run_test("Kalkulasi hari sewa tepat 3 Hari dengan toleransi overtime 2 jam", $days_calc === 3);

$schedule_res = ryokourent_validate_rental_schedule($start_test, $end_test, 2);
run_test("Jadwal sewa 56.5 jam valid (is_valid = true)", $schedule_res['is_valid'] === true);
run_test("Jadwal sewa menghasilkan billable_days = 3", $schedule_res['billable_days'] === 3);
run_test("Summary label memuat '3 Hari' dan '~56.5 Jam'", strpos($schedule_res['summary_label'], '3 Hari') !== false && strpos($schedule_res['summary_label'], '56.5 Jam') !== false);

// 2. Test 2-Hour Overtime Grace Period Matrix
// 24.0 Hours -> 1 Day
$d24 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-02 08:00', 2);
run_test("Sewa tepat 24 jam dihitung 1 Hari", $d24 === 1);

// 26.0 Hours (24h + 2h tolerance) -> 1 Day
$d26 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-02 10:00', 2);
run_test("Sewa 26 jam (toleransi overtime 2 jam) dihitung 1 Hari", $d26 === 1);

// 26.5 Hours (24h + 2.5h overtime, exceeds 2h tolerance) -> 2 Days
$d26_5 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-02 10:30', 2);
run_test("Sewa 26.5 jam (melebihi toleransi 2 jam) dihitung 2 Hari", $d26_5 === 2);

// 48.0 Hours (2 full 24h cycles) -> 2 Days
$d48 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-03 08:00', 2);
run_test("Sewa tepat 48 jam dihitung 2 Hari", $d48 === 2);

// 50.0 Hours (48h + 2h tolerance) -> 2 Days
$d50 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-03 10:00', 2);
run_test("Sewa 50 jam (48 jam + toleransi 2 jam) dihitung 2 Hari", $d50 === 2);

// 50.5 Hours (48h + 2.5h overtime) -> 3 Days
$d50_5 = ryokourent_calculate_rental_days('2026-10-01 08:00', '2026-10-03 10:30', 2);
run_test("Sewa 50.5 jam (melebihi toleransi 2 jam) dihitung 3 Hari", $d50_5 === 3);

// 3. Test Operating Hours Enforcement (07:00 – 23:00 WIB)
run_test("Jam mulai 07:00 berada dalam jam operasional", ryokourent_is_within_operating_hours('2026-10-01 07:00') === true);
run_test("Jam mulai 23:00 berada dalam jam operasional", ryokourent_is_within_operating_hours('2026-10-01 23:00') === true);
run_test("Jam mulai 14:30 berada dalam jam operasional", ryokourent_is_within_operating_hours('2026-10-01 14:30') === true);
run_test("Jam mulai 06:59 di luar jam operasional (terlalu pagi)", ryokourent_is_within_operating_hours('2026-10-01 06:59') === false);
run_test("Jam mulai 02:00 di luar jam operasional (dini hari)", ryokourent_is_within_operating_hours('2026-10-01 02:00') === false);
run_test("Jam selesai 23:30 di luar jam operasional (terlalu larut)", ryokourent_is_within_operating_hours('2026-10-01 23:30') === false);

// 4. Test Schedule Validation with Outside Operating Hours
$early_schedule = ryokourent_validate_rental_schedule('2026-10-01 05:00', '2026-10-02 12:00');
run_test("Jadwal mulai pukul 05:00 ditolak jam operasional", $early_schedule['is_valid'] === false && isset($early_schedule['errors']['start_datetime']));

$late_schedule = ryokourent_validate_rental_schedule('2026-10-01 08:00', '2026-10-02 23:45');
run_test("Jadwal selesai pukul 23:45 ditolak jam operasional", $late_schedule['is_valid'] === false && isset($late_schedule['errors']['end_datetime']));

// 5. Test Backwards Dates / Invalid Range
$backwards = ryokourent_validate_rental_schedule('2026-10-02 10:00', '2026-10-01 10:00');
run_test("Waktu selesai sebelum waktu mulai ditolak", $backwards['is_valid'] === false && isset($backwards['errors']['end_datetime']));

$same_time = ryokourent_validate_rental_schedule('2026-10-01 10:00', '2026-10-01 10:00');
run_test("Waktu selesai sama persis dengan waktu mulai ditolak", $same_time['is_valid'] === false && isset($same_time['errors']['end_datetime']));

// 6. Test Sub-1-Hour Duration
$short_duration = ryokourent_validate_rental_schedule('2026-10-01 10:00', '2026-10-01 10:30');
run_test("Durasi sewa < 1 jam (30 menit) ditolak", $short_duration['is_valid'] === false && isset($short_duration['errors']['end_datetime']));

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
