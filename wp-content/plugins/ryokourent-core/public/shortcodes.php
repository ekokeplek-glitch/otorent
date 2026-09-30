<?php
/**
 * Shortcodes Registration & Public Asset Enqueuing for Ryokourent
 *
 * Registers the [ryokou_catalog] shortcode and enqueues modern mobile-first
 * styles and filter scripts only when needed.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/public
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register public scripts and styles.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_public_assets() {
    // Public stylesheet
    wp_register_style(
        'ryokourent-public',
        RYOKOURENT_PLUGIN_URL . 'assets/css/ryokourent-public.css',
        array(),
        RYOKOURENT_VERSION
    );

    // Filter and interaction script
    wp_register_script(
        'ryokourent-filter',
        RYOKOURENT_PLUGIN_URL . 'assets/js/ryokourent-filter.js',
        array(),
        RYOKOURENT_VERSION,
        true
    );

    $wa_number = get_option('ryokourent_wa_number', defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $clean_wa = preg_replace('/[^0-9]/', '', (string) $wa_number);

    wp_localize_script('ryokourent-filter', 'ryokouFilterConfig', array(
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'waNumber'      => $clean_wa,
        'bookingAnchor' => '#booking-form',
    ));
}
add_action('wp_enqueue_scripts', 'ryokourent_register_public_assets');

/**
 * Shortcode callback for [ryokou_catalog].
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_catalog_shortcode($atts = array()) {
    // Enqueue registered assets when shortcode is evaluated
    wp_enqueue_style('ryokourent-public');
    wp_enqueue_script('ryokourent-filter');

    $parsed_atts = shortcode_atts(
        array(
            'kategori'    => '',
            'limit'       => -1,
            'show_filter' => 'yes',
            'columns'     => 3,
        ),
        $atts,
        'ryokou_catalog'
    );

    if (function_exists('ryokourent_render_catalog_grid')) {
        return ryokourent_render_catalog_grid($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul katalog Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_catalog', 'ryokourent_catalog_shortcode');

/**
 * Shortcode callback for [ryokou_booking_form].
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_booking_form_shortcode($atts = array()) {
    // Enqueue registered public assets
    wp_enqueue_style('ryokourent-public');
    wp_enqueue_script('ryokourent-filter');

    $parsed_atts = shortcode_atts(
        array(
            'form_id'        => 'ryokourent-booking-form',
            'selected_motor' => 0,
            'title'          => __('Formulir Pemesanan Sewa Motor', 'ryokourent'),
        ),
        $atts,
        'ryokou_booking_form'
    );

    if (function_exists('ryokourent_render_booking_form')) {
        return ryokourent_render_booking_form($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul formulir booking Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_booking_form', 'ryokourent_booking_form_shortcode');

