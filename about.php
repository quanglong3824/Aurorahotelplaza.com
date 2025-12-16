<?php
require_once 'helpers/language.php';
initLanguage();
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('about_page.title'); ?></title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
                <!-- Hero Section -->
                <section class="page-hero-glass">
                    <div class="glass-blobs-container">
                        <div class="glass-blob" style="width: 300px; height: 300px; top: 10%; left: -5%;"></div>
                        <div class="glass-blob"
                            style="width: 200px; height: 200px; top: 60%; right: -3%; animation-delay: 2s;"></div>
                    </div>

                    <div class="hero-glass-card">
                        <div class="glass-badge-pill mb-4 justify-center mx-auto">
                            <span class="material-symbols-outlined">auto_awesome</span>
                            <?php _e('about_page.about_us'); ?>
                        </div>

                        <h1 class="hero-title-glass">
                            <?php _e('about_page.page_title'); ?>
                        </h1>

                        <p class="hero-subtitle-glass">
                            <?php _e('about_page.page_subtitle'); ?>
                        </p>

                        <div class="flex flex-wrap gap-4 justify-center">
                            <a href="booking/index.php" class="btn-glass-gold">
                                <span class="material-symbols-outlined">calendar_month</span>
                                <?php _e('about_page.book_now'); ?>
                            </a>
                            <a href="#story" class="btn-glass-outline">
                                <span class="material-symbols-outlined">arrow_downward</span>
                                <?php _e('about_page.learn_more'); ?>
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Story Section -->
                <section id="story" class="py-20 md:py-28 relative z-10">
                    <div class="max-w-7xl mx-auto px-4">
                        <div class="story-split-card">
                            <div class="story-image">
                                <!-- Ensure image path is correct -->
                                <img src="assets/img/src/ui/horizontal/Le_tan_Aurora.jpg" alt="Aurora Hotel Plaza">
                            </div>
                            <div class="story-content">
                                <div class="glass-badge-pill mb-4 w-max">
                                    <span class="material-symbols-outlined text-sm">history_edu</span>
                                    <?php _e('about_page.our_story'); ?>
                                </div>
                                <h2 class="font-display text-3xl md:text-4xl font-bold mb-6 text-white text-shadow-sm">
                                    <?php _e('about_page.story_title'); ?>
                                </h2>
                                <div class="space-y-4 text-white/80 leading-relaxed font-light text-lg">
                                    <p>
                                        <span class="text-accent font-bold">Aurora Hotel Plaza</span>
                                        <?php _e('about_page.story_p1'); ?>
                                    </p>
                                    <p><?php _e('about_page.story_p2'); ?></p>
                                    <p><?php _e('about_page.story_p3'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stats Section -->
                <section class="py-12 relative z-10">
                    <div class="max-w-7xl mx-auto px-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 stats-grid-glass">
                            <div class="stat-card-glass">
                                <div class="stat-icon"><span class="material-symbols-outlined">hotel</span></div>
                                <div class="stat-value">150+</div>
                                <div class="stat-label"><?php _e('about_page.rooms_apartments'); ?></div>
                            </div>
                            <div class="stat-card-glass">
                                <div class="stat-icon"><span class="material-symbols-outlined">groups</span></div>
                                <div class="stat-value">5000+</div>
                                <div class="stat-label"><?php _e('about_page.happy_customers'); ?></div>
                            </div>
                            <div class="stat-card-glass">
                                <div class="stat-icon"><span class="material-symbols-outlined">support_agent</span>
                                </div>
                                <div class="stat-value">24/7</div>
                                <div class="stat-label"><?php _e('about_page.support_service'); ?></div>
                            </div>
                            <div class="stat-card-glass">
                                <div class="stat-icon"><span class="material-symbols-outlined">workspace_premium</span>
                                </div>
                                <div class="stat-value">10+</div>
                                <div class="stat-label"><?php _e('about_page.years_experience'); ?></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Values Section -->
                <section class="py-20 md:py-28 relative z-10">
                    <div class="max-w-7xl mx-auto px-4">
                        <div class="text-center mb-16">
                            <span
                                class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('about_page.core_values'); ?></span>
                            <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4 text-white">
                                <?php _e('about_page.what_we_offer'); ?></h2>
                            <p class="text-white/70 max-w-2xl mx-auto"><?php _e('about_page.values_desc'); ?></p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Value Cards -->
                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">hotel_class</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.luxury'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed"><?php _e('about_page.luxury_desc'); ?>
                                </p>
                            </div>

                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">support_agent</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.dedicated_service'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed">
                                    <?php _e('about_page.dedicated_service_desc'); ?></p>
                            </div>

                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">location_on</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.prime_location'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed">
                                    <?php _e('about_page.prime_location_desc'); ?></p>
                            </div>

                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">restaurant</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.diverse_cuisine'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed">
                                    <?php _e('about_page.diverse_cuisine_desc'); ?></p>
                            </div>

                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">spa</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.modern_amenities'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed">
                                    <?php _e('about_page.modern_amenities_desc'); ?></p>
                            </div>

                            <div class="value-card">
                                <div class="utility-icon mb-6 mx-auto"><span
                                        class="material-symbols-outlined">verified</span></div>
                                <h3 class="font-display text-xl font-bold text-white mb-3">
                                    <?php _e('about_page.quality_guaranteed'); ?></h3>
                                <p class="text-white/70 text-sm leading-relaxed">
                                    <?php _e('about_page.quality_guaranteed_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Map Section -->
                <section class="py-20 relative z-10">
                    <div class="max-w-7xl mx-auto px-4">
                        <div class="text-center mb-12">
                            <span
                                class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('about_page.location'); ?></span>
                            <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4 text-white">
                                <?php _e('about_page.find_us'); ?></h2>
                            <p class="text-white/70">
                                Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai
                            </p>
                        </div>
                        <div class="map-glass-wrapper">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
                                allowfullscreen="" loading="lazy">
                            </iframe>
                        </div>
                    </div>
                </section>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="assets/js/glass-pages.js"></script>
</body>

</html>