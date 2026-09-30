# CHECKLIST SEBELUM PRODUCTION (PRE-PRODUCTION CHECKLIST)

Sebelum situs Ryokourent dinyatakan siap diluncurkan (*Go-Live*) untuk publik, tim teknis dan operasional **WAJIB** menyelesaikan dan menandatangani seluruh butir pemeriksaan di bawah ini.

---

## 1. Konfigurasi Lingkungan Server & WordPress
- [ ] Versi PHP server minimal 8.1 dengan ekstensi `curl`, `json`, `mbstring`, `mysqli` aktif.
- [ ] Pengaturan `WP_DEBUG`, `WP_DEBUG_LOG`, dan `WP_DEBUG_DISPLAY` telah dimatikan (`false`) di `wp-config.php`.
- [ ] Opsi `DISALLOW_FILE_EDIT` diaktifkan (`true`) untuk mencegah modifikasi file via dashboard WordPress.
- [ ] Sertifikat SSL (HTTPS) terpasang valid dengan pengalihan otomatis seluruh traffic HTTP ke HTTPS.
- [ ] Zona waktu WordPress diatur ke **Asia/Jakarta** (`UTC+7`).
- [ ] Struktur permalink diset ke **Post name** (`/%postname%/`).
- [ ] Plugin LiteSpeed Cache atau caching statis telah terkonfigurasi dengan baik.

---

## 2. Konfigurasi Bisnis & Operasional Ryokourent
- [ ] Nomor WhatsApp admin resmi Ryokourent telah diperbarui pada pengaturan plugin dan telah diuji menerima pesan.
- [ ] Data 7 armada motor master (BeAT Deluxe, CBS, Street, Scoopy, Vario 125, Vario 160, CRF 150L) telah terinput lengkap dengan foto resolusi optimal (WebP).
- [ ] Kuota unit fisik awal dan daftar plat nomor motor telah diisi secara akurat oleh tim operasional di WP-Admin.
- [ ] Informasi alamat, jam operasional (07.00 - 23.00 WIB), dan tautan Google Maps untuk Pool Dinoyo Malang dan Pool Diponegoro Batu telah divalidasi.
- [ ] Akun staf operator telah dibuat dengan role `Ryokou Operator` dan password yang kuat.

---

## 3. Verifikasi Alur Pemesanan & Uji Coba Lapangan
- [ ] Uji coba simulasi booking via smartphone (Android Chrome & iPhone Safari):
  * Pengisian formulir berjalan mulus.
  * Durasi sewa dan estimasi tarif terhitung akurat.
  * Live preview draf WhatsApp menampilkan data lengkap.
  * Tautan WhatsApp (`wa.me`) membuka aplikasi WhatsApp dengan pesan rapi tanpa karakter rusak.
  * Transaksi otomatis tercatat pada menu CPT **Penyewaan Motor** di dashboard admin.
- [ ] Validasi pencegahan double booking berfungsi saat kuota suatu unit habis pada rentang tanggal tertentu.
- [ ] Peringatan larangan matik ke Bromo berfungsi dengan baik.

---

## 4. Keamanan Data & Cadangan (Backup & Disaster Recovery)
- [ ] Backup penuh (database MySQL dan folder `wp-content/`) berhasil dibuat dan disimpan di penyimpanan eksternal yang aman.
- [ ] Jadwal backup otomatis harian telah diaktifkan di server hosting.
- [ ] Prosedur rollback telah terdokumentasi dan dipahami oleh administrator.
