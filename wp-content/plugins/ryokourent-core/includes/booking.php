<?php
/**
 * Booking Processing, Customer Data Validation & Anti-Spam Engine for Ryokourent
 *
 * Implements server-side customer data validation (Indonesian cellular phone format,
 * emergency contact separation, name sanitization, address checks), anti-spam honeypot
 * detection, and transient-based IP rate-limiting.
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

// -----------------------------------------------------------------------------
// Rate Limiting Constants
// -----------------------------------------------------------------------------
if (!defined('RYOKOURENT_BOOKING_MAX_ATTEMPTS')) {
    define('RYOKOURENT_BOOKING_MAX_ATTEMPTS', 5);
}
if (!defined('RYOKOURENT_BOOKING_RATE_WINDOW')) {
    define('RYOKOURENT_BOOKING_RATE_WINDOW', 600); // 10 minutes in seconds
}

/**
 * Retrieve and sanitize client IP address.
 *
 * Checks HTTP headers with validation against private/reserved ranges if needed.
 *
 * @since 1.0.0
 * @return string Validated IP address string or fallback '127.0.0.1'.
 */
function ryokourent_get_client_ip() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $ip_list   = explode(',', $forwarded);
        $ip        = trim($ip_list[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '127.0.0.1';
}

/**
 * Check if the submission IP has exceeded the allowed rate limit.
 *
 * Uses WordPress Transients API to store attempt counters per IP hash.
 *
 * @since 1.0.0
 * @param string $ip             Optional IP address to check. Defaults to client IP.
 * @param int    $max_attempts   Maximum allowed attempts in the time window.
 * @param int    $decay_seconds  Time window in seconds before counter expires.
 * @return array Array with keys: 'allowed' (bool), 'attempts' (int), 'remaining' (int).
 */
function ryokourent_check_booking_rate_limit($ip = '', $max_attempts = null, $decay_seconds = null) {
    if (empty($ip)) {
        $ip = ryokourent_get_client_ip();
    }

    if (null === $max_attempts) {
        $max_attempts = RYOKOURENT_BOOKING_MAX_ATTEMPTS;
    }

    if (null === $decay_seconds) {
        $decay_seconds = RYOKOURENT_BOOKING_RATE_WINDOW;
    }

    $transient_key = 'ryokou_rate_' . md5($ip);
    $attempts      = (int) get_transient($transient_key);

    if ($attempts >= $max_attempts) {
        return array(
            'allowed'   => false,
            'attempts'  => $attempts,
            'remaining' => 0,
        );
    }

    // Increment attempts counter
    $new_attempts = $attempts + 1;
    set_transient($transient_key, $new_attempts, $decay_seconds);

    return array(
        'allowed'   => true,
        'attempts'  => $new_attempts,
        'remaining' => max(0, $max_attempts - $new_attempts),
    );
}

/**
 * Check if the hidden honeypot anti-spam field was filled out.
 *
 * Legitimate human users will not see or fill this field due to CSS concealment.
 * Automated bots filling every input form field will trigger this check.
 *
 * @since 1.0.0
 * @param array  $data       Input data array (usually $_POST).
 * @param string $field_name Honeypot field name.
 * @return bool True if honeypot was triggered (bot detected), false if clean.
 */
function ryokourent_is_honeypot_triggered($data = array(), $field_name = 'ryokourent_hp') {
    if (empty($data)) {
        $data = $_POST;
    }

    if (isset($data[$field_name]) && '' !== trim((string) $data[$field_name])) {
        return true;
    }

    return false;
}

/**
 * Validate customer identity information.
 *
 * Enforces strict validation rules:
 * - Full name according to e-KTP (minimum 3 characters, valid personal name chars).
 * - Indonesian cellular WhatsApp number (10 to 15 digits, starting with 628 / 08).
 * - Family emergency contact number (valid Indonesian phone).
 * - Emergency contact MUST NOT be identical to customer WhatsApp number.
 * - Origin KTP address and Malang/Batu accommodation stay address non-empty.
 *
 * @since 1.0.0
 * @param array $input Raw or unslashed customer data array.
 * @return array Array with keys: 'is_valid' (bool), 'errors' (array), 'data' (sanitized array).
 */
function ryokourent_validate_customer_data($input = array()) {
    $errors = array();
    $clean  = array();

    // 1. Customer Full Name (Nama Lengkap sesuai e-KTP)
    $raw_name = isset($input['customer_name']) ? trim(wp_unslash($input['customer_name'])) : '';
    if (empty($raw_name)) {
        $errors['customer_name'] = __('Nama lengkap wajib diisi sesuai e-KTP.', 'ryokourent');
    } elseif (mb_strlen($raw_name, 'UTF-8') < 3) {
        $errors['customer_name'] = __('Nama lengkap minimal 3 karakter.', 'ryokourent');
    } elseif (mb_strlen($raw_name, 'UTF-8') > 100) {
        $errors['customer_name'] = __('Nama lengkap maksimal 100 karakter.', 'ryokourent');
    } else {
        // Allow letters, spaces, dots, commas, hyphens, and apostrophes
        if (!preg_match('/^[\p{L}\s\.\'\,\-]+$/u', $raw_name)) {
            $errors['customer_name'] = __('Nama lengkap hanya boleh memuat huruf dan tanda baca nama wajar.', 'ryokourent');
        } else {
            $clean['customer_name'] = sanitize_text_field($raw_name);
        }
    }

    // 2. Customer WhatsApp Phone Number (Nomor WhatsApp Aktif)
    $raw_wa = isset($input['customer_whatsapp']) ? trim(wp_unslash($input['customer_whatsapp'])) : '';
    if (empty($raw_wa)) {
        $errors['customer_whatsapp'] = __('Nomor WhatsApp aktif wajib diisi.', 'ryokourent');
    } else {
        $clean_wa = ryokourent_sanitize_phone($raw_wa);
        if (!ryokourent_is_valid_phone($clean_wa)) {
            $errors['customer_whatsapp'] = __('Nomor WhatsApp harus nomor seluler Indonesia yang valid (format 08... atau 628..., 10-15 digit).', 'ryokourent');
        } else {
            $clean['customer_whatsapp'] = $clean_wa;
        }
    }

    // 3. Emergency Contact Phone Number (Kontak Darurat Keluarga)
    $raw_emg = isset($input['customer_emergency_phone']) ? trim(wp_unslash($input['customer_emergency_phone'])) : '';
    if (empty($raw_emg)) {
        $errors['customer_emergency_phone'] = __('Nomor kontak darurat keluarga wajib diisi.', 'ryokourent');
    } else {
        $clean_emg = ryokourent_sanitize_phone($raw_emg);
        if (!ryokourent_is_valid_phone($clean_emg)) {
            $errors['customer_emergency_phone'] = __('Nomor kontak darurat harus nomor seluler Indonesia yang valid (format 08... atau 628..., 10-15 digit).', 'ryokourent');
        } elseif (!empty($clean['customer_whatsapp']) && $clean_emg === $clean['customer_whatsapp']) {
            // Critical Rule: Emergency contact must NOT be identical to the customer's phone
            $errors['customer_emergency_phone'] = __('Nomor kontak darurat keluarga tidak boleh sama dengan nomor WhatsApp Anda.', 'ryokourent');
        } else {
            $clean['customer_emergency_phone'] = $clean_emg;
        }
    }

    // 4. Origin KTP Address (Alamat Sesuai KTP)
    $raw_ktp_addr = isset($input['customer_ktp_address']) ? trim(wp_unslash($input['customer_ktp_address'])) : '';
    if (empty($raw_ktp_addr)) {
        $errors['customer_ktp_address'] = __('Alamat domisili asal sesuai e-KTP wajib diisi.', 'ryokourent');
    } elseif (mb_strlen($raw_ktp_addr, 'UTF-8') < 5) {
        $errors['customer_ktp_address'] = __('Alamat KTP minimal 5 karakter.', 'ryokourent');
    } else {
        $clean['customer_ktp_address'] = sanitize_textarea_field($raw_ktp_addr);
    }

    // 5. Accommodation Address in Malang / Batu (Tempat Menginap)
    $raw_stay_addr = isset($input['customer_stay_address']) ? trim(wp_unslash($input['customer_stay_address'])) : '';
    if (empty($raw_stay_addr)) {
        $errors['customer_stay_address'] = __('Tempat menginap di Malang atau Kota Batu wajib diisi (Hotel/Homestay/Kost).', 'ryokourent');
    } elseif (mb_strlen($raw_stay_addr, 'UTF-8') < 3) {
        $errors['customer_stay_address'] = __('Tempat menginap minimal 3 karakter.', 'ryokourent');
    } else {
        $clean['customer_stay_address'] = sanitize_textarea_field($raw_stay_addr);
    }

    // 6. Social Media ID (Instagram / Facebook - Optional)
    $raw_social = isset($input['customer_social_media']) ? trim(wp_unslash($input['customer_social_media'])) : '';
    $clean['customer_social_media'] = !empty($raw_social) ? sanitize_text_field($raw_social) : '';

    return array(
        'is_valid' => empty($errors),
        'errors'   => $errors,
        'data'     => $clean,
    );
}

/**
 * Validate complete booking form submission.
 *
 * Verifies:
 * - CSRF Nonce token
 * - Anti-spam honeypot
 * - Rate limiting
 * - Customer identity fields
 * - Rental fleet selection & location
 *
 * @since 1.0.0
 * @param array $raw_data Array of input data ($_POST).
 * @return array Array with keys: 'success' (bool), 'code' (string), 'errors' (array), 'clean_data' (array).
 */
function ryokourent_validate_booking_submission($raw_data = array()) {
    if (empty($raw_data)) {
        $raw_data = $_POST;
    }

    // 1. Anti-spam Honeypot Check
    if (ryokourent_is_honeypot_triggered($raw_data)) {
        return array(
            'success'    => false,
            'code'       => 'spam_bot_detected',
            'message'    => __('Permintaan ditolak: Aktivitas bot/spam terdeteksi.', 'ryokourent'),
            'errors'     => array('general' => __('Aktivitas bot terdeteksi.', 'ryokourent')),
            'status'     => 400,
            'clean_data' => array(),
        );
    }

    // 2. Nonce Verification Check
    $nonce = isset($raw_data['ryokourent_booking_nonce']) ? sanitize_text_field(wp_unslash($raw_data['ryokourent_booking_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ryokourent_booking_form_action')) {
        return array(
            'success'    => false,
            'code'       => 'invalid_nonce',
            'message'    => __('Sesi keamanan formulir telah kedaluwarsa. Silakan muat ulang halaman.', 'ryokourent'),
            'errors'     => array('general' => __('Token keamanan tidak valid.', 'ryokourent')),
            'status'     => 403,
            'clean_data' => array(),
        );
    }

    // 3. Transient-based Rate Limiting Check
    $rate_status = ryokourent_check_booking_rate_limit();
    if (!$rate_status['allowed']) {
        return array(
            'success'    => false,
            'code'       => 'rate_limit_exceeded',
            'message'    => __('Terlalu banyak permintaan pemesanan dalam waktu singkat. Mohon tunggu beberapa menit sebelum mencoba kembali.', 'ryokourent'),
            'errors'     => array('general' => __('Batas laju pemesanan tercapai. Mohon tunggu sejenak.', 'ryokourent')),
            'status'     => 429,
            'clean_data' => array(),
        );
    }

    // 4. Validate Customer Identity Data
    $customer_result = ryokourent_validate_customer_data($raw_data);
    if (!$customer_result['is_valid']) {
        return array(
            'success'    => false,
            'code'       => 'validation_error',
            'message'    => __('Terdapat kesalahan pada data identitas pelanggan. Mohon periksa kembali isian Anda.', 'ryokourent'),
            'errors'     => $customer_result['errors'],
            'status'     => 400,
            'clean_data' => $customer_result['data'],
        );
    }

    $clean = $customer_result['data'];

    // 5. Validate Motor Selection
    $motor_id = isset($raw_data['rented_motor_id']) ? absint(wp_unslash($raw_data['rented_motor_id'])) : 0;
    if ($motor_id <= 0) {
        return array(
            'success'    => false,
            'code'       => 'missing_motor_selection',
            'message'    => __('Silakan pilih model armada motor yang ingin disewa.', 'ryokourent'),
            'errors'     => array('rented_motor_id' => __('Pilihan armada motor wajib dipilih.', 'ryokourent')),
            'status'     => 400,
            'clean_data' => $clean,
        );
    }
    $clean['rented_motor_id'] = $motor_id;

    // 6. Validate Pickup Location
    $pickup = isset($raw_data['pickup_location']) ? sanitize_text_field(wp_unslash($raw_data['pickup_location'])) : '';
    $allowed_pickups = array('Pool Dinoyo', 'Pool Batu', 'Stasiun Malang', 'Hotel/Homestay');
    if (empty($pickup) || !in_array($pickup, $allowed_pickups, true)) {
        $pickup = 'Pool Dinoyo';
    }
    $clean['pickup_location'] = $pickup;

    // 7. Destination Route
    $route = isset($raw_data['trip_destination']) ? sanitize_key(wp_unslash($raw_data['trip_destination'])) : 'malang_batu';
    if ($route !== 'bromo') {
        $route = 'malang_batu';
    }
    $clean['trip_destination'] = $route;

    // 8. Rental Notes (Optional)
    $notes = isset($raw_data['rental_notes']) ? sanitize_text_field(wp_unslash($raw_data['rental_notes'])) : '';
    $clean['rental_notes'] = $notes;

    // 9. Datetime Schedule (Raw pass-through for TASK-013 & TASK-015 validation)
    $clean['start_datetime'] = isset($raw_data['start_datetime']) ? sanitize_text_field(wp_unslash($raw_data['start_datetime'])) : '';
    $clean['end_datetime']   = isset($raw_data['end_datetime']) ? sanitize_text_field(wp_unslash($raw_data['end_datetime'])) : '';

    return array(
        'success'    => true,
        'code'       => 'validation_passed',
        'message'    => __('Data identitas pelanggan dan formulir valid.', 'ryokourent'),
        'errors'     => array(),
        'status'     => 200,
        'clean_data' => $clean,
    );
}

/**
 * AJAX Handler for Booking Form Submission (Public & Logged-in).
 *
 * Processes customer validation, honeypot anti-spam, and rate-limiting.
 *
 * @since 1.0.0
 * @return void Sends JSON response and exits.
 */
function ryokourent_ajax_submit_booking() {
    $validation = ryokourent_validate_booking_submission($_POST);

    if (!$validation['success']) {
        wp_send_json_error(array(
            'code'    => $validation['code'],
            'message' => $validation['message'],
            'errors'  => $validation['errors'],
        ), $validation['status']);
    }

    // Success response with validated data
    wp_send_json_success(array(
        'code'       => 'validation_success',
        'message'    => $validation['message'],
        'clean_data' => $validation['clean_data'],
    ), 200);
}
add_action('wp_ajax_ryokourent_submit_booking', 'ryokourent_ajax_submit_booking');
add_action('wp_ajax_nopriv_ryokourent_submit_booking', 'ryokourent_ajax_submit_booking');
