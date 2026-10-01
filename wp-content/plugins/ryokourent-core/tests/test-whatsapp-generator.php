<?php
/**
 * Test Suite for Official WhatsApp Draft Generator & Deep Link Engine (TASK-018)
 *
 * Verifies:
 * - Proper extraction and formatting of all 10 booking and identity fields.
 * - Standardized emoticons and clean markdown structure.
 * - Server-authoritative resolution of admin WhatsApp phone number.
 * - Construction of deep link https://wa.me/{number}?text={encoded_text}.
 * - Proper RFC 3986 encoding with rawurlencode() (preserves emojis, newlines, and punctuation).
 * - Exact round-trip: rawurldecode(query) === original message text.
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
if (!defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
    define('RYOKOURENT_DEFAULT_WA_NUMBER', '6281234567890');
}

// Global options mock
$GLOBALS['mock_options'] = array();

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
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return isset($GLOBALS['mock_options'][$option]) ? $GLOBALS['mock_options'][$option] : $default;
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Load helpers and whatsapp modules
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/whatsapp.php';

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

echo "=== Menjalankan Unit Test Generator Pesan WhatsApp Resmi (TASK-018) ===" . PHP_EOL . PHP_EOL;

// 1. Test Server-Authoritative Admin WhatsApp Number
$GLOBALS['mock_options']['ryokourent_wa_number'] = '081234567890';
$admin_phone = ryokourent_get_official_wa_number();
run_test("Nomor admin dinormalisasi dari 08... menjadi format internasional 628...", $admin_phone === '6281234567890');

// 2. Test Message Construction with Full Dataset
$sample_data = array(
    'motor_name'               => 'Honda BeAT Deluxe 110cc',
    'start_datetime'           => '2026-10-02 08:30',
    'end_datetime'             => '2026-10-04 17:00',
    'duration_label'           => '3 Hari (~56.5 Jam)',
    'pickup_location'          => 'Stasiun Malang',
    'trip_destination'         => 'malang_batu',
    'formatted_price'          => 'Rp 255.000',
    'customer_name'            => 'Budi Setiawan',
    'customer_whatsapp'        => '081234567890',
    'customer_emergency_phone' => '081987654321',
    'customer_ktp_address'     => 'Jl. Pahlawan No. 45 Surabaya',
    'customer_stay_address'    => 'Hotel Tugu Malang',
    'customer_social_media'    => '@budisetiawan',
    'rental_notes'             => 'Butuh 2 helm ukuran L dan jas hujan setelan',
);

$msg = ryokourent_build_whatsapp_message($sample_data);

run_test("Pesan memuat header ber-emotikon 🛵 dan nama RYOKOURENT", strpos($msg, '🛵 *FORMULIR PEMESANAN SEWA MOTOR - RYOKOURENT MALANG & BATU*') !== false);
run_test("Pesan memuat nama motor Honda BeAT Deluxe 110cc", strpos($msg, '• Model Motor: *Honda BeAT Deluxe 110cc*') !== false);
run_test("Pesan memuat durasi 3 Hari (~56.5 Jam)", strpos($msg, '• Estimasi Durasi: 3 Hari (~56.5 Jam)') !== false);
run_test("Pesan memuat lokasi pengambilan Stasiun Malang", strpos($msg, '• Lokasi Pengambilan: Stasiun Malang') !== false);
run_test("Pesan memuat estimasi biaya Rp 255.000", strpos($msg, '• Estimasi Biaya Sewa: *Rp 255.000*') !== false);
run_test("Pesan memuat nama penyewa Budi Setiawan", strpos($msg, '• Nama Lengkap: *Budi Setiawan*') !== false);
run_test("Pesan memuat nomor WhatsApp penyewa", strpos($msg, '• Nomor WhatsApp: 081234567890') !== false);
run_test("Pesan memuat kontak darurat keluarga", strpos($msg, '• Kontak Darurat (Keluarga): 081987654321') !== false);
run_test("Pesan memuat alamat KTP dan tempat menginap", strpos($msg, '• Alamat Sesuai KTP: Jl. Pahlawan No. 45 Surabaya') !== false && strpos($msg, '• Tempat Menginap di Malang/Batu: Hotel Tugu Malang') !== false);
run_test("Pesan memuat catatan perlengkapan helm & jas hujan", strpos($msg, '• Catatan Tambahan: Butuh 2 helm ukuran L dan jas hujan setelan') !== false);
run_test("Pesan memuat klausul perlindungan data UU PDP", strpos($msg, 'dilindungi UU PDP') !== false);

// 3. Test Bromo Route Destination Label
$bromo_data = $sample_data;
$bromo_data['trip_destination'] = 'bromo';
$bromo_data['motor_name'] = 'Honda Trail CRF 150L';
$bromo_msg = ryokourent_build_whatsapp_message($bromo_data);
run_test("Rute bromo memuat label 'Trip Kaldera Gunung Bromo (Khusus Trail CRF 150L)'", strpos($bromo_msg, 'Trip Kaldera Gunung Bromo (Khusus Trail CRF 150L)') !== false);

// 4. Test URL Generation with rawurlencode()
$wa_url = ryokourent_get_whatsapp_url($sample_data);

run_test("Tautan dimulai dengan https://wa.me/6281234567890?text=", strpos($wa_url, 'https://wa.me/6281234567890?text=') === 0);
run_test("Tautan tidak mengandung spasi mentah (sudah di-encode)", strpos($wa_url, ' ') === false);
run_test("Tautan tidak mengandung newline mentah", strpos($wa_url, "\n") === false);

// 5. Test Round-trip Integrity: decode(url_text) === original message
$query_parts = explode('?text=', $wa_url);
$decoded_text = rawurldecode($query_parts[1]);
run_test("Teks yang di-decode dari URL 100% identik dengan teks asli tanpa pemotongan karakter", $decoded_text === $msg);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
