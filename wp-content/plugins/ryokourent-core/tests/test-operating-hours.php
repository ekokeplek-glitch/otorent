<?php
/**
 * Test Suite for Operating Hours & Schedule Validation Engine (TASK-015)
 *
 * Verifies:
 * - Pool operating hours enforcement: 07:00 – 23:00 WIB strictly enforced.
 * - Rejection of start time outside operating hours (e.g. 02:00 WIB or 06:59 WIB).
 * - Rejection of end time outside operating hours (e.g. 23:30 WIB).
 * - Rejection of start datetime in the past.
 * - Rejection of end datetime prior to or equal to start datetime.
 * - Handling of ISO standard strings ('Y-m-d H:i' and 'Y-m-d\TH:i') across platforms.
 * - Integration test with ryokourent_validate_booking_submission().
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

// Global mock state
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
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
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
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return ($nonce === 'valid_schedule_nonce_123');
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
        if ($post_id === 1) {
            $meta = array(
                '_ryokou_price_daily'   => 85000,
                '_ryokou_price_weekly'  => 500000,
                '_ryokou_price_monthly' => 1600000,
            );
            return isset($meta[$key]) ? $meta[$key] : '';
        }
        return '';
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Load helpers, booking, and pricing
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

echo "=== Menjalankan Unit Test Validasi Tanggal & Jam Operasional (TASK-015) ===" . PHP_EOL . PHP_EOL;

$tz = new DateTimeZone('Asia/Jakarta');
$future_base = new DateTime('+2 days', $tz);
$future_date = $future_base->format('Y-m-d');

// 1. Test Operating Hours Checking Helper (07:00 – 23:00 WIB)
run_test("Jam 07:00 WIB adalah awal jam operasional resmi", ryokourent_is_within_operating_hours("{$future_date} 07:00") === true);
run_test("Jam 12:30 WIB berada dalam jam operasional", ryokourent_is_within_operating_hours("{$future_date} 12:30") === true);
run_test("Jam 23:00 WIB adalah batas akhir jam operasional", ryokourent_is_within_operating_hours("{$future_date} 23:00") === true);

// Outside operating hours
run_test("Jam 02:00 WIB di luar jam operasional (dini hari)", ryokourent_is_within_operating_hours("{$future_date} 02:00") === false);
run_test("Jam 06:59 WIB di luar jam operasional (sebelum buka)", ryokourent_is_within_operating_hours("{$future_date} 06:59") === false);
run_test("Jam 23:01 WIB di luar jam operasional (setelah tutup)", ryokourent_is_within_operating_hours("{$future_date} 23:01") === false);
run_test("Jam 23:30 WIB di luar jam operasional", ryokourent_is_within_operating_hours("{$future_date} 23:30") === false);

// 2. Test Specific Acceptance Scenario: Request dengan jam mulai 02:00 WIB diblokir
$schedule_0200 = ryokourent_validate_rental_schedule("{$future_date} 02:00", "{$future_date} 10:00");
run_test("Jadwal dengan jam mulai 02:00 WIB ditolak (is_valid = false)", $schedule_0200['is_valid'] === false);
run_test("Pesan error jam mulai memuat 'operasional pool'", isset($schedule_0200['errors']['start_datetime']) && strpos($schedule_0200['errors']['start_datetime'], 'operasional pool') !== false);

// 3. Test Specific Acceptance Scenario: Request dengan tanggal selesai < tanggal mulai diblokir
$schedule_backwards = ryokourent_validate_rental_schedule("{$future_date} 15:00", "{$future_date} 10:00");
run_test("Jadwal tanggal selesai < tanggal mulai ditolak", $schedule_backwards['is_valid'] === false);
run_test("Pesan error tanggal selesai memuat 'harus lebih akhir'", isset($schedule_backwards['errors']['end_datetime']) && strpos($schedule_backwards['errors']['end_datetime'], 'harus lebih akhir') !== false);

// 4. Test Past Date Rejection
$past_schedule = ryokourent_validate_rental_schedule("2020-01-01 08:00", "2020-01-03 17:00");
run_test("Waktu mulai di masa lalu (2020) ditolak", $past_schedule['is_valid'] === false);
run_test("Pesan error masa lalu memuat 'tidak boleh berada di masa lalu'", isset($past_schedule['errors']['start_datetime']) && strpos($past_schedule['errors']['start_datetime'], 'masa lalu') !== false);

// 5. Test Cross-Platform ISO String Formats (HTML5 datetime-local format with 'T')
$valid_start_iso = "{$future_date}T08:30";
$end_base = clone $future_base;
$end_base->modify('+2 days');
$valid_end_iso = $end_base->format('Y-m-d') . "T17:00";

$schedule_iso = ryokourent_validate_rental_schedule($valid_start_iso, $valid_end_iso);
run_test("Format input datetime-local ISO dengan 'T' berhasil diproses", $schedule_iso['is_valid'] === true);
run_test("Schedule ISO menghasilkan durasi sewa yang valid", $schedule_iso['billable_days'] >= 1);

// 6. Test Full Submission Integration with ryokourent_validate_booking_submission()
$base_submission = array(
    'ryokourent_booking_nonce' => 'valid_schedule_nonce_123',
    'ryokourent_hp'            => '',
    'customer_name'            => 'Rian Hidayat',
    'customer_whatsapp'        => '081234567890',
    'customer_emergency_phone' => '081987654321',
    'customer_ktp_address'     => 'Jl. Borobudur No. 20 Malang',
    'customer_stay_address'    => 'Hotel Aria Gajayana',
    'rented_motor_id'          => 1,
    'pickup_location'          => 'Pool Dinoyo',
    'trip_destination'         => 'malang_batu',
);

// Submission dengan jam 02:00 WIB
$submission_0200 = $base_submission;
$submission_0200['start_datetime'] = "{$future_date} 02:00";
$submission_0200['end_datetime']   = "{$future_date} 10:00";
$res_0200 = ryokourent_validate_booking_submission($submission_0200);
run_test("Submit booking dengan jam mulai 02:00 diblokir dengan code invalid_schedule", $res_0200['success'] === false && $res_0200['code'] === 'invalid_schedule' && $res_0200['status'] === 400);

// Submission dengan tanggal selesai < tanggal mulai
$submission_backwards = $base_submission;
$submission_backwards['start_datetime'] = "{$future_date} 16:00";
$submission_backwards['end_datetime']   = "{$future_date} 10:00";
$res_backwards = ryokourent_validate_booking_submission($submission_backwards);
run_test("Submit booking dengan tanggal selesai < tanggal mulai diblokir", $res_backwards['success'] === false && $res_backwards['code'] === 'invalid_schedule');

// Submission dengan jadwal valid dalam jam operasional
$submission_valid = $base_submission;
$submission_valid['start_datetime'] = $valid_start_iso;
$submission_valid['end_datetime']   = $valid_end_iso;
$res_valid = ryokourent_validate_booking_submission($submission_valid);
run_test("Submit booking dengan jadwal valid diterima (validation_passed, status 200)", $res_valid['success'] === true && $res_valid['code'] === 'validation_passed' && $res_valid['status'] === 200);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
