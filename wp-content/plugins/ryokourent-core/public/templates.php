<?php
/**
 * Catalog & Motor Card Template Rendering for Ryokourent
 *
 * Provides template helper functions to output mobile-first motor cards,
 * tabbed category filters, specs badges, pricing breakdown, and CTA buttons.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/public
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format motor price into standard Indonesian Rupiah string or placeholder.
 *
 * @since 1.0.0
 * @param mixed  $price  Numeric price value or empty.
 * @param string $suffix Optional suffix (e.g. '/ 24 Jam').
 * @param string $fallback Fallback text when price is 0 or empty.
 * @return string Formatted price HTML.
 */
function ryokourent_format_catalog_price($price, $suffix = '', $fallback = 'Tanya Admin') {
    $price_num = is_numeric($price) ? floatval($price) : 0;
    if ($price_num <= 0) {
        return '<span class="ryokou-price-placeholder">' . esc_html($fallback) . '</span>';
    }

    $formatted = 'Rp ' . number_format($price_num, 0, ',', '.');
    if (!empty($suffix)) {
        $formatted .= ' <span class="ryokou-price-period">' . esc_html($suffix) . '</span>';
    }

    return $formatted;
}

/**
 * Render single motor card HTML.
 *
 * @since 1.0.0
 * @param WP_Post|int $post_or_id Post object or Post ID.
 * @param array       $custom_data Optional array to override/mock data.
 * @return string HTML card output.
 */
function ryokourent_render_motor_card($post_or_id, $custom_data = array()) {
    $post = is_object($post_or_id) ? $post_or_id : get_post($post_or_id);
    $post_id = $post ? $post->ID : (is_numeric($post_or_id) ? intval($post_or_id) : 0);

    // Default metadata extraction
    $title           = $post ? get_the_title($post) : (isset($custom_data['title']) ? $custom_data['title'] : 'Armada Motor');
    $permalink       = $post ? get_permalink($post) : (isset($custom_data['permalink']) ? $custom_data['permalink'] : '#');
    $engine_cc       = $post ? get_post_meta($post_id, '_ryokou_engine_cc', true) : (isset($custom_data['engine_cc']) ? $custom_data['engine_cc'] : '');
    $transmission    = $post ? get_post_meta($post_id, '_ryokou_transmission', true) : (isset($custom_data['transmission']) ? $custom_data['transmission'] : '');
    $route_char      = $post ? get_post_meta($post_id, '_ryokou_route_character', true) : (isset($custom_data['route_character']) ? $custom_data['route_character'] : '');
    $is_bromo_ready  = $post ? (get_post_meta($post_id, '_ryokou_is_bromo_ready', true) === 'yes') : (!empty($custom_data['is_bromo_ready']));
    $status_label    = $post ? get_post_meta($post_id, '_ryokou_status_label', true) : (isset($custom_data['status_label']) ? $custom_data['status_label'] : 'Tersedia');
    $price_daily     = $post ? get_post_meta($post_id, '_ryokou_price_daily', true) : (isset($custom_data['price_daily']) ? $custom_data['price_daily'] : 0);
    $price_weekly    = $post ? get_post_meta($post_id, '_ryokou_price_weekly', true) : (isset($custom_data['price_weekly']) ? $custom_data['price_weekly'] : 0);
    $price_monthly   = $post ? get_post_meta($post_id, '_ryokou_price_monthly', true) : (isset($custom_data['price_monthly']) ? $custom_data['price_monthly'] : 0);

    // Fallback status if empty
    if (empty($status_label)) {
        $status_label = 'Tersedia';
    }

    // Determine category slugs for data-category filter attribute
    $category_slugs = array();
    $category_names = array();
    if ($post) {
        $terms = get_the_terms($post_id, 'kategori_motor');
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                $category_slugs[] = $t->slug;
                $category_names[] = $t->name;
            }
        }
    } elseif (isset($custom_data['category_slugs'])) {
        $category_slugs = (array) $custom_data['category_slugs'];
        $category_names = isset($custom_data['category_names']) ? (array) $custom_data['category_names'] : $category_slugs;
    }

    $category_attr = esc_attr(implode(' ', $category_slugs));
    $category_display = !empty($category_names) ? esc_html($category_names[0]) : 'Armada Ryokou';

    // Status badge class
    $status_slug = sanitize_title($status_label);
    $status_class = 'ryokou-badge-status-' . $status_slug;
    if ($status_label === 'Tersedia') {
        $status_class = 'ryokou-status-available';
    } elseif ($status_label === 'Booking Menipis') {
        $status_class = 'ryokou-status-warning';
    } elseif ($status_label === 'Penuh') {
        $status_class = 'ryokou-status-full';
    }

    // WhatsApp link preparation
    $wa_number = get_option('ryokourent_wa_number', defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $wa_number_clean = preg_replace('/[^0-9]/', '', (string) $wa_number);
    $wa_message = "Halo Admin Ryokourent, saya ingin sewa motor " . $title . " di Malang/Batu. Apakah masih tersedia?";
    $wa_url = 'https://api.whatsapp.com/send?phone=' . esc_attr($wa_number_clean) . '&text=' . rawurlencode($wa_message);

    // Thumbnail image
    $thumbnail_html = '';
    if ($post && has_post_thumbnail($post_id)) {
        $thumbnail_html = get_the_post_thumbnail($post_id, 'medium_large', array(
            'class'   => 'ryokou-card-img',
            'alt'     => esc_attr($title),
            'loading' => 'lazy',
        ));
    } elseif (!empty($custom_data['image_url'])) {
        $thumbnail_html = '<img src="' . esc_url($custom_data['image_url']) . '" alt="' . esc_attr($title) . '" class="ryokou-card-img" loading="lazy" />';
    } else {
        // High quality stylized placeholder
        $thumbnail_html = '<div class="ryokou-card-img-placeholder">
            <span class="ryokou-card-placeholder-icon">🛵</span>
            <span class="ryokou-card-placeholder-text">' . esc_html($title) . '</span>
        </div>';
    }

    ob_start();
    ?>
    <article class="ryokou-motor-card" data-motor-id="<?php echo esc_attr($post_id); ?>" data-motor-name="<?php echo esc_attr($title); ?>" data-category="<?php echo $category_attr; ?>">
        <div class="ryokou-card-media">
            <a href="<?php echo esc_url($permalink); ?>" class="ryokou-card-media-link" aria-label="<?php echo esc_attr($title); ?>">
                <?php echo $thumbnail_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>

            <!-- Floating Badges Top -->
            <div class="ryokou-card-top-badges">
                <span class="ryokou-badge <?php echo esc_attr($status_class); ?>">
                    <span class="ryokou-badge-dot"></span>
                    <?php echo esc_html($status_label); ?>
                </span>

                <?php if ($is_bromo_ready) : ?>
                    <span class="ryokou-badge ryokou-badge-bromo" title="Armada Resmi & Wajib Rute Kaldera Pasir Bromo">
                        <span class="ryokou-badge-icon">🌋</span> Bromo Ready
                    </span>
                <?php else : ?>
                    <span class="ryokou-badge ryokou-badge-city" title="Hanya untuk rute Malang & Wisata Batu. Dilarang ke Lautan Pasir Bromo.">
                        Malang & Batu
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ryokou-card-content">
            <div class="ryokou-card-header">
                <span class="ryokou-category-label"><?php echo $category_display; ?></span>
                <h3 class="ryokou-motor-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>
            </div>

            <!-- Specs Grid -->
            <div class="ryokou-specs-grid">
                <?php if (!empty($engine_cc)) : ?>
                    <div class="ryokou-spec-item" title="Kapasitas Mesin">
                        <span class="ryokou-spec-label">Mesin</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($engine_cc); ?> cc</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($transmission)) : ?>
                    <div class="ryokou-spec-item" title="Tipe Transmisi">
                        <span class="ryokou-spec-label">Transmisi</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($transmission); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($route_char)) : ?>
                    <div class="ryokou-spec-item ryokou-spec-route" title="Karakter Rute">
                        <span class="ryokou-spec-label">Karakter</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($route_char); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Included Facilities Included in Every Rental -->
            <div class="ryokou-card-facilities">
                <span class="ryokou-facility-item" title="2 Helm SNI Higienis">
                    <span class="ryokou-facility-icon">🪖</span> 2 Helm SNI
                </span>
                <span class="ryokou-facility-item" title="2 Jas Hujan Setelan">
                    <span class="ryokou-facility-icon">🌧️</span> 2 Jas Hujan
                </span>
                <span class="ryokou-facility-item" title="Phone Holder Stang">
                    <span class="ryokou-facility-icon">📱</span> Holder HP
                </span>
            </div>

            <!-- Pricing Section -->
            <div class="ryokou-card-pricing">
                <div class="ryokou-price-primary">
                    <span class="ryokou-price-label">Tarif Harian (24 Jam)</span>
                    <span class="ryokou-price-amount">
                        <?php echo ryokourent_format_catalog_price($price_daily, '/ 24 Jam', 'Tanya Admin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                </div>

                <div class="ryokou-price-secondary">
                    <?php if (!empty($price_weekly) && floatval($price_weekly) > 0) : ?>
                        <span class="ryokou-secondary-rate" title="Tarif Paket Mingguan 7 Hari">
                            Mingguan: Rp <?php echo esc_html(number_format(floatval($price_weekly), 0, ',', '.')); ?>
                        </span>
                    <?php else : ?>
                        <span class="ryokou-secondary-rate">Paket Mingguan: Hemat</span>
                    <?php endif; ?>

                    <?php if (!empty($price_monthly) && floatval($price_monthly) > 0) : ?>
                        <span class="ryokou-secondary-rate" title="Tarif Paket Bulanan 30 Hari">
                            Bulanan: Rp <?php echo esc_html(number_format(floatval($price_monthly), 0, ',', '.')); ?>
                        </span>
                    <?php else : ?>
                        <span class="ryokou-secondary-rate">Paket Bulanan: Hubungi Kami</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons (CTA) -->
            <div class="ryokou-card-actions">
                <a href="#booking-form" class="ryokou-btn ryokou-btn-primary ryokou-btn-select-motor" data-motor-id="<?php echo esc_attr($post_id); ?>" data-motor-name="<?php echo esc_attr($title); ?>">
                    <span class="ryokou-btn-icon">⚡</span>
                    <span class="ryokou-btn-text">Sewa Sekarang</span>
                </a>
                <a href="<?php echo esc_url($wa_url); ?>" class="ryokou-btn ryokou-btn-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp untuk unit <?php echo esc_attr($title); ?>">
                    <span class="ryokou-btn-icon">💬</span>
                    <span class="ryokou-btn-text">Chat WA</span>
                </a>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render the entire catalog grid including header filters and fallback.
 *
 * @since 1.0.0
 * @param array $args Shortcode attributes and query overrides.
 * @return string HTML catalog markup.
 */
function ryokourent_render_catalog_grid($args = array()) {
    $defaults = array(
        'kategori'    => '',
        'limit'       => -1,
        'show_filter' => 'yes',
        'columns'     => 3,
    );
    $parsed_args = wp_parse_args($args, $defaults);

    // Query published motor units
    $query_args = array(
        'post_type'      => 'motor',
        'post_status'    => 'publish',
        'posts_per_page' => intval($parsed_args['limit']),
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    );

    if (!empty($parsed_args['kategori'])) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'kategori_motor',
                'field'    => 'slug',
                'terms'    => sanitize_title($parsed_args['kategori']),
            ),
        );
    }

    $motor_query = new WP_Query($query_args);

    // Fetch categories for tab filters
    $categories = function_exists('ryokourent_get_motor_categories') ? ryokourent_get_motor_categories() : array();

    ob_start();
    ?>
    <section class="ryokou-catalog-section" id="katalog-motor">
        <div class="ryokou-catalog-container">
            <!-- Catalog Section Header -->
            <div class="ryokou-catalog-header">
                <span class="ryokou-section-tag">PILIHAN ARMADA TERBAIK</span>
                <h2 class="ryokou-section-title">Katalog Armada Sepeda Motor Malang & Batu</h2>
                <p class="ryokou-section-desc">
                    Semua unit dalam kondisi prima, rutin servis di bengkel resmi Honda, ban tebal, serta dilengkapi 2 helm SNI steril dan 2 jas hujan setelan.
                </p>
            </div>

            <!-- Tab Filters (Mobile-First Scrollable Pills) -->
            <?php if ($parsed_args['show_filter'] === 'yes') : ?>
                <div class="ryokou-filter-nav-wrapper">
                    <div class="ryokou-filter-tabs" role="tablist" aria-label="Filter Kategori Motor">
                        <button type="button" class="ryokou-filter-btn active" data-filter="all" role="tab" aria-selected="true">
                            Semua Unit
                        </button>
                        <?php if (!empty($categories)) : ?>
                            <?php foreach ($categories as $cat) : ?>
                                <button type="button" class="ryokou-filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>" role="tab" aria-selected="false">
                                    <?php echo esc_html($cat->name); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <!-- Fallback standard categories if terms not yet loaded -->
                            <button type="button" class="ryokou-filter-btn" data-filter="beat-series" role="tab">Honda BeAT Series</button>
                            <button type="button" class="ryokou-filter-btn" data-filter="scoopy-vario" role="tab">Honda Scoopy & Vario</button>
                            <button type="button" class="ryokou-filter-btn" data-filter="trail-adventure" role="tab">Trail Adventure (Bromo)</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Catalog Grid -->
            <div class="ryokou-catalog-grid ryokou-columns-<?php echo esc_attr($parsed_args['columns']); ?>" id="ryokou-catalog-grid">
                <?php
                if ($motor_query->have_posts()) {
                    while ($motor_query->have_posts()) {
                        $motor_query->the_post();
                        echo ryokourent_render_motor_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    wp_reset_postdata();
                } else {
                    // Blueprint Default Fleet Fallback (7 Models) when no posts published yet
                    $blueprint_fleet = ryokourent_get_blueprint_default_fleet();
                    foreach ($blueprint_fleet as $custom_motor) {
                        echo ryokourent_render_motor_card(0, $custom_motor); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
                ?>
            </div>

            <!-- Empty Filter State Notice (Hidden by default, shown by JS if filter has 0 results) -->
            <div class="ryokou-catalog-empty" id="ryokou-catalog-empty" style="display: none;">
                <div class="ryokou-empty-icon">🔍</div>
                <h4 class="ryokou-empty-title">Tidak ada unit motor dalam kategori ini</h4>
                <p class="ryokou-empty-desc">Silakan pilih kategori lain atau hubungi admin via WhatsApp untuk rekomendasi armada.</p>
                <button type="button" class="ryokou-btn ryokou-btn-secondary" id="ryokou-reset-filter-btn">Lihat Semua Unit</button>
            </div>

            <!-- Trust & Inclusions Banner -->
            <div class="ryokou-catalog-trust-banner">
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">🛡️</span>
                    <div>
                        <strong>Unit Terawat & Servis Rutin</strong>
                        <span>Selalu diservis sebelum serah terima unit ke penyewa</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">🪖</span>
                    <div>
                        <strong>Fasilitas Lengkap Gratis</strong>
                        <span>2 Helm SNI bersih + 2 Jas Hujan setelan berkualitas</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">📍</span>
                    <div>
                        <strong>2 Lokasi Pool Resmi</strong>
                        <span>Pool Dinoyo (Malang) & Pool Diponegoro (Kota Batu)</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">⏱️</span>
                    <div>
                        <strong>Jam Layanan 07.00 – 23.00</strong>
                        <span>Antar-jemput stasiun/hotel fleksibel menyesuaikan sikon</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Return default 7 fleet units from blueprint specification for immediate presentation.
 *
 * @since 1.0.0
 * @return array Array of motor unit data dictionaries.
 */
function ryokourent_get_blueprint_default_fleet() {
    return array(
        array(
            'title'           => 'Honda BeAT Deluxe',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Sangat Irit',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 85000,
            'price_weekly'    => 500000,
            'price_monthly'   => 1600000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda BeAT CBS',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Irit Dalam Kota',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 80000,
            'price_weekly'    => 480000,
            'price_monthly'   => 1500000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda BeAT Street',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Naked Handlebar',
            'is_bromo_ready'  => false,
            'status_label'    => 'Booking Menipis',
            'price_daily'     => 85000,
            'price_weekly'    => 500000,
            'price_monthly'   => 1600000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda Scoopy',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman & Stylish Retro',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 95000,
            'price_weekly'    => 570000,
            'price_monthly'   => 1800000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Honda Vario 125',
            'permalink'       => '#',
            'engine_cc'       => 125,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman & Bagasi Lega',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 100000,
            'price_weekly'    => 600000,
            'price_monthly'   => 1900000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Honda Vario 160',
            'permalink'       => '#',
            'engine_cc'       => 160,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman, Bertenaga, Stabil',
            'is_bromo_ready'  => false,
            'status_label'    => 'Booking Menipis',
            'price_daily'     => 130000,
            'price_weekly'    => 780000,
            'price_monthly'   => 2400000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Trail CRF 150L',
            'permalink'       => '#',
            'engine_cc'       => 150,
            'transmission'    => 'Manual 5-Speed',
            'route_character' => 'Adventure (Wajib Bromo)',
            'is_bromo_ready'  => true,
            'status_label'    => 'Tersedia',
            'price_daily'     => 250000,
            'price_weekly'    => 1500000,
            'price_monthly'   => 4500000,
            'category_slugs'  => array('trail-adventure'),
            'category_names'  => array('Trail Adventure (Bromo)'),
        ),
    );
}
