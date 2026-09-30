<?php
/**
 * Test Suite for Kategori Motor Taxonomy (TASK-006)
 *
 * Verifies taxonomy registration parameters, hierarchy, capabilities,
 * rewrite slug, and default terms seeding.
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
$GLOBALS['mock_taxonomies'] = array();
$GLOBALS['mock_terms'] = array();
$GLOBALS['mock_actions'] = array();

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
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_actions'][$tag][] = array(
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }
}
if (!function_exists('register_taxonomy')) {
    function register_taxonomy($taxonomy, $object_type, $args = array()) {
        $GLOBALS['mock_taxonomies'][$taxonomy] = array(
            'object_type' => (array) $object_type,
            'args'        => $args,
        );
        return true;
    }
}
if (!function_exists('taxonomy_exists')) {
    function taxonomy_exists($taxonomy) {
        return isset($GLOBALS['mock_taxonomies'][$taxonomy]);
    }
}
if (!function_exists('term_exists')) {
    function term_exists($term, $taxonomy = '') {
        if (!isset($GLOBALS['mock_terms'][$taxonomy])) {
            return 0;
        }
        return isset($GLOBALS['mock_terms'][$taxonomy][$term]) ? $GLOBALS['mock_terms'][$taxonomy][$term]['term_id'] : 0;
    }
}
if (!function_exists('wp_insert_term')) {
    function wp_insert_term($term, $taxonomy, $args = array()) {
        if (!isset($GLOBALS['mock_terms'][$taxonomy])) {
            $GLOBALS['mock_terms'][$taxonomy] = array();
        }
        $slug = isset($args['slug']) ? $args['slug'] : sanitize_title($term);
        $term_id = count($GLOBALS['mock_terms'][$taxonomy]) + 1;
        $GLOBALS['mock_terms'][$taxonomy][$slug] = array(
            'term_id'     => $term_id,
            'name'        => $term,
            'slug'        => $slug,
            'description' => isset($args['description']) ? $args['description'] : '',
        );
        return array('term_id' => $term_id, 'term_taxonomy_id' => $term_id);
    }
}

// Load taxonomies file
require_once RYOKOURENT_PLUGIN_DIR . 'includes/taxonomies.php';

// Test runner helper
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

echo "=== Menjalankan Unit Test Taxonomy Kategori Motor (TASK-006) ===" . PHP_EOL . PHP_EOL;

// 1. Verify init hook registered
run_test("Hook init mendaftarkan ryokourent_register_taxonomies", isset($GLOBALS['mock_actions']['init']));

// Trigger registration
ryokourent_register_taxonomies();

// 2. Verify taxonomy registration
run_test("Taxonomy 'kategori_motor' terdaftar", isset($GLOBALS['mock_taxonomies']['kategori_motor']));

$tax = $GLOBALS['mock_taxonomies']['kategori_motor'];

// 3. Verify object type
run_test("Taxonomy dikaitkan ke CPT 'motor'", in_array('motor', $tax['object_type'], true));

// 4. Verify hierarchical
run_test("Taxonomy bersifat hierarkis (seperti kategori)", !empty($tax['args']['hierarchical']));

// 5. Verify public and UI
run_test("Taxonomy bersifat public", !empty($tax['args']['public']));
run_test("Taxonomy menampilkan admin column", !empty($tax['args']['show_admin_column']));
run_test("Taxonomy mendukung REST API (Gutenberg)", !empty($tax['args']['show_in_rest']));

// 6. Verify slug
run_test("Slug rewrite adalah 'kategori-motor'", isset($tax['args']['rewrite']['slug']) && $tax['args']['rewrite']['slug'] === 'kategori-motor');

// 7. Verify RBAC capabilities
run_test("Capabilities manage_terms dipegang 'manage_ryokourent_settings'", isset($tax['args']['capabilities']['manage_terms']) && $tax['args']['capabilities']['manage_terms'] === 'manage_ryokourent_settings');
run_test("Capabilities assign_terms dipegang 'edit_posts'", isset($tax['args']['capabilities']['assign_terms']) && $tax['args']['capabilities']['assign_terms'] === 'edit_posts');

// 8. Test seeding default terms
ryokourent_seed_default_motor_categories();

$seeded_terms = isset($GLOBALS['mock_terms']['kategori_motor']) ? $GLOBALS['mock_terms']['kategori_motor'] : array();

run_test("Default term 'beat-series' dibuat", isset($seeded_terms['beat-series']));
run_test("Default term 'scoopy-vario' dibuat", isset($seeded_terms['scoopy-vario']));
run_test("Default term 'trail-adventure' dibuat", isset($seeded_terms['trail-adventure']));

// 9. Re-seeding idempotent test (tidak menduplikasi term)
$terms_count_before = count($seeded_terms);
ryokourent_seed_default_motor_categories();
$terms_count_after = count($GLOBALS['mock_terms']['kategori_motor']);
run_test("Seeding bersifat idempoten (tidak ada duplikasi)", $terms_count_before === $terms_count_after);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;

if ($pass_count !== $test_count) {
    exit(1);
}
