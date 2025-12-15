<?php
require_once 'helpers/language.php';
initLanguage();
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('about_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* ========== FULL PAGE BACKGROUND WITH GLASS BLOCKS ========== */
.page-wrapper {
    position: relative;
    min-height: 100vh;
}

.page-bg {
    position: fixed;
    inset: 0;
    z-index: -2;
    background: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

.page-overlay {
    position: fixed;
    inset: 0;
    z-index: -1;
    background: rgba(0, 0, 0, 0.5);
}

/* Hero Section */
.hero-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120px 1rem 80px;
}

.hero-glass-card {
    max-width: 900px;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 2rem;
    padding: 3rem;
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.4);
    border-radius: 3rem;
    color: #d4af37;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.85);
    max-width: 600px;
    margin: 0 auto 2rem;
    line-height: 1.7;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-stat {
    text-align: center;
}

.hero-stat-number {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: #d4af37;
}

.hero-stat-label {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 0.25rem;
}

/* Content Section */
.content-section {
    padding: 5rem 1rem;
}

.section-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Glass Card */
.glass-block {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    padding: 2.5rem;
    margin-bottom: 2rem;
}

.glass-block-light {
    background: rgba(255, 255, 255, 0.1);
}

/* Section Header */
.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 2rem;
    color: #d4af37;
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.75rem;
}

.section-desc {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.75);
    max-width: 600px;
    margin: 0 auto;
}

/* Story Grid */
.story-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: center;
}

.story-image {
    border-radius: 1.25rem;
    overflow: hidden;
    height: 400px;
}

.story-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-content h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.story-content p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.8;
    margin-bottom: 1rem;
}

.story-highlight {
    color: #d4af37;
    font-weight: 600;
}

/* Values Grid */
.values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.value-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.25rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.value-card:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-5px);
    border-color: rgba(212, 175, 55, 0.3);
}

.value-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1.25rem;
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.value-icon .material-symbols-outlined {
    font-size: 1.75rem;
    color: #d4af37;
}

.value-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.value-desc {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
}

/* Facilities Grid */
.facilities-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.facility-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.facility-item:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(212, 175, 55, 0.3);
}

.facility-item .material-symbols-outlined {
    font-size: 1.5rem;
    color: #d4af37;
}

.facility-item span:last-child {
    font-size: 0.9375rem;
    font-weight: 500;
    color: white;
}

/* Map Container */
.map-container {
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.map-container iframe {
    width: 100%;
    height: 400px;
    display: block;
}

/* CTA Section */
.cta-glass {
    text-align: center;
    padding: 3rem;
}

.cta-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.cta-desc {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary-glass {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: rgba(212, 175, 55, 0.9);
    color: #000;
    border-radius: 0.75rem;
    font-weight: 700;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary-glass:hover {
    background: #d4af37;
    transform: translateY(-2px);
}

.btn-secondary-glass {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 0.75rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-secondary-glass:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-title { font-size: 2.75rem; }
    .story-grid { grid-template-columns: 1fr; }
    .story-image { height: 300px; }
    .values-grid { grid-template-columns: repeat(2, 1fr); }
    .facilities-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .hero-section { padding: 100px 1rem 60px; min-height: auto; }
    .hero-glass-card { padding: 2rem; }
    .hero-title { font-size: 2rem; }
    .hero-stats { gap: 1.5rem; flex-wrap: wrap; }
    .hero-stat-number { font-size: 1.5rem; }
    .glass-block { padding: 1.5rem; }
    .section-title { font-size: 1.75rem; }
    .values-grid { grid-template-columns: 1fr; }
    .facilities-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .hero-title { font-size: 1.75rem; }
    .hero-stats { gap: 1rem; }
    .cta-glass { padding: 2rem 1.5rem; }
}
</style>
</head>

<body class="font-body">
<div class="page-wrapper">
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-glass-card">
                <div class="hero-badge">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">auto_awesome</span>
                    <?php _e('about_page.about_us'); ?>
                </div>
                <h1 class="hero-title"><?php _e('about_page.page_title'); ?></h1>
                <p class="hero-subtitle"><?php _e('about_page.page_subtitle'); ?></p>
                
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="booking/index.php" class="btn-primary-glass">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <?php _e('about_page.book_now'); ?>
                    </a>
                    <a href="#story" class="btn-secondary-glass">
                        <span class="material-symbols-outlined">arrow_downward</span>
                        <?php _e('about_page.learn_more'); ?>
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number">150+</div>
                        <div class="hero-stat-label"><?php _e('about_page.rooms_apartments'); ?></div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">5★</div>
                        <div class="hero-stat-label"><?php _e('about_page.standard'); ?></div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">24/7</div>
                        <div class="hero-stat-label"><?php _e('about_page.support_service'); ?></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Story Section -->
        <section id="story" class="content-section">
            <div class="section-container">
                <div class="glass-block">
                    <div class="section-header">
                        <div class="section-badge">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">history_edu</span>
                            <?php _e('about_page.our_story'); ?>
                        </div>
                        <h2 class="section-title"><?php _e('about_page.story_title'); ?></h2>
                    </div>
                    
                    <div class="story-grid">
                        <div class="story-image">
                            <img src="assets/img/src/ui/horizontal/Le_tan_Aurora.jpg" alt="Aurora Hotel Plaza">
                        </div>
                        <div class="story-content">
                            <h3><?php _e('about_page.welcome'); ?></h3>
                            <p><span class="story-highlight">Aurora Hotel Plaza</span> <?php _e('about_page.story_p1'); ?></p>
                            <p><?php _e('about_page.story_p2'); ?></p>
                            <p><?php _e('about_page.story_p3'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="content-section" style="padding-top: 0;">
            <div class="section-container">
                <div class="glass-block">
                    <div class="section-header">
                        <div class="section-badge">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">workspace_premium</span>
                            <?php _e('about_page.core_values'); ?>
                        </div>
                        <h2 class="section-title"><?php _e('about_page.what_we_offer'); ?></h2>
                        <p class="section-desc"><?php _e('about_page.values_desc'); ?></p>
                    </div>
                    
                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">hotel_class</span></div>
                            <h3 class="value-title"><?php _e('about_page.luxury'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.luxury_desc'); ?></p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">support_agent</span></div>
                            <h3 class="value-title"><?php _e('about_page.dedicated_service'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.dedicated_service_desc'); ?></p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">location_on</span></div>
                            <h3 class="value-title"><?php _e('about_page.prime_location'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.prime_location_desc'); ?></p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">restaurant</span></div>
                            <h3 class="value-title"><?php _e('about_page.diverse_cuisine'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.diverse_cuisine_desc'); ?></p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">spa</span></div>
                            <h3 class="value-title"><?php _e('about_page.modern_amenities'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.modern_amenities_desc'); ?></p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><span class="material-symbols-outlined">verified</span></div>
                            <h3 class="value-title"><?php _e('about_page.quality_guaranteed'); ?></h3>
                            <p class="value-desc"><?php _e('about_page.quality_guaranteed_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Facilities Section -->
        <section class="content-section" style="padding-top: 0;">
            <div class="section-container">
                <div class="glass-block">
                    <div class="section-header">
                        <div class="section-badge">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">star</span>
                            <?php _e('about_page.amenities'); ?>
                        </div>
                        <h2 class="section-title"><?php _e('about_page.five_star_experience'); ?></h2>
                    </div>
                    
                    <div class="facilities-grid">
                        <div class="facility-item">
                            <span class="material-symbols-outlined">hotel</span>
                            <span><?php _e('about_page.amenity_rooms'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">restaurant</span>
                            <span><?php _e('about_page.amenity_restaurant'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">pool</span>
                            <span><?php _e('about_page.amenity_pool'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">fitness_center</span>
                            <span><?php _e('about_page.amenity_gym'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">spa</span>
                            <span><?php _e('about_page.amenity_spa'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">groups</span>
                            <span><?php _e('about_page.amenity_conference'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">airport_shuttle</span>
                            <span><?php _e('about_page.amenity_shuttle'); ?></span>
                        </div>
                        <div class="facility-item">
                            <span class="material-symbols-outlined">local_parking</span>
                            <span><?php _e('about_page.amenity_parking'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="content-section" style="padding-top: 0;">
            <div class="section-container">
                <div class="glass-block">
                    <div class="section-header">
                        <div class="section-badge">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">location_on</span>
                            <?php _e('about_page.location'); ?>
                        </div>
                        <h2 class="section-title"><?php _e('about_page.find_us'); ?></h2>
                        <p class="section-desc">Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai</p>
                    </div>
                    
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="content-section" style="padding-top: 0;">
            <div class="section-container">
                <div class="glass-block cta-glass">
                    <div class="section-badge" style="margin-bottom: 1.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 1rem;">support_agent</span>
                        Hỗ trợ 24/7
                    </div>
                    <h2 class="cta-title"><?php _e('about_page.ready_to_experience'); ?></h2>
                    <p class="cta-desc"><?php _e('about_page.cta_desc'); ?></p>
                    <div class="cta-buttons">
                        <a href="booking/index.php" class="btn-primary-glass">
                            <span class="material-symbols-outlined">calendar_month</span>
                            <?php _e('about_page.book_now'); ?>
                        </a>
                        <a href="tel:+842513918888" class="btn-secondary-glass">
                            <span class="material-symbols-outlined">phone</span>
                            (+84-251) 391.8888
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</div>
</body>
</html>
