<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('apartment_detail.indochine_studio_title'); ?></title>
    <link href="<?php echo asset('css/tailwind-output.css'); ?>" rel="stylesheet" />
    <link href="<?php echo asset('css/fonts.css'); ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/pages-glass.css'); ?>">
    <style>
        body.glass-page::before {
            background-image: url('<?php echo imgUrl('assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg'); ?>') !important;
        }
    </style>
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include __DIR__ . '/../../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Top Hero Section -->
            <div class="relative min-h-[60vh] flex items-center justify-center pt-[100px] pb-12 px-4">
                <!-- Hero Background -->
                <div class="absolute inset-0 z-0">
                    <img src="<?php echo imgUrl('assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg'); ?>"
                        class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>

                <div class="relative z-10 text-center max-w-4xl mx-auto">
                    <span
                        class="glass-badge-pill mb-6 mx-auto bg-accent/20 border-accent/40 text-accent"><?php _e('apartment_detail.badge_indochine'); ?></span>
                    <h1
                        class="text-4xl md:text-6xl font-bold text-white mb-4 font-display text-shadow-lg tracking-tight">
                        <?php _e('apartment_detail.indochine_studio_name'); ?>
                    </h1>
                </div>
            </div>

            <div class="glass-page-wrapper relative z-20 -mt-12">
                <div class="container mx-auto px-4 pb-16">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            <div class="glass-card p-8">
                                <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">description</span>
                                    <?php _e('apartment_detail.indochine_studio_name'); ?>
                                </h2>
                                <p class="text-white/80 leading-relaxed text-lg">
                                    <?php _e('apartment_detail.indochine_studio_desc'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="lg:col-span-1">
                            <div class="sticky top-32 glass-booking-form !p-6 !block space-y-6">
                                <form action="<?php echo url('booking/index.php'); ?>" method="get" class="space-y-4 !block">
                                    <input type="hidden" name="room_type" value="<?php echo htmlspecialchars($room_slug); ?>">
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-white ml-1"><?php _e('apartment_detail.check_in_date'); ?></label>
                                        <input type="date" name="check_in" class="glass-input w-full" required>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-white ml-1"><?php _e('apartment_detail.check_out_date'); ?></label>
                                        <input type="date" name="check_out" class="glass-input w-full" required>
                                    </div>
                                    <button type="submit" class="btn-glass-primary w-full justify-center !mt-6 shadow-lg shadow-accent/20">
                                        <?php _e('inquiry.contact_btn'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                require_once __DIR__ . '/../../helpers/room-helper.php';
                $sectionTitle = __('apartment_detail.other_apartments');
                include __DIR__ . '/../../includes/related-rooms.php';
                ?>
            </div>
        </main>
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>

</html>
