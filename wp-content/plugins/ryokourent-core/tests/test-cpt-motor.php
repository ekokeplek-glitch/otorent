<?php
/**
 * Test Suite for CPT Motor, Meta Fields, and Admin Columns
 *
 * Verifies post type registration parameters, meta field sanitization logic,
 * capability security checks, and admin column structure for TASK-004.
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

// Global mock state for WordPress functions
$GLOBALS['mock_post_types'] = array();
$GLOBALS['mock_post_meta']  = array();
$GLOBALS['mock_filters']    = array();
$GLOBALS['mock_actions']    = array();
$GLOBALS['current_user_can_result'] = true;

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
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_actions'][$tag][] = $callback;
    }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_filters'][$tag][] = $callback;
    }
}
if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array()) {
        $GLOBALS['mock_post_types'][$post_type] = $args;
        return (object) $args;
    }
}
if (!function_exists('register_post_meta')) {
    function register_post_meta($post_type, $meta_key, $args = array()) {
        $GLOBALS['mock_post_meta'][$post_type][$meta_key] = $args;
        return true;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        return $GLOBALS['current_user_can_result'];
    }
}
if (!function_exists('is_admin')) {
    function is_admin() {
        return true;
    }
}

// Load source files
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-fields.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/post-types.php';
require_once RYOKOURENT_PLUGIN_DIR . 'admin/motor-columns.php';

// Trigger initialization functions
ryokourent_register_cpt_motor();
ryokourent_register_motor_meta_fields();

// Test Runner
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function run_test($name, $assertion) {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    if ($assertion) {
        $passed_tests++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed_tests++;
        echo "  [FAIL] {$name}\n";
    }
}

echo "====================================================\n";
echo "RUNNING CPT MOTOR & META FIELDS TEST SUITE (TASK-004)\n";
echo "====================================================\n\n";

// 1. CPT Registration Verification
echo "1. CPT Motor Registration Parameters:\n";
$motor_args = $GLOBALS['mock_post_types']['motor'] ?? null;
run_test("CPT 'motor' is registered in WordPress", null !== $motor_args);
run_test("CPT 'motor' is public", true === ($motor_args['public'] ?? false));
run_test("CPT 'motor' has archive slug 'motor'", 'motor' === ($motor_args['has_archive'] ?? ''));
run_test("CPT 'motor' menu_icon is 'dashicons-car'", 'dashicons-car' === ($motor_args['menu_icon'] ?? ''));
run_test("CPT 'motor' supports thumbnail and editor", in_array('thumbnail', $motor_args['supports'] ?? array(), true) && in_array('editor', $motor_args['supports'] ?? array(), true));
run_test("CPT 'motor' show_in_rest is enabled for Block Editor", true === ($motor_args['show_in_rest'] ?? false));

// 2. Meta Fields Registration Verification
echo "\n2. Meta Fields Schema Registration:\n";
$motor_metas = $GLOBALS['mock_post_meta']['motor'] ?? array();
$expected_metas = array(
    '_ryokou_engine_cc',
    '_ryokou_transmission',
    '_ryokou_route_character',
    '_ryokou_is_bromo_ready',
    '_ryokou_price_daily',
    '_ryokou_price_weekly',
    '_ryokou_price_monthly',
    '_ryokou_physical_stock',
    '_ryokou_plate_numbers',
    '_ryokou_status_label',
);
foreach ($expected_metas as $meta_key) {
    run_test("Meta field '{$meta_key}' is registered with sanitization callback", isset($motor_metas[$meta_key]) && !empty($motor_metas[$meta_key]['sanitize_callback']));
}
run_test("Internal meta '_ryokou_physical_stock' is hidden from public REST", false === ($motor_metas['_ryokou_physical_stock']['show_in_rest'] ?? true));
run_test("Internal meta '_ryokou_plate_numbers' is hidden from public REST", false === ($motor_metas['_ryokou_plate_numbers']['show_in_rest'] ?? true));

// 3. Sanitization Function Tests
echo "\n3. Input Sanitization Callbacks:\n";

// Engine CC
run_test("Sanitize engine CC 110 (valid)", 110 === ryokourent_sanitize_engine_cc('110'));
run_test("Sanitize engine CC with string '125cc'", 125 === ryokourent_sanitize_engine_cc('125cc'));
run_test("Sanitize engine CC negative/invalid", 0 === ryokourent_sanitize_engine_cc('-50'));

// Transmission
run_test("Sanitize transmission 'Otomatis (CVT)'", 'Otomatis (CVT)' === ryokourent_sanitize_transmission('Otomatis (CVT)'));
run_test("Sanitize transmission 'Manual (Kopling)'", 'Manual (Kopling)' === ryokourent_sanitize_transmission('Manual (Kopling)'));
run_test("Sanitize transmission invalid fallback", 'Otomatis (CVT)' === ryokourent_sanitize_transmission('Invalid Transmission'));

// Route Character
run_test("Sanitize route character strip tags", 'Lincah & Sangat Irit' === ryokourent_sanitize_route_character('<b>Lincah & Sangat Irit</b>'));

// Bromo Ready
run_test("Sanitize Bromo ready bool true", 1 === ryokourent_sanitize_bromo_ready(true));
run_test("Sanitize Bromo ready string '1'", 1 === ryokourent_sanitize_bromo_ready('1'));
run_test("Sanitize Bromo ready string '0'", 0 === ryokourent_sanitize_bromo_ready('0'));

// Prices
run_test("Sanitize price integer 85000", 85000 === ryokourent_sanitize_price_integer(85000));
run_test("Sanitize price formatted 'Rp 85.000'", 85000 === ryokourent_sanitize_price_integer('Rp 85.000'));
run_test("Sanitize price weekly 'Rp 500.000'", 500000 === ryokourent_sanitize_price_integer('Rp 500.000'));

// Physical Stock
run_test("Sanitize physical stock 5", 5 === ryokourent_sanitize_physical_stock('5'));
run_test("Sanitize physical stock cap 500", 500 === ryokourent_sanitize_physical_stock('1000'));

// Plate Numbers
$raw_plates = "n 1234 abc\n\nN 5678 def \n<script>alert(1)</script>\nN 1234 ABC";
$clean_plates = ryokourent_sanitize_plate_numbers_text($raw_plates);
run_test("Sanitize plates normalizes uppercase & deduplicates", str_contains($clean_plates, "N 1234 ABC\nN 5678 DEF"));

// Status Label
run_test("Sanitize status 'Tersedia'", 'Tersedia' === ryokourent_sanitize_status_label('Tersedia'));
run_test("Sanitize status 'Booking Menipis'", 'Booking Menipis' === ryokourent_sanitize_status_label('Booking Menipis'));
run_test("Sanitize status invalid fallback", 'Tersedia' === ryokourent_sanitize_status_label('Hacking status'));

// 4. Admin Columns Verification
echo "\n4. Admin Columns Definition & Capabilities:\n";
$columns = ryokourent_motor_columns(array('cb' => '<input />', 'title' => 'Title', 'date' => 'Date'));
run_test("Columns include 'ryokourent_thumb'", isset($columns['ryokourent_thumb']));
run_test("Columns include 'ryokourent_specs'", isset($columns['ryokourent_specs']));
run_test("Columns include 'ryokourent_price_daily'", isset($columns['ryokourent_price_daily']));
run_test("Columns include 'ryokourent_stock'", isset($columns['ryokourent_stock']));
run_test("Columns include 'ryokourent_bromo'", isset($columns['ryokourent_bromo']));
run_test("Columns include 'ryokourent_status'", isset($columns['ryokourent_status']));

// Capability check protection
$GLOBALS['current_user_can_result'] = false;
$unauthorized_columns = ryokourent_motor_columns(array('title' => 'Title'));
run_test("Capability check blocks unauthorized column customization", !isset($unauthorized_columns['ryokourent_specs']));

echo "\n----------------------------------------------------\n";
echo "SUMMARY: {$total_tests} Tests, {$passed_tests} Passed, {$failed_tests} Failed.\n";
echo "----------------------------------------------------\n";

if ($failed_tests > 0) {
    exit(1);
}
exit(0);
