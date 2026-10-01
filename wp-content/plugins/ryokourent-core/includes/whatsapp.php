<?php
/**
 * WhatsApp Official Draft Generator & Deep Link Engine for Ryokourent
 *
 * Implements:
 * - Structured markdown formatting with standardized emoticons.
 * - Complete inclusion of booking details and customer identity fields.
 * - Deep link generation (https://wa.me/{phone}?text={encoded_text}) using rawurlencode().
 * - Authoritative server-side admin phone number resolution.
 * - AJAX endpoint for live preview and link generation.
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
 * Retrieve official admin WhatsApp number configured in WordPress settings.
 *
 * Falls back to default constant RYOKOURENT_DEFAULT_WA_NUMBER if unset.
 *
 * @since 1.0.0
 * @return string Clean normalized international phone number (e.g. '6281234567890').
 */
function ryokourent_get_official_wa_number() {
    $default_wa = defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '6281234567890';
    $raw_wa     = get_option('ryokourent_wa_number', $default_wa);

    if (function_exists('ryokourent_sanitize_phone')) {
        $clean = ryokourent_sanitize_phone($raw_wa);
        if (!empty($clean)) {
            return $clean;
        }
    }

    $digits = preg_replace('/[^0-9]/', '', (string) $raw_wa);
    return !empty($digits) ? $digits : '6281234567890';
}

/**
 * Construct the official structured WhatsApp booking draft message.
 *
 * @since 1.0.0
 * @param array $data Array of booking and customer data fields.
 * @return string Formatted multi-line message string with emoticons and markdown.
 */
function ryokourent_build_whatsapp_message($data) {
    // 1. Motor Name Resolution
    $motor_id   = isset($data['rented_motor_id']) ? absint($data['rented_motor_id']) : 0;
    $motor_name = isset($data['motor_name']) ? sanitize_text_field($data['motor_name']) : '';

    if (empty($motor_name) && $motor_id > 0) {
        $post = get_post($motor_id);
        if ($post) {
            $motor_name = $post->post_title;
        } elseif (function_exists('ryokourent_get_blueprint_default_fleet')) {
            $fleet = ryokourent_get_blueprint_default_fleet();
            $idx = $motor_id - 1;
            if (isset($fleet[$idx])) {
                $motor_name = $fleet[$idx]['title'];
            }
        }
    }
    if (empty($motor_name)) {
        $motor_name = __('Pilihan Motor', 'ryokourent');
    }

    // 2. Schedule Formatting
    $start_raw = isset($data['start_datetime']) ? sanitize_text_field($data['start_datetime']) : '';
    $end_raw   = isset($data['end_datetime']) ? sanitize_text_field($data['end_datetime']) : '';

    $start_display = function_exists('ryokourent_format_datetime_id') && !empty($start_raw)
        ? ryokourent_format_datetime_id($start_raw)
        : (!empty($start_raw) ? str_replace('T', ' ', $start_raw) . ' WIB' : '-');

    $end_display = function_exists('ryokourent_format_datetime_id') && !empty($end_raw)
        ? ryokourent_format_datetime_id($end_raw)
        : (!empty($end_raw) ? str_replace('T', ' ', $end_raw) . ' WIB' : '-');

    // 3. Duration & Price
    $duration_label = isset($data['duration_label']) && !empty($data['duration_label'])
        ? sanitize_text_field($data['duration_label'])
        : (isset($data['total_days']) ? sprintf(__('%d Hari', 'ryokourent'), absint($data['total_days'])) : '-');

    $price_formatted = isset($data['formatted_price']) && !empty($data['formatted_price'])
        ? sanitize_text_field($data['formatted_price'])
        : (isset($data['total_price']) && $data['total_price'] > 0 ? 'Rp ' . number_format($data['total_price'], 0, ',', '.') : __('Konsultasi Admin WA', 'ryokourent'));

    if (!empty($data['requires_consultation'])) {
        $price_formatted = __('Konsultasi Langsung via Admin WA', 'ryokourent');
    }

    // 4. Pickup Location & Destination
    $pickup = isset($data['pickup_location']) && !empty($data['pickup_location'])
        ? sanitize_text_field($data['pickup_location'])
        : 'Pool Dinoyo';

    $dest_raw = isset($data['trip_destination']) ? sanitize_key($data['trip_destination']) : 'malang_batu';
    $dest_label = ($dest_raw === 'bromo')
        ? 'Trip Kaldera Gunung Bromo (Khusus Trail CRF 150L)'
        : 'Wisata Malang & Kota Batu';

    // 5. Customer Identity Data
    $name     = isset($data['customer_name']) ? sanitize_text_field($data['customer_name']) : '-';
    $wa_phone = isset($data['customer_whatsapp']) ? sanitize_text_field($data['customer_whatsapp']) : '-';
    $emg_phone= isset($data['customer_emergency_phone']) ? sanitize_text_field($data['customer_emergency_phone']) : '-';
    $ktp_addr = isset($data['customer_ktp_address']) ? sanitize_text_field($data['customer_ktp_address']) : '-';
    $stay_addr= isset($data['customer_stay_address']) ? sanitize_text_field($data['customer_stay_address']) : '-';
    $socmed   = isset($data['customer_social_media']) && trim($data['customer_social_media']) !== ''
        ? sanitize_text_field($data['customer_social_media'])
        : '-';
    $notes    = isset($data['rental_notes']) && trim($data['rental_notes']) !== ''
        ? sanitize_text_field($data['rental_notes'])
        : '-';

    // 6. Build Message with Emoticons and Markdown
    $lines = array();
    $lines[] = "🛵 *FORMULIR PEMESANAN SEWA MOTOR - RYOKOURENT MALANG & BATU*";
    $lines[] = "────────────────────────────";
    $lines[] = "Halo Admin Ryokourent, saya ingin mengonfirmasi pesanan sewa motor dengan rincian berikut:";
    $lines[] = "";
    $lines[] = "📋 *DETAIL ARMADA & JADWAL SEWA*";
    $lines[] = "• Model Motor: *" . $motor_name . "*";
    $lines[] = "• Waktu Mulai: " . $start_display;
    $lines[] = "• Waktu Selesai: " . $end_display;
    $lines[] = "• Estimasi Durasi: " . $duration_label;
    $lines[] = "• Lokasi Pengambilan: " . $pickup;
    $lines[] = "• Rute Tujuan: " . $dest_label;
    $lines[] = "• Estimasi Biaya Sewa: *" . $price_formatted . "*";
    $lines[] = "";
    $lines[] = "👤 *DATA IDENTITAS PENYEWA*";
    $lines[] = "• Nama Lengkap: *" . $name . "*";
    $lines[] = "• Nomor WhatsApp: " . $wa_phone;
    $lines[] = "• Kontak Darurat (Keluarga): " . $emg_phone;
    $lines[] = "• Alamat Sesuai KTP: " . $ktp_addr;
    $lines[] = "• Tempat Menginap di Malang/Batu: " . $stay_addr;
    $lines[] = "• Akun Media Sosial: " . $socmed;
    $lines[] = "• Catatan Tambahan: " . $notes;
    $lines[] = "";
    $lines[] = "────────────────────────────";
    $lines[] = "🔒 _Data identitas telah diisi sesuai formulir resmi Ryokourent dan dilindungi UU PDP. Mohon informasi ketersediaan unit dan rekening pembayaran jaminan (DP). Terima kasih!_";

    return implode("\n", $lines);
}

/**
 * Generate official https://wa.me/{phone}?text={encoded_text} URL.
 *
 * Uses rawurlencode() to guarantee that spaces, line breaks, emojis,
 * and punctuation are safely encoded per RFC 3986 without truncating.
 *
 * @since 1.0.0
 * @param array  $data        Booking data.
 * @param string $admin_phone Optional admin phone override. Default empty (fetches from server options).
 * @return string Full WhatsApp click-to-chat URL.
 */
function ryokourent_get_whatsapp_url($data, $admin_phone = '') {
    if (empty($admin_phone)) {
        $admin_phone = ryokourent_get_official_wa_number();
    } else {
        if (function_exists('ryokourent_sanitize_phone')) {
            $admin_phone = ryokourent_sanitize_phone($admin_phone);
        } else {
            $admin_phone = preg_replace('/[^0-9]/', '', $admin_phone);
        }
    }

    $message = ryokourent_build_whatsapp_message($data);
    $encoded = rawurlencode($message);

    return 'https://wa.me/' . $admin_phone . '?text=' . $encoded;
}

/**
 * AJAX Handler for Generating WhatsApp Draft & URL.
 *
 * Hook: 'ryokourent_get_whatsapp_draft'
 *
 * @since 1.0.0
 * @return void Sends JSON response and exits.
 */
function ryokourent_ajax_get_whatsapp_draft() {
    $raw_data = isset($_POST) && is_array($_POST) ? wp_unslash($_POST) : array();

    $message = ryokourent_build_whatsapp_message($raw_data);
    $url     = ryokourent_get_whatsapp_url($raw_data);
    $phone   = ryokourent_get_official_wa_number();

    wp_send_json_success(array(
        'message_text' => $message,
        'whatsapp_url' => $url,
        'admin_phone'  => $phone,
    ), 200);
}
add_action('wp_ajax_ryokourent_get_whatsapp_draft', 'ryokourent_ajax_get_whatsapp_draft');
add_action('wp_ajax_nopriv_ryokourent_get_whatsapp_draft', 'ryokourent_ajax_get_whatsapp_draft');
