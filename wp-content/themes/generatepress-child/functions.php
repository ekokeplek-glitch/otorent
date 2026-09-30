<?php
/**
 * GeneratePress Child Theme Functions.
 *
 * Catatan Arsitektur:
 * File ini HANYA digunakan untuk styling tema dan enqueue asset tampilan.
 * Seluruh logika bisnis (CPT, metabox, kalkulasi harga, booking, WhatsApp)
 * WAJIB diletakkan di dalam plugin "ryokourent-core".
 *
 * @package GeneratePress_Child_Ryokourent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent GeneratePress styles and child theme stylesheet.
 */
function ryokourent_child_enqueue_styles() {
    // Parent theme stylesheet
    wp_enqueue_style(
        'generatepress-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        filemtime(get_template_directory() . '/style.css')
    );

    // Child theme stylesheet
    wp_enqueue_style(
        'generatepress-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('generatepress-parent-style'),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // If viewing single motor post, ensure plugin public styles are enqueued
    if (is_singular('motor') && wp_style_is('ryokourent-public', 'registered')) {
        wp_enqueue_style('ryokourent-public');
    }
}
add_action('wp_enqueue_scripts', 'ryokourent_child_enqueue_styles');

/**
 * Filter single post template for CPT motor.
 * Ensures templates/single-motor.php is loaded with fallback.
 *
 * @param string $template Path to existing template file.
 * @return string Filtered template file path.
 */
function ryokourent_child_motor_single_template($template) {
    if (is_singular('motor')) {
        $custom_template = get_stylesheet_directory() . '/templates/single-motor.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
        $root_template = get_stylesheet_directory() . '/single-motor.php';
        if (file_exists($root_template)) {
            return $root_template;
        }
    }
    return $template;
}
add_filter('single_template', 'ryokourent_child_motor_single_template');

