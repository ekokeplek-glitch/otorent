# ATURAN DAN STANDAR KODING (CODING STANDARDS)

Dokumen ini memuat standar teknis yang wajib dipatuhi oleh seluruh pengembang dalam repositori Ryokourent.

---

## 1. Aturan Pokok Pengembangan
1. **Pemisahan Logika:** Seluruh logika bisnis CPT, metabox, kalkulasi harga, algoritma ketersediaan armada, dan handler WhatsApp **WAJIB** berada di dalam plugin `ryokourent-core`. Jangan menaruh fitur penting di `functions.php` tema.
2. **Konvensi Penamaan (Prefix):** Seluruh fungsi, hook (actions & filters), option names, post meta keys, dan transient **WAJIB** menggunakan prefix:
   * Fungsi & Hooks: `ryokourent_` atau `ryokou_`
   * Post Meta Keys: `_ryokou_` atau `_ryokou_booking_`
   * Database Options: `ryokourent_`
3. **Pencegahan Eksekusi Langsung:** Setiap file PHP wajib diawali pengecekan ABSPATH:
   ```php
   if (!defined('ABSPATH')) {
       exit; // Exit if accessed directly.
   }
   ```
4. **Sanitasi Input (Semua Input Wajib Disanitasi):**
   * Teks biasa: `sanitize_text_field($value)`
   * Textarea/alamat: `sanitize_textarea_field($value)`
   * Nomor telepon: `preg_replace('/[^0-9]/', '', $phone)`
   * Email: `sanitize_email($value)`
   * URL: `esc_url_raw($value)`
   * Integer/ID: `absint($value)` atau `intval($value)`
5. **Output Escaping (Semua Output Wajib Di-escape):**
   * Konten HTML umum: `echo esc_html($variable);`
   * Nilai atribut HTML: `echo esc_attr($variable);`
   * Tautan/URL: `echo esc_url($variable);`
   * Teks dengan tag aman: `echo wp_kses_post($variable);`
6. **Keamanan Aksi Admin & AJAX:**
   * Setiap request form atau AJAX yang mengubah data wajib menyertakan Nonce verification:
     ```php
     check_ajax_referer('ryokourent_booking_nonce', 'security');
     ```
   * Setiap aksi admin wajib memeriksa hak akses pengguna:
     ```php
     if (!current_user_can('manage_ryokourent_bookings')) {
         wp_die(esc_html__('Akses ditolak.', 'ryokourent'));
     }
     ```
7. **Prepared Statement untuk Kueri Database:**
   Jika menggunakan kueri SQL manual (`$wpdb`), **wajib** menggunakan prepared statement:
   ```php
   $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type = %s", 'penyewaan'));
   ```
8. **Kerahasiaan Kredensial:**
   Dilarang keras menyimpan password, secret token, atau kunci API di dalam file kode sumber repository.

---

## 2. Formatting & Gaya Penulisan (WordPress Coding Standards)
* Indentasi menggunakan **4 spasi** untuk PHP dan **2 spasi** untuk JSON/CSS/JS.
* Gunakan spasi di dalam kurung kontrol (`if ( condition ) { ... }`).
* Deklarasi array menggunakan sintaks modern `array()` atau `[]` secara konsisten.
* Penamaan variabel dan fungsi menggunakan snake_case (`ryokourent_calculate_price`).
* Penamaan class menggunakan CamelCase berawalan prefix (`Ryokourent_Booking_Engine`).
