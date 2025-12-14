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
<link rel="stylesheet" href="assets/css/about.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-about">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">info</span>
                <?php _e('about_page.about_us'); ?>
            </span>
            <h1 class="page-title"><?php _e('about_page.page_title'); ?></h1>
            <p class="page-subtitle"><?php _e('about_page.page_subtitle'); ?></p>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('about_page.book_now'); ?>
                </a>
                <a href="#story" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('about_page.learn_more'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section id="story" class="section-padding">
        <div class="container-custom">
            <div class="story-section">
                <div class="story-image-wrapper">
                    <img src="assets/img/src/ui/horizontal/Le_tan_Aurora.jpg" alt="Aurora Hotel Plaza" class="story-image">
                </div>
                <div class="story-content">
                    <span class="section-label"><?php _e('about_page.our_story'); ?></span>
                    <h2 class="section-title"><?php _e('about_page.story_title'); ?></h2>
                    <p class="section-description">
                        <span class="highlight-text">Aurora Hotel Plaza</span> <?php _e('about_page.story_p1'); ?>
                    </p>
                    <p class="section-description">
                        <?php _e('about_page.story_p2'); ?>
                    </p>
                    <p class="section-description">
                        <?php _e('about_page.story_p3'); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container-custom">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">150+</div>
                    <div class="stat-label"><?php _e('about_page.rooms_apartments'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5000+</div>
                    <div class="stat-label"><?php _e('about_page.happy_customers'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label"><?php _e('about_page.support_service'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">10+</div>
                    <div class="stat-label"><?php _e('about_page.years_experience'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="section-padding values-section">
        <div class="container-custom">
            <div class="values-header">
                <span class="section-label"><?php _e('about_page.core_values'); ?></span>
                <h2 class="section-title"><?php _e('about_page.what_we_offer'); ?></h2>
                <p class="section-description">
                    <?php _e('about_page.values_desc'); ?>
                </p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">hotel_class</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.luxury'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.luxury_desc'); ?>
                    </p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.dedicated_service'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.dedicated_service_desc'); ?>
                    </p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.prime_location'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.prime_location_desc'); ?>
                    </p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">restaurant</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.diverse_cuisine'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.diverse_cuisine_desc'); ?>
                    </p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">spa</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.modern_amenities'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.modern_amenities_desc'); ?>
                    </p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                    <h3 class="value-title"><?php _e('about_page.quality_guaranteed'); ?></h3>
                    <p class="value-description">
                        <?php _e('about_page.quality_guaranteed_desc'); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Facilities Section -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="story-section">
                <div class="story-content">
                    <span class="section-label"><?php _e('about_page.amenities'); ?></span>
                    <h2 class="section-title"><?php _e('about_page.five_star_experience'); ?></h2>
                    <p class="section-description">
                        <?php _e('about_page.amenities_intro'); ?>
                    </p>
                    <ul class="section-description">
                        <li>✓ <?php _e('about_page.amenity_rooms'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_restaurant'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_pool'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_gym'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_spa'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_conference'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_shuttle'); ?></li>
                        <li>✓ <?php _e('about_page.amenity_parking'); ?></li>
                    </ul>
                </div>
                <div class="story-image-wrapper">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg" alt="Tiện nghi Aurora" class="story-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="section-padding bg-surface-light dark:bg-surface-dark">
        <div class="container-custom">
            <div class="text-center mb-12">
                <span class="section-label"><?php _e('about_page.location'); ?></span>
                <h2 class="section-title"><?php _e('about_page.find_us'); ?></h2>
                <p class="section-description">
                    Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai
                </p>
            </div>
            <div class="rounded-2xl overflow-hidden shadow-xl">
                <iframe 
                    src="https://maps.google.com/maps?q=10.957145,106.842134&hl=vi&z=17&output=embed"
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <span class="badge-liquid-glass mb-6">
                    <span class="material-symbols-outlined text-accent">support_agent</span>
                    <?php _e('contact_page.support_24_7'); ?>
                </span>
                <h2 class="cta-title"><?php _e('about_page.ready_to_experience'); ?></h2>
                <p class="cta-description">
                    <?php _e('about_page.cta_desc'); ?>
                </p>
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="booking/index.php" class="btn-liquid-primary">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <?php _e('about_page.book_now'); ?>
                    </a>
                    <a href="tel:+842513918888" class="btn-liquid-glass">
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
<script src="assets/js/main.js"></script>
</body>
</html>
