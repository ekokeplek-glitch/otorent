# SESSION STATE: RYOKOURENT

Dokumen ini melacak status pengerjaan sesi, task aktif, dependensi yang telah terpenuhi, dan rencana task selanjutnya.

---

## 1. Status Sesi Saat Ini
* **Tanggal / Waktu:** 2026-09-30
* **Cabang Git Aktif:** `develop`
* **Daftar Cabang Proyek Terdaftar (sesuai `GIT_WORKFLOW.md`):**
  - `main` (Branch produksi resmi)
  - `develop` (Branch integrasi aktif)
  - `feature/cpt-motor` (Fitur CPT & katalog armada motor)
  - `feature/cpt-booking` (Fitur CPT penyewaan & transaksi sewa)
  - `feature/booking-form` (Fitur formulir pemesanan & kalkulasi)
  - `feature/pricing` (Fitur kalkulator tarif harian, mingguan, bulanan)
  - `feature/availability` (Fitur pencegahan double booking & pengecekan stok unit)
  - `feature/whatsapp` (Fitur generator draft pesan & URL WhatsApp)
  - `feature/admin-dashboard` (Fitur dashboard admin & pelaporan operasional)
* **Task Terakhir Selesai:** `TASK-015: Buat Validasi Tanggal dan Jam (Operational Hours)`
* **Status Task Terakhir:** **DONE (SELESAI)**
* **Task Selanjutnya:** `TASK-016: Buat Validasi Ketersediaan Unit & Perlindungan Privasi Stok` (Menunggu perintah selanjutnya dari pengguna)

---

## 2. Riwayat Progress Task

| ID Task | Nama Task | Fase | Status | Dependensi | Tanggal Selesai |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TASK-001** | Analisis Blueprint & Dokumentasi | FASE 0 | **DONE** | - | 2026-09-30 |
| **TASK-002** | Struktur Repository & Kerangka Proyek | FASE 1 | **DONE** | TASK-001 | 2026-09-30 |
| **TASK-003** | Plugin Loader & Helper Dasar | FASE 2 | **DONE** | TASK-002 | 2026-09-30 |
| **TASK-004** | Custom Post Type `motor` | FASE 2 | **DONE** | TASK-003 | 2026-09-30 |
| **TASK-005** | Metabox Spesifikasi & Kuota Motor | FASE 2 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-006** | Taxonomy Kategori Motor | FASE 2 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-007** | Tampilan Katalog Motor | FASE 2 | **DONE** | TASK-005, TASK-006 | 2026-09-30 |
| **TASK-008** | Halaman Detail Motor | FASE 2 | **DONE** | TASK-007 | 2026-09-30 |
| **TASK-009** | Custom Post Type `penyewaan` | FASE 3 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-010** | Status Booking Kustom | FASE 3 | **DONE** | TASK-009 | 2026-09-30 |
| **TASK-011** | Form Booking Dasar | FASE 3 | **DONE** | TASK-005 | 2026-09-30 |
| **TASK-012** | Validasi Pelanggan & Anti-Spam | FASE 3 | **DONE** | TASK-011 | 2026-10-01 |
| **TASK-013** | Kalkulasi Durasi Sewa | FASE 3 | **DONE** | TASK-011 | 2026-10-01 |
| **TASK-014** | Kalkulasi Harga Paket Sewa | FASE 3 | **DONE** | TASK-013 | 2026-10-01 |
| **TASK-015** | Validasi Tanggal dan Jam | FASE 3 | **DONE** | TASK-013 | 2026-10-01 |
| **TASK-016** | Validasi Ketersediaan Unit | FASE 3 | **PENDING** | TASK-005, TASK-010 | - |

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-015
1. **Validasi Jadwal & Jam Operasional Pool Backend (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Menegakkan batas jam pelayanan serah terima unit di pool secara ketat antara pukul 07:00 – 23:00 WIB. Jam mulai 02:00 WIB atau di luar rentang resmi langsung ditolak dengan status HTTP 400 (`invalid_schedule`).
   - Mencegah pemilihan tanggal & waktu mulai di masa lalu (`start_datetime < now_wib`) dengan buffer pengisian formulir 15 menit.
   - Memvalidasi rentang waktu sewa: waktu selesai wajib setelah waktu mulai (`end_datetime > start_datetime`) dan durasi sewa minimal 1 jam.
   - Kompatibilitas lintas platform: menangani format standar ISO string (`Y-m-d\TH:i`) dari perangkat seluler Android dan iOS.
2. **Validasi Interaktif & Progressive Enhancement Frontend (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js` & `public/forms.php`):**
   - Penambahan atribut `min` pada field input `datetime-local` di markup HTML (`forms.php`) dan pembaruan dinamis `endInput.min = startInput.value` di JavaScript saat waktu mulai diubah.
   - Pengecekan instan di client-side: mencegah tanggal masa lalu dan jam di luar operasional pool dengan penandaan visual `.has-error` dan pesan peringatan di bawah input.
   - Pengecekan pada event form submit agar tidak mengirimkan form dengan jadwal salah.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-operating-hours.php` (14 pengujian skenario penolakan jam 02:00 WIB, jam operasional 07:00–23:00 WIB, tanggal masa lalu, tanggal terbalik, format ISO, dan integrasi submission AJAX).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-014
1. **Modul Pricing Server-Side (`wp-content/plugins/ryokourent-core/includes/pricing.php`):**
   - Fungsi `ryokourent_calculate_optimal_rental_price`:
     - Menghitung tarif sewa otomatis di sisi server secara *tamper-proof* berdasarkan kombinasi termurah (*best-rate guarantee*) dari paket bulanan (30 hari), paket mingguan (7 hari), dan harian (24 jam).
     - Menangani kasus diskon bertingkat (misal: sewa 6 hari otomatis mengambil tarif paket mingguan Rp 500.000 jika lebih hemat daripada 6 x Rp 85.000 = Rp 510.000).
     - Menghitung sewa jangka menengah (misal: 35 hari = 1 Bulan Rp 1.600.000 + 5 Hari Rp 425.000 = Rp 2.025.000).
     - Mendeteksi harga placeholder/kosong (`daily <= 0`) dan secara elegan menandai `requires_consultation => true` dengan label `"Konsultasi Admin WA"`.
   - Fungsi `ryokourent_calculate_booking_quote`:
     - Menggabungkan durasi sewa presisi, toleransi keterlambatan 2 jam, data tarif motor, dan menghasilkan objek quote lengkap.
   - Endpoint AJAX `ryokourent_get_price_quote` untuk kalkulasi tarif server-authoritative secara instan.
2. **Integrasi Validasi Form Submission (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Menghitung ulang total tarif secara mutlak di backend pada fungsi `ryokourent_validate_booking_submission()` tanpa mempercayai nilai harga yang dikirim dari browser DevTools.
   - Menyertakan `total_price`, `formatted_price`, `requires_consultation`, dan `price_breakdown` ke dalam data pemesanan.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-pricing-calculation.php` (13 pengujian skenario sewa 1 hari, 3 hari, 7 hari, 35 hari, optimasi 6 hari, dan motor tanpa harga).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-013
1. **Modul Kalkulasi Jadwal & Durasi Server-Side (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Fungsi `ryokourent_validate_rental_schedule`:
     - Memvalidasi rentang tanggal dan jam sewa di zona waktu `Asia/Jakarta` (WIB).
     - Menegakkan batas jam operasional serah terima unit (07:00 – 23:00 WIB). Menolak waktu terlalu pagi (< 07:00) atau terlalu malam (> 23:00).
     - Memvalidasi `end_datetime > start_datetime` dan durasi sewa minimal 1 jam.
     - Menghitung durasi jam presisi dan hari sewa tagihan dengan toleransi *overtime* 2 jam (24 jam = 1 hari, 26 jam = 1 hari, 26.5 jam = 2 hari, 56.5 jam = 3 hari).
     - Mengembalikan label ringkasan format Indonesia (misal: "3 Hari (~56.5 Jam)").
   - Integrasi ke `ryokourent_validate_booking_submission`: menyertakan `duration_hours`, `billable_days`, dan `duration_label` ke dalam objek data bersih (*clean_data*).
   - Penambahan endpoint AJAX real-time `ryokourent_calculate_duration` untuk perhitungan durasi dari server.
2. **Kalkulator Durasi Interaktif Client-Side (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`):**
   - Event listener live (`change`/`input`) pada input `#start_datetime` dan `#end_datetime`.
   - Validasi instan jam operasional 07:00–23:00 WIB dengan pesan peringatan di bawah input.
   - Pengecekan waktu selesai harus lebih akhir dari waktu mulai.
   - Perenderan dinamis label durasi real-time pada `#ryokou-live-duration` serta pembaruan estimasi biaya harian.
   - Pengecekan jadwal sewa pada submit form agar tidak mengirimkan jadwal yang tidak valid.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-duration-calculation.php` (17 pengujian skenario durasi, toleransi 2 jam, jam operasional, dan rentang tanggal).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-012
1. **Modul Validasi Backend & Anti-Spam (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Validasi data pelanggan: Nama lengkap e-KTP (min 3 chars), nomor WhatsApp seluler Indonesia (format `08...`/`628...`, 10–15 digit), alamat KTP, dan tempat menginap di Malang/Batu.
   - Aturan khusus pemisahan kontak: Nomor kontak darurat keluarga wajib berbeda secara mutlak dari nomor WhatsApp penyewa.
   - Perlindungan Anti-Spam Honeypot: Field `ryokourent_hp` tersembunyi; jika terisi otomatis memblokir submission dengan status HTTP 400 (`spam_bot_detected`).
   - Perlindungan Rate-Limiting: Berbasis WordPress transients per IP hash (maksimal 5 kali submit per 10 menit, blokir status HTTP 429).
   - Validasi CSRF Token Nonce (`ryokourent_booking_form_action`).
   - Endpoint AJAX resmi: `wp_ajax_ryokourent_submit_booking` dan `wp_ajax_nopriv_ryokourent_submit_booking`.
2. **Validasi Interaktif Frontend (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`):**
   - Validasi real-time saat user mengetik (*input/blur*) untuk nama, format seluler Indonesia, kontak darurat terpisah, dan alamat.
   - Intersepsi pengiriman formulir via AJAX `fetch()` dengan indikator status loading pada tombol submit dan scrolling ke input error pertama.
   - Penanganan respons server: menampilkan banner `.ryokou-form-alert` dan penandaan visual `.has-error` per input field.
3. **Pendaftaran Aset & Styling:**
   - Registrasi dan enqueue `ryokourent-booking` script dengan localized configuration `ryokouBookingConfig` pada `public/shortcodes.php`.
   - Penambahan styling CSS untuk alert error/success dan indikator input invalid pada `assets/css/ryokourent-public.css`.
4. **Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-booking-validation.php`.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-011
1. **Formulir Pemesanan HTML5 (`wp-content/plugins/ryokourent-core/public/forms.php`):**
   - 3 Langkah pemesanan terstruktur:
     - Langkah 1: Pilihan Rute Perjalanan (Malang Kota & Wisata Batu vs Trip Kaldera Bromo) & Dropdown Model Motor dinamis.
     - Langkah 2: Jadwal Sewa (Tanggal & Jam Mulai/Selesai 07.00 - 23.00 WIB) & Live Summary Card durasi serta estimasi tarif total.
     - Langkah 3: Data Identitas Pelanggan sesuai e-KTP (Nama, WhatsApp, Kontak Darurat Keluarga, Alamat KTP, Tempat Menginap di Malang/Batu, Media Sosial, Catatan Tambahan).
   - Perlindungan Keamanan & Anti-Spam: Field honeypot `ryokourent_hp` tersembunyi dan verifikasi nonce `ryokourent_booking_nonce`.
   - Tombol Submit CTA: Langsung terhubung ke WhatsApp Admin resmi dengan draf pesan terstruktur sesuai blueprint §8.
2. **Shortcode `[ryokou_booking_form]` (`wp-content/plugins/ryokourent-core/public/shortcodes.php`):**
   - Pendaftaran shortcode mandiri dengan parameter `form_id`, `selected_motor`, dan `title`.
3. **Logika Interaktif Kuncian Bromo (`assets/js/ryokourent-filter.js`):**
   - Saat opsi rute Bromo dipilih, pilihan model motor otomatis terkunci hanya ke Trail CRF 150L (`data-is-bromo="yes"`) dan menonaktifkan unit matik.
   - Perhitungan durasi real-time dengan toleransi overtime 2 jam.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-010
1. **Pendaftaran 5 Status Booking Kustom (`includes/post-types.php`):**
   - `status_menunggu` (Menunggu Konfirmasi - belum menahan kuota)
   - `status_dikonfirmasi` (Dikonfirmasi - menahan kuota ketersediaan)
   - `status_berjalan` (Sewa Berjalan - menahan kuota ketersediaan)
   - `status_selesai` (Selesai Sewa - kuota dilepas kembali)
   - `status_dibatalkan` (Dibatalkan - kuota dilepas)
   - Parameter keamanan: `public => false`, `exclude_from_search => true`, slug $\le 20$ karakter.
2. **Metabox Status Dropdown & Filter Anti-Reset (`includes/meta-boxes.php`):**
   - Dropdown status pemesanan dengan indikator warna visual dan keterangan efek kuota.
   - Filter `wp_insert_post_data` memastikan status kustom tidak ter-reset ke status default saat diedit dari antarmuka klasik WP-Admin.

---

## 5. Komponen yang Telah Diimplementasikan pada TASK-009
1. **Custom Post Type `penyewaan` (`includes/post-types.php`):**
   - Registrasi CPT internal untuk transaksi pemesanan dengan menu sidebar `dashicons-calendar-alt`.
   - Keamanan PII ketat (UU PDP): `public => false`, `publicly_queryable => false`, `show_in_rest => false` (mencegah akses atau kebocoran data pelanggan melalui REST API publik atau feed RSS).
   - Pemetaan hak akses RBAC: seluruh kapabilitas dipetakan ke `manage_ryokourent_bookings`.
2. **Metabox Data Pelanggan & Alokasi Plat (`includes/meta-boxes.php`):**
   - Panel Data Identitas Pelanggan (Nama, WhatsApp dengan tombol direct chat, kontak darurat, alamat KTP, tempat menginap).
   - Panel Rincian Armada, Jadwal Sewa, Total Tarif, dan Alokasi Plat Nomor Unit Fisik.


---

## 3. Komponen yang Telah Diimplementasikan pada TASK-008
1. **Template Single Post Motor (`wp-content/themes/generatepress-child/templates/single-motor.php` & `single-motor.php`):**
   - Header & breadcrumbs "Kembali ke Katalog Armada".
   - Media showcase & floating status badges (Tersedia / Booking Menipis / Penuh).
   - Spesifikasi teknis terstruktur: Kapasitas mesin (cc), transmisi, karakter rute, dan sistem bahan bakar.
   - Kotak edukasi & peringatan rute Bromo adaptif (peringatan larangan skutik ke pasir Bromo vs unit Trail CRF 150L resmi Bromo).
   - Fasilitas standar gratis (2 Helm SNI steril, 2 Jas Hujan setelan, phone holder stang, dan bantuan darurat jalan).
   - Sticky Pricing Sidebar: Tarif resmi harian dengan catatan toleransi overtime 2 jam, paket mingguan 7 hari, paket bulanan 30 hari.
   - Quick rental requirements: e-KTP asli, 2 dokumen pendukung, dan akun media sosial.
   - Dual CTA Action: Tombol booking cepat langsung memilih unit dan tombol Chat WhatsApp Admin terformat rapi.
2. **Filter Template Hierarchy (`wp-content/themes/generatepress-child/functions.php`):**
   - Hook `single_template` untuk resolusi otomatis template `templates/single-motor.php` dan fallback ke root child theme.
   - Enqueue dinamis stylesheet publik ketika post single motor dikunjungi.
3. **Styling Elegan Tema Gelap (`wp-content/themes/generatepress-child/style.css`):**
   - Layout grid 2 kolom desktop dengan sticky sidebar dan 1 kolom responsif pada smartphone.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-007
1. **Shortcode `[ryokou_catalog]` (`wp-content/plugins/ryokourent-core/public/shortcodes.php`):**
   - Mendaftarkan shortcode fleksibel dengan parameter `kategori`, `limit`, `show_filter`, dan `columns`.
   - Enqueue aset CSS dan JS secara terisolasi hanya pada halaman yang memuat katalog.
   - Localize script configuration (`ajaxUrl`, `waNumber`, `bookingAnchor`).
2. **Template Renderer Motor Card & Grid (`wp-content/plugins/ryokourent-core/public/templates.php`):**
   - Fungsi `ryokourent_render_motor_card()` dan `ryokourent_render_catalog_grid()`.
   - Menampilkan 7 armada resmi blueprint dengan fallback yang kokoh jika basis data WordPress belum terisi.
   - Format harga rapi (`Rp xx.xxx / 24 Jam` atau placeholder `Tanya Admin`).
   - Badges status ketersediaan (hijau, amber, merah) dan penanda rute Bromo.
   - Dual action button: "Sewa Sekarang" dan "Chat WA" dengan format pesan WhatsApp yang di-encode rapi.
   - Banner garansi 4 poin kepercayaan di bawah grid katalog.
3. **Desain Mobile-First & Filter Interaktif (`assets/css/ryokourent-public.css` & `assets/js/ryokourent-filter.js`):**
   - Tab filter pills kategori tanpa reload halaman (*zero jQuery, pure vanilla JS*).
   - Tampilan *empty state* interaktif dengan tombol reset filter.
   - Integrasi auto-select dropdown armada form booking saat tombol "Sewa Sekarang" diklik.

---

## 5. Komponen yang Telah Diimplementasikan pada TASK-006
1. **Taxonomy `kategori_motor` (`wp-content/plugins/ryokourent-core/includes/taxonomies.php`):**
   - Terdaftar secara hierarkis untuk CPT `motor` dengan slug `kategori-motor`.
   - Dukungan Block Editor Gutenberg (`show_in_rest => true`).
   - Proteksi kapabilitas: `manage_ryokourent_settings` untuk manipulasi term dan `edit_posts` untuk penugasan term.
2. **Seeding Kategori Default Idempoten:**
   - Term: `beat-series` (Honda BeAT Series), `scoopy-vario` (Honda Scoopy & Vario), dan `trail-adventure` (Trail Adventure (Bromo)).
   - Helper `ryokourent_get_motor_categories()` untuk kemudahan querying term di admin maupun frontend.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-005

1. **Metabox Spesifikasi & Karakter Armada (`ryokourent_motor_specs_metabox`):**
   - Field Kapasitas Mesin (`_ryokou_engine_cc`), Transmisi (`_ryokou_transmission`), Karakter Rute (`_ryokou_route_character`).
   - Checkbox Bromo Ready (`_ryokou_is_bromo_ready`) dengan pesan peringatan keras bahwa rute Bromo hanya untuk unit Trail CRF 150L.
   - Badge Status Publik (`_ryokou_status_label`: Tersedia, Booking Menipis, Penuh).
2. **Metabox Tarif Sewa Armada (`ryokourent_motor_pricing_metabox`):**
   - Field Tarif Harian 24 Jam (`_ryokou_price_daily`), Mingguan 7 Hari (`_ryokou_price_weekly`), Bulanan 30 Hari (`_ryokou_price_monthly`).
   - Proteksi hak akses: Hanya Administrator (`manage_ryokourent_settings` / `manage_options`) yang dapat mengedit tarif; untuk Operator field terkunci otomatis (*disabled*).
3. **Metabox Inventaris Unit Fisik & Plat Nomor (`ryokourent_motor_stock_metabox`):**
   - Total Unit Fisik (`_ryokou_physical_stock`) dan Daftar Plat Nomor Kendaraan (`_ryokou_plate_numbers`).
   - Tertutup dari REST API publik (`show_in_rest => false`), sanitasi pembersihan plat nomor huruf kapital per baris.
   - Proteksi hak akses Administrator (`manage_ryokourent_settings`).
4. **Keamanan & Guard Penyimpanan (`ryokourent_save_motor_meta_data`):**
   - Guard `DOING_AUTOSAVE`.
   - Verifikasi nonce `wp_verify_nonce($_POST['ryokourent_motor_meta_nonce'], 'ryokourent_save_motor_meta_action')`.
   - Validasi `post_type === 'motor'` dan `current_user_can('edit_post', $post_id)`.
   - Validasi hak akses khusus untuk field sensitif (harga & kuota fisik).

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-004

1. **Custom Post Type `motor` (`includes/post-types.php`):**
   - Registrasi CPT `motor` dengan label bahasa Indonesia lengkap.
   - Dukungan fitur: `title`, `editor`, `thumbnail`, `excerpt`, `custom-fields`.
   - Konfigurasi `public => true`, `has_archive => 'motor'`, `show_in_rest => true` (Block Editor Gutenberg), menu icon `dashicons-car`, posisi menu 25.
   - Kustomisasi pesan pembaruan (`post_updated_messages`).

2. **Skema & Sanitasi Meta Fields (`includes/meta-fields.php`):**
   - Pendaftaran 10 meta fields resmi sesuai `DATA_MODEL.md` via `register_post_meta()`:
     - `_ryokou_engine_cc` (integer, sanitasi angka kapasitas)
     - `_ryokou_transmission` (whitelist: Otomatis CVT / Manual Kopling)
     - `_ryokou_route_character` (sanitasi string deskripsi)
     - `_ryokou_is_bromo_ready` (boolean 0/1 untuk proteksi armada Bromo)
     - `_ryokou_price_daily`, `_ryokou_price_weekly`, `_ryokou_price_monthly` (integer IDR)
     - `_ryokou_physical_stock` (kuota unit fisik internal)
     - `_ryokou_plate_numbers` (daftar plat multi-baris dinormalisasi)
     - `_ryokou_status_label` (whitelist: Tersedia / Booking Menipis / Penuh)
   - Capability checks pada callback autentikasi (`current_user_can('edit_post', $post_id)`).
   - Helper fungsi pembaca data: `ryokourent_get_motor_meta()` dan `ryokourent_is_motor_bromo_ready()`.

3. **Kustomisasi Kolom Admin List Table (`admin/motor-columns.php`):**
   - Penambahan kolom: Foto Thumbnail, Model Motor, Spesifikasi Mesin, Tarif Harian, Unit Fisik, Rute Bromo, Status Publik, dan Tanggal.
   - Escaping output ketat (`esc_html`, `esc_attr`, `esc_url`) pada semua data kolom.
   - Pengecekan capability `current_user_can('edit_posts')`.
   - Sortable columns untuk Tarif Harian dan Jumlah Unit Fisik dengan penanganan query `pre_get_posts`.

---

## 4. Batasan & Aturan Keamanan Terjaga
* **Prefix:** Seluruh fungsi dan hook menggunakan prefix `ryokourent_`.
* **Sanitasi & Escaping:** Setiap input disanitasi sebelum disimpan; setiap output diescape.
* **Audit REVIEW-ARCHITECTURE.md (Lolos 100%):**
  - **K1 & M9:** Role `ryokourent_operator` dan hak akses `manage_ryokourent_bookings` + `manage_ryokourent_settings` diatur aktif pada aktivasi dan `admin_init`.
  - **K2 & ADR-008:** Pengecekan 2 titik dan mekanisme atomic lock didokumentasikan di `DATA_MODEL.md` dan `DECISIONS.md`.
  - **K3:** Penanganan status kustom `public => false` dengan panjang slug $\le 20$ karakter disematkan ke metabox dan filter `wp_insert_post_data`.
  - **K4:** Meta fields internal `_ryokou_physical_stock` dan `_ryokou_plate_numbers` diproteksi `show_in_rest => false` dan respon AJAX ketersediaan hanya boolean.
  - **K5:** Kalkulasi durasi (24 jam + 2 jam grace period), operasional 07:00-23:00 WIB, dan penetapan harga dikunci mutlak di server backend.
  - **K6:** Snippet `functions.php` pada `BLUEPRINT.md` §7A ditandai *superseded* dan disatukan ke plugin `ryokourent-core`.
  - **M1 s/d M15:** Penanganan cache nonce, honeypot anti-spam, format `https://wa.me/`, penyesuaian bulk price, guard autosave, dan sinkronisasi struktur folder arsitektur telah selesai diperbarui tanpa ada yang terlewat.
* **File Terlindungi (Tidak Disentuh):**
  - `booking.php` (TIDAK DIUBAH)
  - `pricing.php` (TIDAK DIUBAH)
  - `availability.php` (TIDAK DIUBAH)
  - `whatsapp.php` (TIDAK DIUBAH)
  - `dashboard.php` (TIDAK DIUBAH)
* **Kompilasi & Build:** Build applet berhasil tanpa error.
