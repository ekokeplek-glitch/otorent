<?php
/**
 * Test Suite for CPT Penyewaan and Custom Post Statuses (TASK-009 & TASK-010)
 *
 * Verifies post type registration parameters, PII security protection (public => false, show_in_rest => false),
 * custom capability mapping (manage_ryokourent_bookings), and 5 custom booking post statuses.
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

// Global mock state
$GLOBALS['mock_post_types'] = array();
$GLOBALS['mock_post_statuses'] = array();
$GLOBALS['mock_actions'] = array();
$GLOBALS['mock_filters'] = array();

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
if (!function_exists('_n_noop')) {
    function _n_noop($singular, $plural, $domain = 'default') {
        return array('singular' => $singular, 'plural' => $plural);
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_actions'][$tag][] = array(
            'callback' => $callback,
            'priority' => $priority,
        );
    }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_filters'][$tag][] = array(
            'callback' => $callback,
            'priority' => $priority,
        );
    }
}
if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array()) {
        $GLOBALS['mock_post_types'][$post_type] = $args;
        return true;
    }
}
if (!function_exists('register_post_status')) {
    function register_post_status($status, $args = array()) {
        $GLOBALS['mock_post_statuses'][$status] = $args;
        return true;
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower($key));
    }
}

// Load helpers, post-types, and meta-boxes
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/post-types.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-boxes.php';

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

echo "=== Menjalankan Unit Test CPT Penyewaan & Custom Post Statuses (TASK-009 & TASK-010) ===" . PHP_EOL . PHP_EOL;

// 1. Trigger registration
ryokourent_register_cpt_penyewaan();
ryokourent_register_booking_post_statuses();

// 2. Verify CPT penyewaan registration
run_test("CPT 'penyewaan' berhasil didaftarkan", isset($GLOBALS['mock_post_types']['penyewaan']));

$penyewaan_args = $GLOBALS['mock_post_types']['penyewaan'];

// 3. Verify PII Protection Parameters (Security Hardening K1 & K4)
run_test("CPT 'penyewaan' public => false (mencegah akses URL publik)", $penyewaan_args['public'] === false);
run_test("CPT 'penyewaan' publicly_queryable => false", $penyewaan_args['publicly_queryable'] === false);
run_test("CPT 'penyewaan' show_in_rest => false (mencegah kebocoran PII via REST API)", $penyewaan_args['show_in_rest'] === false);
run_test("CPT 'penyewaan' exclude_from_search => true", $penyewaan_args['exclude_from_search'] === true);
run_test("CPT 'penyewaan' show_ui => true (tampil di dashboard internal)", $penyewaan_args['show_ui'] === true);
run_test("CPT 'penyewaan' menu_icon => 'dashicons-calendar-alt'", $penyewaan_args['menu_icon'] === 'dashicons-calendar-alt');

// 4. Verify Capabilities Mapping
$booking_cap = 'manage_ryokourent_bookings';
run_test("Capabilities edit_posts dipetakan ke 'manage_ryokourent_bookings'", isset($penyewaan_args['capabilities']['edit_posts']) && $penyewaan_args['capabilities']['edit_posts'] === $booking_cap);
run_test("Capabilities map_meta_cap aktif", !empty($penyewaan_args['map_meta_cap']));

// 5. Verify Custom Post Statuses (TASK-010)
$expected_statuses = array(
    'status_menunggu',
    'status_dikonfirmasi',
    'status_berjalan',
    'status_selesai',
    'status_dibatalkan',
);

$registered_statuses = $GLOBALS['mock_post_statuses'];

foreach ($expected_statuses as $slug) {
    run_test("Status '{$slug}' terdaftar", isset($registered_statuses[$slug]));
    run_test("Slug status '{$slug}' <= 20 karakter", strlen($slug) <= 20);
    run_test("Status '{$slug}' public => false", isset($registered_statuses[$slug]['public']) && $registered_statuses[$slug]['public'] === false);
    run_test("Status '{$slug}' exclude_from_search => true", !empty($registered_statuses[$slug]['exclude_from_search']));
}

// 6. Test Status Dictionary helper
$dict = ryokourent_get_booking_statuses();
run_test("Dictionary status memuat 5 status", count($dict) === 5);
run_test("status_dikonfirmasi ditandai counts_quota = true", $dict['status_dikonfirmasi']['counts_quota'] === true);
run_test("status_berjalan ditandai counts_quota = true", $dict['status_berjalan']['counts_quota'] === true);
run_test("status_menunggu ditandai counts_quota = false", $dict['status_menunggu']['counts_quota'] === false);

// 7. Test wp_insert_post_data preservation filter
$dummy_data = array(
    'post_type'   => 'penyewaan',
    'post_status' => 'draft',
);
$_POST['ryokourent_booking_status'] = 'status_dikonfirmasi';

$filtered_data = ryokourent_filter_booking_post_status($dummy_data, array());
run_test("Filter wp_insert_post_data mempertahankan status kustom status_dikonfirmasi", $filtered_data['post_status'] === 'status_dikonfirmasi');

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
