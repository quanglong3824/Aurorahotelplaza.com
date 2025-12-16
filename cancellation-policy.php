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
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php echo $page_title; ?> - Aurora Hotel Plaza</title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
    <link rel="stylesheet" href="assets/css/policy.css">
</head>

<body class="bg-slate-900 font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Glass Page Wrapper -->
            <div class="glass-page-wrapper"
                style="background-image: url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');">

                <!-- Hero Section - Using Policy Hero Class -->
                <section class="policy-hero-glass">
                    <div class="hero-glass-card">
                        <div class="glass-badge-pill mb-4 justify-center mx-auto">
                            <span class="material-symbols-outlined text-sm">event_busy</span>
                            <?php _e('cancellation.badge'); ?>
                        </div>
                        <h1 class="hero-title-glass">
                            <?php _e('cancellation.title'); ?>
                        </h1>
                        <p class="hero-subtitle-glass">
                            <?php _e('cancellation.subtitle'); ?>
                        </p>
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
                                    <h3 class="font-bold text-lg mb-2 text-white">
                                        <?php _e('cancellation.important_notice'); ?>
                                    </h3>
                                    <p class="text-white/80">
                                        <?php _e('cancellation.notice_text'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Cancellation Timeline -->
                        <div class="mb-12">
                            <h2 class="font-display text-2xl font-bold mb-6 text-center text-white">
                                <?php _e('cancellation.refund_chart'); ?>
                            </h2>
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
                                    <div class="overflow-x-auto glass-card-solid p-0 overflow-hidden">
                                        <table class="w-full border-collapse">
                                            <thead>
                                                <tr class="bg-white/5">
                                                    <th
                                                        class="p-4 text-left font-bold border-b border-white/10 text-accent">
                                                        <?php _e('cancellation.cancel_time'); ?>
                                                    </th>
                                                    <th
                                                        class="p-4 text-left font-bold border-b border-white/10 text-accent">
                                                        <?php _e('cancellation.cancel_fee'); ?>
                                                    </th>
                                                    <th
                                                        class="p-4 text-left font-bold border-b border-white/10 text-accent">
                                                        <?php _e('cancellation.refund'); ?>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-white/80">
                                                <tr class="border-b border-white/5">
                                                    <td class="p-4"><?php _e('cancellation.before_7days'); ?></td>
                                                    <td class="p-4 text-green-400 font-semibold">
                                                        <?php _e('cancellation.free'); ?>
                                                    </td>
                                                    <td class="p-4"><?php _e('cancellation.deposit_100'); ?></td>
                                                </tr>
                                                <tr class="border-b border-white/5">
                                                    <td class="p-4"><?php _e('cancellation.before_3_6days'); ?></td>
                                                    <td class="p-4 text-yellow-400 font-semibold">
                                                        <?php _e('cancellation.deposit_30'); ?>
                                                    </td>
                                                    <td class="p-4"><?php _e('cancellation.deposit_70'); ?></td>
                                                </tr>
                                                <tr class="border-b border-white/5">
                                                    <td class="p-4"><?php _e('cancellation.before_1_2days'); ?></td>
                                                    <td class="p-4 text-orange-400 font-semibold">
                                                        <?php _e('cancellation.deposit_50'); ?>
                                                    </td>
                                                    <td class="p-4"><?php _e('cancellation.deposit_50'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="p-4"><?php _e('cancellation.within_24h'); ?></td>
                                                    <td class="p-4 text-red-400 font-semibold">
                                                        <?php _e('cancellation.deposit_100'); ?>
                                                    </td>
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
                                                <span
                                                    class="material-symbols-outlined text-green-400 text-2xl">check_circle</span>
                                                <h4 class="font-bold text-lg text-white !m-0">
                                                    <?php _e('cancellation.flexible_rate'); ?>
                                                </h4>
                                            </div>
                                            <ul class="space-y-2 text-sm text-white/80 !m-0">
                                                <?php foreach (explode('|', __('cancellation.flexible_items')) as $item): ?>
                                                    <li class="flex items-start gap-2 !p-0 !m-0 before:!content-none">
                                                        <span class="text-green-400">✓</span>
                                                        <?php echo $item; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="glass-card-solid p-6">
                                            <div class="flex items-center gap-3 mb-4">
                                                <span
                                                    class="material-symbols-outlined text-red-400 text-2xl">lock</span>
                                                <h4 class="font-bold text-lg text-white !m-0">
                                                    <?php _e('cancellation.non_refundable'); ?>
                                                </h4>
                                            </div>
                                            <ul class="space-y-2 text-sm text-white/80 !m-0">
                                                <?php
                                                $items = explode('|', __('cancellation.non_refundable_items'));
                                                foreach ($items as $i => $item):
                                                    $isPositive = ($i == count($items) - 1);
                                                    ?>
                                                    <li class="flex items-start gap-2 !p-0 !m-0 before:!content-none">
                                                        <span
                                                            class="<?php echo $isPositive ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $isPositive ? '✓' : '✗'; ?></span>
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
                                <div class="policy-section-content text-white/80">
                                    <div class="glass-card-solid p-6 mb-4">
                                        <h4 class="font-bold mb-3 text-white !m-0">
                                            <?php _e('cancellation.peak_season_intro'); ?>
                                        </h4>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span
                                                class="glass-badge-solid"><?php _e('cancellation.christmas'); ?></span>
                                            <span class="glass-badge-solid"><?php _e('cancellation.new_year'); ?></span>
                                            <span
                                                class="glass-badge-solid"><?php _e('cancellation.lunar_new_year'); ?></span>
                                            <span
                                                class="glass-badge-solid"><?php _e('cancellation.april_may'); ?></span>
                                            <span
                                                class="glass-badge-solid"><?php _e('cancellation.national_day'); ?></span>
                                        </div>
                                    </div>
                                    <p><strong><?php _e('cancellation.peak_policy'); ?></strong></p>
                                    <ul>
                                        <?php foreach (explode('|', __('cancellation.peak_items')) as $item): ?>
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
                                <div class="policy-section-content text-white/80">
                                    <h4 class="text-accent">4.1. <?php _e('cancellation.change_date'); ?></h4>
                                    <ul>
                                        <?php foreach (explode('|', __('cancellation.change_date_items')) as $item): ?>
                                            <li><?php echo $item; ?></li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <h4 class="text-accent">4.2. <?php _e('cancellation.change_room'); ?></h4>
                                    <ul>
                                        <?php foreach (explode('|', __('cancellation.change_room_items')) as $item): ?>
                                            <li><?php echo $item; ?></li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <h4 class="text-accent">4.3. <?php _e('cancellation.shorten_stay'); ?></h4>
                                    <ul>
                                        <?php foreach (explode('|', __('cancellation.shorten_items')) as $item): ?>
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
                                <div class="policy-section-content text-white/80">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                        <div class="glass-card-solid p-4 text-center">
                                            <div
                                                class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                                <span class="text-accent font-bold text-xl">1</span>
                                            </div>
                                            <h5 class="font-bold mb-2 text-white"><?php _e('cancellation.step1'); ?>
                                            </h5>
                                            <p class="text-sm text-white/70"><?php _e('cancellation.step1_desc'); ?></p>
                                        </div>
                                        <div class="glass-card-solid p-4 text-center">
                                            <div
                                                class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                                <span class="text-accent font-bold text-xl">2</span>
                                            </div>
                                            <h5 class="font-bold mb-2 text-white"><?php _e('cancellation.step2'); ?>
                                            </h5>
                                            <p class="text-sm text-white/70"><?php _e('cancellation.step2_desc'); ?></p>
                                        </div>
                                        <div class="glass-card-solid p-4 text-center">
                                            <div
                                                class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                                <span class="text-accent font-bold text-xl">3</span>
                                            </div>
                                            <h5 class="font-bold mb-2 text-white"><?php _e('cancellation.step3'); ?>
                                            </h5>
                                            <p class="text-sm text-white/70"><?php _e('cancellation.step3_desc'); ?></p>
                                        </div>
                                    </div>

                                    <h4 class="text-accent"><?php _e('cancellation.refund_method'); ?></h4>
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
                                <div class="policy-section-content text-white/80">
                                    <p><?php _e('cancellation.force_majeure_intro'); ?></p>
                                    <ul>
                                        <?php foreach (explode('|', __('cancellation.force_majeure_items')) as $item): ?>
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
                                <h3 class="font-bold text-xl mb-4 flex items-center gap-2 text-white">
                                    <span class="material-symbols-outlined text-accent">support_agent</span>
                                    <?php _e('cancellation.contact_support'); ?>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-white/80">
                                    <div>
                                        <p class="mb-4"><?php _e('cancellation.contact_intro'); ?></p>
                                        <div class="space-y-3">
                                            <p class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-accent">phone</span>
                                                <a href="tel:+842513918888"
                                                    class="hover:text-accent font-semibold text-white">(+84-251)
                                                    391.8888</a>
                                            </p>
                                            <p class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-accent">email</span>
                                                <a href="mailto:booking@aurorahotelplaza.com"
                                                    class="hover:text-accent text-white">booking@aurorahotelplaza.com</a>
                                            </p>
                                            <p class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-accent">schedule</span>
                                                <span><?php _e('cancellation.support_24_7'); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="mb-4"><?php _e('cancellation.info_required'); ?></p>
                                        <ul class="space-y-2 text-sm !m-0">
                                            <?php foreach (explode('|', __('cancellation.info_items')) as $item): ?>
                                                <li class="flex items-center gap-2 !p-0 !m-0 before:!content-none">
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
            </div><!-- End Glass Page Wrapper -->
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

</body>

</html>