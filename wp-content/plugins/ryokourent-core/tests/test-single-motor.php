<?php
/**
 * Test Suite for Single Motor Template (TASK-008)
 *
 * Verifies template existence in child theme, filter hook registration,
 * Bromo advisory differentiation (Matic vs Trail CRF), pricing breakdown,
 * and WhatsApp direct booking links.
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

// Define ABSPATH and plugin constants for mock environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}

$child_theme_dir = ABSPATH . 'wp-content/themes/generatepress-child/';

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

echo "=== Menjalankan Unit Test Halaman Detail Motor (TASK-008) ===" . PHP_EOL . PHP_EOL;

// 1. Verify template files exist
$template_file = $child_theme_dir . 'templates/single-motor.php';
$root_file = $child_theme_dir . 'single-motor.php';
$functions_file = $child_theme_dir . 'functions.php';

run_test("File templates/single-motor.php tersedia di child theme", file_exists($template_file));
run_test("File single-motor.php tersedia di root child theme", file_exists($root_file));
run_test("File functions.php tersedia di child theme", file_exists($functions_file));

// 2. Verify functions.php contains single_template filter
$functions_code = file_get_contents($functions_file);
run_test("functions.php mendaftarkan filter single_template", strpos($functions_code, 'ryokourent_child_motor_single_template') !== false && strpos($functions_code, "add_filter('single_template'") !== false);

// 3. Inspect template code logic
$template_code = file_get_contents($template_file);

run_test("Template memeriksa post meta _ryokou_is_bromo_ready", strpos($template_code, '_ryokou_is_bromo_ready') !== false);
run_test("Template memuat peringatan rute Bromo untuk matik dan CRF", strpos($template_code, 'ryokou-advisory-bromo') !== false && strpos($template_code, 'ryokou-advisory-city') !== false);
run_test("Template menampilkan spesifikasi mesin cc, transmisi, dan karakter", strpos($template_code, '_ryokou_engine_cc') !== false && strpos($template_code, '_ryokou_transmission') !== false && strpos($template_code, '_ryokou_route_character') !== false);
run_test("Template memuat fasilitas 2 Helm SNI dan 2 Jas Hujan", strpos($template_code, '2 Helm SNI') !== false && strpos($template_code, '2 Jas Hujan') !== false);
run_test("Template memuat pricing card dengan paket harian, mingguan, bulanan", strpos($template_code, 'ryokou-pricing-card') !== false && strpos($template_code, 'Paket Mingguan') !== false && strpos($template_code, 'Paket Bulanan') !== false);
run_test("Template memuat link WhatsApp dengan URL resmi", strpos($template_code, 'api.whatsapp.com/send') !== false);
run_test("Template memuat tombol navigasi kembali ke katalog", strpos($template_code, 'Kembali ke Katalog Armada') !== false);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
