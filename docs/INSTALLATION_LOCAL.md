# PANDUAN INSTALASI LINGKUNGAN LOKAL (LOCAL DEVELOPMENT)

Dokumen ini memandu pemasangan lingkungan pengembangan lokal untuk proyek website rental motor Ryokourent.

---

## 1. Kebutuhan Sistem Lokal
* **Web Server:** Apache atau Nginx (disarankan menggunakan [LocalWP](https://localwp.com/), Laragon, Docker, atau Valet)
* **PHP:** Versi 8.1 atau 8.2 (ekstensi yang wajib aktif: `curl`, `json`, `mbstring`, `mysqli`, `zip`)
* **Database:** MySQL $\ge 8.0$ atau MariaDB $\ge 10.4$
* **WordPress:** Versi 6.4 atau yang lebih baru
* **Composer** (opsional untuk PHP_CodeSniffer)
* **Node.js:** Versi 20 LTS (opsional untuk asset tooling)

---

## 2. Langkah-Langkah Instalasi

### Langkah 1: Siapkan WordPress Lokal
1. Buat situs baru di LocalWP atau Laragon dengan nama domain lokal, misalnya `http://ryokourent.local`.
2. Pastikan database dan user database telah terhubung di `wp-config.php`.
3. Atur zona waktu di WP-Admin -> Settings -> General ke **UTC+7** (Jakarta).

### Langkah 2: Kloning Repositori
Kloning repositori ini ke dalam direktori root instalasi WordPress Anda:
```bash
git clone https://github.com/oned250/sewaoto.git .
```
Atau jika menghubungkan ke folder WordPress yang sudah ada:
* Letakkan `wp-content/plugins/ryokourent-core/` di `wp-content/plugins/`
* Letakkan `wp-content/themes/generatepress-child/` di `wp-content/themes/`

### Langkah 3: Pasang Tema Induk GeneratePress
1. Buka WP-Admin -> **Appearance (Tampilan)** -> **Themes**.
2. Klik **Add New**, cari tema **GeneratePress**, lalu klik **Install**.
3. *Jangan aktifkan GeneratePress induk langsung*, melainkan aktifkan **GeneratePress Child - Ryokourent**.

### Langkah 4: Aktifkan Plugin Ryokourent Core
1. Buka WP-Admin -> **Plugins** -> **Installed Plugins**.
2. Cari plugin **Ryokourent Core**.
3. Klik **Activate**.

### Langkah 5: Konfigurasi Permalink
1. Buka WP-Admin -> **Settings** -> **Permalinks**.
2. Pilih struktur **Post name** (`/%postname%/`).
3. Klik **Save Changes** agar rewrite rules CPT ter-refresh.

---

## 3. Konfigurasi Debugging Lokal
Tambahkan baris berikut pada file `wp-config.php` lokal Anda untuk memastikan tidak ada notice atau error yang terlewat:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
@ini_set('display_errors', 1);
define('SCRIPT_DEBUG', true);
```
Periksa log error secara berkala pada file `wp-content/debug.log`.
