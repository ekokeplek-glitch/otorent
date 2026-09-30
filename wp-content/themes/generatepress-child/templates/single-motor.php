<?php
/**
 * Single Motor Post Template (CPT motor)
 *
 * Displays detailed fleet specifications, route characteristics,
 * mandatory Bromo advisory warning, included facilities, and fast booking CTA.
 *
 * @package GeneratePress_Child_Ryokourent
 * @since   1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue public plugin styles if available
if (function_exists('wp_enqueue_style')) {
    wp_enqueue_style('ryokourent-public');
}

while (have_posts()) :
    the_post();
    $post_id        = get_the_ID();
    $title          = get_the_title();
    $engine_cc      = get_post_meta($post_id, '_ryokou_engine_cc', true);
    $transmission   = get_post_meta($post_id, '_ryokou_transmission', true);
    $route_char     = get_post_meta($post_id, '_ryokou_route_character', true);
    $is_bromo_ready = get_post_meta($post_id, '_ryokou_is_bromo_ready', true) === 'yes';
    $status_label   = get_post_meta($post_id, '_ryokou_status_label', true);
    if (empty($status_label)) {
        $status_label = 'Tersedia';
    }

    $price_daily    = get_post_meta($post_id, '_ryokou_price_daily', true);
    $price_weekly   = get_post_meta($post_id, '_ryokou_price_weekly', true);
    $price_monthly  = get_post_meta($post_id, '_ryokou_price_monthly', true);

    $wa_number      = get_option('ryokourent_wa_number', defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $clean_wa       = preg_replace('/[^0-9]/', '', (string) $wa_number);
    $wa_text        = "Halo Admin Ryokourent, saya ingin menyewa unit " . $title . " di Malang/Batu. Mohon info ketersediaan slot.";
    $wa_url         = 'https://api.whatsapp.com/send?phone=' . esc_attr($clean_wa) . '&text=' . rawurlencode($wa_text);

    // Categories
    $categories     = get_the_terms($post_id, 'kategori_motor');
    $category_name  = (!empty($categories) && !is_wp_error($categories)) ? $categories[0]->name : 'Armada Motor';
    ?>

    <div class="ryokou-single-motor-wrapper">
        <div class="ryokou-single-container">
            <!-- Navigation Back Bar -->
            <nav class="ryokou-single-nav" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/#katalog-motor')); ?>" class="ryokou-back-link">
                    <span class="ryokou-back-arrow">&larr;</span> Kembali ke Katalog Armada
                </a>
                <span class="ryokou-nav-separator">/</span>
                <span class="ryokou-nav-current"><?php echo esc_html($title); ?></span>
            </nav>

            <div class="ryokou-single-layout">
                <!-- Left Column: Details & Specs -->
                <div class="ryokou-single-main">
                    <div class="ryokou-single-gallery">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="ryokou-single-media">
                                <?php the_post_thumbnail('large', array('class' => 'ryokou-single-img', 'alt' => esc_attr($title))); ?>
                            </div>
                        <?php else : ?>
                            <div class="ryokou-single-media-placeholder">
                                <span class="ryokou-placeholder-icon">🛵</span>
                                <span class="ryokou-placeholder-title"><?php echo esc_html($title); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Top Badges -->
                        <div class="ryokou-single-badges">
                            <span class="ryokou-badge ryokou-badge-cat"><?php echo esc_html($category_name); ?></span>
                            <span class="ryokou-badge <?php echo ($status_label === 'Tersedia') ? 'ryokou-status-available' : (($status_label === 'Penuh') ? 'ryokou-status-full' : 'ryokou-status-warning'); ?>">
                                <span class="ryokou-badge-dot"></span> <?php echo esc_html($status_label); ?>
                            </span>
                            <?php if ($is_bromo_ready) : ?>
                                <span class="ryokou-badge ryokou-badge-bromo">🌋 Wajib Bromo</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <header class="ryokou-single-header">
                        <h1 class="ryokou-single-title"><?php echo esc_html($title); ?></h1>
                        <p class="ryokou-single-subtitle">
                            Rental motor harian, mingguan, dan bulanan terpercaya di Malang Raya & Kota Wisata Batu.
                        </p>
                    </header>

                    <!-- Technical Specs Grid -->
                    <div class="ryokou-single-specs-card">
                        <h3 class="ryokou-specs-title">Spesifikasi Kendaraan</h3>
                        <div class="ryokou-single-specs-grid">
                            <div class="ryokou-spec-box">
                                <span class="ryokou-spec-icon">⚡</span>
                                <div>
                                    <span class="ryokou-spec-label">Kapasitas Mesin</span>
                                    <strong class="ryokou-spec-val"><?php echo esc_html($engine_cc ? $engine_cc . ' cc' : '110 cc eSP'); ?></strong>
                                </div>
                            </div>
                            <div class="ryokou-spec-box">
                                <span class="ryokou-spec-icon">⚙️</span>
                                <div>
                                    <span class="ryokou-spec-label">Tipe Transmisi</span>
                                    <strong class="ryokou-spec-val"><?php echo esc_html($transmission ? $transmission : 'Otomatis (CVT)'); ?></strong>
                                </div>
                            </div>
                            <div class="ryokou-spec-box">
                                <span class="ryokou-spec-icon">🛣️</span>
                                <div>
                                    <span class="ryokou-spec-label">Karakter Rute</span>
                                    <strong class="ryokou-spec-val"><?php echo esc_html($route_char ? $route_char : 'Lincah & Nyaman'); ?></strong>
                                </div>
                            </div>
                            <div class="ryokou-spec-box">
                                <span class="ryokou-spec-icon">⛽</span>
                                <div>
                                    <span class="ryokou-spec-label">Bahan Bakar</span>
                                    <strong class="ryokou-spec-val">Bensin / PGM-FI Irit</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bromo Advisory Callout -->
                    <?php if ($is_bromo_ready) : ?>
                        <div class="ryokou-advisory-box ryokou-advisory-bromo">
                            <div class="ryokou-advisory-icon">🌋</div>
                            <div class="ryokou-advisory-content">
                                <h4 class="ryokou-advisory-title">Armada Resmi & Disetujui Trip Bromo</h4>
                                <p class="ryokou-advisory-desc">
                                    Unit ini dibekali suspensi upside-down Showa dan ban dual-purpose yang tangguh untuk melibas lautan pasir berbisik, tanjakan Penanjakan, dan bukit teletubbies Bromo dengan aman dan stabil.
                                </p>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="ryokou-advisory-box ryokou-advisory-city">
                            <div class="ryokou-advisory-icon">⚠️</div>
                            <div class="ryokou-advisory-content">
                                <h4 class="ryokou-advisory-title">Ketentuan Rute: Khusus Malang Kota & Kota Wisata Batu</h4>
                                <p class="ryokou-advisory-desc">
                                    <strong>Dilarang keras dibawa ke lautan pasir Bromo.</strong> Transmisi otomatis skutik rawan mengalami slip pada medan pasir dan kampas ganda terbakar. Untuk perjalanan ke Gunung Bromo, Anda wajib menyewa unit <strong>Trail CRF 150L</strong> demi keselamatan jiwa.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Included Free Equipment -->
                    <div class="ryokou-single-inclusions">
                        <h3 class="ryokou-inclusions-title">Fasilitas Standar Setiap Sewa (Gratis)</h3>
                        <div class="ryokou-inclusions-grid">
                            <div class="ryokou-inclusion-item">
                                <span class="ryokou-inclusion-icon">🪖</span>
                                <div>
                                    <strong>2 Helm SNI Higienis</strong>
                                    <span>Pilihan kaca jernih & wangi, ukuran M / L / XL</span>
                                </div>
                            </div>
                            <div class="ryokou-inclusion-item">
                                <span class="ryokou-inclusion-icon">🌧️</span>
                                <div>
                                    <strong>2 Jas Hujan Setelan</strong>
                                    <span>Model baju & celana tebal anti bocor</span>
                                </div>
                            </div>
                            <div class="ryokou-inclusion-item">
                                <span class="ryokou-inclusion-icon">📱</span>
                                <div>
                                    <strong>Holder HP Stang Kuat</strong>
                                    <span>Memudahkan navigasi GPS saat berkendara</span>
                                </div>
                            </div>
                            <div class="ryokou-inclusion-item">
                                <span class="ryokou-inclusion-icon">🔧</span>
                                <div>
                                    <strong>Layanan Bantuan Darurat</strong>
                                    <span>Dukungan teknis sigap untuk area Malang & Batu</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Description -->
                    <div class="ryokou-single-desc">
                        <h3 class="ryokou-desc-title">Deskripsi & Performa</h3>
                        <div class="ryokou-entry-content">
                            <?php
                            if (get_the_content()) {
                                the_content();
                            } else {
                                echo '<p>Armada pilihan terbaik untuk kebutuhan mobilitas Anda di Malang dan Kota Batu. Unit selalu dicek menyeluruh sebelum keberangkatan, meliputi tekanan angin ban, sistem pengereman, kelistrikan, dan oli mesin.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sticky Booking & Pricing Widget -->
                <aside class="ryokou-single-sidebar">
                    <div class="ryokou-pricing-card">
                        <div class="ryokou-pricing-header">
                            <span class="ryokou-pricing-badge">Tarif Resmi</span>
                            <div class="ryokou-pricing-main">
                                <span class="ryokou-price-lg">
                                    <?php echo (is_numeric($price_daily) && floatval($price_daily) > 0) ? 'Rp ' . number_format(floatval($price_daily), 0, ',', '.') : 'Tanya Admin'; ?>
                                </span>
                                <span class="ryokou-price-period">/ 24 Jam</span>
                            </div>
                            <span class="ryokou-pricing-overtime">*Toleransi overtime sewa hingga 2 jam</span>
                        </div>

                        <!-- Tier Rates -->
                        <div class="ryokou-pricing-tiers">
                            <div class="ryokou-tier-row">
                                <span class="ryokou-tier-name">Paket Mingguan (7 Hari)</span>
                                <strong class="ryokou-tier-val">
                                    <?php echo (is_numeric($price_weekly) && floatval($price_weekly) > 0) ? 'Rp ' . number_format(floatval($price_weekly), 0, ',', '.') : 'Hubungi Admin'; ?>
                                </strong>
                            </div>
                            <div class="ryokou-tier-row">
                                <span class="ryokou-tier-name">Paket Bulanan (30 Hari)</span>
                                <strong class="ryokou-tier-val">
                                    <?php echo (is_numeric($price_monthly) && floatval($price_monthly) > 0) ? 'Rp ' . number_format(floatval($price_monthly), 0, ',', '.') : 'Hubungi Admin'; ?>
                                </strong>
                            </div>
                        </div>

                        <!-- CTA Actions -->
                        <div class="ryokou-sidebar-actions">
                            <a href="<?php echo esc_url(home_url('/#booking-form?motor_id=' . $post_id)); ?>" class="ryokou-btn ryokou-btn-primary ryokou-btn-full">
                                <span class="ryokou-btn-icon">⚡</span>
                                <span>Pesan Motor Ini Sekarang</span>
                            </a>
                            <a href="<?php echo esc_url($wa_url); ?>" class="ryokou-btn ryokou-btn-whatsapp ryokou-btn-full" target="_blank" rel="noopener noreferrer">
                                <span class="ryokou-btn-icon">💬</span>
                                <span>Chat WhatsApp Admin</span>
                            </a>
                        </div>

                        <!-- Quick Requirements Info -->
                        <div class="ryokou-quick-requirements">
                            <h5 class="ryokou-req-title">Syarat Sewa Cepat:</h5>
                            <ul class="ryokou-req-list">
                                <li>e-KTP Asli (wajib dibawa saat serah terima)</li>
                                <li>2 Identitas Pendukung (SIM A / NPWP / BPJS / KTM / Paspor)</li>
                                <li>Akun media sosial aktif untuk verifikasi</li>
                            </ul>
                        </div>

                        <!-- Pool & Pickup Info -->
                        <div class="ryokou-pool-info">
                            <span class="ryokou-pool-title">📍 Lokasi Serah Terima:</span>
                            <p class="ryokou-pool-desc">
                                • Pool 1: Dinoyo, Lowokwaru, Malang<br>
                                • Pool 2: Jl. Diponegoro, Kota Wisata Batu<br>
                                • Layanan antar ke Stasiun Malang & Hotel (Sikon)
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <?php
endwhile;

get_footer();
