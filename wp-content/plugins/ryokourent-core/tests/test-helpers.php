<?php
/**
 * Unit Tests for Ryokourent Helpers
 *
 * This test file can be run in standalone PHP CLI environments or integrated
 * with WP_UnitTestCase / PHPUnit suites.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/tests
 */

// If running in standalone CLI mode without WordPress loaded:
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');

    // Mock minimal WordPress functions if absent
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
    if (!function_exists('esc_html__')) {
        function esc_html__($text, $domain = 'default') {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            return $default;
        }
    }
}

// Load helper functions
require_once dirname(__DIR__) . '/includes/helpers.php';

/**
 * Basic Assertion Test Runner
 */
class Ryokourent_Helpers_Test {
    private static int $passed = 0;
    private static int $failed = 0;

    public static function assert($condition, $test_name) {
        if ($condition) {
            self::$passed++;
            echo "[PASS] " . $test_name . "\n";
        } else {
            self::$failed++;
            echo "[FAIL] " . $test_name . "\n";
        }
    }

    public static function run_all() {
        echo "=== Running Ryokourent Helper Tests ===\n\n";

        // 1. Currency Formatting (IDR)
        self::assert(ryokourent_format_rupiah(85000) === 'Rp 85.000', 'format_rupiah standard');
        self::assert(ryokourent_format_rupiah(85000, false) === '85.000', 'format_rupiah without prefix');
        self::assert(ryokourent_format_rupiah(0) === 'Rp 0', 'format_rupiah zero value');
        self::assert(ryokourent_format_rupiah(-5000) === 'Rp 0', 'format_rupiah negative input');
        self::assert(ryokourent_format_rupiah(1250000) === 'Rp 1.250.000', 'format_rupiah millions');

        // 2. Phone Sanitization & Validation (WhatsApp 62 format)
        self::assert(ryokourent_sanitize_phone('081234567890') === '6281234567890', 'sanitize_phone leading 0');
        self::assert(ryokourent_sanitize_phone('+62 812-3456-7890') === '6281234567890', 'sanitize_phone symbols & spaces');
        self::assert(ryokourent_sanitize_phone('81234567890') === '6281234567890', 'sanitize_phone missing 62 prefix');
        self::assert(ryokourent_is_valid_phone('081234567890') === true, 'is_valid_phone valid number');
        self::assert(ryokourent_is_valid_phone('12345') === false, 'is_valid_phone invalid length');
        self::assert(ryokourent_is_valid_phone('0217654321') === false, 'is_valid_phone landline not mobile');

        // 3. Timezone & DateTime in WIB
        self::assert(ryokourent_get_timezone()->getName() === 'Asia/Jakarta', 'get_timezone Asia/Jakarta');
        $now_wib = ryokourent_get_now_wib('Y');
        self::assert(!empty($now_wib) && strlen($now_wib) === 4, 'get_now_wib returns current year');
        $formatted_date = ryokourent_format_datetime_id('2026-10-01 10:00:00', 'd M Y H:i');
        self::assert(str_contains($formatted_date, 'Okt 2026 10:00'), 'format_datetime_id indonesian month translation');

        // 4. Duration and Rental Days Calculation
        $start = '2026-10-01 09:00:00';
        $end_24h = '2026-10-02 09:00:00';
        $end_26h = '2026-10-02 11:00:00'; // within 2 hours grace period
        $end_27h = '2026-10-02 12:00:00'; // exceeds 2 hours grace period -> 2 days

        self::assert(ryokourent_calculate_duration_hours($start, $end_24h) == 24.0, 'calculate_duration_hours 24 hours');
        self::assert(ryokourent_calculate_duration_hours($start, $end_26h) == 26.0, 'calculate_duration_hours 26 hours');
        self::assert(ryokourent_calculate_rental_days($start, $end_24h) === 1, 'calculate_rental_days 24h = 1 day');
        self::assert(ryokourent_calculate_rental_days($start, $end_26h) === 1, 'calculate_rental_days 26h with grace = 1 day');
        self::assert(ryokourent_calculate_rental_days($start, $end_27h) === 2, 'calculate_rental_days 27h = 2 days');

        // 5. Operating Hours (07:00 - 23:00 WIB)
        self::assert(ryokourent_is_within_operating_hours('08:00') === true, 'operating hours 08:00 valid');
        self::assert(ryokourent_is_within_operating_hours('22:30') === true, 'operating hours 22:30 valid');
        self::assert(ryokourent_is_within_operating_hours('05:00') === false, 'operating hours 05:00 too early');
        self::assert(ryokourent_is_within_operating_hours('23:30') === false, 'operating hours 23:30 too late');

        // 6. License Plate & KTP Sanitization
        self::assert(ryokourent_sanitize_plate_number('n 1234 abc') === 'N 1234 ABC', 'sanitize_plate_number uppercase & single space');
        self::assert(ryokourent_sanitize_plate_number('  n   9876  xyz! ') === 'N 9876 XYZ', 'sanitize_plate_number removes special chars');
        self::assert(ryokourent_sanitize_ktp('3578-0123-4567-0001') === '3578012345670001', 'sanitize_ktp digits only');
        self::assert(ryokourent_is_valid_ktp('3578012345670001') === true, 'is_valid_ktp 16 digits');
        self::assert(ryokourent_is_valid_ktp('12345') === false, 'is_valid_ktp short');

        // 7. Booking Statuses
        $statuses = ryokourent_get_booking_statuses();
        self::assert(isset($statuses['status_menunggu']), 'statuses contains status_menunggu');
        self::assert(isset($statuses['status_dikonfirmasi']), 'statuses contains status_dikonfirmasi');
        self::assert(count($statuses) === 5, 'statuses contains exactly 5 statuses');

        $labels = ryokourent_get_booking_status_labels();
        self::assert(isset($labels['status_menunggu']), 'labels contains status_menunggu');
        self::assert(count($labels) === 5, 'labels contains exactly 5 statuses');

        // 8. Locations List
        $locations = ryokourent_get_pool_locations();
        self::assert(isset($locations['pool_dinoyo']), 'locations contains pool_dinoyo');
        self::assert(isset($locations['pool_batu']), 'locations contains pool_batu');
        self::assert(count($locations) === 4, 'locations contains 4 locations');

        echo "\nSummary: " . self::$passed . " Passed, " . self::$failed . " Failed.\n";
        return (self::$failed === 0);
    }
}

// Execute if run directly via CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $success = Ryokourent_Helpers_Test::run_all();
    exit($success ? 0 : 1);
}
