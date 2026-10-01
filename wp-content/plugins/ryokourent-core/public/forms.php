<?php
/**
 * Booking Form Rendering & HTML5 Layout for Ryokourent
 *
 * Renders the mobile-first customer booking form including identity fields,
 * route destination selector (Malang/Batu vs Bromo), fleet selection,
 * operating hour constraints (07:00-23:00 WIB), honeypot anti-spam, and nonce.
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
 * Render the HTML5 booking form.
 *
 * @since 1.0.0
 * @param array $args Optional styling or prefill arguments.
 * @return string HTML form markup.
 */
function ryokourent_render_booking_form($args = array()) {
    $defaults = array(
        'form_id'       => 'ryokourent-booking-form',
        'selected_motor'=> 0,
        'title'         => __('Formulir Pemesanan Sewa Motor', 'ryokourent'),
    );
    $parsed_args = wp_parse_args($args, $defaults);

    // Query published motor armada units
    $motors = get_posts(array(
        'post_type'      => 'motor',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ));

    // Fallback to blueprint fleet if no published motors in DB
    $fleet_options = array();
    if (!empty($motors)) {
        foreach ($motors as $m) {
            $is_bromo = get_post_meta($m->ID, '_ryokou_is_bromo_ready', true) === 'yes';
            $daily    = get_post_meta($m->ID, '_ryokou_price_daily', true);
            $weekly   = get_post_meta($m->ID, '_ryokou_price_weekly', true);
            $monthly  = get_post_meta($m->ID, '_ryokou_price_monthly', true);
            $fleet_options[] = array(
                'id'             => $m->ID,
                'name'           => $m->post_title,
                'is_bromo_ready' => $is_bromo,
                'price_daily'    => $daily ? floatval($daily) : 0,
                'price_weekly'   => $weekly ? floatval($weekly) : 0,
                'price_monthly'  => $monthly ? floatval($monthly) : 0,
            );
        }
    } elseif (function_exists('ryokourent_get_blueprint_default_fleet')) {
        $blueprint = ryokourent_get_blueprint_default_fleet();
        $mock_id = 1;
        foreach ($blueprint as $item) {
            $fleet_options[] = array(
                'id'             => $mock_id++,
                'name'           => $item['title'],
                'is_bromo_ready' => !empty($item['is_bromo_ready']),
                'price_daily'    => $item['price_daily'],
                'price_weekly'   => $item['price_weekly'],
                'price_monthly'  => $item['price_monthly'],
            );
        }
    }

    // Default dates in WIB (tomorrow at 08:30 to day after at 17:00)
    $timezone = new DateTimeZone('Asia/Jakarta');
    $now_wib  = new DateTime('now', $timezone);
    $default_start = clone $now_wib;
    $default_start->modify('+1 day')->setTime(8, 30);
    $default_end = clone $now_wib;
    $default_end->modify('+3 days')->setTime(17, 0);

    $start_val = $default_start->format('Y-m-d\TH:i');
    $end_val   = $default_end->format('Y-m-d\TH:i');
    $min_start = $now_wib->format('Y-m-d\TH:i');

    ob_start();
    ?>
    <section class="ryokou-booking-section" id="booking-form">
        <div class="ryokou-booking-container">
            <!-- Section Header -->
            <div class="ryokou-booking-header">
                <span class="ryokou-section-tag"><?php esc_html_e('ZERO-FRICTION WHATSAPP BOOKING', 'ryokourent'); ?></span>
                <h2 class="ryokou-booking-title"><?php echo esc_html($parsed_args['title']); ?></h2>
                <p class="ryokou-booking-desc">
                    <?php esc_html_e('Tentukan jadwal sewa dan lengkapi data identitas. Pesanan otomatis tersimpan dan Anda langsung terhubung ke WhatsApp Admin dengan draf terformat rapi.', 'ryokourent'); ?>
                </p>
            </div>

            <!-- Booking Form -->
            <form id="<?php echo esc_attr($parsed_args['form_id']); ?>" class="ryokou-form-card" method="post" action="" novalidate>
                <!-- CSRF Nonce -->
                <?php wp_nonce_field('ryokourent_booking_form_action', 'ryokourent_booking_nonce'); ?>

                <!-- Anti-Spam Honeypot Field (Invisible to human users) -->
                <div class="ryokou-hp-wrap" style="display:none !important; position:absolute; left:-9999px;">
                    <label for="ryokourent_hp">Leave this empty if human</label>
                    <input type="text" name="ryokourent_hp" id="ryokourent_hp" value="" tabindex="-1" autocomplete="off" />
                </div>

                <!-- Step 1: Destination & Motor Selection -->
                <div class="ryokou-form-group-section">
                    <div class="ryokou-section-label-bar">
                        <span class="ryokou-step-number">1</span>
                        <h3 class="ryokou-step-heading"><?php esc_html_e('Rute Perjalanan & Pilihan Armada', 'ryokourent'); ?></h3>
                    </div>

                    <!-- Destination Route Radio -->
                    <div class="ryokou-field-block mb-4">
                        <label class="ryokou-field-label">
                            <?php esc_html_e('Tujuan Rute Perjalanan:', 'ryokourent'); ?>
                            <span class="ryokou-required">*</span>
                        </label>
                        <div class="ryokou-route-selector">
                            <label class="ryokou-route-option active">
                                <input type="radio" name="trip_destination" value="malang_batu" checked />
                                <span class="ryokou-route-box">
                                    <span class="ryokou-route-icon">🏙️</span>
                                    <span class="ryokou-route-meta">
                                        <strong>Malang Kota & Wisata Batu</strong>
                                        <span>Seluruh rute perkotaan, kampus, kuliner, dan tanjakan Batu.</span>
                                    </span>
                                </span>
                            </label>
                            <label class="ryokou-route-option">
                                <input type="radio" name="trip_destination" value="bromo" />
                                <span class="ryokou-route-box">
                                    <span class="ryokou-route-icon">🌋</span>
                                    <span class="ryokou-route-meta">
                                        <strong>Trip Kaldera Gunung Bromo</strong>
                                        <span class="text-amber-400 font-semibold">Wajib menggunakan Trail CRF 150L.</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Motor Selector -->
                    <div class="ryokou-grid-2">
                        <div class="ryokou-field-block">
                            <label for="rented_motor_id" class="ryokou-field-label">
                                <?php esc_html_e('Pilih Model Motor:', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <select name="rented_motor_id" id="rented_motor_id" class="ryokou-input-select" required>
                                <option value=""><?php esc_html_e('-- Pilih Model Motor --', 'ryokourent'); ?></option>
                                <?php foreach ($fleet_options as $motor_opt) : ?>
                                    <option 
                                        value="<?php echo esc_attr($motor_opt['id']); ?>"
                                        data-price-daily="<?php echo esc_attr($motor_opt['price_daily']); ?>"
                                        data-price-weekly="<?php echo esc_attr($motor_opt['price_weekly']); ?>"
                                        data-price-monthly="<?php echo esc_attr($motor_opt['price_monthly']); ?>"
                                        data-is-bromo="<?php echo $motor_opt['is_bromo_ready'] ? 'yes' : 'no'; ?>"
                                        <?php selected($parsed_args['selected_motor'], $motor_opt['id']); ?>
                                    >
                                        <?php echo esc_html($motor_opt['name']); ?> 
                                        <?php if ($motor_opt['price_daily'] > 0) : ?>
                                            (Rp <?php echo esc_html(number_format($motor_opt['price_daily'], 0, ',', '.')); ?>/hari)
                                        <?php endif; ?>
                                        <?php if ($motor_opt['is_bromo_ready']) : ?>
                                            - [Wajib Bromo]
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Pickup Location -->
                        <div class="ryokou-field-block">
                            <label for="pickup_location" class="ryokou-field-label">
                                <?php esc_html_e('Lokasi Pengambilan Unit:', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <select name="pickup_location" id="pickup_location" class="ryokou-input-select" required>
                                <option value="Pool Dinoyo"><?php esc_html_e('Pool Dinoyo (Lowokwaru, Kota Malang)', 'ryokourent'); ?></option>
                                <option value="Pool Batu"><?php esc_html_e('Pool Batu (Jl. Diponegoro, Kota Batu)', 'ryokourent'); ?></option>
                                <option value="Stasiun Malang"><?php esc_html_e('Diantar ke Stasiun Malang Kota Baru (Sesuai Sikon)', 'ryokourent'); ?></option>
                                <option value="Hotel/Homestay"><?php esc_html_e('Diantar ke Hotel / Penginapan (Sesuai Sikon)', 'ryokourent'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Schedule & Rental Duration -->
                <div class="ryokou-form-group-section">
                    <div class="ryokou-section-label-bar">
                        <span class="ryokou-step-number">2</span>
                        <h3 class="ryokou-step-heading"><?php esc_html_e('Jadwal Sewa & Estimasi Biaya', 'ryokourent'); ?></h3>
                    </div>

                    <div class="ryokou-grid-2">
                        <div class="ryokou-field-block">
                            <label for="start_datetime" class="ryokou-field-label">
                                <?php esc_html_e('Tanggal & Jam Mulai (WIB):', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <input 
                                type="datetime-local" 
                                name="start_datetime" 
                                id="start_datetime" 
                                class="ryokou-input-text" 
                                value="<?php echo esc_attr($start_val); ?>"
                                min="<?php echo esc_attr($min_start); ?>"
                                required 
                            />
                            <span class="ryokou-hint"><?php esc_html_e('Jam pelayanan serah terima unit: 07.00 – 23.00 WIB', 'ryokourent'); ?></span>
                        </div>

                        <div class="ryokou-field-block">
                            <label for="end_datetime" class="ryokou-field-label">
                                <?php esc_html_e('Tanggal & Jam Selesai (WIB):', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <input 
                                type="datetime-local" 
                                name="end_datetime" 
                                id="end_datetime" 
                                class="ryokou-input-text" 
                                value="<?php echo esc_attr($end_val); ?>"
                                min="<?php echo esc_attr($start_val); ?>"
                                required 
                            />
                            <span class="ryokou-hint"><?php esc_html_e('Toleransi keterlambatan sewa (overtime) s/d 2 jam', 'ryokourent'); ?></span>
                        </div>
                    </div>

                    <!-- Live Duration and Price Calculation Card -->
                    <div class="ryokou-calc-summary-card" id="ryokou-calc-summary">
                        <div class="ryokou-calc-row">
                            <div>
                                <span class="ryokou-calc-label"><?php esc_html_e('Estimasi Durasi Sewa:', 'ryokourent'); ?></span>
                                <strong class="ryokou-calc-duration" id="ryokou-live-duration"><?php esc_html_e('2 Hari (~56 Jam)', 'ryokourent'); ?></strong>
                            </div>
                            <div class="text-right">
                                <span class="ryokou-calc-label"><?php esc_html_e('Estimasi Total Tarif:', 'ryokourent'); ?></span>
                                <strong class="ryokou-calc-price" id="ryokou-live-price"><?php esc_html_e('Rp 170.000', 'ryokourent'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Customer Identity & Verification -->
                <div class="ryokou-form-group-section">
                    <div class="ryokou-section-label-bar">
                        <span class="ryokou-step-number">3</span>
                        <h3 class="ryokou-step-heading"><?php esc_html_e('Data Identitas Pelanggan (Sesuai e-KTP)', 'ryokourent'); ?></h3>
                    </div>

                    <div class="ryokou-field-block mb-3">
                        <label for="customer_name" class="ryokou-field-label">
                            <?php esc_html_e('Nama Lengkap (sesuai e-KTP):', 'ryokourent'); ?>
                            <span class="ryokou-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="customer_name" 
                            id="customer_name" 
                            class="ryokou-input-text" 
                            placeholder="Contoh: Dimas Aditya Pratama" 
                            required 
                        />
                    </div>

                    <div class="ryokou-grid-2">
                        <div class="ryokou-field-block">
                            <label for="customer_whatsapp" class="ryokou-field-label">
                                <?php esc_html_e('Nomor WhatsApp Aktif:', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                name="customer_whatsapp" 
                                id="customer_whatsapp" 
                                class="ryokou-input-text" 
                                placeholder="081234567890" 
                                pattern="[0-9]{10,15}"
                                required 
                            />
                            <span class="ryokou-hint"><?php esc_html_e('Pastikan nomor terhubung dengan WhatsApp aktif', 'ryokourent'); ?></span>
                        </div>

                        <div class="ryokou-field-block">
                            <label for="customer_emergency_phone" class="ryokou-field-label">
                                <?php esc_html_e('Nomor Kontak Darurat (Keluarga):', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                name="customer_emergency_phone" 
                                id="customer_emergency_phone" 
                                class="ryokou-input-text" 
                                placeholder="081345678901 (Keluarga/Orang Tua)" 
                                pattern="[0-9]{10,15}"
                                required 
                            />
                            <span class="ryokou-hint"><?php esc_html_e('Keluarga/kerabat yang tidak ikut dalam perjalanan', 'ryokourent'); ?></span>
                        </div>
                    </div>

                    <div class="ryokou-grid-2">
                        <div class="ryokou-field-block">
                            <label for="customer_ktp_address" class="ryokou-field-label">
                                <?php esc_html_e('Alamat Sesuai KTP:', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <textarea 
                                name="customer_ktp_address" 
                                id="customer_ktp_address" 
                                class="ryokou-input-textarea" 
                                rows="2" 
                                placeholder="Alamat asal sesuai e-KTP" 
                                required
                            ></textarea>
                        </div>

                        <div class="ryokou-field-block">
                            <label for="customer_stay_address" class="ryokou-field-label">
                                <?php esc_html_e('Tempat Menginap di Malang / Batu:', 'ryokourent'); ?>
                                <span class="ryokou-required">*</span>
                            </label>
                            <textarea 
                                name="customer_stay_address" 
                                id="customer_stay_address" 
                                class="ryokou-input-textarea" 
                                rows="2" 
                                placeholder="Hotel Santika / Homestay Batu / Kost Dinoyo" 
                                required
                            ></textarea>
                        </div>
                    </div>

                    <div class="ryokou-grid-2">
                        <div class="ryokou-field-block">
                            <label for="customer_social_media" class="ryokou-field-label">
                                <?php esc_html_e('ID Akun Media Sosial (Instagram/FB):', 'ryokourent'); ?>
                            </label>
                            <input 
                                type="text" 
                                name="customer_social_media" 
                                id="customer_social_media" 
                                class="ryokou-input-text" 
                                placeholder="@username_instagram" 
                            />
                        </div>

                        <div class="ryokou-field-block">
                            <label for="rental_notes" class="ryokou-field-label">
                                <?php esc_html_e('Catatan Tambahan (Ukuran Helm / Fasilitas):', 'ryokourent'); ?>
                            </label>
                            <input 
                                type="text" 
                                name="rental_notes" 
                                id="rental_notes" 
                                class="ryokou-input-text" 
                                placeholder="Butuh 2 helm ukuran L dan jas hujan setelan" 
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit Button & Disclaimer -->
                <div class="ryokou-form-footer">
                    <button type="submit" class="ryokou-btn-submit-booking" id="ryokou-btn-submit">
                        <span class="ryokou-submit-icon">💬</span>
                        <span class="ryokou-submit-text"><?php esc_html_e('Lanjutkan Pemesanan via WhatsApp', 'ryokourent'); ?></span>
                    </button>
                    <p class="ryokou-form-disclaimer">
                        <?php esc_html_e('🔒 Data identitas Anda aman dan dilindungi sesuai UU Perlindungan Data Pribadi (UU PDP). Setelah submit, pesanan langsung tersimpan ke sistem operasional Ryokourent dan Anda akan diarahkan ke WhatsApp Admin resmi untuk konfirmasi ketersediaan unit & pembayaran jaminan.', 'ryokourent'); ?>
                    </p>
                </div>
            </form>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
