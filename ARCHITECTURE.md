# ARCHITECTURE DOCUMENT: RYOKOURENT

## 1. Arsitektur WordPress & Prinsip Desain
Ryokourent dibangun dengan prinsip pemisahan tanggung jawab (*Separation of Concerns*) yang ketat:
* **Tampilan & Presentasi:** Diatur oleh **GeneratePress Child Theme** (`generatepress-child`). Tidak boleh ada logika bisnis database, validasi ketersediaan, atau perhitungan harga yang ditulis di `functions.php`.
* **Logika Bisnis & Data:** Terpusat sepenuhnya di dalam plugin custom **`ryokourent-core`**. Jika tema diganti di masa depan, seluruh data armada, rekaman penyewaan, kuota, aturan harga, dan generator WhatsApp tetap berfungsi utuh.
* **Performa & Kecepatan:** Tanpa ketergantungan jQuery berat, memprioritaskan JavaScript vanilla ringan untuk interaksi form, kalkulasi durasi, serta sanitasi frontend.

## 2. Pembagian Plugin dan Theme

### A. Plugin Custom: `ryokourent-core`
Plugin ini mengelola:
1. Registrasi Custom Post Type (CPT `motor` dan CPT `penyewaan`).
2. Registrasi Custom Status (`status_menunggu`, `status_dikonfirmasi`, `status_berjalan`, `status_selesai`, `status_dibatalkan`).
3. Registrasi Custom Taxonomy (Kategori Motor).
4. Meta box data teknis motor dan data penyewaan.
5. Algoritma ketersediaan & pencegahan *double booking*.
6. Mesin kalkulasi tarif (harian 24 jam, mingguan, bulanan, bulk update).
7. Generator pesan terformat WhatsApp (`wa.me`).
8. Konfigurasi Role & Capabilities (`operator` vs `administrator`).
9. Dashboard ringkasan booking & manajemen armada.
10. Endpoint AJAX / REST API untuk validasi ketersediaan dan penyimpanan booking.

### B. Child Theme: `generatepress-child`
Theme ini mengelola:
1. Skema warna kontras tinggi (Dark modern: Navy/Hitam dengan aksen oranye/kuning energik).
2. Layout mobile-first, typography hierarchy, dan container responsive.
3. Template part untuk katalog motor, detail motor, kartu pool, dan FAQ accordion.
4. CSS ultra-ringan (< 50KB) untuk memastikan skor PageSpeed optimal (> 95).

## 3. Struktur Folder Terperinci

```
wp-content/
├── plugins/
│   └── ryokourent-core/
│       ├── ryokourent-core.php          # Main plugin file, bootstrap, konstanta, activation hooks
│       ├── uninstall.php                # Cleanup jika plugin dihapus
│       ├── readme.txt                   # Metadata plugin WordPress
│       ├── includes/
│       │   ├── helpers.php              # Sanitasi input, format rupiah, timezone WIB helpers
│       │   ├── post-types.php           # Registrasi CPT motor & CPT penyewaan
│       │   ├── meta-fields.php          # Skema WordPress register_post_meta & sanitasi callback
│       │   ├── taxonomies.php           # Registrasi taxonomy kategori_motor
│       │   ├── meta-boxes.php           # Custom fields & metabox (spesifikasi, harga, data sewa)
│       │   ├── pricing.php              # Logika kalkulasi harga (harian, mingguan, bulanan, bulk)
│       │   ├── availability.php         # Algoritma pengecekan stok unit & pencegahan double booking
│       │   ├── booking.php              # Handler pemrosesan form booking & validasi data
│       │   ├── whatsapp.php             # Formatter pesan WhatsApp resmi & URL generator
│       │   ├── user-roles.php           # Registrasi role operator & custom capabilities
│       │   └── settings.php             # Pengaturan nomor WhatsApp, jam buka, info pool
│       ├── admin/
│       │   ├── dashboard.php            # Widget/halaman ringkasan operasional unit & booking
│       │   ├── motor-columns.php        # Kustomisasi kolom daftar armada motor di WP-Admin
│       │   ├── booking-columns.php      # Kustomisasi kolom daftar penyewaan di WP-Admin
│       │   └── admin-settings.php       # Halaman pengaturan admin (nomor WA, tarif bulk)
│       ├── public/
│       │   ├── shortcodes.php           # Shortcode: [ryokou_catalog], [ryokou_booking_form], dll.
│       │   ├── forms.php                # Markup form booking & form konsultan rute
│       │   └── templates.php            # Template loader untuk kartu motor & arsip
│       ├── assets/
│       │   ├── css/
│       │   │   ├── ryokourent-public.css # Style untuk form, filter katalog, modal
│       │   │   └── ryokourent-admin.css  # Style badge status booking di admin
│       │   └── js/
│       │       ├── ryokourent-booking.js # Vanilla JS hitung durasi, live draft WA, submit AJAX
│       │       └── ryokourent-filter.js  # Filter instan kategori motor
│       └── tests/
│           ├── test-helpers.php         # Unit test helper sanitasi & durasi
│           ├── test-cpt-motor.php       # Unit test CPT motor & meta sanitasi
│           ├── test-pricing.php         # Unit test kalkulator harga
│           └── test-availability.php    # Unit test pencegahan double booking
│
└── themes/
    └── generatepress-child/
        ├── style.css                    # CSS overrides GeneratePress
        ├── functions.php                # Enqueue asset child theme
        ├── screenshot.png               # Tampilan preview tema
        └── templates/
            ├── page-booking.php         # Template khusus halaman booking
            └── single-motor.php         # Template detail unit motor
```

## 4. Alur Data Booking (End-to-End Workflow)

```
[Pengguna di Web Browser (Mobile / Desktop)]
  │
  ├─ 1. Memilih Motor & Jadwal (Mulai & Selesai)
  ├─ 2. Input Data: Nama, KTP, Domisili/Hotel, WA, No. Darurat, Medsos
  ├─ 3. ryokourent-booking.js secara instan:
  │      ├─ Menghitung durasi (Hari & Jam)
  │      ├─ Mengkalkulasi estimasi tarif
  │      └─ Memperbarui "Live Preview Pesan WhatsApp"
  │
  ├─ 4. Mengklik "Kirim Booking via WhatsApp"
  │      │
  │      ├─> Mengirim Request AJAX ke backend (`ryokourent_process_booking`)
  │      │      │
  │      │      ├──> Verifikasi Nonce & Sanitasi Data Pelanggan
  │      │      ├──> Validasi Ketersediaan Unit (Cek apakah unit fisik masih ada di rentang tanggal)
  │      │      ├──> Jika Valid: Buat Post baru pada CPT `penyewaan` dengan status `status_menunggu`
  │      │      └──> Return JSON Success { booking_id, wa_url }
  │      │
  │      └─> Browser secara mulus mengarahkan pengguna ke `https://api.whatsapp.com/send?phone=...&text=...`
  │
[Admin / Operator di WhatsApp & WP-Admin]
  │
  ├─ 5. Admin menerima pesan WhatsApp terformat lengkap
  ├─ 6. Admin melakukan verifikasi e-KTP dan meminta DP/Jaminan
  ├─ 7. Operator membuka WP-Admin -> Penyewaan Motor:
  │      ├─ Ubah status: "Menunggu" -> "Dikonfirmasi" (Alokasikan Plat Motor)
  │      ├─ Hari H: Saat serah terima di Pool/Stasiun -> Ubah status: "Sewa Berjalan"
  │      └─ Pengembalian Unit: Cek kondisi motor -> Ubah status: "Selesai" (Slot kuota kembali tersedia)
```

## 5. Hubungan Antar-Fitur & Dependensi Modul
1. **Katalog Motor & Form Booking:** Saat tombol "Sewa Sekarang" pada kartu motor diklik, ID motor dioper langsung ke input dropdown form booking (auto-selected).
2. **Kalkulator Durasi & Mesin Harga:** Durasi (hari/jam) dihitung otomatis dari `start_datetime` dan `end_datetime`. Mesin harga menerapkan tarif harian (24 jam) dengan toleransi overtime 2 jam, paket mingguan (7 hari), atau bulanan (30 hari). Seluruh perhitungan harga divalidasi mutlak di server; harga dari client tidak dipercaya mentah-mentah.
3. **Validasi Ketersediaan & Status Booking:** Kuota unit fisik terikat pada CPT `motor`. Pada rentang tanggal yang dipilih, sistem memeriksa semua post CPT `penyewaan` dengan status `status_dikonfirmasi` dan `status_berjalan`. Jika `jumlah_booking_aktif >= total_unit_fisik`, unit ditandai tidak tersedia. Pengecekan dijalankan di 2 titik: saat submit online dan saat status diubah ke `status_dikonfirmasi`.
4. **Role & Capabilities:** Pengaturan harga dan manajemen kuota unit diproteksi dengan capability `manage_ryokourent_settings` (hanya `administrator`), sementara operator (`ryokourent_operator`) hanya memiliki capability `manage_ryokourent_bookings` untuk pembaruan status dan verifikasi data sewa.

## 6. Keamanan Dasar (Security Hardening)
1. **Data Sanitization & Validation:** Seluruh input formulir disaring menggunakan fungsi WordPress (`sanitize_text_field`, `sanitize_textarea_field`, `wp_strip_all_tags`, validasi format nomor telepon Indonesia `08... / 62...`).
2. **Output Escaping:** Seluruh data yang dirender ke HTML wajib menggunakan `esc_html()`, `esc_attr()`, `esc_url()`.
3. **Cross-Site Request Forgery (CSRF) Protection:** Menggunakan WordPress Nonces (`wp_create_nonce` dan `check_ajax_referer` / `wp_verify_nonce`) pada setiap form publik dan form admin. Halaman formulir booking dikecualikan dari caching agresif atau menggunakan AJAX nonce refresher agar tidak kadaluwarsa pada LiteSpeed/WP Rocket.
4. **Spam & Abuse Protection:** Formulir pemesanan dilengkapi field honeypot tak kasat mata (`ryokourent_hp`) dan pembatasan frekuensi pengiriman (rate-limiting via transient per IP).
5. **Authorization & Capability Checks:** Menggunakan `current_user_can('manage_ryokourent_settings')` dan `current_user_can('manage_ryokourent_bookings')` untuk membatasi akses menu dan data sensitif penyewa.
6. **Direct Script Execution Prevention:** Setiap file PHP diawali dengan:
   ```php
   if (!defined('ABSPATH')) {
       exit; // Exit if accessed directly
   }
   ```
7. **Perlindungan Privasi Pelanggan (UU PDP):** Tidak menyimpan foto fisik identitas (KTP/SIM) di direktori publik server `wp-content/uploads/`.
8. **Pencegahan Data Leak Armada:** Informasi plat nomor dan stok unit fisik dinonaktifkan dari REST API publik (`show_in_rest => false`), dan respons AJAX ketersediaan hanya mengembalikan status boolean.
