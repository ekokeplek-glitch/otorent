<?php
/**
 * Custom Post Types Registration for Ryokourent
 *
 * Registers the 'motor' Custom Post Type (Fleet Catalog) and sets up
 * associated capabilities, archive slugs, and admin interfaces.
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
 * Register Custom Post Type: motor (Armada Motor).
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_cpt_motor() {
    $labels = array(
        'name'                  => _x('Armada Motor', 'Post Type General Name', 'ryokourent'),
        'singular_name'         => _x('Motor', 'Post Type Singular Name', 'ryokourent'),
        'menu_name'             => __('Armada Motor', 'ryokourent'),
        'name_admin_bar'        => __('Motor', 'ryokourent'),
        'archives'              => __('Katalog Armada Motor', 'ryokourent'),
        'attributes'            => __('Atribut Motor', 'ryokourent'),
        'parent_item_colon'     => __('Motor Induk:', 'ryokourent'),
        'all_items'             => __('Semua Armada Motor', 'ryokourent'),
        'add_new_item'          => __('Tambah Motor Baru', 'ryokourent'),
        'add_new'               => __('Tambah Baru', 'ryokourent'),
        'new_item'              => __('Motor Baru', 'ryokourent'),
        'edit_item'             => __('Edit Motor', 'ryokourent'),
        'update_item'           => __('Perbarui Motor', 'ryokourent'),
        'view_item'             => __('Lihat Motor', 'ryokourent'),
        'view_items'            => __('Lihat Armada', 'ryokourent'),
        'search_items'          => __('Cari Motor', 'ryokourent'),
        'not_found'             => __('Tidak ada armada motor ditemukan', 'ryokourent'),
        'not_found_in_trash'    => __('Tidak ada armada motor di Tempat Sampah', 'ryokourent'),
        'featured_image'        => __('Foto Utama Motor', 'ryokourent'),
        'set_featured_image'    => __('Pilih Foto Utama Motor', 'ryokourent'),
        'remove_featured_image' => __('Hapus Foto Utama Motor', 'ryokourent'),
        'use_featured_image'    => __('Gunakan sebagai Foto Utama', 'ryokourent'),
        'insert_into_item'      => __('Sisipkan ke dalam Motor', 'ryokourent'),
        'uploaded_to_this_item' => __('Diunggah ke Motor ini', 'ryokourent'),
        'items_list'            => __('Daftar Armada Motor', 'ryokourent'),
        'items_list_navigation' => __('Navigasi Daftar Motor', 'ryokourent'),
        'filter_items_list'     => __('Filter Daftar Motor', 'ryokourent'),
    );

    $rewrite = array(
        'slug'       => 'motor',
        'with_front' => false,
        'pages'      => true,
        'feeds'      => false,
    );

    $args = array(
        'label'               => __('Motor', 'ryokourent'),
        'description'         => __('Katalog armada sepeda motor sewa Ryokourent Malang & Batu', 'ryokourent'),
        'labels'              => $labels,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'taxonomies'          => array('kategori_motor'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-car',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => 'motor',
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'show_in_rest'        => true,
        'rewrite'             => $rewrite,
    );

    register_post_type('motor', $args);
}
add_action('init', 'ryokourent_register_cpt_motor', 5);

/**
 * Register Custom Post Type: penyewaan (Data Penyewaan Motor).
 *
 * Internal operational CPT to record rental bookings. Kept private and
 * strictly protected via RBAC capability 'manage_ryokourent_bookings'
 * to safeguard Customer PII (Indonesian PDP Law compliance).
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_cpt_penyewaan() {
    $labels = array(
        'name'                  => _x('Penyewaan Motor', 'Post Type General Name', 'ryokourent'),
        'singular_name'         => _x('Penyewaan', 'Post Type Singular Name', 'ryokourent'),
        'menu_name'             => __('Penyewaan Motor', 'ryokourent'),
        'name_admin_bar'        => __('Penyewaan', 'ryokourent'),
        'archives'              => __('Arsip Penyewaan', 'ryokourent'),
        'attributes'            => __('Atribut Penyewaan', 'ryokourent'),
        'all_items'             => __('Semua Data Sewa', 'ryokourent'),
        'add_new_item'          => __('Tambah Penyewaan Baru', 'ryokourent'),
        'add_new'               => __('Tambah Baru', 'ryokourent'),
        'new_item'              => __('Penyewaan Baru', 'ryokourent'),
        'edit_item'             => __('Edit Data Sewa', 'ryokourent'),
        'update_item'           => __('Perbarui Data Sewa', 'ryokourent'),
        'view_item'             => __('Lihat Data Sewa', 'ryokourent'),
        'search_items'          => __('Cari Penyewaan', 'ryokourent'),
        'not_found'             => __('Tidak ada data penyewaan ditemukan', 'ryokourent'),
        'not_found_in_trash'    => __('Tidak ada data penyewaan di Tempat Sampah', 'ryokourent'),
        'items_list'            => __('Daftar Riwayat Sewa', 'ryokourent'),
        'items_list_navigation' => __('Navigasi Daftar Sewa', 'ryokourent'),
        'filter_items_list'     => __('Filter Daftar Sewa', 'ryokourent'),
    );

    $booking_cap = 'manage_ryokourent_bookings';

    $args = array(
        'label'               => __('Penyewaan', 'ryokourent'),
        'description'         => __('Data transaksi pemesanan sewa motor Ryokourent internal', 'ryokourent'),
        'labels'              => $labels,
        'supports'            => array('title'),
        'hierarchical'        => false,
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-calendar-alt',
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'query_var'           => false,
        'rewrite'             => false,
        'show_in_rest'        => false, // Strictly false to protect customer PII
        'map_meta_cap'        => true,
        'capabilities'        => array(
            'edit_post'              => $booking_cap,
            'read_post'              => $booking_cap,
            'delete_post'            => $booking_cap,
            'edit_posts'             => $booking_cap,
            'edit_others_posts'      => $booking_cap,
            'publish_posts'          => $booking_cap,
            'read_private_posts'     => $booking_cap,
            'delete_posts'           => $booking_cap,
            'delete_private_posts'   => $booking_cap,
            'delete_published_posts' => $booking_cap,
            'delete_others_posts'    => $booking_cap,
            'edit_private_posts'     => $booking_cap,
            'edit_published_posts'   => $booking_cap,
        ),
    );

    register_post_type('penyewaan', $args);
}
add_action('init', 'ryokourent_register_cpt_penyewaan', 5);

/**
 * Return official dictionary of Ryokourent booking statuses.
 *
 * All slugs are strictly <= 20 characters to comply with WordPress DB schema.
 *
 * @since 1.0.0
 * @return array Array of status definitions with labels, colors, and descriptions.
 */
function ryokourent_get_booking_statuses() {
    return array(
        'status_menunggu'    => array(
            'label'        => _x('Menunggu Konfirmasi', 'post status label', 'ryokourent'),
            'description'  => __('Pesanan baru dari website, menunggu verifikasi identitas e-KTP dan DP.', 'ryokourent'),
            'color'        => '#f59e0b',
            'badge_class'  => 'ryokou-status-menunggu',
            'counts_quota' => false,
        ),
        'status_dikonfirmasi' => array(
            'label'        => _x('Dikonfirmasi', 'post status label', 'ryokourent'),
            'description'  => __('Identitas e-KTP valid & DP diterima. Slot kuota armada terkunci.', 'ryokourent'),
            'color'        => '#3b82f6',
            'badge_class'  => 'ryokou-status-dikonfirmasi',
            'counts_quota' => true,
        ),
        'status_berjalan'    => array(
            'label'        => _x('Sewa Berjalan', 'post status label', 'ryokourent'),
            'description'  => __('Unit telah diserahkan di pool/stasiun, armada sedang dipakai pelanggan.', 'ryokourent'),
            'color'        => '#10b981',
            'badge_class'  => 'ryokou-status-berjalan',
            'counts_quota' => true,
        ),
        'status_selesai'     => array(
            'label'        => _x('Selesai', 'post status label', 'ryokourent'),
            'description'  => __('Unit dikembalikan dalam kondisi baik, deposit jaminan dikembalikan.', 'ryokourent'),
            'color'        => '#64748b',
            'badge_class'  => 'ryokou-status-selesai',
            'counts_quota' => false,
        ),
        'status_dibatalkan'  => array(
            'label'        => _x('Dibatalkan', 'post status label', 'ryokourent'),
            'description'  => __('Pemesanan dibatalkan pelanggan atau ditolak operator.', 'ryokourent'),
            'color'        => '#ef4444',
            'badge_class'  => 'ryokou-status-dibatalkan',
            'counts_quota' => false,
        ),
    );
}

/**
 * Register Custom Post Statuses for CPT 'penyewaan'.
 *
 * Registers: status_menunggu, status_dikonfirmasi, status_berjalan,
 * status_selesai, and status_dibatalkan with public => false.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_booking_post_statuses() {
    $statuses = ryokourent_get_booking_statuses();

    foreach ($statuses as $slug => $data) {
        register_post_status($slug, array(
            'label'                     => $data['label'],
            'public'                    => false,
            'protected'                 => true,
            'private'                   => true,
            'internal'                  => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                $data['label'] . ' <span class="count">(%s)</span>',
                $data['label'] . ' <span class="count">(%s)</span>',
                'ryokourent'
            ),
        ));
    }
}
add_action('init', 'ryokourent_register_booking_post_statuses', 5);


/**
 * Customize update messages for CPT 'motor'.
 *
 * @since 1.0.0
 * @param array $messages Post updated messages.
 * @return array Modified messages array.
 */
function ryokourent_motor_updated_messages($messages) {
    global $post;

    if (!isset($post->post_type) || 'motor' !== $post->post_type) {
        return $messages;
    }

    $permalink = get_permalink($post->ID);

    $messages['motor'] = array(
        0  => '', // Unused. Messages start at index 1.
        1  => sprintf(
            /* translators: %s: URL to view motor */
            __('Armada motor berhasil diperbarui. <a href="%s">Lihat armada</a>', 'ryokourent'),
            esc_url($permalink)
        ),
        2  => __('Custom field motor berhasil diperbarui.', 'ryokourent'),
        3  => __('Custom field motor berhasil dihapus.', 'ryokourent'),
        4  => __('Armada motor berhasil diperbarui.', 'ryokourent'),
        5  => isset($_GET['revision']) ? sprintf(
            /* translators: %s: revision ID */
            __('Armada motor dikembalikan ke revisi dari %s.', 'ryokourent'),
            wp_post_revision_title((int) $_GET['revision'], false)
        ) : false,
        6  => sprintf(
            /* translators: %s: URL to view motor */
            __('Armada motor berhasil diterbitkan. <a href="%s">Lihat armada</a>', 'ryokourent'),
            esc_url($permalink)
        ),
        7  => __('Armada motor berhasil disimpan.', 'ryokourent'),
        8  => sprintf(
            /* translators: %s: URL to preview motor */
            __('Armada motor dikirim untuk ditinjau. <a target="_blank" href="%s">Pratinjau armada</a>', 'ryokourent'),
            esc_url(add_query_arg('preview', 'true', $permalink))
        ),
        9  => sprintf(
            /* translators: 1: Scheduled date, 2: URL to preview motor */
            __('Armada motor dijadwalkan terbit pada: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Pratinjau armada</a>', 'ryokourent'),
            date_i18n(__('M j, Y @ G:i', 'ryokourent'), strtotime($post->post_date)),
            esc_url($permalink)
        ),
        10 => sprintf(
            /* translators: %s: URL to preview motor */
            __('Draf armada motor berhasil diperbarui. <a target="_blank" href="%s">Pratinjau armada</a>', 'ryokourent'),
            esc_url(add_query_arg('preview', 'true', $permalink))
        ),
    );

    return $messages;
}
add_filter('post_updated_messages', 'ryokourent_motor_updated_messages');

// Load meta fields registration for CPT motor.
if (file_exists(RYOKOURENT_PLUGIN_DIR . 'includes/meta-fields.php')) {
    require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-fields.php';
}

// Load admin columns and list table customizations.
if (is_admin() && file_exists(RYOKOURENT_PLUGIN_DIR . 'admin/motor-columns.php')) {
    require_once RYOKOURENT_PLUGIN_DIR . 'admin/motor-columns.php';
}
