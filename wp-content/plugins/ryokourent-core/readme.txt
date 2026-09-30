=== Ryokourent Core ===
Contributors: ryokourent
Tags: motor, rental, booking, whatsapp, generatepress
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Logika bisnis utama dan alur pemesanan WhatsApp untuk rental motor Ryokourent (Malang & Batu).

== Description ==

Plugin ini menyediakan fungsionalitas inti untuk website rental motor Ryokourent:
* Custom Post Type Armada Motor (`motor`).
* Custom Post Type Pemesanan Motor (`penyewaan`).
* Status booking kustom (`menunggu`, `dikonfirmasi`, `berjalan`, `selesai`, `dibatalkan`).
* Validasi ketersediaan unit dan pencegahan double booking.
* Mesin kalkulasi tarif harian, mingguan, dan bulanan.
* Generator pesan WhatsApp resmi langsung ke admin.
* Role Operator dengan pembatasan akses pengaturan tarif.

== Installation ==

1. Upload direktori `ryokourent-core` ke direktori `/wp-content/plugins/`.
2. Aktifkan plugin melalui menu 'Plugins' di WordPress.
3. Pastikan tema GeneratePress dan child theme `generatepress-child` telah aktif.

== Changelog ==

= 1.0.0 =
* Initial project setup and architecture skeleton.
