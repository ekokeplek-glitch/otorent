# REVIEW-ARCHITECTURE: Ryokourent

Reviewer: Claude. Tanggal: 2026-10-01.

**Cakupan:** BLUEPRINT.md, TASKS.md, AI_RULES.md, ARCHITECTURE.md, DATA_MODEL.md. Yang direview berupa dokumen desain dan satu potongan kode di BLUEPRINT §7A. File kode TASK-004 (`post-types.php`, `meta-fields.php`, `motor-columns.php`, `test-cpt-motor.php`) tidak dilampirkan sehingga tidak diaudit. Dokumen ini hanya berisi saran, tanpa penulisan ulang kode.

---

## 1. Masalah Kritis

| ID | Masalah | Dampak |
|---|---|---|
| K1 | CPT `penyewaan` dan `motor` memakai `capability_type => 'post'` (BLUEPRINT §7A). Role dengan `edit_posts` (Author, Editor, Contributor) bisa melihat atau mengubah data KTP, WA, kontak darurat, tarif, dan stok. Capability custom belum di-grant ke `administrator`. | Kebocoran PII, bertentangan dengan ARCHITECTURE §5.4 dan §6.7. |
| K2 | Ketersediaan hanya menghitung `dikonfirmasi` dan `berjalan`. Booking `menunggu` tidak menahan kuota, sehingga lock saat submit (TASK-017) nyaris tak berguna. Race sebenarnya terjadi saat operator mengubah status ke `dikonfirmasi` (TASK-023), dan tidak ada dokumen yang mewajibkan cek ulang di titik itu. | Overbooking. |
| K3 | Custom post status tidak otomatis muncul di dropdown edit post. Simpan dari layar edit klasik bisa mereset status kustom. Status didaftarkan `public => true`. Slug `status_dikonfirmasi` sudah 19 dari batas 20 karakter. | Kriteria TASK-010 tidak terpenuhi, status hilang, risiko PII. |
| K4 | `motor` public dengan `show_in_rest => true` dan `custom-fields`. Jika meta stok/plat diberi `show_in_rest => true`, data terekspos. TASK-016 juga menyebut fungsi cek mengembalikan "sisa kuota"; jika ikut dikirim ke respons AJAX publik, stok bocor. | Melanggar ARCHITECTURE §6.7. |
| K5 | Harga dan durasi dihitung di JS. Jika server menerima `total_price` dari client, harga bisa dimanipulasi. Jam operasional 07:00–23:00 dan `end > start` harus divalidasi di server. Zona waktu harus dipaksa `Asia/Jakarta`. | Manipulasi harga dan jadwal, bug timezone. |
| K6 | Snippet BLUEPRINT menaruh registrasi CPT dan status di `functions.php` dengan prefix `ryokou_`. Ini melanggar AI_RULES (logika di plugin, prefix `ryokourent_`). Jika disalin bersamaan dengan plugin, CPT terdaftar ganda dan nama function bisa bertabrakan. | Konflik dan pelanggaran aturan proyek. |

## 2. Masalah Sedang

- **M1. Nonce dan cache.** Halaman ter-cache (LiteSpeed/WP Rocket) membuat nonce kedaluwarsa dan booking gagal. Hook `wp_ajax_nopriv_*` belum tertulis, sehingga pengunjung tanpa login selalu gagal.
- **M2. Tanpa proteksi spam.** Endpoint publik yang membuat post berisi PII bisa dibanjiri booking palsu.
- **M3. Redirect WhatsApp.** ARCHITECTURE memakai `api.whatsapp.com/send`, BLUEPRINT memakai `wa.me`. Membuka WA setelah AJAX sering diblok popup blocker. Teks harus di-encode dengan benar, dan nomor tujuan diambil dari settings di server.
- **M4. Aturan harga ambigu.** Contoh 56,5 jam dihitung "3 Hari". "Toleransi overtime" belum didefinisikan. Sewa 35 hari belum jelas (30 + 5 hari atau kombinasi mingguan). Harga placeholder "Tanya Admin" bisa menghasilkan Rp 0.
- **M5. Data model tidak cocok dengan TASKS.** TASK-022 meminta "Unit Siap di Pool Dinoyo/Batu", padahal stok hanya satu angka tanpa lokasi pool. BLUEPRINT menyebut "Dalam Servis", tetapi tidak ada field-nya.
- **M6. Alokasi plat nomor tidak divalidasi.** Plat bisa bukan milik model tersebut atau sudah dipakai booking lain yang tumpang-tindih.
- **M7. Aksi admin.** Quick action status (TASK-023) dan bulk price (TASK-024) perlu nonce, capability check, whitelist nilai, dan batas angka (harga tidak boleh ≤ 0, persentase dibatasi).
- **M8. Save metabox.** Belum dipersyaratkan guard `DOING_AUTOSAVE`, `post_type`, dan `current_user_can('edit_post')`. Field harga/stok harus dibatasi ke `manage_ryokourent_settings`. Plat nomor disanitasi per baris.
- **M9. Inkonsistensi penamaan.** Role: "operator" / `role: operator` / `ryokou_operator` (aturan prefix menuntut `ryokourent_`). Status: `status_dikonfirmasi` (DATA_MODEL) vs `dikonfirmasi` dan `berjalan` (ARCHITECTURE §5.3). ARCHITECTURE §6.4 memakai `manage_options`, padahal capability custom sudah dirancang.
- **M10. Status publik manual vs komputasi.** `_ryokou_status_label` dipilih manual sehingga bisa menampilkan "Tersedia" saat kuota penuh.
- **M11. Aturan Bromo belum ditegakkan.** Form tidak punya field "tujuan trip"; aturan CRF 150L hanya berupa teks peringatan dan catatan bebas.
- **M12. Query dan performa.** Hindari `posts_per_page => -1` untuk sekadar menghitung. Statistik dashboard perlu transient. Sort kolom admin berdasarkan meta harus numerik.
- **M13. Siklus plugin.** Flush rewrite sebaiknya di activation hook, bukan manual lewat Permalinks (risiko TASK-004). `uninstall.php` sebaiknya memeriksa `WP_UNINSTALL_PLUGIN`. Folder `tests/` jangan ikut ke produksi.
- **M14. Drift dokumen.** BLUEPRINT masih menyebut ACF dan Fluent Forms/WS Form, sedangkan arsitektur final memakai meta `_ryokou_*` dan AJAX custom. `meta-fields.php`, `motor-columns.php`, dan `test-cpt-motor.php` tidak ada di struktur folder ARCHITECTURE. TASK-004 dan TASK-005 sama-sama menyentuh sanitasi meta sehingga berisiko duplikat atau bentrok.
- **M15. Ikon.** Pastikan `dashicons-car` tersedia di versi WP target.

## 3. Saran Perbaikan

| Untuk | Saran |
|---|---|
| K1, M9 | Petakan capability kedua CPT ke `manage_ryokourent_bookings` (penyewaan) dan `manage_ryokourent_settings` (motor), dengan `map_meta_cap` aktif. Beri kedua capability ke `administrator` saat aktivasi. Seragamkan nama role menjadi `ryokourent_operator` di semua dokumen. |
| K2 | Wajibkan cek ketersediaan di dua titik: saat submit dan saat status berubah ke `dikonfirmasi` (kecualikan booking itu sendiri). Bungkus keduanya dengan lock per motor (mis. `GET_LOCK` MySQL). Ubah TASK-017 agar menyebut titik konfirmasi secara eksplisit. |
| K3 | Sediakan select status sendiri di metabox penyewaan dan terapkan lewat filter `wp_insert_post_data` dengan nonce dan capability check. Set status `public => false`. Jangan menambah panjang slug status. Alternatif yang lebih sederhana: simpan status di meta dan pakai `post_status` standar. Ini keputusan arsitektur, catat di DECISIONS.md. |
| K4 | Semua meta internal `show_in_rest => false` dengan `auth_callback` ke `manage_ryokourent_settings`. Respons AJAX publik hanya boleh berisi status tersedia/tidak. Sisa kuota hanya untuk admin. |
| K5 | Server memparse tanggal secara ketat (format, error parsing), memvalidasi jam operasional, `end > start`, dan tanggal tidak di masa lalu, lalu menghitung ulang durasi dan harga. Abaikan nilai harga dari client. |
| K6 | Tandai snippet BLUEPRINT §7A sebagai *superseded*. Registrasi hanya di `includes/post-types.php` dengan prefix `ryokourent_`. |
| M1, M2 | Tambah hook `nopriv`. Ambil nonce segar lewat AJAX ringan atau kecualikan halaman booking dari cache. Tambah honeypot dan throttle sederhana (transient per hash IP). |
| M3 | Pilih satu format URL (disarankan `wa.me`). Redirect memakai navigasi langsung setelah respons, atau buka jendela secara sinkron lalu isi URL-nya. Gunakan `rawurlencode` untuk teks. |
| M4 | Tulis aturan pembulatan dan toleransi overtime di DATA_MODEL dan `pricing.php`. Untuk mingguan/bulanan, tentukan kombinasi (disarankan termurah). Tolak atau tandai harga kosong, jangan hitung sebagai 0. |
| M5 | Hapus metrik per-pool dari TASK-022, atau putuskan terpisah menambah data lokasi. Jangan dikerjakan diam-diam. Samakan juga soal "servis". |
| M6 | Validasi plat: harus ada di daftar plat model itu dan tidak dipakai booking lain pada rentang yang sama. |
| M7, M8 | Tetapkan checklist wajib di TASK-023/024/005: nonce, capability, whitelist, batas nilai, guard autosave. |
| M10 | Hitung label publik dari ketersediaan, atau jadikan manual sebagai override yang jelas. |
| M11 | Putuskan apakah cukup peringatan teks, atau perlu field tujuan trip. Jika ditambah, itu fitur baru dan perlu persetujuan. |
| M12 | Gunakan query yang hanya mengambil ID dan jumlah. Cache statistik dashboard dengan transient. |
| M13 | Pindahkan flush rewrite ke aktivasi. Kecualikan `tests/` dari paket produksi. |
| M14 | Perbarui BLUEPRINT dan ARCHITECTURE agar satu sumber kebenaran. Tambahkan file TASK-004 ke struktur folder. Tetapkan satu file pemilik sanitasi meta. |

## 4. File yang Terdampak

| File | Terkait |
|---|---|
| `includes/post-types.php` | K1, K3, K6, M15 |
| `includes/user-roles.php`, `ryokourent-core.php` (aktivasi) | K1, M9, M13 |
| `includes/meta-fields.php`, `includes/meta-boxes.php` | K4, M8, M14 |
| `includes/availability.php` | K2, K4, M6, M12 |
| `includes/booking.php` | K2, K5, M1, M2, M3 |
| `includes/pricing.php` | K5, M4 |
| `includes/whatsapp.php`, `assets/js/ryokourent-booking.js` | M3 |
| `admin/booking-columns.php`, `admin/admin-settings.php`, `admin/dashboard.php` | M5, M7, M12 |
| `BLUEPRINT.md`, `ARCHITECTURE.md`, `DATA_MODEL.md`, `TASKS.md` | K6, M5, M9, M14 |

## 5. Cara Pengujian Ulang

1. **Lint:** `php -l` semua file, lalu `phpcs --standard=WordPress-Core,WordPress-Security`. Aktifkan `WP_DEBUG` dan `WP_DEBUG_LOG`; log harus bersih setelah aktivasi.
2. **Capability (K1):** Login sebagai Author, Editor, Operator, dan Administrator. Author/Editor tidak boleh melihat menu Penyewaan/Armada. Operator hanya Penyewaan; URL edit motor harus 403. Administrator tetap akses penuh.
3. **REST (K4):** Saat logout, buka `/wp-json/wp/v2/motor` dan `/wp-json/wp/v2/penyewaan`. Tidak boleh ada stok atau plat, dan `penyewaan` harus 404. Respons AJAX ketersediaan hanya boolean.
4. **Status (K3):** Ubah status lewat metabox, klik Update, muat ulang. Status harus bertahan dan filter status di daftar admin berfungsi.
5. **Double booking (K2):** Dengan stok 1, buat booking A dan B (rentang sama, keduanya `menunggu`). Konfirmasi A, lalu coba konfirmasi B; B harus ditolak. Kirim dua request booking bersamaan; hanya satu yang lolos. Uji batas: selesai 17:00 lalu mulai 17:00 lolos, mulai 16:59 bentrok.
6. **Validasi server (K5):** Kirim POST manual dengan `total_price` palsu, jam 02:00, `end < start`, dan `motor_id` bukan CPT motor. Semuanya ditolak atau diabaikan, dan harga tersimpan adalah hasil hitungan server.
7. **Telepon:** Uji `0812-3456 7890`, `+62 812 3456 7890`, `62812...`, `12345`, dan kontak darurat sama dengan kontak utama.
8. **Nonce dan cache (M1):** Aktifkan cache halaman dan uji submit dari halaman lama sebagai pengunjung tanpa login.
9. **WhatsApp (M3):** Uji di iOS Safari dan Android Chrome dengan popup blocker aktif. Baris baru, emoji, `&`, dan `#` harus terkirim utuh.
10. **Aksi admin (M7):** Ulangi quick action dan bulk price tanpa nonce atau sebagai Operator; harus ditolak. Uji nilai negatif dan persentase ekstrem.
11. **Pricing (M4):** Setelah aturan diputuskan, jalankan `test-pricing.php` untuk 1, 3, 7, 35 hari, contoh 56,5 jam, dan harga kosong.
12. **Aktivasi/deaktivasi (M13):** Aktifkan lalu nonaktifkan plugin. `/motor/` langsung bisa dibuka tanpa simpan Permalinks manual, dan role/capability tetap konsisten.
