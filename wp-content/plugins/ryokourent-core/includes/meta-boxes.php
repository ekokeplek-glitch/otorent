<?php
/**
 * Custom Meta Boxes for CPT Motor
 *
 * Provides admin interfaces for managing motor specifications, route character,
 * Bromo trip authorization, rental pricing packages, and internal physical fleet inventory.
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
 * Register meta boxes for CPT 'motor'.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_add_motor_meta_boxes() {
    add_meta_box(
        'ryokourent_motor_specs_metabox',
        __('Spesifikasi & Karakter Armada', 'ryokourent'),
        'ryokourent_render_motor_specs_metabox',
        'motor',
        'normal',
        'high'
    );

    add_meta_box(
        'ryokourent_motor_pricing_metabox',
        __('Tarif Sewa Armada (Rupiah)', 'ryokourent'),
        'ryokourent_render_motor_pricing_metabox',
        'motor',
        'normal',
        'high'
    );

    add_meta_box(
        'ryokourent_motor_stock_metabox',
        __('Inventaris Unit Fisik (Operator Internal)', 'ryokourent'),
        'ryokourent_render_motor_stock_metabox',
        'motor',
        'side',
        'default'
    );
}
add_action('add_meta_boxes_motor', 'ryokourent_add_motor_meta_boxes');

/**
 * Render Specifications Meta Box.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_motor_specs_metabox($post) {
    wp_nonce_field('ryokourent_save_motor_meta_action', 'ryokourent_motor_meta_nonce');

    $engine_cc        = get_post_meta($post->ID, '_ryokou_engine_cc', true);
    $transmission     = get_post_meta($post->ID, '_ryokou_transmission', true) ?: 'Otomatis (CVT)';
    $route_character  = get_post_meta($post->ID, '_ryokou_route_character', true);
    $is_bromo_ready   = (bool) get_post_meta($post->ID, '_ryokou_is_bromo_ready', true);
    $status_label     = get_post_meta($post->ID, '_ryokou_status_label', true) ?: 'Tersedia';
    ?>
    <table class="form-table" style="margin-top: 0;">
        <tbody>
            <tr>
                <th scope="row" style="width: 220px;">
                    <label for="ryokou_engine_cc"><?php esc_html_e('Kapasitas Mesin (CC)', 'ryokourent'); ?></label>
                </th>
                <td>
                    <input type="number" id="ryokou_engine_cc" name="_ryokou_engine_cc" value="<?php echo esc_attr($engine_cc); ?>" class="regular-text" style="max-width: 150px;" min="50" max="2000" step="1" placeholder="110" />
                    <span class="description"><?php esc_html_e('Contoh: 110, 125, 150, atau 160 cc.', 'ryokourent'); ?></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_transmission"><?php esc_html_e('Jenis Transmisi', 'ryokourent'); ?></label>
                </th>
                <td>
                    <select id="ryokou_transmission" name="_ryokou_transmission" style="min-width: 200px;">
                        <option value="Otomatis (CVT)" <?php selected($transmission, 'Otomatis (CVT)'); ?>><?php esc_html_e('Otomatis (CVT)', 'ryokourent'); ?></option>
                        <option value="Manual (Kopling)" <?php selected($transmission, 'Manual (Kopling)'); ?>><?php esc_html_e('Manual (Kopling)', 'ryokourent'); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e('Tipe transmisi kendaraan.', 'ryokourent'); ?></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_route_character"><?php esc_html_e('Karakter Rute / Keunggulan', 'ryokourent'); ?></label>
                </th>
                <td>
                    <input type="text" id="ryokou_route_character" name="_ryokou_route_character" value="<?php echo esc_attr($route_character); ?>" class="large-text" placeholder="Misal: Lincah & Sangat Irit, Nyaman & Bagasi Lega" />
                    <span class="description"><?php esc_html_e('Deskripsi singkat rute atau kenyamanan armada untuk panduan wisatawan.', 'ryokourent'); ?></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_is_bromo_ready"><?php esc_html_e('Kelayakan Rute Bromo', 'ryokourent'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="ryokou_is_bromo_ready" name="_ryokou_is_bromo_ready" value="1" <?php checked($is_bromo_ready, true); ?> />
                        <strong><?php esc_html_e('Unit Siap & Diizinkan untuk Trip Bromo (Wajib Trail CRF)', 'ryokourent'); ?></strong>
                    </label>
                    <p class="description" style="color: #b45309; margin-top: 4px;">
                        <span class="dashicons dashicons-warning" style="vertical-align: middle; font-size: 16px;"></span>
                        <?php esc_html_e('PERINGATAN: Centang opsi ini HANYA untuk motor Honda Trail CRF 150L. Seluruh motor matik dilarang keras untuk trip lautan pasir Gunung Bromo demi keselamatan penyewa.', 'ryokourent'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_status_label"><?php esc_html_e('Badge Status Publik', 'ryokourent'); ?></label>
                </th>
                <td>
                    <select id="ryokou_status_label" name="_ryokou_status_label" style="min-width: 200px;">
                        <option value="Tersedia" <?php selected($status_label, 'Tersedia'); ?>><?php esc_html_e('Tersedia (Hijau)', 'ryokourent'); ?></option>
                        <option value="Booking Menipis" <?php selected($status_label, 'Booking Menipis'); ?>><?php esc_html_e('Booking Menipis (Kuning/Oranye)', 'ryokourent'); ?></option>
                        <option value="Penuh" <?php selected($status_label, 'Penuh'); ?>><?php esc_html_e('Penuh (Merah)', 'ryokourent'); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e('Label ketersediaan visual di halaman katalog frontend.', 'ryokourent'); ?></span>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Render Pricing Meta Box.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_motor_pricing_metabox($post) {
    $price_daily   = get_post_meta($post->ID, '_ryokou_price_daily', true);
    $price_weekly  = get_post_meta($post->ID, '_ryokou_price_weekly', true);
    $price_monthly = get_post_meta($post->ID, '_ryokou_price_monthly', true);

    $can_edit_pricing = current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');
    $disabled_attr    = $can_edit_pricing ? '' : 'disabled="disabled"';
    ?>
    <p class="description" style="margin-bottom: 12px;">
        <?php esc_html_e('Masukkan tarif dalam satuan angka Rupiah penuh (tanpa titik atau koma). Sistem kalkulator booking otomatis menghitung paket harian, mingguan, atau bulanan termurah.', 'ryokourent'); ?>
    </p>

    <?php if (!$can_edit_pricing) : ?>
        <div class="notice notice-warning inline" style="margin: 0 0 15px 0;">
            <p><?php esc_html_e('Anda hanya memiliki izin sebagai Operator. Pengubahan tarif sewa memerlukan kapabilitas Administrator.', 'ryokourent'); ?></p>
        </div>
    <?php endif; ?>

    <table class="form-table" style="margin-top: 0;">
        <tbody>
            <tr>
                <th scope="row" style="width: 220px;">
                    <label for="ryokou_price_daily"><?php esc_html_e('Tarif Harian (24 Jam)', 'ryokourent'); ?></label>
                </th>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #64748b;">Rp</span>
                        <input type="number" id="ryokou_price_daily" name="_ryokou_price_daily" value="<?php echo esc_attr($price_daily); ?>" class="regular-text" style="max-width: 180px;" min="0" step="1000" placeholder="85000" <?php echo $disabled_attr; ?> />
                        <span class="description"><?php esc_html_e('Contoh: 85000 (Rp 85.000 / 24 jam)', 'ryokourent'); ?></span>
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_price_weekly"><?php esc_html_e('Tarif Mingguan (7 Hari)', 'ryokourent'); ?></label>
                </th>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #64748b;">Rp</span>
                        <input type="number" id="ryokou_price_weekly" name="_ryokou_price_weekly" value="<?php echo esc_attr($price_weekly); ?>" class="regular-text" style="max-width: 180px;" min="0" step="1000" placeholder="500000" <?php echo $disabled_attr; ?> />
                        <span class="description"><?php esc_html_e('Contoh: 500000 (Paket 7 hari hemat)', 'ryokourent'); ?></span>
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ryokou_price_monthly"><?php esc_html_e('Tarif Bulanan (30 Hari)', 'ryokourent'); ?></label>
                </th>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #64748b;">Rp</span>
                        <input type="number" id="ryokou_price_monthly" name="_ryokou_price_monthly" value="<?php echo esc_attr($price_monthly); ?>" class="regular-text" style="max-width: 180px;" min="0" step="1000" placeholder="1600000" <?php echo $disabled_attr; ?> />
                        <span class="description"><?php esc_html_e('Contoh: 1600000 (Paket 30 hari langganan)', 'ryokourent'); ?></span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Render Physical Stock & License Plates Meta Box.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_motor_stock_metabox($post) {
    $physical_stock = get_post_meta($post->ID, '_ryokou_physical_stock', true);
    $plate_numbers  = get_post_meta($post->ID, '_ryokou_plate_numbers', true);

    $can_edit_stock = current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');
    $disabled_attr  = $can_edit_stock ? '' : 'disabled="disabled"';
    ?>
    <p style="margin-top: 0; font-size: 12px; color: #64748b;">
        <span class="dashicons dashicons-lock" style="font-size: 15px; vertical-align: middle;"></span>
        <?php esc_html_e('Data ini bersifat RAHASIA OPERATOR INTERNAL. Tertutup dari REST API publik.', 'ryokourent'); ?>
    </p>

    <p>
        <label for="ryokou_physical_stock"><strong><?php esc_html_e('Total Unit Fisik Dimiliki:', 'ryokourent'); ?></strong></label><br />
        <input type="number" id="ryokou_physical_stock" name="_ryokou_physical_stock" value="<?php echo esc_attr($physical_stock); ?>" style="width: 100%; margin-top: 4px;" min="0" max="500" step="1" placeholder="5" <?php echo $disabled_attr; ?> />
        <span class="description" style="font-size: 11px;"><?php esc_html_e('Batas kuota maksimal unit fisik untuk model ini.', 'ryokourent'); ?></span>
    </p>

    <p style="margin-top: 15px;">
        <label for="ryokou_plate_numbers"><strong><?php esc_html_e('Daftar Plat Nomor Unit:', 'ryokourent'); ?></strong></label><br />
        <textarea id="ryokou_plate_numbers" name="_ryokou_plate_numbers" rows="6" style="width: 100%; font-family: monospace; font-size: 12px; margin-top: 4px;" placeholder="N 1234 ABC&#10;N 5678 DEF" <?php echo $disabled_attr; ?>><?php echo esc_textarea($plate_numbers); ?></textarea>
        <span class="description" style="font-size: 11px;"><?php esc_html_e('Tulis satu plat nomor per baris (otomatis dikapitalisasi & dibersihkan).', 'ryokourent'); ?></span>
    </p>
    <?php
}

/**
 * Save meta box data when a 'motor' post is saved.
 *
 * @since 1.0.0
 * @param int $post_id The post ID.
 * @return void
 */
function ryokourent_save_motor_meta_data($post_id) {
    // 1. Guard against autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 2. Verify nonce
    if (!isset($_POST['ryokourent_motor_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ryokourent_motor_meta_nonce'])), 'ryokourent_save_motor_meta_action')) {
        return;
    }

    // 3. Verify post type
    if (!isset($_POST['post_type']) || 'motor' !== $_POST['post_type']) {
        return;
    }

    // 4. Verify post editing capability
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // 5. Save General Specifications (Requires edit_post)
    if (isset($_POST['_ryokou_engine_cc'])) {
        $clean_cc = function_exists('ryokourent_sanitize_engine_cc')
            ? ryokourent_sanitize_engine_cc(wp_unslash($_POST['_ryokou_engine_cc']))
            : absint(wp_unslash($_POST['_ryokou_engine_cc']));
        update_post_meta($post_id, '_ryokou_engine_cc', $clean_cc);
    }

    if (isset($_POST['_ryokou_transmission'])) {
        $clean_trans = function_exists('ryokourent_sanitize_transmission')
            ? ryokourent_sanitize_transmission(wp_unslash($_POST['_ryokou_transmission']))
            : sanitize_text_field(wp_unslash($_POST['_ryokou_transmission']));
        update_post_meta($post_id, '_ryokou_transmission', $clean_trans);
    }

    if (isset($_POST['_ryokou_route_character'])) {
        $clean_route = function_exists('ryokourent_sanitize_route_character')
            ? ryokourent_sanitize_route_character(wp_unslash($_POST['_ryokou_route_character']))
            : sanitize_text_field(wp_unslash($_POST['_ryokou_route_character']));
        update_post_meta($post_id, '_ryokou_route_character', $clean_route);
    }

    // Bromo readiness checkbox (0 if unchecked, 1 if checked)
    $bromo_val = isset($_POST['_ryokou_is_bromo_ready']) ? 1 : 0;
    update_post_meta($post_id, '_ryokou_is_bromo_ready', $bromo_val);

    if (isset($_POST['_ryokou_status_label'])) {
        $clean_status = function_exists('ryokourent_sanitize_status_label')
            ? ryokourent_sanitize_status_label(wp_unslash($_POST['_ryokou_status_label']))
            : sanitize_text_field(wp_unslash($_POST['_ryokou_status_label']));
        update_post_meta($post_id, '_ryokou_status_label', $clean_status);
    }

    // 6. Save Pricing & Stock (Requires manage_ryokourent_settings or manage_options)
    $can_manage_settings = current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');

    if ($can_manage_settings) {
        if (isset($_POST['_ryokou_price_daily'])) {
            $clean_daily = function_exists('ryokourent_sanitize_price_integer')
                ? ryokourent_sanitize_price_integer(wp_unslash($_POST['_ryokou_price_daily']))
                : absint(wp_unslash($_POST['_ryokou_price_daily']));
            update_post_meta($post_id, '_ryokou_price_daily', $clean_daily);
        }

        if (isset($_POST['_ryokou_price_weekly'])) {
            $clean_weekly = function_exists('ryokourent_sanitize_price_integer')
                ? ryokourent_sanitize_price_integer(wp_unslash($_POST['_ryokou_price_weekly']))
                : absint(wp_unslash($_POST['_ryokou_price_weekly']));
            update_post_meta($post_id, '_ryokou_price_weekly', $clean_weekly);
        }

        if (isset($_POST['_ryokou_price_monthly'])) {
            $clean_monthly = function_exists('ryokourent_sanitize_price_integer')
                ? ryokourent_sanitize_price_integer(wp_unslash($_POST['_ryokou_price_monthly']))
                : absint(wp_unslash($_POST['_ryokou_price_monthly']));
            update_post_meta($post_id, '_ryokou_price_monthly', $clean_monthly);
        }

        if (isset($_POST['_ryokou_physical_stock'])) {
            $clean_stock = function_exists('ryokourent_sanitize_physical_stock')
                ? ryokourent_sanitize_physical_stock(wp_unslash($_POST['_ryokou_physical_stock']))
                : min(500, absint(wp_unslash($_POST['_ryokou_physical_stock'])));
            update_post_meta($post_id, '_ryokou_physical_stock', $clean_stock);
        }

        if (isset($_POST['_ryokou_plate_numbers'])) {
            $clean_plates = function_exists('ryokourent_sanitize_plate_numbers_text')
                ? ryokourent_sanitize_plate_numbers_text(wp_unslash($_POST['_ryokou_plate_numbers']))
                : sanitize_textarea_field(wp_unslash($_POST['_ryokou_plate_numbers']));
            update_post_meta($post_id, '_ryokou_plate_numbers', $clean_plates);
        }
    }
}
add_action('save_post_motor', 'ryokourent_save_motor_meta_data');

/**
 * Register meta boxes for CPT 'penyewaan' (Data Penyewaan Motor).
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_add_booking_meta_boxes() {
    add_meta_box(
        'ryokourent_booking_status_metabox',
        __('Status Pemesanan Sewa', 'ryokourent'),
        'ryokourent_render_booking_status_metabox',
        'penyewaan',
        'side',
        'high'
    );

    add_meta_box(
        'ryokourent_booking_customer_metabox',
        __('Data Identitas Pelanggan (PII Terproteksi)', 'ryokourent'),
        'ryokourent_render_booking_customer_metabox',
        'penyewaan',
        'normal',
        'high'
    );

    add_meta_box(
        'ryokourent_booking_details_metabox',
        __('Rincian Armada, Jadwal Sewa & Alokasi Plat', 'ryokourent'),
        'ryokourent_render_booking_details_metabox',
        'penyewaan',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_penyewaan', 'ryokourent_add_booking_meta_boxes');

/**
 * Render Booking Status Meta Box.
 *
 * Provides custom status selector and indicators for CPT 'penyewaan'.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_booking_status_metabox($post) {
    wp_nonce_field('ryokourent_save_booking_meta_action', 'ryokourent_booking_meta_nonce');

    $current_status = $post->post_status;
    $statuses = function_exists('ryokourent_get_booking_statuses') ? ryokourent_get_booking_statuses() : array();

    // Default status if empty or new post
    if (empty($current_status) || $current_status === 'auto-draft' || !array_key_exists($current_status, $statuses)) {
        $current_status = 'status_menunggu';
    }

    $current_info = isset($statuses[$current_status]) ? $statuses[$current_status] : null;
    ?>
    <div class="ryokourent-metabox-wrapper">
        <p style="margin-top:0;">
            <label for="ryokourent_booking_status" style="font-weight:600; display:block; margin-bottom:6px;">
                <?php esc_html_e('Pilih Status Pesanan:', 'ryokourent'); ?>
            </label>
            <select name="ryokourent_booking_status" id="ryokourent_booking_status" class="widefat" style="font-size:14px; font-weight:600; padding:6px 8px;">
                <?php foreach ($statuses as $slug => $data) : ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($current_status, $slug); ?>>
                        <?php echo esc_html($data['label']); ?>
                        <?php echo $data['counts_quota'] ? ' [Kunci Kuota]' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php if ($current_info) : ?>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid <?php echo esc_attr($current_info['color']); ?>; padding:10px; border-radius:4px; margin-top:10px;">
                <strong style="color:<?php echo esc_attr($current_info['color']); ?>; display:block; font-size:12px; text-transform:uppercase; margin-bottom:4px;">
                    <?php echo esc_html($current_info['label']); ?>
                </strong>
                <p style="margin:0; font-size:12px; color:#475569; line-height:1.4;">
                    <?php echo esc_html($current_info['description']); ?>
                </p>
            </div>
        <?php endif; ?>

        <div style="margin-top:12px; font-size:11px; color:#64748b; line-height:1.4; border-top:1px dashed #cbd5e1; padding-top:8px;">
            <em>*Perubahan status ke <strong>Dikonfirmasi</strong> atau <strong>Sewa Berjalan</strong> otomatis menahan kuota ketersediaan unit fisik pada rentang tanggal sewa.</em>
        </div>
    </div>
    <?php
}

/**
 * Filter post data on save to preserve custom post status.
 *
 * Prevents WordPress default classic editor from resetting custom status to 'draft'.
 *
 * @since 1.0.0
 * @param array $data    An array of slashed, sanitized, and processed post data.
 * @param array $postarr An array of sanitized (and unsanitized) post data.
 * @return array Modified post data array.
 */
function ryokourent_filter_booking_post_status($data, $postarr) {
    if (isset($data['post_type']) && $data['post_type'] === 'penyewaan') {
        if (isset($_POST['ryokourent_booking_status'])) {
            $new_status = sanitize_key($_POST['ryokourent_booking_status']);
            $valid_statuses = function_exists('ryokourent_get_booking_statuses')
                ? array_keys(ryokourent_get_booking_statuses())
                : array('status_menunggu', 'status_dikonfirmasi', 'status_berjalan', 'status_selesai', 'status_dibatalkan');

            if (in_array($new_status, $valid_statuses, true)) {
                $data['post_status'] = $new_status;
            }
        }
    }

    return $data;
}
add_filter('wp_insert_post_data', 'ryokourent_filter_booking_post_status', 10, 2);

/**
 * Render Customer Identity Meta Box for CPT 'penyewaan'.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_booking_customer_metabox($post) {
    $name      = get_post_meta($post->ID, '_ryokou_customer_name', true);
    $ktp_addr  = get_post_meta($post->ID, '_ryokou_customer_ktp_address', true);
    $stay_addr = get_post_meta($post->ID, '_ryokou_customer_stay_address', true);
    $whatsapp  = get_post_meta($post->ID, '_ryokou_customer_whatsapp', true);
    $emergency = get_post_meta($post->ID, '_ryokou_customer_emergency_phone', true);
    $social    = get_post_meta($post->ID, '_ryokou_customer_social_media', true);

    $clean_wa = preg_replace('/[^0-9]/', '', (string) $whatsapp);
    $wa_chat_url = !empty($clean_wa) ? 'https://api.whatsapp.com/send?phone=' . esc_attr($clean_wa) : '';
    ?>
    <div class="ryokourent-metabox-wrapper" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <p style="grid-column: span 2; margin:0 0 8px;">
            <label for="_ryokou_customer_name" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Nama Lengkap (sesuai e-KTP):', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_customer_name" id="_ryokou_customer_name" value="<?php echo esc_attr($name); ?>" class="widefat" placeholder="Contoh: Dimas Aditya Pratama" />
        </p>

        <p style="margin:0;">
            <label for="_ryokou_customer_whatsapp" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Nomor WhatsApp Aktif:', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_customer_whatsapp" id="_ryokou_customer_whatsapp" value="<?php echo esc_attr($whatsapp); ?>" class="widefat" placeholder="081234567890" />
            <?php if (!empty($wa_chat_url)) : ?>
                <a href="<?php echo esc_url($wa_chat_url); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block; margin-top:4px; font-size:12px; color:#16a34a; text-decoration:none; font-weight:600;">
                    &rarr; <?php esc_html_e('Buka Chat WhatsApp Pelanggan', 'ryokourent'); ?>
                </a>
            <?php endif; ?>
        </p>

        <p style="margin:0;">
            <label for="_ryokou_customer_emergency_phone" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Kontak Darurat (Keluarga):', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_customer_emergency_phone" id="_ryokou_customer_emergency_phone" value="<?php echo esc_attr($emergency); ?>" class="widefat" placeholder="081345678901 (Keluarga tidak ikut trip)" />
        </p>

        <p style="grid-column: span 2; margin:0;">
            <label for="_ryokou_customer_social_media" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Akun Media Sosial (Instagram / Facebook):', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_customer_social_media" id="_ryokou_customer_social_media" value="<?php echo esc_attr($social); ?>" class="widefat" placeholder="@username_instagram" />
        </p>

        <p style="margin:0;">
            <label for="_ryokou_customer_ktp_address" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Alamat Sesuai KTP:', 'ryokourent'); ?>
            </label>
            <textarea name="_ryokou_customer_ktp_address" id="_ryokou_customer_ktp_address" rows="3" class="widefat" placeholder="Alamat KTP kota asal"><?php echo esc_textarea($ktp_addr); ?></textarea>
        </p>

        <p style="margin:0;">
            <label for="_ryokou_customer_stay_address" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Tempat Menginap di Malang/Batu:', 'ryokourent'); ?>
            </label>
            <textarea name="_ryokou_customer_stay_address" id="_ryokou_customer_stay_address" rows="3" class="widefat" placeholder="Hotel / Homestay / Kost tempat menginap"><?php echo esc_textarea($stay_addr); ?></textarea>
        </p>
    </div>
    <?php
}

/**
 * Render Booking Details, Fleet Allocation, and Schedule Meta Box.
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object.
 * @return void
 */
function ryokourent_render_booking_details_metabox($post) {
    $motor_id      = get_post_meta($post->ID, '_ryokou_rented_motor_id', true);
    $allocated_plt = get_post_meta($post->ID, '_ryokou_booking_allocated_plate', true);
    $pickup_loc    = get_post_meta($post->ID, '_ryokou_pickup_location', true);
    $start_dt      = get_post_meta($post->ID, '_ryokou_start_datetime', true);
    $end_dt        = get_post_meta($post->ID, '_ryokou_end_datetime', true);
    $total_days    = get_post_meta($post->ID, '_ryokou_total_days', true);
    $total_price   = get_post_meta($post->ID, '_ryokou_total_price', true);
    $rental_notes  = get_post_meta($post->ID, '_ryokou_rental_notes', true);

    // Query published motors for dropdown
    $motors = get_posts(array(
        'post_type'      => 'motor',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
    ?>
    <div class="ryokourent-metabox-wrapper" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <p style="margin:0;">
            <label for="_ryokou_rented_motor_id" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Model Armada yang Disewa:', 'ryokourent'); ?>
            </label>
            <select name="_ryokou_rented_motor_id" id="_ryokou_rented_motor_id" class="widefat">
                <option value=""><?php esc_html_e('-- Pilih Model Motor --', 'ryokourent'); ?></option>
                <?php if (!empty($motors)) : ?>
                    <?php foreach ($motors as $m) : ?>
                        <option value="<?php echo esc_attr($m->ID); ?>" <?php selected($motor_id, $m->ID); ?>>
                            <?php echo esc_html($m->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value="0" selected><?php esc_html_e('Armada Blueprint (Contoh)', 'ryokourent'); ?></option>
                <?php endif; ?>
            </select>
        </p>

        <p style="margin:0;">
            <label for="_ryokou_booking_allocated_plate" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Alokasi Plat Nomor Unit Fisik:', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_booking_allocated_plate" id="_ryokou_booking_allocated_plate" value="<?php echo esc_attr($allocated_plt); ?>" class="widefat" placeholder="Contoh: N 1234 ABC" />
            <span style="font-size:11px; color:#64748b;"><?php esc_html_e('Diisi oleh operator saat konfirmasi / serah terima unit.', 'ryokourent'); ?></span>
        </p>

        <p style="margin:0;">
            <label for="_ryokou_pickup_location" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Lokasi Pengambilan / Penyerahan:', 'ryokourent'); ?>
            </label>
            <select name="_ryokou_pickup_location" id="_ryokou_pickup_location" class="widefat">
                <option value="Pool Dinoyo" <?php selected($pickup_loc, 'Pool Dinoyo'); ?>>Pool Dinoyo (Lowokwaru, Malang)</option>
                <option value="Pool Batu" <?php selected($pickup_loc, 'Pool Batu'); ?>>Pool Batu (Jl. Diponegoro, Kota Batu)</option>
                <option value="Stasiun Malang" <?php selected($pickup_loc, 'Stasiun Malang'); ?>>Diantar ke Stasiun Malang Kota Baru (Sesuai Sikon)</option>
                <option value="Hotel/Homestay" <?php selected($pickup_loc, 'Hotel/Homestay'); ?>>Diantar ke Penginapan / Hotel (Sesuai Sikon)</option>
            </select>
        </p>

        <p style="margin:0;">
            <label for="_ryokou_total_price" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Total Tarif Sewa (Rp):', 'ryokourent'); ?>
            </label>
            <input type="number" name="_ryokou_total_price" id="_ryokou_total_price" value="<?php echo esc_attr($total_price); ?>" class="widefat" step="1000" min="0" placeholder="Contoh: 170000" />
        </p>

        <p style="margin:0;">
            <label for="_ryokou_start_datetime" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Jadwal Mulai Sewa (WIB):', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_start_datetime" id="_ryokou_start_datetime" value="<?php echo esc_attr($start_dt); ?>" class="widefat" placeholder="YYYY-MM-DD HH:MM (07:00 - 23:00 WIB)" />
        </p>

        <p style="margin:0;">
            <label for="_ryokou_end_datetime" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Jadwal Selesai Sewa (WIB):', 'ryokourent'); ?>
            </label>
            <input type="text" name="_ryokou_end_datetime" id="_ryokou_end_datetime" value="<?php echo esc_attr($end_dt); ?>" class="widefat" placeholder="YYYY-MM-DD HH:MM (07:00 - 23:00 WIB)" />
        </p>

        <p style="grid-column: span 2; margin:0;">
            <label for="_ryokou_rental_notes" style="font-weight:600; display:block; margin-bottom:4px;">
                <?php esc_html_e('Catatan Tambahan (Ukuran Helm, Jas Hujan, Rute):', 'ryokourent'); ?>
            </label>
            <textarea name="_ryokou_rental_notes" id="_ryokou_rental_notes" rows="2" class="widefat" placeholder="Butuh 2 helm ukuran L dan jas hujan setelan."><?php echo esc_textarea($rental_notes); ?></textarea>
        </p>
    </div>
    <?php
}

/**
 * Save meta data for CPT 'penyewaan'.
 *
 * @since 1.0.0
 * @param int $post_id Post ID.
 * @return void
 */
function ryokourent_save_booking_meta_data($post_id) {
    // 1. Guard against autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 2. Nonce verification
    if (!isset($_POST['ryokourent_booking_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ryokourent_booking_meta_nonce'])), 'ryokourent_save_booking_meta_action')) {
        return;
    }

    // 3. Post type and capability check
    if (!isset($_POST['post_type']) || 'penyewaan' !== $_POST['post_type']) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // 4. Save Customer fields
    if (isset($_POST['_ryokou_customer_name'])) {
        update_post_meta($post_id, '_ryokou_customer_name', sanitize_text_field(wp_unslash($_POST['_ryokou_customer_name'])));
    }
    if (isset($_POST['_ryokou_customer_ktp_address'])) {
        update_post_meta($post_id, '_ryokou_customer_ktp_address', sanitize_textarea_field(wp_unslash($_POST['_ryokou_customer_ktp_address'])));
    }
    if (isset($_POST['_ryokou_customer_stay_address'])) {
        update_post_meta($post_id, '_ryokou_customer_stay_address', sanitize_textarea_field(wp_unslash($_POST['_ryokou_customer_stay_address'])));
    }
    if (isset($_POST['_ryokou_customer_whatsapp'])) {
        $raw_wa = sanitize_text_field(wp_unslash($_POST['_ryokou_customer_whatsapp']));
        $clean_wa = function_exists('ryokourent_sanitize_phone') ? ryokourent_sanitize_phone($raw_wa) : preg_replace('/[^0-9]/', '', $raw_wa);
        update_post_meta($post_id, '_ryokou_customer_whatsapp', $clean_wa);
    }
    if (isset($_POST['_ryokou_customer_emergency_phone'])) {
        $raw_emg = sanitize_text_field(wp_unslash($_POST['_ryokou_customer_emergency_phone']));
        $clean_emg = function_exists('ryokourent_sanitize_phone') ? ryokourent_sanitize_phone($raw_emg) : preg_replace('/[^0-9]/', '', $raw_emg);
        update_post_meta($post_id, '_ryokou_customer_emergency_phone', $clean_emg);
    }
    if (isset($_POST['_ryokou_customer_social_media'])) {
        update_post_meta($post_id, '_ryokou_customer_social_media', sanitize_text_field(wp_unslash($_POST['_ryokou_customer_social_media'])));
    }

    // 5. Save Booking details
    if (isset($_POST['_ryokou_rented_motor_id'])) {
        update_post_meta($post_id, '_ryokou_rented_motor_id', absint(wp_unslash($_POST['_ryokou_rented_motor_id'])));
    }
    if (isset($_POST['_ryokou_booking_allocated_plate'])) {
        $raw_plate = sanitize_text_field(wp_unslash($_POST['_ryokou_booking_allocated_plate']));
        $clean_plate = strtoupper(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $raw_plate)));
        update_post_meta($post_id, '_ryokou_booking_allocated_plate', $clean_plate);
    }
    if (isset($_POST['_ryokou_pickup_location'])) {
        update_post_meta($post_id, '_ryokou_pickup_location', sanitize_text_field(wp_unslash($_POST['_ryokou_pickup_location'])));
    }
    if (isset($_POST['_ryokou_start_datetime'])) {
        update_post_meta($post_id, '_ryokou_start_datetime', sanitize_text_field(wp_unslash($_POST['_ryokou_start_datetime'])));
    }
    if (isset($_POST['_ryokou_end_datetime'])) {
        update_post_meta($post_id, '_ryokou_end_datetime', sanitize_text_field(wp_unslash($_POST['_ryokou_end_datetime'])));
    }
    if (isset($_POST['_ryokou_total_price'])) {
        update_post_meta($post_id, '_ryokou_total_price', absint(wp_unslash($_POST['_ryokou_total_price'])));
    }
    if (isset($_POST['_ryokou_rental_notes'])) {
        update_post_meta($post_id, '_ryokou_rental_notes', sanitize_textarea_field(wp_unslash($_POST['_ryokou_rental_notes'])));
    }
}
add_action('save_post_penyewaan', 'ryokourent_save_booking_meta_data');

