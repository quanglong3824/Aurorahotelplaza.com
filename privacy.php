<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$page_title = __('privacy.title');
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo $page_title; ?> - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="policy-hero">
        <div class="policy-hero-overlay"></div>
        <div class="policy-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">security</span>
                <?php _e('privacy.badge'); ?>
            </span>
            <h1 class="policy-hero-title"><?php _e('privacy.title'); ?></h1>
            <p class="policy-hero-subtitle"><?php _e('privacy.subtitle'); ?></p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Quick Navigation -->
            <div class="glass-card-solid p-6 mb-8">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent">menu_book</span>
                    <?php _e('privacy.toc'); ?>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="#thu-thap" class="policy-nav-link">1. <?php _e('privacy.section1_title'); ?></a>
                    <a href="#su-dung" class="policy-nav-link">2. <?php _e('privacy.section2_title'); ?></a>
                    <a href="#bao-mat" class="policy-nav-link">3. <?php _e('privacy.section3_title'); ?></a>
                    <a href="#chia-se" class="policy-nav-link">4. <?php _e('privacy.section4_title'); ?></a>
                    <a href="#cookie" class="policy-nav-link">5. <?php _e('privacy.section5_title'); ?></a>
                    <a href="#quyen-loi" class="policy-nav-link">6. <?php _e('privacy.section6_title'); ?></a>
                    <a href="#lien-he" class="policy-nav-link">7. <?php _e('privacy.section7_title'); ?></a>
                </div>
            </div>

            <!-- Policy Content -->
            <div class="policy-content">
                <div class="policy-intro glass-card-solid p-6 mb-8">
                    <p class="text-lg leading-relaxed">
                        <?php _e('privacy.intro'); ?>
                    </p>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-4">
                        <strong><?php _e('privacy.last_updated'); ?>:</strong> 01/12/2025
                    </p>
                </div>

                <div id="thu-thap" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">1</span>
                        <?php _e('privacy.section1_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section1_intro'); ?></p>
                        <h4>1.1. <?php _e('privacy.section1_1'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section1_1_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h4>1.2. <?php _e('privacy.section1_2'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section1_2_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h4>1.3. <?php _e('privacy.section1_3'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section1_3_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="su-dung" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">2</span>
                        <?php _e('privacy.section2_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section2_intro'); ?></p>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section2_items')) as $item): 
                                $parts = explode(': ', $item, 2);
                            ?>
                            <li><strong><?php echo $parts[0]; ?>:</strong> <?php echo $parts[1] ?? ''; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="bao-mat" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">3</span>
                        <?php _e('privacy.section3_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section3_intro'); ?></p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">encrypted</span>
                                <h4 class="font-bold mb-1"><?php _e('privacy.ssl_title'); ?></h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('privacy.ssl_desc'); ?></p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">shield</span>
                                <h4 class="font-bold mb-1"><?php _e('privacy.firewall_title'); ?></h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('privacy.firewall_desc'); ?></p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">lock</span>
                                <h4 class="font-bold mb-1"><?php _e('privacy.access_title'); ?></h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('privacy.access_desc'); ?></p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">backup</span>
                                <h4 class="font-bold mb-1"><?php _e('privacy.backup_title'); ?></h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('privacy.backup_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="chia-se" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">4</span>
                        <?php _e('privacy.section4_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section4_intro'); ?></p>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section4_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="cookie" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">5</span>
                        <?php _e('privacy.section5_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section5_intro'); ?></p>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section5_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-4"><?php _e('privacy.section5_note'); ?></p>
                    </div>
                </div>

                <div id="quyen-loi" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">6</span>
                        <?php _e('privacy.section6_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section6_intro'); ?></p>
                        <ul>
                            <?php foreach(explode('|', __('privacy.section6_items')) as $item): 
                                $parts = explode(': ', $item, 2);
                            ?>
                            <li><strong><?php echo $parts[0]; ?>:</strong> <?php echo $parts[1] ?? ''; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="lien-he" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">7</span>
                        <?php _e('privacy.section7_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('privacy.section7_intro'); ?></p>
                        <div class="glass-card-solid p-6 mt-4">
                            <h4 class="font-bold text-lg mb-4">Aurora Hotel Plaza</h4>
                            <div class="space-y-3">
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">location_on</span>
                                    Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Đồng Nai
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">phone</span>
                                    <a href="tel:+842513918888" class="hover:text-accent">(+84-251) 391.8888</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">email</span>
                                    <a href="mailto:privacy@aurorahotelplaza.com" class="hover:text-accent">privacy@aurorahotelplaza.com</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<style>
.policy-hero {
    position: relative;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.7)), url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
    background-size: cover;
    background-position: center;
    padding: 160px 20px 80px;
    text-align: center;
    color: white;
    min-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.policy-hero-overlay {
    display: none;
}

.policy-hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
}

.policy-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 16px;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.policy-hero-subtitle {
    font-size: 18px;
    opacity: 0.9;
}

.policy-nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(212, 175, 55, 0.1);
    border-radius: 8px;
    color: var(--text-primary-light);
    font-size: 14px;
    transition: all 0.2s ease;
}

.policy-nav-link:hover {
    background: rgba(212, 175, 55, 0.2);
    color: #cc9a2c;
}

.dark .policy-nav-link {
    color: var(--text-primary-dark);
}

.policy-section {
    margin-bottom: 40px;
}

.policy-section-title {
    display: flex;
    align-items: center;
    gap: 16px;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
}

.policy-section-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border-radius: 50%;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 18px;
    font-weight: 700;
}

.policy-section-content {
    padding-left: 56px;
}

.policy-section-content p {
    margin-bottom: 16px;
    line-height: 1.8;
}

.policy-section-content h4 {
    font-weight: 700;
    margin: 20px 0 12px;
    color: #cc9a2c;
}

.policy-section-content ul {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.policy-section-content ul li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 12px;
    line-height: 1.6;
}

.policy-section-content ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #cc9a2c;
    font-weight: 700;
}

@media (max-width: 768px) {
    .policy-hero-title {
        font-size: 32px;
    }
    
    .policy-section-content {
        padding-left: 0;
    }
    
    .policy-section-title {
        font-size: 20px;
    }
}
</style>

</body>
</html>
