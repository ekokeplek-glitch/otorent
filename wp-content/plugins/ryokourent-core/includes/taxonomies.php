<?php
/**
 * Taxonomy Registration for CPT Motor
 *
 * Registers the 'kategori_motor' hierarchical taxonomy for categorizing
 * motorcycle fleet units into standard groups (BeAT Series, Scoopy & Vario, Trail Adventure).
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
 * Register 'kategori_motor' custom taxonomy.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_taxonomies() {
    $labels = array(
        'name'                       => _x('Kategori Motor', 'taxonomy general name', 'ryokourent'),
        'singular_name'              => _x('Kategori Motor', 'taxonomy singular name', 'ryokourent'),
        'search_items'               => __('Cari Kategori Motor', 'ryokourent'),
        'all_items'                  => __('Semua Kategori Motor', 'ryokourent'),
        'parent_item'                => __('Induk Kategori', 'ryokourent'),
        'parent_item_colon'          => __('Induk Kategori:', 'ryokourent'),
        'edit_item'                  => __('Edit Kategori Motor', 'ryokourent'),
        'update_item'                => __('Perbarui Kategori Motor', 'ryokourent'),
        'add_new_item'               => __('Tambah Kategori Motor Baru', 'ryokourent'),
        'new_item_name'              => __('Nama Kategori Motor Baru', 'ryokourent'),
        'menu_name'                  => __('Kategori Motor', 'ryokourent'),
        'not_found'                  => __('Kategori motor tidak ditemukan.', 'ryokourent'),
        'no_terms'                   => __('Tidak ada kategori motor.', 'ryokourent'),
        'items_list_navigation'      => __('Navigasi Daftar Kategori', 'ryokourent'),
        'items_list'                 => __('Daftar Kategori Motor', 'ryokourent'),
        'back_to_items'              => __('&larr; Kembali ke Kategori Motor', 'ryokourent'),
    );

    $rewrite = array(
        'slug'         => 'kategori-motor',
        'with_front'   => false,
        'hierarchical' => true,
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rewrite'           => $rewrite,
        'capabilities'      => array(
            'manage_terms' => 'manage_ryokourent_settings',
            'edit_terms'   => 'manage_ryokourent_settings',
            'delete_terms' => 'manage_ryokourent_settings',
            'assign_terms' => 'edit_posts',
        ),
    );

    register_taxonomy('kategori_motor', array('motor'), $args);
}
add_action('init', 'ryokourent_register_taxonomies', 6);

/**
 * Seed default motor categories if not yet created.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_seed_default_motor_categories() {
    if (!taxonomy_exists('kategori_motor')) {
        return;
    }

    $default_terms = array(
        'beat-series' => array(
            'name'        => 'Honda BeAT Series',
            'description' => 'Armada Honda BeAT Series (Deluxe, CBS, Street) - Sangat lincah dan hemat bahan bakar untuk keliling kota Malang dan Batu.',
        ),
        'scoopy-vario' => array(
            'name'        => 'Honda Scoopy & Vario',
            'description' => 'Armada matik premium Honda Scoopy, Vario 125, Vario 160, dan PCX 160 - Nyaman untuk berboncengan dan bagasi lega.',
        ),
        'trail-adventure' => array(
            'name'        => 'Trail Adventure (Bromo)',
            'description' => 'Armada khusus petualangan dan rute ekstrem pasir Gunung Bromo (Honda CRF 150L).',
        ),
    );

    foreach ($default_terms as $slug => $data) {
        if (!term_exists($slug, 'kategori_motor')) {
            wp_insert_term(
                $data['name'],
                'kategori_motor',
                array(
                    'slug'        => $slug,
                    'description' => $data['description'],
                )
            );
        }
    }
}
add_action('init', 'ryokourent_seed_default_motor_categories', 15);

/**
 * Retrieve all registered motor categories with fallback.
 *
 * @since 1.0.0
 * @param array $args Optional query args for get_terms.
 * @return array Array of WP_Term objects or empty array.
 */
function ryokourent_get_motor_categories($args = array()) {
    $defaults = array(
        'taxonomy'   => 'kategori_motor',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    );
    $parsed_args = wp_parse_args($args, $defaults);
    $terms = get_terms($parsed_args);

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return $terms;
}

