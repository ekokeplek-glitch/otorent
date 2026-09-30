# PANDUAN INSTALASI DI CLOUD HOSTING / CPANEL

Dokumen ini memandu pemasangan sistem Ryokourent pada server hosting produksi (cPanel, LiteSpeed Web Server, VPS, atau Cloud Panel).

---

## 1. Kebutuhan Server Hosting
* **Web Server:** LiteSpeed Web Server (disarankan) atau Nginx
* **PHP:** Versi 8.1 / 8.2 / 8.3
  * `memory_limit` minimal `256M` (disarankan `512M`)
  * `upload_max_filesize` minimal `64M`
  * `max_execution_time` minimal `120`
* **Database:** MySQL 8.0+ atau MariaDB 10.5+ (Collation: `utf8mb4_unicode_ci`)
* **Sertifikat SSL:** Let's Encrypt / AutoSSL aktif (Wajib HTTPS)
* **Lokasi Server:** Data center Indonesia (Jakarta) atau Singapura untuk latensi terendah (< 50ms) bagi wisatawan di Malang & Batu.

---

## 2. Prosedur Instalasi Bertahap

### A. Persiapan Database & WordPress
1. Masuk ke cPanel / Panel Hosting Anda.
2. Buat database baru (misal: `ryokou_prod`) dan buat user database dengan akses penuh (*All Privileges*).
3. Lakukan instalasi WordPress versi terbaru melalui Softaculous / WP Toolkit atau unggah paket resmi WordPress.
4. Pastikan URL situs menggunakan protokol `https://`.

### B. Upload Tema GeneratePress & Child Theme
1. Pasang tema induk **GeneratePress** resmi via Dashboard WP atau upload zip `generatepress.zip` ke `wp-content/themes/`.
2. Upload folder `generatepress-child` ke direktori `wp-content/themes/generatepress-child/`.
3. Buka WP-Admin -> **Tampilan** -> **Tema**, lalu klik **Aktifkan** pada **GeneratePress Child - Ryokourent**.

### C. Upload & Aktivasi Plugin Ryokourent Core
1. Zip folder `wp-content/plugins/ryokourent-core/`.
2. Buka WP-Admin -> **Plugins** -> **Tambah Baru** -> **Unggah Plugin**, pilih zip tersebut.
3. Klik **Install Sekarang** lalu klik **Aktifkan Plugin**.
4. Verifikasi bahwa menu **Armada Motor** dan **Penyewaan Motor** muncul di bilah navigasi admin.

### D. Konfigurasi Server & Keamanan Hosting
1. Atur rewrite permalink di WP-Admin -> **Pengaturan** -> **Permalink** ke **Nama Tulisan** (`/%postname%/`).
2. Pasang plugin cache server (seperti **LiteSpeed Cache**) dan optimalkan gambar ke format WebP.
3. Pastikan direktori `wp-content/plugins/` dan `wp-content/themes/` memiliki permission folder `755` dan file `644`.
4. Nonaktifkan file editing melalui dashboard WP dengan menambahkan baris ini di `wp-config.php`:
   ```php
   define('DISALLOW_FILE_EDIT', true);
   ```

### E. Konfigurasi WhatsApp Resmi & Zona Waktu
1. Buka WP-Admin -> **Pengaturan** -> **Umum**, pastikan **Zona Waktu** diset ke **Jakarta** (`Asia/Jakarta`).
2. Masuk ke menu **Pengaturan Ryokou** di WP-Admin.
3. Masukkan nomor WhatsApp resmi admin (format internasional: contoh `6281234567890`).
4. Simpan perubahan.
