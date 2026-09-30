<?php
/**
 * Test Suite for Booking Form Layout and Shortcode (TASK-011)
 *
 * Verifies HTML5 form markup, required identity fields, Bromo trip destination toggle,
 * honeypot anti-spam protection, nonce generation, and shortcode registration.
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
if (!defined('RYOKOURENT_PLUGIN_URL')) {
    define('RYOKOURENT_PLUGIN_URL', 'http://example.com/wp-content/plugins/ryokourent-core/');
}
if (!defined('RYOKOURENT_VERSION')) {
    define('RYOKOURENT_VERSION', '1.0.0');
}
if (!defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
    define('RYOKOURENT_DEFAULT_WA_NUMBER', '62895384017772');
}

// Global mock state
$GLOBALS['mock_shortcodes'] = array();

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
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
    function esc_url($text) {
        return $text;
    }
}
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        return array_merge($defaults, (array) $args);
    }
}
if (!function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '') {
        $atts = (array) $atts;
        $out = array();
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }
        return $out;
    }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        $GLOBALS['mock_shortcodes'][$tag] = $callback;
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        // no-op
    }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
        $html = '<input type="hidden" name="' . esc_attr($name) . '" value="mock_nonce_123" />';
        if ($echo) {
            echo $html;
        }
        return $html;
    }
}
if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        $out = ((string) $selected === (string) $current) ? 'selected="selected"' : '';
        if ($echo) {
            echo $out;
        }
        return $out;
    }
}
if (!function_exists('get_posts')) {
    function get_posts($args = array()) {
        return array();
    }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle) {}
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle) {}
}

// Load forms and shortcodes
require_once RYOKOURENT_PLUGIN_DIR . 'public/templates.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/forms.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/shortcodes.php';

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

echo "=== Menjalankan Unit Test Formulir Pemesanan Booking (TASK-011) ===" . PHP_EOL . PHP_EOL;

// 1. Render form test
$form_html = ryokourent_render_booking_form();
run_test("Form booking merender container #booking-form", strpos($form_html, 'id="booking-form"') !== false);
run_test("Form booking memiliki tag <form>", strpos($form_html, '<form') !== false);

// 2. Verify identity fields exist
run_test("Field customer_name tersedia", strpos($form_html, 'name="customer_name"') !== false);
run_test("Field customer_whatsapp tersedia", strpos($form_html, 'name="customer_whatsapp"') !== false);
run_test("Field customer_emergency_phone tersedia", strpos($form_html, 'name="customer_emergency_phone"') !== false);
run_test("Field customer_ktp_address tersedia", strpos($form_html, 'name="customer_ktp_address"') !== false);
run_test("Field customer_stay_address tersedia", strpos($form_html, 'name="customer_stay_address"') !== false);
run_test("Field customer_social_media tersedia", strpos($form_html, 'name="customer_social_media"') !== false);

// 3. Verify rental and route fields exist
run_test("Selector trip_destination (Malang/Batu vs Bromo) tersedia", strpos($form_html, 'name="trip_destination"') !== false && strpos($form_html, 'value="bromo"') !== false);
run_test("Dropdown rented_motor_id tersedia", strpos($form_html, 'name="rented_motor_id"') !== false);
run_test("Dropdown pickup_location tersedia", strpos($form_html, 'name="pickup_location"') !== false);
run_test("Input start_datetime & end_datetime tersedia", strpos($form_html, 'name="start_datetime"') !== false && strpos($form_html, 'name="end_datetime"') !== false);

// 4. Verify security: Honeypot & Nonce
run_test("Field honeypot ryokourent_hp terpasang", strpos($form_html, 'name="ryokourent_hp"') !== false);
run_test("Field nonce ryokourent_booking_nonce terpasang", strpos($form_html, 'name="ryokourent_booking_nonce"') !== false);

// 5. Verify shortcode registration
run_test("Shortcode [ryokou_booking_form] terdaftar", isset($GLOBALS['mock_shortcodes']['ryokou_booking_form']));

$shortcode_output = ryokourent_booking_form_shortcode(array());
run_test("Output shortcode memuat formulir booking", strpos($shortcode_output, 'id="booking-form"') !== false);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
