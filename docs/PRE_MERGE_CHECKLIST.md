# CHECKLIST SEBELUM MERGE (PRE-MERGE CHECKLIST)

Setiap Pull Request (PR) ke branch `develop` atau `main` **WAJIB** memenuhi seluruh kriteria verifikasi berikut sebelum disetujui dan di-merge.

---

## 1. Verifikasi Integritas Kode
- [ ] Kode mematuhi standar penamaan prefix `ryokourent_` atau `ryokou_`.
- [ ] Setiap file PHP memiliki proteksi eksekusi langsung `if (!defined('ABSPATH')) exit;`.
- [ ] Tidak ada PHP Fatal Error, Warning, atau Notice saat dijalankan dengan `WP_DEBUG: true`.
- [ ] Lulus pengecekan PHP_CodeSniffer dengan aturan `.phpcs.xml.dist`.
- [ ] Tidak ada file cache, log (`*.log`), atau direktori sementara yang ter-commit.

---

## 2. Verifikasi Keamanan (Security Audit)
- [ ] Seluruh input pengguna disanitasi (`sanitize_text_field`, `absint`, dll.).
- [ ] Seluruh output yang ditampilkan ke HTML telah di-escape (`esc_html`, `esc_attr`, `esc_url`).
- [ ] Seluruh request formulir atau AJAX yang memodifikasi data menyertakan verifikasi Nonce.
- [ ] Seluruh aksi admin memeriksa hak akses pengguna (`current_user_can`).
- [ ] Seluruh kueri manual database `$wpdb` menggunakan prepared statement (`$wpdb->prepare`).
- [ ] Tidak ada token rahasia, nomor WhatsApp pribadi, atau kredensial database di dalam commit.
- [ ] Tidak ada berkas identitas pelanggan (KTP/SIM) yang diunggah ke server web.

---

## 3. Verifikasi Logika Bisnis & Performa
- [ ] Perubahan kode sesuai dengan ruang lingkup task aktif di `TASKS.md`.
- [ ] Tidak mengubah file fitur lain tanpa alasan dan dokumentasi jelas.
- [ ] Validasi rute Bromo (larangan matik & kewajiban Trail CRF 150L) tetap utuh.
- [ ] Kerahasiaan kuota fisik dan plat nomor motor tetap terlindungi dari sisi publik.
- [ ] Ukuran stylesheet dan script tetap ramping tanpa pustaka pihak ketiga berlebihan.

---

## 4. Verifikasi Dokumentasi & Pengujian
- [ ] Langkah pengujian (*how to test*) tercantum jelas pada deskripsi PR.
- [ ] Hasil pengujian manual atau otomatis telah diverifikasi dan dicatat pada `TESTING.md`.
- [ ] Berkas `CHANGELOG.md` telah diperbarui jika terdapat penambahan fitur atau perbaikan bug.
