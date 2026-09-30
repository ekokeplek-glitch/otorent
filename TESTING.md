# TESTING SCENARIOS & QUALITY ASSURANCE: RYOKOURENT

Dokumen ini memuat skenario pengujian komprehensif dari tahap pengembangan awal hingga kesiapan produksi untuk sistem rental motor Ryokourent.

---

## 1. Lingkup & Sasaran Pengujian
1. **Integritas Plugin & Theme:** Plugin aktif tanpa fatal error atau notice.
2. **Keamanan & Otorisasi:** Nonce validation, sanitasi input, escaping output, proteksi akses role Operator vs Admin.
3. **Logika Bisnis & Validasi:**
   * Perhitungan durasi sewa akurat.
   * Perhitungan tarif harian, mingguan, dan bulanan akurat.
   * Pencegahan pemilihan tanggal masa lalu / jam di luar operasional (07.00 - 23.00 WIB).
   * Validasi armada Bromo (kewajiban Trail CRF 150L).
   * Validasi nomor WhatsApp & kontak darurat.
   * Pengecekan ketersediaan kuota unit & pencegahan *double booking*.
4. **Generator WhatsApp:** Format draf pesan rapi, data lengkap, dan tautan `wa.me` valid.
5. **Responsivitas & UI/UX Mobile:** Tampilan bebas overflow pada viewport smartphone, tombol sentuh ergonomis.

---

## 2. Tabel Matriks Pengujian Skenario

| ID | Skenario | Input Uji | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-001** | Aktivasi Plugin Core | Aktifkan `ryokourent-core` di WP-Admin | Plugin aktif tanpa pesan error atau warning PHP | Menunggu Eksekusi | READY | Pastikan `WP_DEBUG: true` aktif saat pengujian |
| **TC-002** | Registrasi CPT Motor | Buka WP-Admin -> Armada Motor | Menu muncul dengan ikon mobil/motor, form input motor siap | Menunggu Eksekusi | READY | Verifikasi kemampuan upload gambar unit |
| **TC-003** | Penyimpanan Data Teknis Motor | Isi spesifikasi cc, transmisi, harga harian/mingguan/bulanan, stok fisik | Data tersimpan utuh di post meta dan tampil kembali saat diedit | Menunggu Eksekusi | READY | Cek verifikasi nonce metabox |
| **TC-004** | Proteksi Data Kuota Fisik di Frontend | Buka katalog publik dan view-source HTML | Stok fisik (angka) dan plat nomor tidak muncul di DOM publik | Menunggu Eksekusi | READY | Hanya status "Tersedia" / "Menipis" yang tampil |
| **TC-005** | Filter Kategori Katalog | Klik tab "BeAT Series", "Scoopy-Vario", "Trail Adventure" | Grid motor memfilter unit secara instan tanpa reload halaman | Menunggu Eksekusi | READY | Diuji pada mobile viewport (390px) |
| **TC-006** | Validasi Input Wajib Form Booking | Submit form dalam keadaan seluruh field kosong | Form menolak submit, border merah pada field wajib, pesan peringatan | Menunggu Eksekusi | READY | Validasi HTML5 + JavaScript |
| **TC-007** | Validasi Format Nomor WhatsApp | Input No. WA: `12345` atau `abcdefgh` | Ditolak dengan pesan: "Nomor WhatsApp tidak valid (Gunakan format 08xx / 62xx)" | Menunggu Eksekusi | READY | Regex: `^(08|628)[0-9]{8,12}$` |
| **TC-008** | Validasi Kontak Darurat Unik | Input No. Darurat sama persis dengan No. WhatsApp pelanggan | Ditolak dengan pesan: "Kontak darurat harus berbeda dari nomor penyewa" | Menunggu Eksekusi | READY | Verifikasi independensi kontak penjamin |
| **TC-009** | Validasi Tanggal Sewa Kronologis | Set Tanggal Selesai lebih awal dari Tanggal Mulai | Ditolak dengan pesan: "Tanggal selesai tidak boleh mendahului tanggal mulai" | Menunggu Eksekusi | READY | Validasi client-side & server-side |
| **TC-010** | Validasi Jam Operasional Pool | Pilih jam sewa: `03:00 WIB` atau `23:45 WIB` | Ditolak dengan pesan: "Layanan sewa & ambil unit hanya pukul 07.00 - 23.00 WIB" | Menunggu Eksekusi | READY | Mematuhi jam kerja 2 pool resmi |
| **TC-011** | Aturan Wajib Bromo untuk Skutik | Pilih rute "Kaldera Bromo" dengan motor "Honda BeAT" | Muncul peringatan keras larangan matik ke Bromo dan otomatis merekomendasikan Trail CRF 150L | Menunggu Eksekusi | READY | Sesuai ADR-004 demi keselamatan |
| **TC-012** | Kalkulator Durasi Sewa Otomatis | Mulai: 02/10 08:30, Selesai: 04/10 17:00 | Label menampilkan: "Estimasi Durasi: 3 Hari (~57 Jam)" | Menunggu Eksekusi | READY | Toleransi overtime terhitung proporsional |
| **TC-013** | Kalkulasi Tarif Sewa Harian | BeAT Deluxe (Rp 85.000/hari) x 3 hari sewa | Estimasi Total Biaya menampilkan: "Rp 255.000" | Menunggu Eksekusi | READY | Format Rupiah dengan pemisah ribuan titik |
| **TC-014** | Pengecekan Ketersediaan Unit (Stok Tersedia) | Pesan unit Vario 160 (stok fisik 5, booking aktif 2) | Form menyetujui, tombol booking aktif, status ketersediaan lolos | Menunggu Eksekusi | READY | Sisa unit masih ada 3 |
| **TC-015** | Pencegahan Double Booking (Stok Penuh) | Pesan unit CRF 150L (stok fisik 3, booking aktif 3 pada tanggal sama) | Submit diblokir dengan pesan: "Unit penuh pada tanggal tersebut" | Menunggu Eksekusi | READY | Mencegah over-booking armada |
| **TC-016** | Penyimpanan CPT Penyewaan via AJAX | Klik "Kirim Pesanan ke WhatsApp" | Post baru terbuat pada CPT `penyewaan` dengan status `status_menunggu` dan kode `RYK-...` | Menunggu Eksekusi | READY | Cek data tersimpan di WP-Admin |
| **TC-017** | Format & Encoding Pesan WhatsApp | Verifikasi link URL redirect WhatsApp | Pesan rapi berformat teks blueprint, emotikon utuh, URL ter-encode dengan benar | Menunggu Eksekusi | READY | Menggunakan `rawurlencode` / `encodeURIComponent` |
| **TC-018** | Pembatasan Akses Role Operator | Login sebagai akun Operator, akses halaman Pengaturan Tarif | Akses ditolak (403 Forbidden / "Anda tidak memiliki wewenang") | Menunggu Eksekusi | READY | Hak admin terlindungi secara ketat |
| **TC-019** | Perubahan Status Booking oleh Operator | Ubah status dari "Menunggu" -> "Dikonfirmasi" dan masukkan plat motor | Status berubah warna jadi biru, plat nomor tersimpan di meta booking | Menunggu Eksekusi | READY | Quick update status di tabel admin |
| **TC-020** | Audit Responsivitas & Thumb-Zone Mobile | Buka web di smartphone (layar 375px - 414px) | Floating bar < 15% layar, tombol WA mudah dijangkau jempol, tidak ada horizontal scroll | Menunggu Eksekusi | READY | Mobile usability check |

---

## 3. Tahapan Pengujian Produksi (Pre-Launch Checklist)
* [ ] Konfigurasi timezone WordPress diatur ke `Asia/Jakarta` (WIB, UTC+7).
* [ ] Permalink diatur ke format `/post-name/`.
* [ ] Mode `WP_DEBUG` dimatikan (`false`) dan `WP_DEBUG_LOG` dimatikan di production.
* [ ] Nomor WhatsApp resmi Ryokourent telah diperbarui di halaman pengaturan admin.
* [ ] 7 model motor telah diinput dengan spesifikasi dan foto asli resolusi optimal (WebP).
* [ ] Kuota unit fisik awal dan daftar plat nomor motor telah diverifikasi oleh tim operasional.
* [ ] Akun staf operator telah dibuat dengan password kuat dan role `Ryokou Operator`.
* [ ] Sertifikat SSL aktif (HTTPS) dan redirect HTTP -> HTTPS berjalan lancar.
