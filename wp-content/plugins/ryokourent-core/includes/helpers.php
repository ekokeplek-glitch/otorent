<?php
/**
 * Helper Functions for Ryokourent Core
 *
 * Provides utility functions for currency formatting, phone number sanitization,
 * date/time manipulation in Asia/Jakarta (WIB) timezone, rental duration calculations,
 * and data validation.
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
 * Format a numeric amount into Indonesian Rupiah (IDR).
 *
 * @since 1.0.0
 * @param mixed $amount      Numeric value to format.
 * @param bool  $with_prefix Whether to prepend 'Rp ' string. Default true.
 * @return string Formatted currency string (e.g., 'Rp 85.000' or '85.000').
 */
function ryokourent_format_rupiah($amount, $with_prefix = true) {
    if (!is_numeric($amount) || $amount < 0) {
        $amount = 0;
    }

    $formatted = number_format((float) $amount, 0, ',', '.');

    if ($with_prefix) {
        return 'Rp ' . $formatted;
    }

    return $formatted;
}

/**
 * Sanitize and normalize a phone number into WhatsApp international format (628xxxxxxxxxx).
 *
 * Removes non-numeric characters, converts local leading '0' or '+62' or bare '8' to '62'.
 *
 * @since 1.0.0
 * @param string $phone Raw phone string.
 * @return string Clean normalized phone number with '62' prefix.
 */
function ryokourent_sanitize_phone($phone) {
    if (empty($phone)) {
        return '';
    }

    // Strip all non-digit characters.
    $digits = preg_replace('/[^0-9]/', '', (string) $phone);

    if (empty($digits)) {
        return '';
    }

    // Convert leading '0' to '62' (e.g., 08123456789 -> 628123456789).
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
        // Missing country code, e.g., 8123456789 -> 628123456789.
        $digits = '62' . $digits;
    }

    return $digits;
}

/**
 * Validate whether a phone number matches standard Indonesian cellular format.
 *
 * Criteria: Starts with '628', followed by 8 to 12 digits (total 11 to 15 digits).
 *
 * @since 1.0.0
 * @param string $phone Phone number (raw or sanitized).
 * @return bool True if valid Indonesian mobile number, false otherwise.
 */
function ryokourent_is_valid_phone($phone) {
    $sanitized = ryokourent_sanitize_phone($phone);

    // Matches Indonesian mobile numbers: 628 + 8 to 12 digits.
    return (bool) preg_match('/^628[1-9][0-9]{7,11}$/', $sanitized);
}

/**
 * Get operational timezone object (WIB / Asia/Jakarta).
 *
 * @since 1.0.0
 * @return DateTimeZone DateTimeZone instance for Asia/Jakarta.
 */
function ryokourent_get_timezone() {
    static $tz = null;

    if (null === $tz) {
        $tz_string = defined('RYOKOURENT_TIMEZONE') ? RYOKOURENT_TIMEZONE : 'Asia/Jakarta';
        try {
            $tz = new DateTimeZone($tz_string);
        } catch (Exception $e) {
            $tz = new DateTimeZone('Asia/Jakarta');
        }
    }

    return $tz;
}

/**
 * Get current date/time in Asia/Jakarta (WIB) timezone.
 *
 * @since 1.0.0
 * @param string $format PHP date format string. Default 'Y-m-d H:i:s'.
 * @return string Current formatted date and time in WIB.
 */
function ryokourent_get_now_wib($format = 'Y-m-d H:i:s') {
    try {
        $date = new DateTime('now', ryokourent_get_timezone());
        return $date->format($format);
    } catch (Exception $e) {
        return gmdate($format, time() + (7 * 3600)); // Fallback UTC+7
    }
}

/**
 * Format a MySQL datetime string or timestamp into readable Indonesian date.
 *
 * @since 1.0.0
 * @param string|int $datetime Date string (e.g., '2026-10-01 09:00:00') or UNIX timestamp.
 * @param string     $format   Output format. Default 'd M Y H:i WIB'.
 * @return string Formatted localized date string.
 */
function ryokourent_format_datetime_id($datetime, $format = 'd M Y H:i \W\I\B') {
    if (empty($datetime)) {
        return '-';
    }

    try {
        if (is_numeric($datetime)) {
            $date = new DateTime('@' . $datetime);
            $date->setTimezone(ryokourent_get_timezone());
        } else {
            $date = new DateTime((string) $datetime, ryokourent_get_timezone());
        }

        $formatted = $date->format($format);

        // Translate English month names to Indonesian abbreviations.
        $months_en = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $months_id = array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');

        return str_replace($months_en, $months_id, $formatted);
    } catch (Exception $e) {
        return (string) $datetime;
    }
}

/**
 * Calculate the exact duration in hours between two dates in WIB timezone.
 *
 * @since 1.0.0
 * @param string $start_datetime Start datetime string (Y-m-d H:i:s or Y-m-d\TH:i).
 * @param string $end_datetime   End datetime string (Y-m-d H:i:s or Y-m-d\TH:i).
 * @return float Total hours between start and end. Returns 0 on invalid or reversed dates.
 */
function ryokourent_calculate_duration_hours($start_datetime, $end_datetime) {
    if (empty($start_datetime) || empty($end_datetime)) {
        return 0.0;
    }

    try {
        $tz = ryokourent_get_timezone();
        $start = new DateTime((string) $start_datetime, $tz);
        $end   = new DateTime((string) $end_datetime, $tz);

        if ($end <= $start) {
            return 0.0;
        }

        $diff_seconds = $end->getTimestamp() - $start->getTimestamp();
        return round($diff_seconds / 3600, 2);
    } catch (Exception $e) {
        return 0.0;
    }
}

/**
 * Calculate billable rental days based on 24-hour cycle and tolerance grace period.
 *
 * Rules:
 * - 1 day = 24 hours.
 * - Standard tolerance grace period is 2 hours (default).
 * - Minimum billable rental is 1 day.
 * - Example:
 *   - 24 hours -> 1 day
 *   - 26 hours (within 2 hours grace) -> 1 day
 *   - 27 hours -> 2 days
 *
 * @since 1.0.0
 * @param string $start_datetime  Start datetime.
 * @param string $end_datetime    End datetime.
 * @param int    $tolerance_hours Free grace period in hours. Default 2.
 * @return int Total billable days (minimum 1). Returns 0 if invalid input.
 */
function ryokourent_calculate_rental_days($start_datetime, $end_datetime, $tolerance_hours = 2) {
    $hours = ryokourent_calculate_duration_hours($start_datetime, $end_datetime);

    if ($hours <= 0) {
        return 0;
    }

    if ($hours <= (24 + $tolerance_hours)) {
        return 1;
    }

    // Deduct grace period from remainder after 24h intervals.
    $full_days = floor($hours / 24);
    $extra_hours = fmod($hours, 24);

    if ($extra_hours > $tolerance_hours) {
        $billable_days = $full_days + 1;
    } else {
        $billable_days = $full_days;
    }

    return (int) max(1, $billable_days);
}

/**
 * Check if a time string falls within Ryokourent operating hours (07:00 - 23:00 WIB).
 *
 * @since 1.0.0
 * @param string $time_str   Time string (e.g. '08:30' or '2026-10-01 14:00:00').
 * @param string $open_time  Opening time in 'H:i' format. Default '07:00'.
 * @param string $close_time Closing time in 'H:i' format. Default '23:00'.
 * @return bool True if within operating hours, false otherwise.
 */
function ryokourent_is_within_operating_hours($time_str, $open_time = '07:00', $close_time = '23:00') {
    if (empty($time_str)) {
        return false;
    }

    try {
        $tz = ryokourent_get_timezone();
        // Check if string contains full date or only time.
        if (str_contains((string) $time_str, ' ') || str_contains((string) $time_str, 'T')) {
            $dt = new DateTime((string) $time_str, $tz);
            $check_time = $dt->format('H:i');
        } else {
            $check_time = date('H:i', strtotime((string) $time_str));
        }

        $check_mins = (int) substr($check_time, 0, 2) * 60 + (int) substr($check_time, 3, 2);
        $open_mins  = (int) substr($open_time, 0, 2) * 60 + (int) substr($open_time, 3, 2);
        $close_mins = (int) substr($close_time, 0, 2) * 60 + (int) substr($close_time, 3, 2);

        return ($check_mins >= $open_mins && $check_mins <= $close_mins);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Sanitize Indonesian e-KTP / NIK number (16 digits).
 *
 * @since 1.0.0
 * @param string $ktp Raw KTP/NIK string.
 * @return string Digits-only string (max 16 characters).
 */
function ryokourent_sanitize_ktp($ktp) {
    if (empty($ktp)) {
        return '';
    }

    $digits = preg_replace('/[^0-9]/', '', (string) $ktp);
    return substr($digits, 0, 16);
}

/**
 * Validate whether a string is a valid 16-digit Indonesian e-KTP number.
 *
 * @since 1.0.0
 * @param string $ktp KTP/NIK string.
 * @return bool True if exactly 16 digits, false otherwise.
 */
function ryokourent_is_valid_ktp($ktp) {
    $sanitized = ryokourent_sanitize_ktp($ktp);
    return strlen($sanitized) === 16;
}

/**
 * Sanitize motorcycle license plate format (e.g., 'N 1234 ABC').
 *
 * Converts to uppercase, replaces multiple spaces with single space, and trims.
 *
 * @since 1.0.0
 * @param string $plate Raw license plate.
 * @return string Normalized uppercase plate string.
 */
function ryokourent_sanitize_plate_number($plate) {
    if (empty($plate)) {
        return '';
    }

    $cleaned = strtoupper(trim((string) $plate));
    // Replace multiple spaces with a single space.
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    // Remove unwanted special characters, allowing letters, digits, and space.
    $cleaned = preg_replace('/[^A-Z0-9 ]/', '', $cleaned);

    return $cleaned;
}

/**
 * Get all recognized booking status slugs and localized Indonesian labels.
 *
 * @since 1.0.0
 * @return array<string, string> Associative array of slug => readable label.
 */
function ryokourent_get_booking_statuses() {
    return array(
        'status_menunggu'    => __('Menunggu Konfirmasi', 'ryokourent'),
        'status_dikonfirmasi' => __('Dikonfirmasi', 'ryokourent'),
        'status_berjalan'    => __('Sewa Berjalan', 'ryokourent'),
        'status_selesai'     => __('Selesai', 'ryokourent'),
        'status_batal'       => __('Dibatalkan', 'ryokourent'),
    );
}

/**
 * Get the official WhatsApp number for customer bookings.
 *
 * Checks database option `ryokourent_wa_number`, then constant `RYOKOURENT_DEFAULT_WA_NUMBER`,
 * falling back to default dummy number '6281234567890'.
 *
 * @since 1.0.0
 * @return string Clean normalized WhatsApp number.
 */
function ryokourent_get_default_wa_number() {
    $option_number = function_exists('get_option') ? get_option('ryokourent_wa_number', '') : '';

    if (!empty($option_number)) {
        return ryokourent_sanitize_phone($option_number);
    }

    if (defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
        return ryokourent_sanitize_phone(RYOKOURENT_DEFAULT_WA_NUMBER);
    }

    return '6281234567890';
}

/**
 * Get the list of official pickup and dropoff locations in Malang & Batu.
 *
 * @since 1.0.0
 * @return array<string, string> Associative array of location_key => Location Name.
 */
function ryokourent_get_pool_locations() {
    return array(
        'pool_dinoyo'    => __('Pool Malang Dinoyo (Pusat Kota / Kampus)', 'ryokourent'),
        'pool_batu'      => __('Pool Batu Diponegoro (Kota Wisata Batu)', 'ryokourent'),
        'stasiun_malang' => __('Stasiun Malang Kotabaru (Antar-Jemput Gratis)', 'ryokourent'),
        'antar_hotel'    => __('Antar ke Hotel / Homestay (Malang & Batu)', 'ryokourent'),
    );
}
