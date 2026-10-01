# DAFTAR TASK IMPLEMENTASI (TASKS.md)

Dokumen ini berisi rincian urutan 30 task proyek Ryokourent sesuai dengan arsitektur FASE 0 hingga FASE 4.

---

### TASK-001: Analisis Blueprint dan Dokumentasi Proyek
* **Tujuan:** Memahami seluruh kebutuhan, model data, alur bisnis, aturan ketat (Bromo CRF), dan menghasilkan dokumen arsitektur awal.
* **File yang Dibuat/Diubah:** `BLUEPRINT.md`, `PROJECT_OVERVIEW.md`, `ARCHITECTURE.md`, `DATA_MODEL.md`, `TASKS.md`, `AI_WORKFLOW.md`, `DECISIONS.md`, `TESTING.md`, `.env.example`.
* **Dependensi:** Tidak ada.
* **Kriteria Selesai:** Seluruh dokumen perencanaan (FASE 0) selesai dibuat, valid, dan disetujui.
* **Cara Pengujian:** Review dokumen checklist perencanaan dan verifikasi kelengkapan FASE 0.
* **Risiko:** Perubahan spek bisnis di tengah jalan jika ada asumsi yang tidak disetujui stakeholder.

---

### TASK-002: Buat Struktur Repository dan Plugin Kosong
* **Tujuan:** Menyiapkan struktur folder standar plugin `ryokourent-core` dan child theme `generatepress-child`, file `.gitignore`, `.editorconfig`, `.phpcs.xml.dist`.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/ryokourent-core.php`
  * `wp-content/plugins/ryokourent-core/readme.txt`
  * `wp-content/themes/generatepress-child/style.css`
  * `wp-content/themes/generatepress-child/functions.php`
  * `.gitignore`, `.editorconfig`, `.phpcs.xml.dist`
* **Dependensi:** TASK-001 disetujui.
* **Kriteria Selesai:** Plugin dan theme terdeteksi di WordPress tanpa menimbulkan error saat diaktifkan.
* **Cara Pengujian:** Aktifkan theme dan plugin di WP-Admin; pastikan tidak ada PHP Fatal Error atau Warning.
* **Risiko:** Konflik path direktori jika struktur tidak konsisten.

---

### TASK-003: Buat Plugin Loader dan Helper Dasar
* **Tujuan:** Membangun bootstrap loader utama pada plugin, konstanta plugin, sanitasi helper, formatting mata uang Rupiah, dan helper waktu zona WIB.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/ryokourent-core.php`
  * `wp-content/plugins/ryokourent-core/includes/helpers.php`
* **Dependensi:** TASK-002.
* **Kriteria Selesai:** Fungsi helper `ryokourent_format_rupiah()`, `ryokourent_sanitize_phone()`, `ryokourent_get_now_wib()` dapat dipanggil dan lolos testing fungsi dasar.
* **Cara Pengujian:** Panggil helper dengan berbagai input data; pastikan formatting dan sanitasi bekerja sesuai aturan.
* **Risiko:** Masalah timezone server non-WIB (UTC).

---

### TASK-004: Buat Custom Post Type `motor` [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Mendaftarkan CPT `motor` untuk mengelola data katalog armada dengan dukungan judul, editor deskripsi, gambar thumbnail, excerpt, REST API Gutenberg, skema meta fields (`_ryokou_*`), sanitasi input, escaping output, capability check, serta kustomisasi kolom admin list table.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/post-types.php`
  * `wp-content/plugins/ryokourent-core/includes/meta-fields.php`
  * `wp-content/plugins/ryokourent-core/admin/motor-columns.php`
  * `wp-content/plugins/ryokourent-core/tests/test-cpt-motor.php`
* **Dependensi:** TASK-003.
* **Kriteria Selesai:** CPT `motor` terdaftar dengan slug `motor`, menu "Armada Motor" di sidebar WP-Admin dengan ikon `dashicons-car`, skema 10 meta fields terdaftar aman dengan sanitasi, kolom admin menampilkan foto, spesifikasi, harga harian, stok, rute bromo, dan status badge.
* **Cara Pengujian:**
  1. Buka dashboard WP-Admin -> Menu sidebar "Armada Motor".
  2. Klik "Tambah Motor Baru", masukkan judul armada (misal "Honda BeAT Deluxe"), deskripsi rute, dan foto unggulan.
  3. Verifikasi daftar armada menampilkan kolom: Foto, Model Motor, Spesifikasi Mesin, Tarif Harian, Unit Fisik, Rute Bromo, Status Publik, dan Tanggal.
  4. Uji pengurutan kolom berdasarkan Tarif Harian dan Unit Fisik.
* **Risiko:** Konflik slug rewrite permalink jika belum melakukan flush rewrite rules pada WP-Admin -> Settings -> Permalinks.

---

### TASK-005: Buat Field Data Motor (Metabox Spesifikasi & Kuota) [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Menambahkan meta box kustom untuk menyimpan kapasitas mesin (cc), transmisi, karakter rute, penanda khusus Bromo, tarif harian/mingguan/bulanan, dan kuota unit fisik serta daftar plat nomor.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/meta-boxes.php`
  * `wp-content/plugins/ryokourent-core/tests/test-meta-boxes.php`
* **Dependensi:** TASK-004.
* **Kriteria Selesai:** Tiga panel meta box muncul rapi di halaman edit CPT `motor` (Spesifikasi & Karakter, Tarif Sewa, Inventaris Unit Fisik). Dilengkapi guard `DOING_AUTOSAVE`, verifikasi nonce `ryokourent_motor_meta_nonce`, pemeriksaan hak akses `current_user_can('edit_post', $post_id)` serta pengecekan `manage_ryokourent_settings` untuk pengubahan tarif & kuota fisik. Sanitasi plat nomor per baris secara ketat dan normalisasi kapital.
* **Cara Pengujian:**
  1. Buka dashboard WP-Admin -> Armada Motor -> Tambah Motor Baru (atau Edit motor yang ada).
  2. Isi field: Kapasitas Mesin (`110`), Transmisi (`Otomatis (CVT)`), Karakter Rute (`Lincah & Sangat Irit`), Status Publik (`Tersedia`).
  3. Masukkan Tarif Harian (`85000`), Mingguan (`500000`), Bulanan (`1600000`).
  4. Masukkan Total Unit Fisik (`5`) dan daftar plat nomor (misal `N 1234 ABC` dan `N 5678 DEF` satu per baris).
  5. Klik "Terbitkan" atau "Perbarui"; muat ulang halaman dan pastikan seluruh nilai tersimpan persisten.
  6. Login sebagai operator non-admin; pastikan field tarif dan kuota fisik berstatus *disabled* dan tidak dapat diubah.
* **Risiko:** Kesalahan sanitasi field array plat nomor jika ada karakter ilegal (teratasi dengan normalisasi preg_replace huruf besar, angka, dan spasi tunggal).

---

### TASK-006: Buat Taxonomy Kategori Motor [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Mendaftarkan taxonomy hierarkis `kategori_motor` (BeAT Series, Scoopy & Vario, Trail Adventure).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/taxonomies.php`
  * `wp-content/plugins/ryokourent-core/tests/test-taxonomies.php`
* **Dependensi:** TASK-004.
* **Kriteria Selesai:** Kategori Motor dapat dikelola dari submenu CPT `motor` dan dikaitkan ke masing-masing unit armada. Dilengkapi dengan seeding idempoten untuk 3 kategori default (`beat-series`, `scoopy-vario`, `trail-adventure`), proteksi kapabilitas `manage_ryokourent_settings` untuk pengeditan dan `edit_posts` untuk penetapan term ke armada, serta helper `ryokourent_get_motor_categories()`.
* **Cara Pengujian:** Verifikasi pendaftaran taxonomy `kategori_motor`, seeding 3 term utama, dan verifikasi hirarki via test suite `test-taxonomies.php`.
* **Risiko:** Duplikasi nama kategori atau slug URL bertabrakan (teratasi dengan pengecekan `term_exists` idempoten).

---

### TASK-007: Buat Tampilan Katalog Motor [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Membuat shortcode `[ryokou_catalog]` dan template grid katalog motor mobile-first dengan filter tab kategori, spesifikasi, dan tombol CTA "Sewa Sekarang" serta "Chat WA".
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/public/shortcodes.php`
  * `wp-content/plugins/ryokourent-core/public/templates.php`
  * `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js`
  * `wp-content/plugins/ryokourent-core/tests/test-catalog.php`
* **Dependensi:** TASK-005, TASK-006.
* **Kriteria Selesai:** Katalog menampilkan 7 armada sesuai blueprint (BeAT Deluxe, BeAT CBS, BeAT Street, Scoopy, Vario 125, Vario 160, Trail CRF 150L) dengan tab filter responsif tanpa reload halaman, floating badges status ketersediaan, penanda khusus Bromo, rincian harga harian/mingguan/bulanan, fasilitas termasuk (2 Helm SNI + 2 Jas Hujan), dan tombol aksi "Sewa Sekarang" & "Chat WA".
* **Cara Pengujian:** Jalankan unit test `test-catalog.php`, verifikasi format harga, fallback armada, shortcode attributes, dan interaktivitas filter tab.
* **Risiko:** Gambar motor lambat dimuat jika ukuran tidak dioptimasi (teratasi dengan placeholder modern, responsive image attributes, dan lazy loading).

---

### TASK-008: Buat Halaman Detail Motor [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Membuat template single post (`single-motor.php`) pada child theme yang menampilkan detail mendalam motor, keunggulan rute, peringatan rute Bromo, kelengkapan helm/jas hujan, dan form booking cepat.
* **File yang Dibuat/Diubah:**
  * `wp-content/themes/generatepress-child/templates/single-motor.php`
  * `wp-content/themes/generatepress-child/single-motor.php`
  * `wp-content/themes/generatepress-child/functions.php`
  * `wp-content/themes/generatepress-child/style.css`
  * `wp-content/plugins/ryokourent-core/tests/test-single-motor.php`
* **Dependensi:** TASK-007.
* **Kriteria Selesai:** Akses single post motor menampilkan layout elegan dengan informasi spesifikasi lengkap (cc mesin, transmisi, karakter rute), peringatan khusus Bromo (larangan matik ke pasir Bromo vs unit Trail CRF 150L resmi Bromo), fasilitas helm/jas hujan/holder HP, sidebar card tarif resmi dengan toleransi overtime 2 jam, syarat sewa cepat, dan direct CTA booking. Filter `single_template` di `functions.php` memastikan resolusi template selalu sukses.
* **Cara Pengujian:** Jalankan unit test `test-single-motor.php`, verifikasi template di root & folder templates, deteksi post meta `_ryokou_is_bromo_ready`, dan link WhatsApp.
* **Risiko:** Override template hierarchy GeneratePress tidak terbaca (teratasi dengan menyediakan template di root child theme serta filter `single_template`).

---

### TASK-009: Buat Custom Post Type `penyewaan` [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Mendaftarkan CPT `penyewaan` (internal admin) untuk menampung riwayat pesanan booking dari website dengan kapabilitas terproteksi.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/post-types.php`
  * `wp-content/plugins/ryokourent-core/includes/meta-boxes.php`
  * `wp-content/plugins/ryokourent-core/tests/test-cpt-penyewaan.php`
* **Dependensi:** TASK-004.
* **Kriteria Selesai:** Menu "Penyewaan Motor" muncul di sidebar dengan ikon kalender, `public => false`, `publicly_queryable => false`, `exclude_from_search => true`, `show_in_rest => false` (mencegah kebocoran PII via REST API publik). Hak akses dipetakan ke custom capability `manage_ryokourent_bookings` sehingga Author, Editor, dan Contributor biasa tidak dapat mengintip PII penyewa (KTP, nomor telepon, alamat).
* **Cara Pengujian:** Jalankan unit test `test-cpt-penyewaan.php` untuk memverifikasi pendaftaran CPT, parameter privasi PII, dan kapabilitas RBAC.
* **Risiko:** Data pelanggan terekspos ke feed RSS atau REST API publik jika parameter `public` salah diset (teratasi dengan `public => false` dan `show_in_rest => false`).

---

### TASK-010: Buat Status Booking Kustom [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Mendaftarkan post status kustom: `status_menunggu`, `status_dikonfirmasi`, `status_berjalan`, `status_selesai`, `status_dibatalkan` dengan parameter aman (`public => false`).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/post-types.php`
  * `wp-content/plugins/ryokourent-core/includes/meta-boxes.php`
  * `wp-content/plugins/ryokourent-core/tests/test-cpt-penyewaan.php`
* **Dependensi:** TASK-009.
* **Kriteria Selesai:** Dropdown status pada metabox CPT `penyewaan` memuat seluruh status kustom dengan label warna yang jelas. Pengubahan status ditangani via filter `wp_insert_post_data` agar status kustom tidak ter-reset ke status default saat diedit dari WP-Admin. Seluruh slug status $\le 20$ karakter.
* **Cara Pengujian:** Jalankan unit test `test-cpt-penyewaan.php`, verifikasi kelima status kustom terdaftar, slug length, dan filter `wp_insert_post_data` mempertahankan status pilihan.
* **Risiko:** Status kustom tidak muncul pada filter tabel default WordPress jika parameter `show_in_admin_all_list` tidak diset (teratasi dengan `show_in_admin_all_list => true`).

---

### TASK-011: Buat Form Booking Dasar [DONE]
* **Status:** Selesai (DONE) - 2026-09-30
* **Tujuan:** Membangun formulir booking HTML5 yang bersih dan terstruktur mencakup seluruh field identitas, pilihan rute (Malang/Batu vs Trip Bromo dengan kuncian unit CRF 150L), proteksi honeypot (`ryokourent_hp`), dan tombol submit WhatsApp.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/public/forms.php`
  * `wp-content/plugins/ryokourent-core/public/shortcodes.php`
  * `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js`
  * `wp-content/plugins/ryokourent-core/tests/test-booking-form.php`
* **Dependensi:** TASK-005.
* **Kriteria Selesai:** Shortcode `[ryokou_booking_form]` merender formulir booking lengkap dan responsif di smartphone. Field honeypot tersembunyi dari pengguna biasa. Pilihan rute Bromo otomatis mengunci dropdown motor hanya pada CRF 150L. Terdapat kartu kalkulasi estimasi durasi dan tarif sewa secara real-time.
* **Cara Pengujian:** Jalankan unit test `test-booking-form.php`, uji toggle rute Bromo dan verifikasi penguncian model motor serta field identitas pelanggan.
* **Risiko:** Input form terlalu panjang untuk pengguna smartphone jika tidak ditata rapi (teratasi dengan pengelompokan 3 langkah terstruktur).

---

### TASK-012: Buat Validasi Data Pelanggan & Anti-Spam [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Memvalidasi nama pelanggan, nomor WhatsApp (format Indonesia `08...`), nomor kontak darurat keluarga (berbeda dari kontak utama), honeypot anti-spam, dan pembatasan laju pengiriman (rate-limiting via transient per IP).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`
  * `wp-content/plugins/ryokourent-core/public/shortcodes.php`
  * `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`
  * `wp-content/plugins/ryokourent-core/tests/test-booking-validation.php`
* **Dependensi:** TASK-011.
* **Kriteria Selesai:** Form menolak nomor HP tidak valid (kurang dari 10 digit atau bukan format seluler Indonesia). Nomor kontak darurat tidak boleh sama dengan nomor WhatsApp penyewa. Bot yang mengisi field honeypot langsung ditolak dengan status HTTP 400. Transient rate limiting membatasi frekuensi submit per IP.
* **Cara Pengujian:** Jalankan unit test `test-booking-validation.php`, uji submission honeypot terisi, nomor WhatsApp format asing/pendek, kontak darurat duplikat, dan rate limit.
* **Risiko:** Validasi nomor HP terlalu ketat hingga menolak nomor dengan spasi atau tanda hubung (teratasi dengan normalisasi `ryokourent_sanitize_phone()`).

---

### TASK-013: Buat Kalkulasi Durasi Sewa [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Menghitung selisih waktu sewa secara real-time berdasarkan tanggal & jam mulai serta tanggal & jam selesai di zona waktu `Asia/Jakarta` (WIB).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`
  * `wp-content/plugins/ryokourent-core/tests/test-duration-calculation.php`
* **Dependensi:** TASK-011.
* **Kriteria Selesai:** UI menampilkan indikator "Durasi: X Hari (~Y Jam)" secara instan saat pengguna mengubah tanggal/jam. Jam wajib berada pada rentang operasional (07:00 – 23:00 WIB). Toleransi keterlambatan sewa (overtime) s/d 2 jam terhitung tepat. Waktu selesai sewa divalidasi harus lebih akhir dari waktu mulai.
* **Cara Pengujian:** Jalankan unit test `test-duration-calculation.php` untuk memverifikasi skenario mulai 02/10/2026 08:30 dan selesai 04/10/2026 17:00 menghasilkan 3 Hari (~56.5 Jam), batas overtime 2 jam, jam operasional 07:00-23:00 WIB, dan penolakan tanggal mundur.
* **Risiko:** Kesalahan perhitungan karena perbedaan zona waktu browser penyewa (teratasi dengan selalu memaksa zona WIB `Asia/Jakarta` di server dan normalisasi Date object).

---

### TASK-014: Buat Kalkulasi Harga Harian, Mingguan, dan Bulanan [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Membangun modul `pricing.php` untuk menghitung tarif sewa otomatis di sisi server (paket harian 24 jam dengan toleransi overtime 2 jam, paket mingguan 7 hari, bulanan 30 hari).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/pricing.php`
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
  * `wp-content/plugins/ryokourent-core/tests/test-pricing-calculation.php`
* **Dependensi:** TASK-013.
* **Kriteria Selesai:** Server menghitung total tarif berdasarkan kombinasi termurah (best-rate guarantee). Client hanya mengirim tanggal/jam dan motor ID; server tidak mempercayai data kiriman harga dari client. Harga placeholder/kosong ditolak dari booking instan dan diarahkan ke konsultasi WA.
* **Cara Pengujian:** Jalankan unit test `test-pricing-calculation.php` untuk kalkulasi sewa 1 hari, 3 hari, 7 hari, 35 hari, optimasi 6 hari, dan penanganan motor tanpa harga pasti.
* **Risiko:** Manipulasi harga di browser DevTools (teratasi karena server menghitung ulang secara independen dan mutlak).

---

### TASK-015: Buat Validasi Tanggal dan Jam (Operational Hours) [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Membatasi pilihan jam sewa hanya pada jam operasional pool (07.00 – 23.00 WIB) dan mencegah pemilihan tanggal selesai sebelum tanggal mulai atau tanggal di masa lalu.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`
  * `wp-content/plugins/ryokourent-core/public/forms.php`
  * `wp-content/plugins/ryokourent-core/tests/test-operating-hours.php`
* **Dependensi:** TASK-013.
* **Kriteria Selesai:** Input jam di luar 07.00 - 23.00 WIB ditolak dengan pemberitahuan jam operasional resmi pool. Waktu mulai di masa lalu diblokir. Tanggal selesai sebelum tanggal mulai diblokir. Format tanggal ISO didukung penuh lintas platform.
* **Cara Pengujian:** Jalankan unit test `test-operating-hours.php` untuk memvalidasi request jam 02:00 WIB, request tanggal masa lalu, dan request tanggal selesai < tanggal mulai yang semuanya sukses diblokir server.
* **Risiko:** Format tanggal berbeda antara browser Android dan iOS (teratasi dengan normalisasi ISO string standar `Y-m-d\TH:i` dan DateTime parser WIB).

---

### TASK-016: Buat Validasi Ketersediaan Unit & Perlindungan Privasi Stok [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Membangun mesin kueri `availability.php` untuk memeriksa sisa kuota unit fisik model motor pada rentang tanggal yang diminta tanpa membocorkan data kuota ke publik.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/availability.php`
  * `wp-content/plugins/ryokourent-core/tests/test-availability.php`
* **Dependensi:** TASK-005, TASK-010.
* **Kriteria Selesai:** Fungsi `ryokourent_check_availability($motor_id, $start, $end)` mengembalikan status `true`/`false`. Endpoint AJAX publik hanya mengembalikan boolean ketersediaan; kuota fisik internal tidak pernah diekspos ke publik.
* **Cara Pengujian:** Jalankan unit test `test-availability.php` untuk mensimulasikan 3 booking aktif pada motor dengan stok 3 dan memastikan pengecekan berikutnya menghasilkan status `available: false`, serta memvalidasi ketiadaan kebocoran angka stok fisik ke publik.
* **Risiko:** Query lambat jika jumlah data booking besar (teratasi dengan kueri efisien `fields => 'ids'` dan index post meta).

---

### TASK-017: Buat Pencegahan Double Booking Atomik (Dua Titik Kritis) [DONE]
* **Status:** Selesai (DONE) - 2026-10-01
* **Tujuan:** Menerapkan penguncian logika pada dua titik: (1) saat submit pesanan awal di web, dan (2) saat operator mengubah status menjadi `status_dikonfirmasi`.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/availability.php`
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
  * `wp-content/plugins/ryokourent-core/tests/test-atomic-lock.php`
* **Dependensi:** TASK-016.
* **Kriteria Selesai:** Dilengkapi fungsi `ryokourent_with_motor_lock($motor_id, $callback)` berbasis `GET_LOCK` MySQL untuk eksekusi atomik. Operator diblokir mengonfirmasi pesanan jika pada titik konfirmasi kuota sudah penuh terisi booking lain. Pelepasan lock terjamin pada blok `finally`.
* **Cara Pengujian:** Jalankan unit test `test-atomic-lock.php` untuk menguji dua request bersamaan pada unit dengan sisa kuota 1 (hanya satu yang lolos) serta memverifikasi pemblokiran konfirmasi operator saat kuota armada habis.
* **Risiko:** Deadlock jika lock tidak dilepas (teratasi dengan blok `finally { RELEASE_LOCK }` yang selalu dieksekusi).

---

### TASK-018: Buat Generator Pesan WhatsApp Resmi
* **Tujuan:** Menyusun draf pesan WhatsApp resmi yang rapi, ber-emotikon terstruktur, dan menghasilkan tautan resmi `https://wa.me/{nomor}?text={encoded_text}` dengan `rawurlencode()`.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/whatsapp.php`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`
* **Dependensi:** TASK-011, TASK-014.
* **Kriteria Selesai:** Live preview pesan WhatsApp di form terisi dinamis dan tombol mengarahkan ke WhatsApp dengan pesan siap kirim. Nomor tujuan diambil dari pengaturan server (bukan dari client).
* **Cara Pengujian:** Isi formulir secara lengkap, klik tombol, cek teks yang muncul di aplikasi WhatsApp Web/Mobile.
* **Risiko:** Teks terpotong jika karakter khusus tidak di-encode dengan `rawurlencode()`.

---

### TASK-019: Buat Penyimpanan Booking (AJAX & Nonce Handler Kompatibel Cache)
* **Tujuan:** Menyimpan data formulir ke CPT `penyewaan` dengan status `status_menunggu` dan kode unik `RYK-...` secara asynchronous via hook `wp_ajax_ryokourent_process_booking` dan `wp_ajax_nopriv_ryokourent_process_booking`.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
* **Dependensi:** TASK-017, TASK-018.
* **Kriteria Selesai:** Formulir dapat dikirim oleh pengunjung yang belum login (`nopriv`). Kompatibel dengan LiteSpeed Cache/WP Rocket melalui AJAX nonce fetcher atau pengecualian cache pada halaman booking.
* **Cara Pengujian:** Uji submit form sebagai pengunjung tanpa login (incognito mode) saat halaman dalam kondisi ter-cache.
* **Risiko:** Nonce invalid (-1 / 403) pada halaman yang ter-cache lama.
* **Dependensi:** TASK-017, TASK-018.
* **Kriteria Selesai:** Data booking langsung masuk ke WP-Admin sebelum jendela WhatsApp terbuka, dengan respons JSON status sukses.
* **Cara Pengujian:** Submit booking dari frontend, periksa daftar post pada CPT `penyewaan` di backend.
* **Risiko:** Pop-up blocker browser menghalangi pembukaan tab WhatsApp setelah AJAX selesai.

---

### TASK-020: Buat Role Operator
* **Tujuan:** Mendaftarkan peran user WordPress baru `ryokourent_operator` dengan hak akses terbatas pada menu operasional harian rental motor.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/user-roles.php`
* **Dependensi:** TASK-009.
* **Kriteria Selesai:** Role `Ryokourent Operator` terdaftar resmi dengan kapabilitas `read` dan `manage_ryokourent_bookings`. Role tidak memiliki hak edit tema, plugin, atau pengaturan harga.
* **Cara Pengujian:** Buat user dengan role operator dan uji login.
* **Risiko:** Role tidak terhapus bersih saat deaktivasi jika tidak di-handle dengan rapi.

---

### TASK-021: Buat Capability dan Pembatasan Akses
* **Tujuan:** Mengonfigurasi capabilities (`manage_ryokourent_bookings` vs `manage_ryokourent_settings`) agar operator dilarang mengakses halaman pengaturan tarif, kuota armada, dan manajemen user.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/includes/user-roles.php`
  * `wp-content/plugins/ryokourent-core/admin/admin-settings.php`
* **Dependensi:** TASK-020.
* **Kriteria Selesai:** Operator hanya dapat melihat dan mengedit data penyewaan; menu plugin settings dan tema terkunci (403 forbidden).
* **Cara Pengujian:** Login sebagai operator, coba akses URL langsung halaman pengaturan; pastikan ditolak.
* **Risiko:** Eskalasi privilege jika capability tidak dicek secara server-side.

---

### TASK-022: Buat Dashboard Booking & Operasional Armada
* **Tujuan:** Membuat halaman ringkasan operasional di WP-Admin yang menampilkan metrik: Unit Disewa Hari Ini, Booking Menunggu Konfirmasi, Unit Aktif per Lokasi Pool resmi (`ryokourent_get_pool_locations`), dan statistik berkala yang di-cache menggunakan transient.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/admin/dashboard.php`
  * `wp-content/plugins/ryokourent-core/admin/booking-columns.php`
* **Dependensi:** TASK-010, TASK-019.
* **Kriteria Selesai:** Dashboard menampilkan kartu statistik cepat tanpa query `posts_per_page => -1` (menggunakan query `fields => 'ids'` dan transient caching 5-10 menit).
* **Cara Pengujian:** Buka menu Dashboard Ryokou, verifikasi sinkronisasi angka dengan data CPT `penyewaan`.
* **Risiko:** Beban kueri jika tidak menggunakan transient caching untuk statistik dashboard.

---

### TASK-023: Buat Perubahan Status Booking (Quick Actions & Validasi Plat)
* **Tujuan:** Memfasilitasi alur kerja operator untuk mengubah status booking secara aman (Menunggu -> Dikonfirmasi -> Berjalan -> Selesai -> Dibatalkan) dengan cek ulang ketersediaan kuota dan validasi plat nomor motor yang diserahkan.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/admin/booking-columns.php`
  * `wp-content/plugins/ryokourent-core/includes/booking.php`
* **Dependensi:** TASK-022.
* **Kriteria Selesai:** Quick action dilindungi nonce `check_admin_referer` dan capability `manage_ryokourent_bookings`. Saat status diubah ke `status_dikonfirmasi`, sistem memverifikasi `ryokourent_count_overlapping` dan menolak perubahan jika kuota penuh. Plat nomor divalidasi: harus terdaftar pada model motor tersebut dan tidak bertabrakan dengan sewa aktif lain.
* **Cara Pengujian:** Coba konfirmasi booking ketika unit sudah terisi penuh; pastikan sistem menolak dengan pesan peringatan kuota habis.
* **Risiko:** Alokasi plat nomor ganda pada waktu sewa yang sama jika validasi tumpang tindih terlewat.

---

### TASK-024: Buat Pengaturan Harga dan Nomor WhatsApp (Admin Settings)
* **Tujuan:** Membuat antarmuka pengaturan admin (`manage_ryokourent_settings`) untuk nomor WhatsApp admin resmi, teks default, jam operasional, dan fitur multi-update harga (bulk price adjustment nominal/persentase untuk peak season).
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/admin/admin-settings.php`
  * `wp-content/plugins/ryokourent-core/includes/settings.php`
  * `wp-content/plugins/ryokourent-core/includes/pricing.php`
* **Dependensi:** TASK-014, TASK-021.
* **Kriteria Selesai:** Dilindungi nonce `check_admin_referer` dan capability `manage_ryokourent_settings`. Admin dapat mengubah nomor tujuan WhatsApp dan menerapkan penyesuaian harga bulk per kategori motor. Penyesuaian memiliki batas nilai angka (tidak boleh menghasilkan harga $\le 0$ atau persentase ekstrem $> 200\%$).
* **Cara Pengujian:** Naikkan harga kategori BeAT +10.000 melalui bulk update, periksa perubahan harga pada katalog. Uji input angka negatif atau tidak valid; pastikan ditolak.
* **Risiko:** Salah input formula persentase yang merusak data harga master jika tidak divalidasi batasnya.

---

### TASK-025: Buat Halaman FAQ dan Lokasi Pool
* **Tujuan:** Membuat komponen informasi 2 Pool resmi (Dinoyo Malang & Diponegoro Batu), jam operasional (07.00 - 23.00), aturan ketat Bromo (Trail CRF 150L wajib), dan FAQ accordion 7 poin sesuai blueprint.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/public/templates.php`
  * `wp-content/themes/generatepress-child/templates/`
* **Dependensi:** TASK-007.
* **Kriteria Selesai:** Halaman menyajikan alamat pool lengkap dengan tautan Google Maps, syarat dokumen e-KTP, dan accordion FAQ interaktif.
* **Cara Pengujian:** Klik setiap item FAQ, uji tautan Google Maps Pool 1 dan Pool 2.
* **Risiko:** Tampilan accordion rusak pada perangkat layar kecil.

---

### TASK-026: Buat Responsive Design & Mobile-First Optimization
* **Tujuan:** Mengoptimalkan seluruh elemen UI (katalog, form booking, floating mobile bar < 15% viewport, navigasi) agar tampil sempurna di resolusi smartphone 360px - 430px.
* **File yang Dibuat/Diubah:**
  * `wp-content/themes/generatepress-child/style.css`
  * `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`
* **Dependensi:** TASK-011, TASK-025.
* **Kriteria Selesai:** Tidak ada horizontal overflow, tombol WhatsApp nyaman dijangkau satu tangan (thumb zone), skor Lighthouse Mobile > 90.
* **Cara Pengujian:** Jalankan audit Lighthouse di Chrome DevTools pada mode emulasi mobile.
* **Risiko:** Floating bar menutupi tombol penting pada form.

---

### TASK-027: Buat Validasi Keamanan (Security Hardening)
* **Tujuan:** Melakukan audit menyeluruh: sanitasi seluruh input (`sanitize_text_field`), escaping seluruh output (`esc_html`, `esc_attr`, `esc_url`), verifikasi nonce pada setiap request POST/AJAX, dan pencegahan eksekusi langsung file PHP.
* **File yang Dibuat/Diubah:** Seluruh file pada `wp-content/plugins/ryokourent-core/`.
* **Dependensi:** TASK-002 s/d TASK-026.
* **Kriteria Selesai:** Kode lolos uji PHP_CodeSniffer WordPress Coding Standards (WordPress-Core, WordPress-Security).
* **Cara Pengujian:** Jalankan `phpcs` pada direktori plugin dan uji penetrasi input payload XSS/SQL Injection pada form.
* **Risiko:** False positive rule sniffer atau missing sanitization pada field custom array.

---

### TASK-028: Buat Pengujian Manual dan Otomatis
* **Tujuan:** Menjalankan rangkaian unit test kalkulasi tarif, tes ketersediaan kuota, serta pengujian manual end-to-end dari pemilihan motor hingga pesan WhatsApp diterima.
* **File yang Dibuat/Diubah:**
  * `wp-content/plugins/ryokourent-core/tests/test-pricing.php`
  * `wp-content/plugins/ryokourent-core/tests/test-availability.php`
  * `TESTING.md`
* **Dependensi:** TASK-027.
* **Kriteria Selesai:** Seluruh 18 skenario uji pada `TESTING.md` berstatus PASSED.
* **Cara Pengujian:** Eksekusi script testing dan verifikasi manual di perangkat HP nyata.
* **Risiko:** Ketergantungan environment PHP CLI lokal.

---

### TASK-029: Buat Dokumentasi Admin & SOP Operator
* **Tujuan:** Menyusun buku panduan operasional (SOP) untuk admin dan operator: cara konfirmasi pesanan WA dalam < 5 menit, verifikasi e-KTP, pengalokasian plat motor, dan pengelolaan kuota hari libur.
* **File yang Dibuat/Diubah:**
  * `docs/OPERATOR_MANUAL.md`
  * `docs/ADMIN_GUIDE.md`
* **Dependensi:** TASK-023, TASK-024.
* **Kriteria Selesai:** Dokumen panduan tersedia dan mudah dipahami oleh staf non-teknis.
* **Cara Pengujian:** Uji keterbacaan panduan bersama calon operator.
* **Risiko:** SOP tidak dipatuhi operator jika terlalu rumit.

---

### TASK-030: Buat Panduan Deployment & Checklist Produksi
* **Tujuan:** Menyusun dokumentasi deployment lengkap ke server hosting (LiteSpeed / Nginx), konfigurasi SSL, cache rules, konfigurasi permalink (flush rewrite otomatis di activation hook), backup otomatis, dan prosedur rollback. Memastikan direktori `tests/` dikecualikan dari paket rilis produksi dan `uninstall.php` memverifikasi konstanta `WP_UNINSTALL_PLUGIN`.
* **File yang Dibuat/Diubah:**
  * `docs/DEPLOYMENT_GUIDE.md`
  * `README.md`
  * `.gitattributes`
* **Dependensi:** TASK-028, TASK-029.
* **Kriteria Selesai:** Checklist pra-produksi lengkap dan siap dieksekusi untuk go-live tanpa meninggalkan berkas pengujian di server publik.
* **Cara Pengujian:** Lakukan simulasi dry-run deployment di staging server.
* **Risiko:** Perbedaan konfigurasi environment staging vs production.
