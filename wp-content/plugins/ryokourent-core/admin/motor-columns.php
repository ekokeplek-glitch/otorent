<?php
/**
 * Admin Columns and List Table Customization for CPT Motor
 *
 * Configures custom columns in the WordPress admin list table for 'motor'
 * (Armada Motor), displaying thumbnail previews, engine CC, daily pricing,
 * physical stock count, Bromo readiness, and public status badges.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/admin
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define custom columns for the 'motor' post type list table.
 *
 * @since 1.0.0
 * @param array $columns Existing columns array.
 * @return array Modified columns array.
 */
function ryokourent_motor_columns($columns) {
    if (!current_user_can('edit_posts')) {
        return $columns;
    }

    $new_columns = array();

    // Keep checkbox first
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }

    // Thumbnail column
    $new_columns['ryokourent_thumb'] = __('Foto', 'ryokourent');

    // Title / Model name
    $new_columns['title'] = __('Model Motor', 'ryokourent');

    // Engine CC & Transmission
    $new_columns['ryokourent_specs'] = __('Spesifikasi Mesin', 'ryokourent');

    // Rates
    $new_columns['ryokourent_price_daily'] = __('Tarif Harian (24 Jam)', 'ryokourent');

    // Internal stock
    $new_columns['ryokourent_stock'] = __('Unit Fisik', 'ryokourent');

    // Route / Bromo Readiness
    $new_columns['ryokourent_bromo'] = __('Rute Bromo', 'ryokourent');

    // Public Status Badge
    $new_columns['ryokourent_status'] = __('Status Publik', 'ryokourent');

    // Date
    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }

    return $new_columns;
}
add_filter('manage_motor_posts_columns', 'ryokourent_motor_columns');

/**
 * Render custom column content for CPT 'motor'.
 *
 * @since 1.0.0
 * @param string $column  The name of the column to display.
 * @param int    $post_id The current post ID.
 * @return void
 */
function ryokourent_motor_custom_column_content($column, $post_id) {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $post_id = absint($post_id);

    switch ($column) {
        case 'ryokourent_thumb':
            if (has_post_thumbnail($post_id)) {
                $thumb_url = get_the_post_thumbnail_url($post_id, array(60, 60));
                echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">';
                echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #dcdcde;" />';
                echo '</a>';
            } else {
                echo '<span style="display:inline-block;width:50px;height:50px;line-height:50px;text-align:center;background:#f0f0f1;color:#8c8f94;border-radius:6px;font-size:11px;">' . esc_html__('No Img', 'ryokourent') . '</span>';
            }
            break;

        case 'ryokourent_specs':
            $cc = absint(get_post_meta($post_id, '_ryokou_engine_cc', true));
            $trans = sanitize_text_field(get_post_meta($post_id, '_ryokou_transmission', true));
            $character = sanitize_text_field(get_post_meta($post_id, '_ryokou_route_character', true));

            echo '<strong>' . ($cc > 0 ? esc_html($cc) . ' cc' : '<span style="color:#999;">-</span>') . '</strong>';
            if (!empty($trans)) {
                echo ' &bull; <span style="color:#50575e;">' . esc_html($trans) . '</span>';
            }
            if (!empty($character)) {
                echo '<br><small style="color:#646970;">' . esc_html($character) . '</small>';
            }
            break;

        case 'ryokourent_price_daily':
            $price_daily = absint(get_post_meta($post_id, '_ryokou_price_daily', true));
            if ($price_daily > 0) {
                if (function_exists('ryokourent_format_rupiah')) {
                    $formatted = ryokourent_format_rupiah($price_daily);
                } else {
                    $formatted = 'Rp ' . number_format($price_daily, 0, ',', '.');
                }
                echo '<span style="font-weight:600;color:#0f172a;">' . esc_html($formatted) . '</span><span style="font-size:11px;color:#64748b;"> /hari</span>';
            } else {
                echo '<span style="color:#94a3b8;font-style:italic;">' . esc_html__('Belum diset', 'ryokourent') . '</span>';
            }
            break;

        case 'ryokourent_stock':
            $stock = absint(get_post_meta($post_id, '_ryokou_physical_stock', true));
            $plates_raw = get_post_meta($post_id, '_ryokou_plate_numbers', true);
            $plates_count = !empty($plates_raw) ? count(array_filter(explode("\n", (string) $plates_raw))) : 0;

            if ($stock > 0) {
                echo '<strong>' . esc_html($stock) . ' ' . esc_html__('Unit', 'ryokourent') . '</strong>';
                if ($plates_count > 0) {
                    echo '<br><small style="color:#64748b;">' . esc_html($plates_count) . ' ' . esc_html__('plat terdaftar', 'ryokourent') . '</small>';
                }
            } else {
                echo '<span style="color:#dc2626;font-weight:500;">0 Unit</span>';
            }
            break;

        case 'ryokourent_bromo':
            $is_bromo = (bool) get_post_meta($post_id, '_ryokou_is_bromo_ready', true);
            if ($is_bromo) {
                echo '<span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;">' . esc_html__('Wajib Bromo', 'ryokourent') . '</span>';
            } else {
                echo '<span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:#f1f5f9;color:#64748b;">' . esc_html__('Dalam Kota & Batu', 'ryokourent') . '</span>';
            }
            break;

        case 'ryokourent_status':
            $status = sanitize_text_field(get_post_meta($post_id, '_ryokou_status_label', true));
            if (empty($status)) {
                $status = 'Tersedia';
            }

            switch ($status) {
                case 'Tersedia':
                    echo '<span style="display:inline-block;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#dcfce7;color:#15803d;">' . esc_html($status) . '</span>';
                    break;
                case 'Booking Menipis':
                    echo '<span style="display:inline-block;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#fef3c7;color:#b45309;">' . esc_html($status) . '</span>';
                    break;
                case 'Penuh':
                    echo '<span style="display:inline-block;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#fee2e2;color:#b91c1c;">' . esc_html($status) . '</span>';
                    break;
                default:
                    echo esc_html($status);
                    break;
            }
            break;
    }
}
add_action('manage_motor_posts_custom_column', 'ryokourent_motor_custom_column_content', 10, 2);

/**
 * Register sortable columns for CPT 'motor'.
 *
 * @since 1.0.0
 * @param array $sortable_columns Existing sortable columns.
 * @return array Modified sortable columns.
 */
function ryokourent_motor_sortable_columns($sortable_columns) {
    if (!current_user_can('edit_posts')) {
        return $sortable_columns;
    }

    $sortable_columns['ryokourent_price_daily'] = 'ryokourent_price_daily';
    $sortable_columns['ryokourent_stock']       = 'ryokourent_stock';

    return $sortable_columns;
}
add_filter('manage_edit-motor_sortable_columns', 'ryokourent_motor_sortable_columns');

/**
 * Handle custom sorting logic for post meta fields in CPT 'motor' query.
 *
 * @since 1.0.0
 * @param WP_Query $query The main query object.
 * @return void
 */
function ryokourent_motor_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        return;
    }

    $post_type = $query->get('post_type');
    if ('motor' !== $post_type) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('ryokourent_price_daily' === $orderby) {
        $query->set('meta_key', '_ryokou_price_daily');
        $query->set('orderby', 'meta_value_num');
    } elseif ('ryokourent_stock' === $orderby) {
        $query->set('meta_key', '_ryokou_physical_stock');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'ryokourent_motor_columns_orderby');
