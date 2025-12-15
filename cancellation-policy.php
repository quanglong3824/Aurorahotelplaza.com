<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$page_title = __('cancellation.title');
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
        <div class="policy-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">event_busy</span>
                <?php _e('cancellation.badge'); ?>
            </span>
            <h1 class="policy-hero-title"><?php _e('cancellation.title'); ?></h1>
            <p class="policy-hero-subtitle"><?php _e('cancellation.subtitle'); ?></p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Important Notice -->
            <div class="glass-card-accent p-6 mb-8 border-l-4 border-accent">
                <div class="flex items-start gap-4">
                    <span class="material-symbols-outlined text-accent text-3xl">info</span>
                    <div>
                        <h3 class="font-bold text-lg mb-2"><?php _e('cancellation.important_notice'); ?></h3>
                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                            <?php _e('cancellation.notice_text'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cancellation Timeline -->
            <div class="mb-12">
                <h2 class="font-display text-2xl font-bold mb-6 text-center"><?php _e('cancellation.refund_chart'); ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="cancellation-card cancellation-full">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_very_satisfied</span>
                        </div>
                        <div class="cancellation-time"><?php _e('cancellation.days_7plus'); ?></div>
                        <div class="cancellation-percent">100%</div>
                        <div class="cancellation-label"><?php _e('cancellation.full_refund'); ?></div>
                    </div>
                    <div class="cancellation-card cancellation-high">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_satisfied</span>
                        </div>
                        <div class="cancellation-time"><?php _e('cancellation.days_3_6'); ?></div>
                        <div class="cancellation-percent">70%</div>
                        <div class="cancellation-label"><?php _e('cancellation.refund_70'); ?></div>
                    </div>
                    <div class="cancellation-card cancellation-medium">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_neutral</span>
                        </div>
                        <div class="cancellation-time"><?php _e('cancellation.days_1_2'); ?></div>
                        <div class="cancellation-percent">50%</div>
                        <div class="cancellation-label"><?php _e('cancellation.refund_50'); ?></div>
                    </div>
                    <div class="cancellation-card cancellation-none">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_dissatisfied</span>
                        </div>
                        <div class="cancellation-time"><?php _e('cancellation.hours_24'); ?></div>
                        <div class="cancellation-percent">0%</div>
                        <div class="cancellation-label"><?php _e('cancellation.no_refund'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Policy Content -->
            <div class="policy-content">
                <div id="chinh-sach-chung" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">1</span>
                        <?php _e('cancellation.section1_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-accent/10">
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30"><?php _e('cancellation.cancel_time'); ?></th>
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30"><?php _e('cancellation.cancel_fee'); ?></th>
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30"><?php _e('cancellation.refund'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4"><?php _e('cancellation.before_7days'); ?></td>
                                        <td class="p-4 text-green-600 font-semibold"><?php _e('cancellation.free'); ?></td>
                                        <td class="p-4"><?php _e('cancellation.deposit_100'); ?></td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4"><?php _e('cancellation.before_3_6days'); ?></td>
                                        <td class="p-4 text-yellow-600 font-semibold"><?php _e('cancellation.deposit_30'); ?></td>
                                        <td class="p-4"><?php _e('cancellation.deposit_70'); ?></td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4"><?php _e('cancellation.before_1_2days'); ?></td>
                                        <td class="p-4 text-orange-600 font-semibold"><?php _e('cancellation.deposit_50'); ?></td>
                                        <td class="p-4"><?php _e('cancellation.deposit_50'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="p-4"><?php _e('cancellation.within_24h'); ?></td>
                                        <td class="p-4 text-red-600 font-semibold"><?php _e('cancellation.deposit_100'); ?></td>
                                        <td class="p-4"><?php _e('cancellation.no_refund'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="loai-gia" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">2</span>
                        <?php _e('cancellation.section2_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="glass-card-solid p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-green-500 text-2xl">check_circle</span>
                                    <h4 class="font-bold text-lg"><?php _e('cancellation.flexible_rate'); ?></h4>
                                </div>
                                <ul class="space-y-2 text-sm">
                                    <?php foreach(explode('|', __('cancellation.flexible_items')) as $item): ?>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-500">✓</span>
                                        <?php echo $item; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="glass-card-solid p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-red-500 text-2xl">lock</span>
                                    <h4 class="font-bold text-lg"><?php _e('cancellation.non_refundable'); ?></h4>
                                </div>
                                <ul class="space-y-2 text-sm">
                                    <?php 
                                    $items = explode('|', __('cancellation.non_refundable_items'));
                                    foreach($items as $i => $item): 
                                        $isPositive = ($i == count($items) - 1);
                                    ?>
                                    <li class="flex items-start gap-2">
                                        <span class="<?php echo $isPositive ? 'text-green-500' : 'text-red-500'; ?>"><?php echo $isPositive ? '✓' : '✗'; ?></span>
                                        <?php echo $item; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mua-cao-diem" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">3</span>
                        <?php _e('cancellation.section3_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <div class="glass-card-accent p-6 mb-4">
                            <h4 class="font-bold mb-3"><?php _e('cancellation.peak_season_intro'); ?></h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="glass-badge-solid"><?php _e('cancellation.christmas'); ?></span>
                                <span class="glass-badge-solid"><?php _e('cancellation.new_year'); ?></span>
                                <span class="glass-badge-solid"><?php _e('cancellation.lunar_new_year'); ?></span>
                                <span class="glass-badge-solid"><?php _e('cancellation.april_may'); ?></span>
                                <span class="glass-badge-solid"><?php _e('cancellation.national_day'); ?></span>
                            </div>
                        </div>
                        <p><strong><?php _e('cancellation.peak_policy'); ?></strong></p>
                        <ul>
                            <?php foreach(explode('|', __('cancellation.peak_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="thay-doi" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">4</span>
                        <?php _e('cancellation.section4_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <h4>4.1. <?php _e('cancellation.change_date'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('cancellation.change_date_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h4>4.2. <?php _e('cancellation.change_room'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('cancellation.change_room_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <h4>4.3. <?php _e('cancellation.shorten_stay'); ?></h4>
                        <ul>
                            <?php foreach(explode('|', __('cancellation.shorten_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="hoan-tien" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">5</span>
                        <?php _e('cancellation.section5_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">1</span>
                                </div>
                                <h5 class="font-bold mb-2"><?php _e('cancellation.step1'); ?></h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('cancellation.step1_desc'); ?></p>
                            </div>
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">2</span>
                                </div>
                                <h5 class="font-bold mb-2"><?php _e('cancellation.step2'); ?></h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('cancellation.step2_desc'); ?></p>
                            </div>
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">3</span>
                                </div>
                                <h5 class="font-bold mb-2"><?php _e('cancellation.step3'); ?></h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('cancellation.step3_desc'); ?></p>
                            </div>
                        </div>
                        
                        <h4><?php _e('cancellation.refund_method'); ?></h4>
                        <ul>
                            <li><?php _e('cancellation.refund_card'); ?></li>
                            <li><?php _e('cancellation.refund_transfer'); ?></li>
                            <li><?php _e('cancellation.refund_cash'); ?></li>
                        </ul>
                    </div>
                </div>

                <div id="bat-kha-khang" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">6</span>
                        <?php _e('cancellation.section6_title'); ?>
                    </h2>
                    <div class="policy-section-content">
                        <p><?php _e('cancellation.force_majeure_intro'); ?></p>
                        <ul>
                            <?php foreach(explode('|', __('cancellation.force_majeure_items')) as $item): ?>
                            <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-4">
                            <?php _e('cancellation.force_majeure_note'); ?>
                        </p>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="glass-card-solid p-6 mt-8">
                    <h3 class="font-bold text-xl mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">support_agent</span>
                        <?php _e('cancellation.contact_support'); ?>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="mb-4"><?php _e('cancellation.contact_intro'); ?></p>
                            <div class="space-y-3">
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">phone</span>
                                    <a href="tel:+842513918888" class="hover:text-accent font-semibold">(+84-251) 391.8888</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">email</span>
                                    <a href="mailto:booking@aurorahotelplaza.com" class="hover:text-accent">booking@aurorahotelplaza.com</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">schedule</span>
                                    <span><?php _e('cancellation.support_24_7'); ?></span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="mb-4"><?php _e('cancellation.info_required'); ?></p>
                            <ul class="space-y-2 text-sm">
                                <?php foreach(explode('|', __('cancellation.info_items')) as $item): ?>
                                <li class="flex items-center gap-2">
                                    <span class="text-accent">•</span>
                                    <?php echo $item; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
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

/* Cancellation Cards */
.cancellation-card {
    text-align: center;
    padding: 24px 16px;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.cancellation-card:hover {
    transform: translateY(-8px);
}

.cancellation-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.cancellation-time {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    opacity: 0.9;
}

.cancellation-percent {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 4px;
}

.cancellation-label {
    font-size: 13px;
    opacity: 0.8;
}

.cancellation-full {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.cancellation-high {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.cancellation-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.cancellation-none {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
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
    
    .cancellation-percent {
        font-size: 28px;
    }
}
</style>

</body>
</html>
