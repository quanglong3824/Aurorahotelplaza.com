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
/* ========== ABOUT PAGE - ELEGANT GLASS STYLE ========== */

/* Hero with Parallax */
.about-hero {
    position: relative;
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.about-hero-bg {
    position: absolute;
    inset: 0;
    background: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    transform: scale(1.1);
}

.about-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(26, 35, 126, 0.85) 0%,
        rgba(17, 24, 39, 0.75) 50%,
        rgba(212, 175, 55, 0.4) 100%
    );
}

.about-hero::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 200px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    z-index: 2;
}

.dark .about-hero::after {
    background: linear-gradient(to top, var(--background-dark, #0f172a), transparent);
}

/* Floating Glass Elements */
.floating-glass {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: float 6s ease-in-out infinite;
}

.floating-glass:nth-child(1) { width: 300px; height: 300px; top: 10%; left: -5%; animation-delay: 0s; }
.floating-glass:nth-child(2) { width: 200px; height: 200px; top: 60%; right: -3%; animation-delay: 2s; }
.floating-glass:nth-child(3) { width: 150px; height: 150px; bottom: 20%; left: 10%; animation-delay: 4s; }

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

/* Hero Content Card */
.hero-content-glass {
    position: relative;
    z-index: 10;
    max-width: 900px;
    text-align: center;
    padding: 3rem;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(212, 175, 55, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(212, 175, 55, 0.3);
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
    line-height: 1.2;
    margin-bottom: 1.5rem;
    text-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
}

.hero-title span {
    color: #d4af37;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.7;
    max-width: 700px;
    margin: 0 auto 2rem;
}

/* Story Section - Split Glass */
.story-glass-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    border-radius: 2rem;
    overflow: hidden;
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
}

.dark .story-glass-card {
    background: rgba(30, 41, 59, 0.9);
}

.story-image-side {
    position: relative;
    min-height: 500px;
}

.story-image-side img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(26, 35, 126, 0.3), rgba(212, 175, 55, 0.2));
}

.story-content-side {
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.story-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #d4af37;
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 1rem;
}

.story-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary-light);
    margin-bottom: 1.5rem;
    line-height: 1.3;
}

.dark .story-title {
    color: var(--text-primary-dark);
}

.story-text {
    font-size: 1rem;
    color: var(--text-secondary-light);
    line-height: 1.8;
    margin-bottom: 1rem;
}

.dark .story-text {
    color: var(--text-secondary-dark);
}

.story-highlight {
    color: #d4af37;
    font-weight: 700;
}

/* Stats Section - Floating Glass */
.stats-floating {
    position: relative;
    padding: 6rem 0;
    background: linear-gradient(135deg, #1A237E 0%, #0f172a 100%);
    overflow: hidden;
}

.stats-floating::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.stats-grid-glass {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    position: relative;
    z-index: 1;
}

.stat-glass-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1.5rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-glass-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #d4af37, transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.stat-glass-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.stat-glass-card:hover::before {
    opacity: 1;
}

.stat-icon-ring {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.25rem;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(212, 175, 55, 0.05));
    border: 2px solid rgba(212, 175, 55, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.stat-glass-card:hover .stat-icon-ring {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border-color: #d4af37;
    transform: scale(1.1);
}

.stat-icon-ring .material-symbols-outlined {
    font-size: 2rem;
    color: #d4af37;
    transition: color 0.3s;
}

.stat-glass-card:hover .stat-icon-ring .material-symbols-outlined {
    color: white;
}

.stat-number-glass {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.stat-label-glass {
    font-size: 0.9375rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

/* Values Section - Hexagon Grid */
.values-section-glass {
    padding: 6rem 0;
    background: linear-gradient(180deg, var(--background-light) 0%, #f1f5f9 100%);
}

.dark .values-section-glass {
    background: linear-gradient(180deg, var(--background-dark) 0%, #1e293b 100%);
}

.values-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 4rem;
}

.values-grid-glass {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.value-card-glass {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 1.5rem;
    padding: 2.5rem 2rem;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.dark .value-card-glass {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.value-card-glass::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1A237E, #d4af37);
    transform: scaleX(0);
    transition: transform 0.4s;
}

.value-card-glass:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
}

.value-card-glass:hover::after {
    transform: scaleX(1);
}

.value-icon-glass {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, rgba(26, 35, 126, 0.1), rgba(212, 175, 55, 0.1));
    border-radius: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s;
}

.value-card-glass:hover .value-icon-glass {
    background: linear-gradient(135deg, #1A237E, #d4af37);
    transform: rotate(10deg) scale(1.1);
}

.value-icon-glass .material-symbols-outlined {
    font-size: 2.5rem;
    background: linear-gradient(135deg, #1A237E, #d4af37);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transition: all 0.4s;
}

.value-card-glass:hover .value-icon-glass .material-symbols-outlined {
    background: white;
    -webkit-background-clip: text;
    background-clip: text;
}

.value-title-glass {
    font-family: 'Playfair Display', serif;
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--text-primary-light);
    margin-bottom: 0.75rem;
}

.dark .value-title-glass {
    color: var(--text-primary-dark);
}

.value-desc-glass {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.7;
}

.dark .value-desc-glass {
    color: var(--text-secondary-dark);
}

/* Facilities Section */
.facilities-glass {
    padding: 6rem 0;
    background: #f8fafc;
}

.dark .facilities-glass {
    background: #0f172a;
}

.facilities-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.facility-item-glass {
    background: white;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s;
}

.dark .facility-item-glass {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
}

.facility-item-glass:hover {
    transform: translateX(8px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border-color: #d4af37;
}

.facility-icon-glass {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.facility-icon-glass .material-symbols-outlined {
    font-size: 1.5rem;
    color: #d4af37;
}

.facility-text-glass {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary-light);
}

.dark .facility-text-glass {
    color: var(--text-primary-dark);
}

/* Map Section */
.map-glass-wrapper {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 2rem;
    padding: 1rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.dark .map-glass-wrapper {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
}

.map-glass-wrapper iframe {
    border-radius: 1.5rem;
}

/* CTA Section */
.cta-elegant {
    position: relative;
    padding: 6rem 0;
    background: linear-gradient(135deg, #1A237E 0%, #0f172a 50%, #d4af37 150%);
    overflow: hidden;
}

.cta-elegant::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg');
    background-size: cover;
    background-position: center;
    opacity: 0.1;
}

.cta-glass-card {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 2rem;
    padding: 3rem;
    text-align: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-title { font-size: 2.75rem; }
    .story-glass-card { grid-template-columns: 1fr; }
    .story-image-side { min-height: 350px; }
    .stats-grid-glass { grid-template-columns: repeat(2, 1fr); }
    .values-grid-glass { grid-template-columns: repeat(2, 1fr); }
    .facilities-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .about-hero { min-height: 70vh; }
    .hero-title { font-size: 2.25rem; }
    .hero-subtitle { font-size: 1rem; }
    .hero-content-glass { padding: 1.5rem; }
    .story-content-side { padding: 2rem; }
    .story-title { font-size: 2rem; }
    .stat-number-glass { font-size: 2.25rem; }
    .values-grid-glass { grid-template-columns: 1fr; }
    .value-card-glass { padding: 2rem 1.5rem; }
    .cta-glass-card { padding: 2rem; margin: 0 1rem; }
}

@media (max-width: 480px) {
    .hero-title { font-size: 1.875rem; }
    .stats-grid-glass { grid-template-columns: 1fr 1fr; gap: 1rem; }
    .stat-glass-card { padding: 1.5rem 1rem; }
    .stat-icon-ring { width: 4rem; height: 4rem; }
    .stat-number-glass { font-size: 1.75rem; }
    .facilities-grid { grid-template-columns: 1fr; }
}
</style>
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="about-hero-bg"></div>
        <div class="about-hero-overlay"></div>
        
        <!-- Floating Glass Elements -->
        <div class="floating-glass"></div>
        <div class="floating-glass"></div>
        <div class="floating-glass"></div>
        
        <div class="hero-content-glass">
            <div class="hero-badge">
                <span class="material-symbols-outlined">auto_awesome</span>
                <?php _e('about_page.about_us'); ?>
            </div>
            
            <h1 class="hero-title">
                <?php _e('about_page.page_title'); ?>
            </h1>
            
            <p class="hero-subtitle">
                <?php _e('about_page.page_subtitle'); ?>
            </p>
            
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="btn-glass-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('about_page.book_now'); ?>
                </a>
                <a href="#story" class="btn-glass-secondary">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('about_page.learn_more'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section id="story" class="py-20 md:py-28">
        <div class="max-w-7xl mx-auto px-4">
            <div class="story-glass-card">
                <div class="story-image-side">
                    <img src="assets/img/src/ui/horizontal/Le_tan_Aurora.jpg" alt="Aurora Hotel Plaza">
                    <div class="story-image-overlay"></div>
                </div>
                <div class="story-content-side">
                    <div class="story-label">
                        <span class="material-symbols-outlined">history_edu</span>
                        <?php _e('about_page.our_story'); ?>
                    </div>
                    <h2 class="story-title"><?php _e('about_page.story_title'); ?></h2>
                    <p class="story-text">
                        <span class="story-highlight">Aurora Hotel Plaza</span> <?php _e('about_page.story_p1'); ?>
                    </p>
                    <p class="story-text"><?php _e('about_page.story_p2'); ?></p>
                    <p class="story-text"><?php _e('about_page.story_p3'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-floating">
        <div class="max-w-7xl mx-auto px-4">
            <div class="stats-grid-glass">
                <div class="stat-glass-card">
                    <div class="stat-icon-ring">
                        <span class="material-symbols-outlined">hotel</span>
                    </div>
                    <div class="stat-number-glass">150+</div>
                    <div class="stat-label-glass"><?php _e('about_page.rooms_apartments'); ?></div>
                </div>
                <div class="stat-glass-card">
                    <div class="stat-icon-ring">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <div class="stat-number-glass">5000+</div>
                    <div class="stat-label-glass"><?php _e('about_page.happy_customers'); ?></div>
                </div>
                <div class="stat-glass-card">
                    <div class="stat-icon-ring">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <div class="stat-number-glass">24/7</div>
                    <div class="stat-label-glass"><?php _e('about_page.support_service'); ?></div>
                </div>
                <div class="stat-glass-card">
                    <div class="stat-icon-ring">
                        <span class="material-symbols-outlined">workspace_premium</span>
                    </div>
                    <div class="stat-number-glass">10+</div>
                    <div class="stat-label-glass"><?php _e('about_page.years_experience'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section-glass">
        <div class="max-w-7xl mx-auto px-4">
            <div class="values-header">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('about_page.core_values'); ?></span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4"><?php _e('about_page.what_we_offer'); ?></h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark"><?php _e('about_page.values_desc'); ?></p>
            </div>
            
            <div class="values-grid-glass">
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">hotel_class</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.luxury'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.luxury_desc'); ?></p>
                </div>
                
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.dedicated_service'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.dedicated_service_desc'); ?></p>
                </div>
                
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.prime_location'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.prime_location_desc'); ?></p>
                </div>
                
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">restaurant</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.diverse_cuisine'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.diverse_cuisine_desc'); ?></p>
                </div>
                
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">spa</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.modern_amenities'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.modern_amenities_desc'); ?></p>
                </div>
                
                <div class="value-card-glass">
                    <div class="value-icon-glass">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                    <h3 class="value-title-glass"><?php _e('about_page.quality_guaranteed'); ?></h3>
                    <p class="value-desc-glass"><?php _e('about_page.quality_guaranteed_desc'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Facilities Section -->
    <section class="facilities-glass">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('about_page.amenities'); ?></span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4"><?php _e('about_page.five_star_experience'); ?></h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto"><?php _e('about_page.amenities_intro'); ?></p>
            </div>
            
            <div class="facilities-grid">
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">hotel</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_rooms'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">restaurant</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_restaurant'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">pool</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_pool'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">fitness_center</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_gym'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">spa</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_spa'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">groups</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_conference'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">airport_shuttle</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_shuttle'); ?></span>
                </div>
                <div class="facility-item-glass">
                    <div class="facility-icon-glass"><span class="material-symbols-outlined">local_parking</span></div>
                    <span class="facility-text-glass"><?php _e('about_page.amenity_parking'); ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-20 md:py-28">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('about_page.location'); ?></span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4"><?php _e('about_page.find_us'); ?></h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark">
                    Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai
                </p>
            </div>
            <div class="map-glass-wrapper">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
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
    <section class="cta-elegant">
        <div class="max-w-7xl mx-auto px-4">
            <div class="cta-glass-card">
                <div class="glass-badge mb-4 inline-flex">
                    <span class="material-symbols-outlined text-accent text-sm">support_agent</span>
                    Hỗ trợ 24/7
                </div>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                    <?php _e('about_page.ready_to_experience'); ?>
                </h2>
                <p class="text-white/85 text-lg mb-8 max-w-2xl mx-auto">
                    <?php _e('about_page.cta_desc'); ?>
                </p>
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="booking/index.php" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-primary rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <?php _e('about_page.book_now'); ?>
                    </a>
                    <a href="tel:+842513918888" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-bold hover:bg-white/20 transition-all">
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