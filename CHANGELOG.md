# CHANGELOG: RYOKOURENT

Semua perubahan penting pada proyek Ryokourent akan didokumentasikan di file ini.
Format penulisan berpedoman pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mengikuti standar [Semantic Versioning](https://semver.org/).

---

## [Unreleased] - 2026-10-01

### Fixed
- **Fatal Error Redeclaration `ryokourent_get_booking_statuses()`:**
  - Menyelesaikan konflik fatal PHP akibat deklarasi ganda fungsi `ryokourent_get_booking_statuses()` pada `includes/helpers.php` dan `includes/post-types.php`.
  - Memusatkan definisi lengkap dictionary status booking (multidimensi mencakup label, deskripsi, warna, badge class, dan efek kuota) ke dalam `includes/helpers.php` dengan pembungkus guard `if (!function_exists(...))`.
  - Menghapus deklarasi duplikat pada `includes/post-types.php` dan menambahkan helper `ryokourent_get_booking_status_labels()` untuk kemudahan akses label ringkas.
  - Memperbarui berkas pengujian `tests/test-cpt-penyewaan.php` dan `tests/test-helpers.php` agar sinkron dengan struktur status terpusat.

## [Unreleased] - 2026-09-30

### Added
- **Fase 0 (Analisis dan Perencanaan):**
  - Penyusunan `PROJECT_OVERVIEW.md` mencakup tujuan, target pengguna, ruang lingkup MVP, dan asumsi bisnis.
  - Penyusunan `ARCHITECTURE.md` mengatur pemisahan plugin `ryokourent-core` dan child theme `generatepress-child`.
  - Penyusunan `DATA_MODEL.md` mencakup spesifikasi CPT `motor`, CPT `penyewaan`, taxonomy `kategori_motor`, meta fields, 5 status kustom, dan relasi data.
  - Penyusunan `TASKS.md` merinci 30 task implementasi berurutan dari TASK-001 hingga TASK-030.
  - Penyusunan `AI_WORKFLOW.md` dan `AI_RULES.md` mengatur pembagian kerja AI, standar git branching, dan batasan operasional.
  - Penyusunan `DECISIONS.md` mendokumentasikan 7 Architectural Decision Records (ADR-001 s/d ADR-007).
  - Penyusunan `TESTING.md` memetakan 20 skenario uji (TC-001 s/d TC-020).
  - Penyusunan `.env.example` dan `CONFIG.example.php`.
- **Fase 3 (TASK-011: Form Booking Dasar & Interaksi WhatsApp):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/public/forms.php` untuk merender formulir booking HTML5 responsif 3-langkah (pilihan rute & armada, jadwal sewa & kalkulasi durasi, identitas pelanggan sesuai e-KTP), dilengkapi honeypot anti-spam `ryokourent_hp` dan verifikasi nonce `ryokourent_booking_nonce`.
  - Pendaftaran shortcode `[ryokou_booking_form]` pada `wp-content/plugins/ryokourent-core/public/shortcodes.php`.
  - Penambahan interaksi dinamis pada `wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js`: kuncian rute Bromo otomatis menonaktifkan skutik dan mengunci pilihan motor ke Trail CRF 150L, kalkulasi durasi live dengan toleransi overtime 2 jam, dan penataan draf pesan WhatsApp siap kirim.
  - Penambahan styling CSS lengkap form booking pada `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`.
  - Pembuatan automated unit test `wp-content/plugins/ryokourent-core/tests/test-booking-form.php`.
- **Fase 3 (TASK-010: Status Booking Kustom):**
  - Pendaftaran 5 post status kustom internal (`status_menunggu`, `status_dikonfirmasi`, `status_berjalan`, `status_selesai`, `status_dibatalkan`) pada `wp-content/plugins/ryokourent-core/includes/post-types.php` dengan `public => false` dan slug $\le 20$ karakter.
  - Penyediaan metabox dropdown status pemesanan dengan penanda visual warna pada `wp-content/plugins/ryokourent-core/includes/meta-boxes.php`.
  - Penerapan filter `wp_insert_post_data` untuk mencegah WordPress me-reset status kustom kembali ke draft saat diedit dari WP-Admin.
- **Fase 3 (TASK-009: Custom Post Type Penyewaan & RBAC):**
  - Pendaftaran CPT `penyewaan` internal pada `wp-content/plugins/ryokourent-core/includes/post-types.php` dengan parameter keamanan PII ketat (`public => false`, `publicly_queryable => false`, `exclude_from_search => true`, `show_in_rest => false`).
  - Pemetaan seluruh kapabilitas CPT ke custom RBAC `manage_ryokourent_bookings`.
  - Pembuatan panel metabox data identitas pelanggan (nama, nomor WA dengan link direct chat, kontak darurat, alamat KTP, alamat menginap) dan panel rincian armada, jadwal sewa, total tarif, dan alokasi plat nomor unit fisik pada `wp-content/plugins/ryokourent-core/includes/meta-boxes.php`.
  - Pembuatan automated unit test `wp-content/plugins/ryokourent-core/tests/test-cpt-penyewaan.php`.
- **Fase 2 (TASK-008: Halaman Detail Motor GeneratePress Child Theme):**
  - Pembuatan template single post `wp-content/themes/generatepress-child/templates/single-motor.php` dan `single-motor.php` untuk merender informasi lengkap armada: header & breadcrumb navigasi kembali ke katalog, galeri foto, spesifikasi teknis (mesin cc, transmisi, karakter rute, bensin), callout edukasi khusus rute Bromo (peringatan larangan matik vs trail CRF 150L resmi Bromo), fasilitas sewa gratis (2 helm SNI steril, 2 jas hujan setelan, phone holder), dan sticky sidebar tarif resmi (harian toleransi overtime 2 jam, paket mingguan, paket bulanan, syarat sewa cepat, dan WhatsApp CTA).
  - Pembaruan `wp-content/themes/generatepress-child/functions.php` dengan filter `single_template` dan dynamic stylesheet enqueue.
  - Penambahan styling CSS responsif modern gelap di `wp-content/themes/generatepress-child/style.css`.
  - Pembuatan automated unit test `wp-content/plugins/ryokourent-core/tests/test-single-motor.php`.
- **Fase 2 (TASK-007: Tampilan Katalog Motor & Interaksi):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/public/shortcodes.php` untuk pendaftaran shortcode `[ryokou_catalog]` dengan atribut kustom, enqueue otomatis stylesheet, dan skrip filter.
  - Pembuatan file `wp-content/plugins/ryokourent-core/public/templates.php` yang menyediakan fungsi render kartu motor (`ryokourent_render_motor_card`), formatting harga harian/mingguan/bulanan, badges ketersediaan & rute Bromo, fallback 7 armada resmi blueprint, dan banner 4 garansi layanan.
  - Pembuatan file CSS `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css` dengan desain mobile-first, high contrast dark theme, floating badges, dan touch-friendly action buttons.
  - Pembuatan file JS `wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js` (vanilla JS tanpa dependensi jQuery) untuk filter tab kategori tanpa reload, empty-state toggling, dan auto-select motor pada form booking.
  - Pembuatan file automated unit test `wp-content/plugins/ryokourent-core/tests/test-catalog.php`.
- **Fase 2 (TASK-006: Taxonomy Kategori Motor):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/includes/taxonomies.php` untuk pendaftaran taxonomy hierarkis `kategori_motor` (slug: `kategori-motor`), dukungan Gutenberg Block Editor, proteksi capability RBAC, dan seeding default 3 kategori utama (`beat-series`, `scoopy-vario`, `trail-adventure`) secara idempoten.
  - Penambahan fungsi pembantu `ryokourent_get_motor_categories()` untuk mempermudah querying kategori di admin dan frontend.
  - Pembuatan file automated unit test `wp-content/plugins/ryokourent-core/tests/test-taxonomies.php`.
- **Fase 2 (TASK-005: Field Data Motor & Metabox):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/includes/meta-boxes.php` yang menyediakan 3 panel metabox pada form edit CPT `motor`: panel Spesifikasi & Karakter Rute, panel Tarif Sewa (harian, mingguan, bulanan), dan panel Inventaris Unit Fisik & Plat Nomor.
  - Penerapan proteksi guard `DOING_AUTOSAVE`, verifikasi nonce `ryokourent_motor_meta_nonce`, dan pembatasan hak akses berbasis role (Operator hanya dapat mengubah spesifikasi, sedangkan perubahan tarif sewa dan kuota fisik internal dikunci hanya untuk Administrator).
  - Pembuatan file automated unit test `wp-content/plugins/ryokourent-core/tests/test-meta-boxes.php` untuk memverifikasi seluruh skenario keamanan, autosave guard, boundary capability, dan sanitasi data.
- **Fase 2 (TASK-004: Custom Post Type Motor & Meta Fields):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/includes/post-types.php` untuk pendaftaran CPT `motor` (Armada Motor), dukungan Gutenberg REST API, arsip `motor`, thumbnail, excerpt, dan custom updated messages.
  - Pembuatan file `wp-content/plugins/ryokourent-core/includes/meta-fields.php` untuk registrasi skema WordPress `register_post_meta()` untuk 10 atribut CPT `motor` (`_ryokou_engine_cc`, `_ryokou_transmission`, `_ryokou_route_character`, `_ryokou_is_bromo_ready`, `_ryokou_price_daily`, `_ryokou_price_weekly`, `_ryokou_price_monthly`, `_ryokou_physical_stock`, `_ryokou_plate_numbers`, `_ryokou_status_label`) dilengkapi fungsi sanitasi dan validasi capability.
  - Pembuatan file `wp-content/plugins/ryokourent-core/admin/motor-columns.php` untuk kustomisasi tabel daftar armada di WP-Admin (preview foto, spesifikasi mesin, tarif harian berformat Rupiah, kuota unit fisik, badge rute Bromo, badge ketersediaan publik) beserta sortable columns.
  - Pembuatan file automated test `wp-content/plugins/ryokourent-core/tests/test-cpt-motor.php` untuk pengujian parameter CPT, meta sanitasi, dan kolom admin.
- **Fase 2 (TASK-003: Plugin Loader & Helper Dasar):**
  - Pembuatan file `wp-content/plugins/ryokourent-core/includes/helpers.php` yang memuat fungsi utilitas inti: formatting Rupiah (`ryokourent_format_rupiah`), sanitasi & validasi nomor WhatsApp internasional (`ryokourent_sanitize_phone`, `ryokourent_is_valid_phone`), manipulasi waktu zona WIB (`ryokourent_get_timezone`, `ryokourent_get_now_wib`, `ryokourent_format_datetime_id`), perhitungan durasi sewa & toleransi 24 jam (`ryokourent_calculate_duration_hours`, `ryokourent_calculate_rental_days`), validasi jam operasional 07.00-23.00 WIB (`ryokourent_is_within_operating_hours`), sanitasi NIK e-KTP dan plat nomor motor, serta getter status booking dan lokasi pool resmi.
  - Pembaruan bootstrap loader `wp-content/plugins/ryokourent-core/ryokourent-core.php` untuk memuat helper secara otomatis dan menyiapkan modular autoloading pada hook `plugins_loaded`.
  - Pembuatan automated unit testing script `wp-content/plugins/ryokourent-core/tests/test-helpers.php` untuk verifikasi fungsi helper secara independen.

  - Inisialisasi struktur repositori resmi dengan `.editorconfig` dan `.phpcs.xml.dist`.
  - Pembuatan kerangka plugin `wp-content/plugins/ryokourent-core/` beserta file bootstrap, `uninstall.php`, `readme.txt`, dan struktur folder modular (`includes/`, `admin/`, `public/`, `assets/`, `tests/`).
  - Pembuatan kerangka child theme `wp-content/themes/generatepress-child/` beserta `style.css` dan `functions.php`.
  - Pembuatan dokumen panduan teknis pada direktori `/docs/` (instalasi lokal, hosting, coding standards, git workflow, checklist pra-merge, dan checklist produksi).
