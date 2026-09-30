<?php
/**
 * Test Suite for Catalog Shortcode and Card Templates (TASK-007)
 *
 * Verifies shortcode registration, motor card rendering, price formatting,
 * blueprint fallback fleet, and Bromo advisory badge logic.
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
$GLOBALS['mock_scripts'] = array();
$GLOBALS['mock_styles'] = array();
$GLOBALS['mock_options'] = array();

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
if (!function_exists('sanitize_title')) {
    function sanitize_title($text) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }
}
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r = &$args;
        } else {
            $r = array();
        }
        return array_merge($defaults, $r);
    }
}
if (!function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '') {
        $atts = (array) $atts;
        $out = array();
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
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
        // no-op mock
    }
}
if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
        $GLOBALS['mock_styles'][$handle] = $src;
    }
}
if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
        $GLOBALS['mock_scripts'][$handle] = $src;
    }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle) {
        // no-op mock
    }
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle) {
        // no-op mock
    }
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $name, $data) {
        // no-op mock
    }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . $path;
    }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return isset($GLOBALS['mock_options'][$option]) ? $GLOBALS['mock_options'][$option] : $default;
    }
}
if (!function_exists('get_post')) {
    function get_post($post = null) {
        return null;
    }
}
if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post = null) {
        return false;
    }
}
if (!function_exists('get_the_post_thumbnail')) {
    function get_the_post_thumbnail() {
        return '';
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

// Mock WP_Query class for template testing
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = array();
        public function __construct($args = array()) {
            $this->posts = array();
        }
        public function have_posts() {
            return false;
        }
        public function the_post() {
            return null;
        }
    }
}
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        // no-op
    }
}

// Load templates and shortcodes
require_once RYOKOURENT_PLUGIN_DIR . 'public/templates.php';
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

echo "=== Menjalankan Unit Test Tampilan Katalog Motor (TASK-007) ===" . PHP_EOL . PHP_EOL;

// 1. Price formatting test
$formatted_price = ryokourent_format_catalog_price(85000, '/ 24 Jam');
run_test("Format harga 85.000 menghasilkan Rp 85.000 / 24 Jam", strpos($formatted_price, 'Rp 85.000') !== false && strpos($formatted_price, '/ 24 Jam') !== false);

$zero_price = ryokourent_format_catalog_price(0, '', 'Tanya Admin');
run_test("Harga 0 atau kosong menghasilkan fallback 'Tanya Admin'", strpos($zero_price, 'Tanya Admin') !== false);

// 2. Blueprint default fleet test
$blueprint_fleet = ryokourent_get_blueprint_default_fleet();
run_test("Blueprint default fleet memuat tepat 7 model motor", count($blueprint_fleet) === 7);

$beat_deluxe = $blueprint_fleet[0];
run_test("Model pertama adalah Honda BeAT Deluxe", $beat_deluxe['title'] === 'Honda BeAT Deluxe');
run_test("BeAT Deluxe kategori adalah beat-series", in_array('beat-series', $beat_deluxe['category_slugs'], true));

$crf = $blueprint_fleet[6];
run_test("Model ke-7 adalah Trail CRF 150L", $crf['title'] === 'Trail CRF 150L');
run_test("Trail CRF 150L ditandai is_bromo_ready = true", $crf['is_bromo_ready'] === true);

// 3. Render motor card test
$card_html = ryokourent_render_motor_card(0, $crf);
run_test("Render card menghasilkan artikel dengan class ryokou-motor-card", strpos($card_html, 'class="ryokou-motor-card"') !== false);
run_test("Render card CRF memiliki badge Bromo Ready", strpos($card_html, 'Bromo Ready') !== false);
run_test("Render card memiliki link WhatsApp dengan tombol Chat WA", strpos($card_html, 'api.whatsapp.com/send') !== false && strpos($card_html, 'Chat WA') !== false);
run_test("Render card memiliki tombol Sewa Sekarang", strpos($card_html, 'Sewa Sekarang') !== false);

// 4. Test non-bromo motor
$card_beat_html = ryokourent_render_motor_card(0, $beat_deluxe);
run_test("Render card BeAT Deluxe memiliki badge Malang & Batu", strpos($card_beat_html, 'Malang &amp; Batu') !== false || strpos($card_beat_html, 'Malang & Batu') !== false);

// 5. Shortcode test
run_test("Shortcode [ryokou_catalog] terdaftar", isset($GLOBALS['mock_shortcodes']['ryokou_catalog']));

$catalog_html = ryokourent_catalog_shortcode(array());
run_test("Output shortcode memuat section #katalog-motor", strpos($catalog_html, 'id="katalog-motor"') !== false);
run_test("Output shortcode memuat tab filter kategori", strpos($catalog_html, 'class="ryokou-filter-tabs"') !== false);
run_test("Output shortcode memuat grid armada", strpos($catalog_html, 'class="ryokou-catalog-grid') !== false);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
