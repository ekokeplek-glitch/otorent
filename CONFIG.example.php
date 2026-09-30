<?php
/**
 * Contoh Konfigurasi Ryokourent (CONFIG.example.php)
 * 
 * Salin dan sesuaikan konstanta di bawah ini jika ingin mendefinisikan
 * konfigurasi melalui wp-config.php daripada database options.
 * 
 * JANGAN GUNAKAN KREDENSIAL ATAU NOMOR NYATA DI FILE INI.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Nomor WhatsApp Admin Utama (Format: 628xxxxxxxxxx tanpa + atau spasi)
define('RYOKOURENT_DEFAULT_WA_NUMBER', '6281234567890');

// Nomor WhatsApp Cadangan untuk CS Tambahan
define('RYOKOURENT_SECONDARY_WA_NUMBER', '6289876543210');

// Zona Waktu Operasional
define('RYOKOURENT_TIMEZONE', 'Asia/Jakarta');

// Jam Operasional Layanan Pool (Format 24 Jam)
define('RYOKOURENT_OPEN_HOUR', '07:00');
define('RYOKOURENT_CLOSE_HOUR', '23:00');

// Pengaturan Default Booking
define('RYOKOURENT_MIN_RENTAL_HOURS', 24); // Minimal sewa 1 hari (24 jam)
define('RYOKOURENT_MAX_RENTAL_DAYS', 30);  // Maksimal sewa via form web (di atas itu hubungi admin)

// Tarif Dasar Referensi Contoh (IDR)
define('RYOKOURENT_SAMPLE_PRICE_BEAT_DAILY', 85000);
define('RYOKOURENT_SAMPLE_PRICE_VARIO_DAILY', 110000);
define('RYOKOURENT_SAMPLE_PRICE_CRF_DAILY', 225000);
