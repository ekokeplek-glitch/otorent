<?php
/**
 * Test Suite for Customer Data Validation & Anti-Spam Engine (TASK-012)
 *
 * Verifies server-side validation rules for:
 * - Customer full name according to e-KTP.
 * - Indonesian cellular mobile WhatsApp phone format (08... / 628...).
 * - Emergency family contact number (valid and separated from customer WhatsApp).
 * - Anti-spam honeypot detection.
 * - Transient-based IP rate-limiting.
 * - Nonce token validation.
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
if (!defined('RYOKOURENT_VERSION')) {
    define('RYOKOURENT_VERSION', '1.0.0');
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
        return ($nonce === 'valid_booking_nonce_123');
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

echo "=== Menjalankan Unit Test Validasi Data Pelanggan & Anti-Spam (TASK-012) ===" . PHP_EOL . PHP_EOL;

// 1. Test Honeypot Anti-Spam
$clean_post = array('ryokourent_hp' => '');
$bot_post   = array('ryokourent_hp' => 'bot-filled-value');
run_test("Honeypot kosong menghasilkan is_honeypot_triggered = false", ryokourent_is_honeypot_triggered($clean_post) === false);
run_test("Honeypot terisi menghasilkan is_honeypot_triggered = true", ryokourent_is_honeypot_triggered($bot_post) === true);

// 2. Test Customer Name Validation
$invalid_short_name = array('customer_name' => 'AB');
$res_short = ryokourent_validate_customer_data($invalid_short_name);
run_test("Nama < 3 karakter ditolak", isset($res_short['errors']['customer_name']));

$invalid_char_name = array('customer_name' => 'Budi123!@#');
$res_chars = ryokourent_validate_customer_data($invalid_char_name);
run_test("Nama dengan karakter angka/simbol tidak wajar ditolak", isset($res_chars['errors']['customer_name']));

$valid_name_data = array('customer_name' => "Dimas Aditya Pratama, S.Kom");
$res_valid_name = ryokourent_validate_customer_data($valid_name_data);
run_test("Nama valid diterima", !isset($res_valid_name['errors']['customer_name']));

// 3. Test Indonesian WhatsApp Phone Validation
$invalid_phones = array('12345', '0217654321', '0812', 'abcdefghijk');
foreach ($invalid_phones as $bad_phone) {
    $res_bad_phone = ryokourent_validate_customer_data(array('customer_whatsapp' => $bad_phone));
    run_test("Nomor WA tidak valid '{$bad_phone}' ditolak", isset($res_bad_phone['errors']['customer_whatsapp']));
}

$valid_phones = array('081234567890', '+6281234567890', '6281234567890', '0812-3456-7890', '0813 4567 8901');
foreach ($valid_phones as $good_phone) {
    $res_good_phone = ryokourent_validate_customer_data(array(
        'customer_name' => 'Budi Santoso',
        'customer_whatsapp' => $good_phone,
        'customer_emergency_phone' => '081987654321',
        'customer_ktp_address' => 'Jl. Diponegoro No. 10 Surabaya',
        'customer_stay_address' => 'Hotel Santika Malang',
    ));
    run_test("Nomor WA valid '{$good_phone}' diterima", !isset($res_good_phone['errors']['customer_whatsapp']));
}

// 4. Test Emergency Contact Separation (Must NOT be identical to WhatsApp)
$duplicate_phone_data = array(
    'customer_name' => 'Dimas Pratama',
    'customer_whatsapp' => '081234567890',
    'customer_emergency_phone' => '081234567890', // Identical!
    'customer_ktp_address' => 'Jl. Ijen No. 12 Malang',
    'customer_stay_address' => 'Homestay Batu',
);
$res_duplicate = ryokourent_validate_customer_data($duplicate_phone_data);
run_test("Kontak darurat sama persis dengan nomor WhatsApp ditolak", isset($res_duplicate['errors']['customer_emergency_phone']));

$duplicate_formatted_data = array(
    'customer_name' => 'Dimas Pratama',
    'customer_whatsapp' => '0812-3456-7890',
    'customer_emergency_phone' => '+6281234567890', // Same after normalization!
    'customer_ktp_address' => 'Jl. Ijen No. 12 Malang',
    'customer_stay_address' => 'Homestay Batu',
);
$res_duplicate_fmt = ryokourent_validate_customer_data($duplicate_formatted_data);
run_test("Kontak darurat sama dengan nomor WhatsApp beda format ditolak", isset($res_duplicate_fmt['errors']['customer_emergency_phone']));

$distinct_phone_data = array(
    'customer_name' => 'Dimas Pratama',
    'customer_whatsapp' => '081234567890',
    'customer_emergency_phone' => '081398765432', // Distinct family phone!
    'customer_ktp_address' => 'Jl. Ijen No. 12 Malang',
    'customer_stay_address' => 'Homestay Batu',
);
$res_distinct = ryokourent_validate_customer_data($distinct_phone_data);
run_test("Kontak darurat berbeda diterima", !isset($res_distinct['errors']['customer_emergency_phone']));

// 5. Test Address Validation
$empty_addr = array('customer_ktp_address' => '12', 'customer_stay_address' => 'A');
$res_addr = ryokourent_validate_customer_data($empty_addr);
run_test("Alamat KTP terlalu pendek (< 5 chars) ditolak", isset($res_addr['errors']['customer_ktp_address']));
run_test("Alamat Menginap terlalu pendek (< 3 chars) ditolak", isset($res_addr['errors']['customer_stay_address']));

// 6. Test Transient Rate Limiting
$test_ip = '192.168.1.50';
delete_transient('ryokou_rate_' . md5($test_ip));

for ($i = 1; $i <= 5; $i++) {
    $rate_res = ryokourent_check_booking_rate_limit($test_ip, 5, 600);
    run_test("Percobaan submit {$i} diizinkan", $rate_res['allowed'] === true);
}
// 6th attempt should be blocked
$blocked_res = ryokourent_check_booking_rate_limit($test_ip, 5, 600);
run_test("Percobaan submit ke-6 diblokir rate limit", $blocked_res['allowed'] === false);

// 7. Test Full Submission Validation Pipeline
$valid_full_submission = array(
    'ryokourent_booking_nonce' => 'valid_booking_nonce_123',
    'ryokourent_hp'            => '',
    'customer_name'            => 'Eko Prasetyo',
    'customer_whatsapp'        => '081234567890',
    'customer_emergency_phone' => '081987654321',
    'customer_ktp_address'     => 'Jl. Pahlawan No. 99 Surabaya',
    'customer_stay_address'    => 'Hotel Tugu Malang',
    'rented_motor_id'          => 2,
    'pickup_location'          => 'Pool Dinoyo',
    'trip_destination'         => 'malang_batu',
);

$full_result = ryokourent_validate_booking_submission($valid_full_submission);
run_test("Valid submission berhasil diproses", $full_result['success'] === true && $full_result['code'] === 'validation_passed');
run_test("Sanitized data memuat customer_name bersih", $full_result['clean_data']['customer_name'] === 'Eko Prasetyo');
run_test("Sanitized data memuat customer_whatsapp normalisasi '6281234567890'", $full_result['clean_data']['customer_whatsapp'] === '6281234567890');

// Test Honeypot interception in full submission
$bot_submission = $valid_full_submission;
$bot_submission['ryokourent_hp'] = 'I am spammer bot';
$bot_result = ryokourent_validate_booking_submission($bot_submission);
run_test("Submission dengan honeypot diblokir dengan code spam_bot_detected dan status 400", $bot_result['success'] === false && $bot_result['status'] === 400 && $bot_result['code'] === 'spam_bot_detected');

// Test Invalid Nonce interception
$bad_nonce_submission = $valid_full_submission;
$bad_nonce_submission['ryokourent_booking_nonce'] = 'forged_fake_nonce';
$bad_nonce_result = ryokourent_validate_booking_submission($bad_nonce_submission);
run_test("Submission dengan nonce salah diblokir dengan status 403", $bad_nonce_result['success'] === false && $bad_nonce_result['status'] === 403 && $bad_nonce_result['code'] === 'invalid_nonce');

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
