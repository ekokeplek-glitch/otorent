<?php
/**
 * Post Meta Fields Registration & Sanitization for CPT Motor
 *
 * Defines WordPress post meta schema, sanitization callbacks, and REST API
 * exposure for the 'motor' Custom Post Type in accordance with DATA_MODEL.md.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/includes
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all custom post meta fields for CPT 'motor'.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_motor_meta_fields() {
    $auth_callback = function($allowed, $meta_key, $post_id, $user_id, $cap, $caps) {
        return current_user_can('edit_post', $post_id);
    };

    // 1. Kapasitas Mesin (CC)
    register_post_meta('motor', '_ryokou_engine_cc', array(
        'type'              => 'integer',
        'description'       => __('Kapasitas mesin motor dalam satuan CC (misal 110, 125, 150, 160)', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_engine_cc',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 2. Transmisi Motor
    register_post_meta('motor', '_ryokou_transmission', array(
        'type'              => 'string',
        'description'       => __('Jenis transmisi motor: Otomatis (CVT) atau Manual (Kopling)', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_transmission',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 3. Karakter Rute
    register_post_meta('motor', '_ryokou_route_character', array(
        'type'              => 'string',
        'description'       => __('Deskripsi ringkas kecocokan rute jalan (misal: Lincah & Sangat Irit)', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_route_character',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 4. Khusus Bromo Ready (Hanya Trail CRF 150L)
    register_post_meta('motor', '_ryokou_is_bromo_ready', array(
        'type'              => 'boolean',
        'description'       => __('Menandakan armada diizinkan secara resmi untuk trip Bromo (1 atau 0)', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_bromo_ready',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 5. Tarif Harian (24 Jam)
    register_post_meta('motor', '_ryokou_price_daily', array(
        'type'              => 'integer',
        'description'       => __('Tarif sewa harian (siklus 24 jam) dalam Rupiah', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_price_integer',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 6. Tarif Mingguan (7 Hari)
    register_post_meta('motor', '_ryokou_price_weekly', array(
        'type'              => 'integer',
        'description'       => __('Tarif sewa paket 7 hari dalam Rupiah', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_price_integer',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 7. Tarif Bulanan (30 Hari)
    register_post_meta('motor', '_ryokou_price_monthly', array(
        'type'              => 'integer',
        'description'       => __('Tarif sewa paket 30 hari dalam Rupiah', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_price_integer',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));

    // 8. Jumlah Unit Fisik (Operator Internal - REST tertutup untuk keamanan data)
    register_post_meta('motor', '_ryokou_physical_stock', array(
        'type'              => 'integer',
        'description'       => __('Total unit armada fisik yang dimiliki untuk model ini', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_physical_stock',
        'auth_callback'     => function() {
            return current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');
        },
        'show_in_rest'      => false,
    ));

    // 9. Daftar Plat Nomor (Operator Internal - REST tertutup untuk keamanan data)
    register_post_meta('motor', '_ryokou_plate_numbers', array(
        'type'              => 'string',
        'description'       => __('Daftar plat nomor unit fisik (satu plat per baris)', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_plate_numbers_text',
        'auth_callback'     => function() {
            return current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');
        },
        'show_in_rest'      => false,
    ));

    // 10. Badge Status Publik
    register_post_meta('motor', '_ryokou_status_label', array(
        'type'              => 'string',
        'description'       => __('Status ketersediaan publik: Tersedia, Booking Menipis, atau Penuh', 'ryokourent'),
        'single'            => true,
        'sanitize_callback' => 'ryokourent_sanitize_status_label',
        'auth_callback'     => $auth_callback,
        'show_in_rest'      => true,
    ));
}
add_action('init', 'ryokourent_register_motor_meta_fields', 10);

// -----------------------------------------------------------------------------
// Sanitization Callbacks
// -----------------------------------------------------------------------------

/**
 * Sanitize engine CC value.
 *
 * @since 1.0.0
 * @param mixed $value Raw input value.
 * @return int Non-negative integer.
 */
function ryokourent_sanitize_engine_cc($value) {
    $int = abs(intval($value));
    return ($int > 0 && $int < 2000) ? $int : 0;
}

/**
 * Sanitize transmission field against whitelist.
 *
 * @since 1.0.0
 * @param mixed $value Raw input value.
 * @return string Sanitized transmission string.
 */
function ryokourent_sanitize_transmission($value) {
    $clean = sanitize_text_field((string) $value);
    $valid_options = array(
        'Otomatis (CVT)',
        'Manual (Kopling)',
    );

    return in_array($clean, $valid_options, true) ? $clean : 'Otomatis (CVT)';
}

/**
 * Sanitize route character description string.
 *
 * @since 1.0.0
 * @param mixed $value Raw input string.
 * @return string Clean sanitized text.
 */
function ryokourent_sanitize_route_character($value) {
    return sanitize_text_field((string) $value);
}

/**
 * Sanitize Bromo readiness flag.
 *
 * @since 1.0.0
 * @param mixed $value Raw input value.
 * @return int 1 if ready, 0 otherwise.
 */
function ryokourent_sanitize_bromo_ready($value) {
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    $str = strtolower(trim((string) $value));
    return in_array($str, array('1', 'true', 'yes', 'on'), true) ? 1 : 0;
}

/**
 * Sanitize monetary price into positive integer IDR.
 *
 * @since 1.0.0
 * @param mixed $value Raw input value.
 * @return int Clean non-negative integer amount.
 */
function ryokourent_sanitize_price_integer($value) {
    // Strip non-digit characters if formatted like "Rp 85.000"
    if (is_string($value)) {
        $clean_digits = preg_replace('/[^0-9]/', '', $value);
        return abs(intval($clean_digits));
    }
    return abs(intval($value));
}

/**
 * Sanitize physical stock count.
 *
 * @since 1.0.0
 * @param mixed $value Raw input value.
 * @return int Non-negative integer (0 - 500).
 */
function ryokourent_sanitize_physical_stock($value) {
    $stock = abs(intval($value));
    return min(500, $stock);
}

/**
 * Sanitize multi-line license plate numbers.
 *
 * Each line is sanitized via `ryokourent_sanitize_plate_number()`, empty lines discarded,
 * and result joined by standard newlines.
 *
 * @since 1.0.0
 * @param mixed $value Raw multi-line textarea string.
 * @return string Clean multi-line plate string.
 */
function ryokourent_sanitize_plate_numbers_text($value) {
    if (empty($value)) {
        return '';
    }

    $lines = explode("\n", (string) $value);
    $cleaned_plates = array();

    foreach ($lines as $line) {
        $clean_line = trim($line);
        if ('' === $clean_line) {
            continue;
        }

        if (function_exists('ryokourent_sanitize_plate_number')) {
            $sanitized = ryokourent_sanitize_plate_number($clean_line);
        } else {
            $sanitized = strtoupper(preg_replace('/[^A-Z0-9 ]/', '', $clean_line));
        }

        if (!empty($sanitized) && !in_array($sanitized, $cleaned_plates, true)) {
            $cleaned_plates[] = $sanitized;
        }
    }

    return implode("\n", $cleaned_plates);
}

/**
 * Sanitize public status badge.
 *
 * Allowed: 'Tersedia', 'Booking Menipis', 'Penuh'.
 *
 * @since 1.0.0
 * @param mixed $value Raw input string.
 * @return string Validated status string.
 */
function ryokourent_sanitize_status_label($value) {
    $clean = sanitize_text_field((string) $value);
    $valid_statuses = array(
        'Tersedia',
        'Booking Menipis',
        'Penuh',
    );

    return in_array($clean, $valid_statuses, true) ? $clean : 'Tersedia';
}

// -----------------------------------------------------------------------------
// Data Access Helper Functions for Motor Meta
// -----------------------------------------------------------------------------

/**
 * Retrieve all specifications and internal data for a motor post.
 *
 * @since 1.0.0
 * @param int $post_id Motor post ID.
 * @return array Associative array of motor specifications.
 */
function ryokourent_get_motor_meta($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) {
        return array();
    }

    $raw_plates = get_post_meta($post_id, '_ryokou_plate_numbers', true);
    $plates = !empty($raw_plates) ? array_filter(array_map('trim', explode("\n", $raw_plates))) : array();

    return array(
        'engine_cc'       => absint(get_post_meta($post_id, '_ryokou_engine_cc', true)),
        'transmission'    => get_post_meta($post_id, '_ryokou_transmission', true) ?: 'Otomatis (CVT)',
        'route_character' => get_post_meta($post_id, '_ryokou_route_character', true) ?: '',
        'is_bromo_ready'  => (bool) get_post_meta($post_id, '_ryokou_is_bromo_ready', true),
        'price_daily'     => absint(get_post_meta($post_id, '_ryokou_price_daily', true)),
        'price_weekly'    => absint(get_post_meta($post_id, '_ryokou_price_weekly', true)),
        'price_monthly'   => absint(get_post_meta($post_id, '_ryokou_price_monthly', true)),
        'physical_stock'  => absint(get_post_meta($post_id, '_ryokou_physical_stock', true)),
        'plate_numbers'   => $plates,
        'status_label'    => get_post_meta($post_id, '_ryokou_status_label', true) ?: 'Tersedia',
    );
}

/**
 * Check if a specific motor model is allowed for Mount Bromo trips.
 *
 * @since 1.0.0
 * @param int $post_id Motor post ID.
 * @return bool True if Bromo ready (e.g. CRF 150L), false otherwise.
 */
function ryokourent_is_motor_bromo_ready($post_id) {
    return (bool) get_post_meta(absint($post_id), '_ryokou_is_bromo_ready', true);
}
