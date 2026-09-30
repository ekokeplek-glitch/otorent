<?php
/**
 * Test Suite for CPT Motor Meta Boxes (TASK-005)
 *
 * Verifies metabox registration, nonce verification, autosave guards,
 * capability boundaries (Operator vs Admin), and data persistence with sanitization.
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
$GLOBALS['mock_meta_boxes'] = array();
$GLOBALS['mock_post_meta_db'] = array();
$GLOBALS['mock_current_caps'] = array(
    'edit_post'                  => true,
    'manage_ryokourent_settings' => true,
    'manage_options'             => true,
);

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
if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
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
if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        $out = ((string) $checked === (string) $current) ? 'checked="checked"' : '';
        if ($echo) {
            echo $out;
        }
        return $out;
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
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($val) {
        return is_string($val) ? stripslashes($val) : $val;
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        // Mock hook registration
    }
}
if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null) {
        $GLOBALS['mock_meta_boxes'][$id] = array(
            'title'    => $title,
            'screen'   => $screen,
            'context'  => $context,
            'priority' => $priority,
        );
    }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name) {
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="valid_nonce" />';
    }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return ('valid_nonce' === $nonce);
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        return !empty($GLOBALS['mock_current_caps'][$capability]);
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        return $GLOBALS['mock_post_meta_db'][$post_id][$key] ?? '';
    }
}
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value, $prev_value = '') {
        $GLOBALS['mock_post_meta_db'][$post_id][$key] = $value;
        return true;
    }
}

// Load source helpers, meta fields, and meta boxes
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-fields.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-boxes.php';

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
echo "RUNNING CPT MOTOR METABOX TEST SUITE (TASK-005)\n";
echo "====================================================\n\n";

// 1. Metabox Registration
echo "1. Metabox Registration Verification:\n";
ryokourent_add_motor_meta_boxes();
run_test("Metabox 'ryokourent_motor_specs_metabox' is registered", isset($GLOBALS['mock_meta_boxes']['ryokourent_motor_specs_metabox']));
run_test("Metabox 'ryokourent_motor_pricing_metabox' is registered", isset($GLOBALS['mock_meta_boxes']['ryokourent_motor_pricing_metabox']));
run_test("Metabox 'ryokourent_motor_stock_metabox' is registered", isset($GLOBALS['mock_meta_boxes']['ryokourent_motor_stock_metabox']));

// 2. Nonce Guard Check
echo "\n2. Security Nonce Guard Check:\n";
$_POST = array(
    'post_type' => 'motor',
    '_ryokou_engine_cc' => '125',
    // Missing nonce
);
$post_id = 101;
ryokourent_save_motor_meta_data($post_id);
run_test("Rejects save when nonce is missing", empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc']));

$_POST['ryokourent_motor_meta_nonce'] = 'invalid_nonce';
ryokourent_save_motor_meta_data($post_id);
run_test("Rejects save when nonce is invalid", empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc']));

// 3. Post Type Guard Check
echo "\n3. Post Type Guard Check:\n";
$_POST['ryokourent_motor_meta_nonce'] = 'valid_nonce';
$_POST['post_type'] = 'page';
ryokourent_save_motor_meta_data($post_id);
run_test("Rejects save when post_type is not 'motor'", empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc']));

// 4. Capability Check: Unauthorized User
echo "\n4. Capability Check (Operator without Settings Cap):\n";
$_POST['post_type'] = 'motor';
$_POST['_ryokou_engine_cc'] = '150';
$_POST['_ryokou_price_daily'] = '95000';
$_POST['_ryokou_physical_stock'] = '10';

// Operator can edit_post, but cannot manage_ryokourent_settings
$GLOBALS['mock_current_caps'] = array(
    'edit_post'                  => true,
    'manage_ryokourent_settings' => false,
    'manage_options'             => false,
);

ryokourent_save_motor_meta_data($post_id);
run_test("Operator can save specifications (engine cc)", 150 === ($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc'] ?? null));
run_test("Operator is BLOCKED from modifying pricing", empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_price_daily']));
run_test("Operator is BLOCKED from modifying physical stock", empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_physical_stock']));

// 5. Capability Check: Administrator Full Save with Sanitization
echo "\n5. Administrator Full Save & Sanitization Check:\n";
$GLOBALS['mock_current_caps'] = array(
    'edit_post'                  => true,
    'manage_ryokourent_settings' => true,
    'manage_options'             => true,
);

$_POST = array(
    'ryokourent_motor_meta_nonce' => 'valid_nonce',
    'post_type'                   => 'motor',
    '_ryokou_engine_cc'           => '150cc',
    '_ryokou_transmission'        => 'Manual (Kopling)',
    '_ryokou_route_character'     => '<b>Adventure (Wajib Bromo)</b>',
    '_ryokou_is_bromo_ready'      => '1',
    '_ryokou_status_label'        => 'Booking Menipis',
    '_ryokou_price_daily'         => 'Rp 150.000',
    '_ryokou_price_weekly'        => '900000',
    '_ryokou_price_monthly'       => '3200000',
    '_ryokou_physical_stock'      => '6',
    '_ryokou_plate_numbers'       => "n 1111 abc\n\nN 2222 DEF\nN 1111 ABC",
);

ryokourent_save_motor_meta_data($post_id);

run_test("Engine CC sanitized to integer (150)", 150 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc']);
run_test("Transmission saved as 'Manual (Kopling)'", 'Manual (Kopling)' === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_transmission']);
run_test("Route character stripped of HTML tags", 'Adventure (Wajib Bromo)' === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_route_character']);
run_test("Bromo ready saved as 1", 1 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_is_bromo_ready']);
run_test("Status label saved as 'Booking Menipis'", 'Booking Menipis' === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_status_label']);
run_test("Daily price sanitized from 'Rp 150.000' to integer 150000", 150000 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_price_daily']);
run_test("Weekly price saved as 900000", 900000 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_price_weekly']);
run_test("Monthly price saved as 3200000", 3200000 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_price_monthly']);
run_test("Physical stock saved as 6", 6 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_physical_stock']);
run_test("Plate numbers normalized & deduplicated", "N 1111 ABC\nN 2222 DEF" === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_plate_numbers']);

// Test unchecking Bromo ready
unset($_POST['_ryokou_is_bromo_ready']);
ryokourent_save_motor_meta_data($post_id);
run_test("Unchecked Bromo ready updates to 0", 0 === $GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_is_bromo_ready']);

echo "\n----------------------------------------------------\n";
echo "SUMMARY: {$total_tests} Tests, {$passed_tests} Passed, {$failed_tests} Failed.\n";
echo "----------------------------------------------------\n";

if ($failed_tests > 0) {
    exit(1);
}
exit(0);
