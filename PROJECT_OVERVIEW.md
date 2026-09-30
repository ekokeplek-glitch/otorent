# PROJECT OVERVIEW: RYOKOURENT

## 1. Identitas & Tujuan Proyek
* **Nama Brand:** Ryokourent
* **Kategori Bisnis:** Rental Sepeda Motor & Adventure Fleet di Malang Raya & Kota Wisata Batu.
* **Tujuan Utama:**
  Membangun website rental motor berbasis WordPress yang *mobile-first*, cepat (< 1.5 detik), aman, dan mengutamakan alur pemesanan *zero-friction WhatsApp booking*. Sistem mencakup pencatatan data penyewaan di WordPress (Custom Post Type), verifikasi ketersediaan armada, kalkulasi harga/durasi sewa, manajemen peran (Admin vs Operator), serta kepatuhan aturan rute (misal: kewajiban Honda Trail CRF 150L untuk ke Bromo).

## 2. Target Pengguna
1. **Wisatawan Nusantara & Mancanegara:** Pelancong yang berkunjung ke Malang, Kota Wisata Batu, dan kawasan Taman Nasional Bromo Tengger Semeru yang membutuhkan mobilitas praktis tanpa macet.
2. **Mahasiswa:** Komunitas mahasiswa perguruan tinggi Malang (UB, UIN, UM, dll.) yang memerlukan persewaan harian, mingguan, atau bulanan.
3. **Penyewa Harian & Bisnis:** Pengguna yang membutuhkan transportasi gesit untuk keperluan kerja, dinas, atau silaturahmi.
4. **Admin & Operator Ryokourent (Internal):** Tim operasional yang mengelola kuota unit fisik, konfirmasi booking WhatsApp, verifikasi identitas e-KTP, dan status sewa kendaraan.

## 3. Ruang Lingkup MVP (Minimum Viable Product)
* **Frontend Mobile-First:**
  * Top bar responsif (Brand, Navigasi, Tombol Cepat Booking WA).
  * Hero section dengan headline, 3 trust badge, dan CTA ganda.
  * 6 kartu keunggulan layanan.
  * Katalog motor dengan filter kategori (BeAT Series, Scoopy-Vario Series, Trail CRF 150L).
  * Fitur asisten konsultan rute wisata (rekomendasi unit berdasarkan medan Malang, Batu, atau Bromo).
  * Panduan cara sewa 3 langkah.
  * Profil 2 Pool resmi (Dinoyo Malang & Diponegoro Batu) beserta info jam operasional (07.00 - 23.00 WIB) & ketentuan antar-jemput.
  * Syarat, ketentuan & FAQ accordion interaktif.
  * Form booking interaktif dengan kalkulator durasi sewa & generator format pesan WhatsApp resmi.
  * Tombol floating mobile bar (< 15% viewport).
* **Backend & Logika Bisnis (Plugin `ryokourent-core`):**
  * CPT `motor`: Pengelolaan model unit, spesifikasi mesin, karakter rute, harga harian/mingguan/bulanan, dan kuota unit fisik internal.
  * CPT `penyewaan`: Penyimpanan rekaman booking dari form web dengan status kustom: `menunggu`, `dikonfirmasi`, `berjalan`, `selesai`, `dibatalkan`.
  * Validasi ketersediaan unit dan pencegahan *double booking* berbasis tanggal & jam sewa.
  * Role-Based Access Control (RBAC): Pemisahan wewenang Administrator (akses penuh, harga, kuota unit, user staf) vs Operator (operasional harian, ubah status pesanan, verifikasi dokumen).
  * Dashboard ringkas untuk operator/admin memantau unit jalan, unit servis, dan booking masuk.

## 4. Fitur yang Ditunda (Post-MVP / Tahap Lanjutan)
1. **Online Payment Gateway (Midtrans/Xendit):** Sesuai aturan kerja, pembayaran langsung melalui gateway ditunda hingga sistem operasional booking WhatsApp dan manual DP stabil.
2. **Penyimpanan Upload Foto e-KTP / Dokumen Identitas di Server:** Untuk mematuhi privasi data dan keamanan data pribadi pelanggan pada fase awal, verifikasi e-KTP dan 2 dokumen pendukung dilakukan secara langsung di jalur privat WhatsApp admin.
3. **Integrasi GPS Pelacak Real-Time Unit:** Integrasi API telematika GPS pada armada fisik akan dikembangkan pada fase Enterprise.
4. **Multi-Cabang di Luar Malang-Batu:** Sistem saat ini difokuskan penuh pada 2 Pool (Dinoyo Malang & Diponegoro Batu).

## 5. Asumsi & Aturan Bisnis
* **Kerahasiaan Kuota Fisik:** Jumlah unit fisik dan plat nomor motor adalah data internal operasional yang **TIDAK** ditampilkan ke publik.
* **Jam Operasional:** Booking dan serah terima unit dilayani antara pukul 07.00 – 23.00 WIB.
* **Batas Wilayah Layanan:** Penggunaan motor terbatas di Malang Raya & Kota Batu. Penggunaan keluar wilayah wajib persetujuan tertulis dari Admin.
* **Aturan Keras Bromo:** Unit matik (BeAT, Scoopy, Vario) **dilarang keras** memasuki lautan pasir Bromo. Trip Bromo wajib menyewa Honda Trail CRF 150L.
* **Kelengkapan Sewa:** Setiap persewaan sudah mencakup 2 Helm SNI bersih dan  Jas Hujan.
