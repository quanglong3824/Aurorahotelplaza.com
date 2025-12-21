<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
initLanguage();

$room_slug = 'premium-twin';
$room_price = 1600000;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT base_price FROM room_types WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$room_slug]);
    $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($room_data)
        $room_price = $room_data['base_price'];
} catch (Exception $e) {
    error_log("Room detail error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('room_detail.premium_twin_title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Top Hero Section -->
            <div class="relative min-h-[60vh] flex items-center justify-center pt-[100px] pb-12 px-4">
                <!-- Hero Background -->
                <div class="absolute inset-0 z-0">
                    <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg'); ?>"
                        class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>

                <div class="relative z-10 text-center max-w-4xl mx-auto">
                    <span
                        class="glass-badge-pill mb-6 mx-auto bg-accent/20 border-accent/40 text-accent"><?php _e('room_detail.premium_twin_badge'); ?></span>
                    <h1
                        class="text-4xl md:text-6xl font-bold text-white mb-4 font-display text-shadow-lg tracking-tight">
                        <?php _e('room_detail.premium_twin_name'); ?>
                    </h1>
                    <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto font-light leading-relaxed">
                        <?php _e('room_detail.premium_twin_subtitle'); ?>
                    </p>
                </div>
            </div>

            <!-- Glass Page Wrapper for Content -->
            <div class="glass-page-wrapper relative z-20 -mt-12">
                <div class="container mx-auto px-4 pb-16">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- Left Column: Details -->
                        <div class="lg:col-span-2 space-y-8">

                            <!-- Description Card -->
                            <div class="glass-card p-8">
                                <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">description</span>
                                    <?php _e('room_detail.description'); ?>
                                </h2>
                                <p class="text-white/80 leading-relaxed text-lg">
                                    <?php _e('room_detail.premium_twin_desc'); ?>
                                </p>
                            </div>

                            <!-- Specs Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">bed</span>
                                    <span class="text-sm text-white/60"><?php _e('room_detail.bed_type'); ?></span>
                                    <span class="font-bold text-accent"><?php _e('room_detail.bed_twin'); ?></span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">square_foot</span>
                                    <span class="text-sm text-white/60"><?php _e('room_detail.area'); ?></span>
                                    <span class="font-bold text-accent">42 m²</span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">group</span>
                                    <span class="text-sm text-white/60"><?php _e('room_detail.capacity'); ?></span>
                                    <span class="font-bold text-accent">2 <?php _e('room_detail.guests'); ?></span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">visibility</span>
                                    <span class="text-sm text-white/60"><?php _e('room_detail.view'); ?></span>
                                    <span class="font-bold text-accent"><?php _e('room_detail.city_view'); ?></span>
                                </div>
                            </div>

                            <!-- Amenities -->
                            <div class="glass-card p-8">
                                <h3 class="text-xl font-bold text-white mb-6 border-b border-white/10 pb-4">
                                    <?php _e('room_detail.amenities'); ?>
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">wifi</span>
                                        <?php _e('room_detail.amenity_wifi'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">tv</span>
                                        <?php _e('room_detail.amenity_tv'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">ac_unit</span>
                                        <?php _e('room_detail.amenity_ac'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">kitchen</span>
                                        <?php _e('room_detail.amenity_minibar'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">lock</span>
                                        <?php _e('room_detail.amenity_safe'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">desk</span>
                                        <?php _e('room_detail.amenity_desk'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">bathtub</span>
                                        <?php _e('room_detail.amenity_bathroom'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">shower</span>
                                        <?php _e('room_detail.amenity_shower'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">dry_cleaning</span>
                                        <?php _e('room_detail.amenity_hairdryer'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">soap</span>
                                        <?php _e('room_detail.amenity_toiletries'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">do_not_step</span>
                                        <?php _e('room_detail.amenity_slippers'); ?></div>
                                    <div class="flex items-center gap-3 text-white/80"><span
                                            class="material-symbols-outlined text-accent text-sm">coffee_maker</span>
                                        <?php _e('room_detail.amenity_kettle'); ?></div>
                                </div>
                            </div>

                            <!-- Gallery -->
                            <div>
                                <h3 class="text-2xl font-bold text-white mb-6 text-center">
                                    <?php _e('room_detail.room_gallery'); ?>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-2.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-3.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-4.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-6.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="aspect-video rounded-2xl overflow-hidden glass-card-solid group">
                                        <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-7.jpg'); ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Booking Form (Sticky) -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-32 glass-booking-form !p-6 !block space-y-6">
                                <div class="text-center pb-6 border-b border-white/10">
                                    <p class="text-sm text-white/60 uppercase tracking-wider mb-1">
                                        <?php _e('room_detail.room_price'); ?>
                                    </p>
                                    <div class="flex items-end justify-center gap-1">
                                        <span
                                            class="text-3xl font-bold text-accent"><?php echo number_format($room_price, 0, ',', '.'); ?>đ</span>
                                        <span
                                            class="text-sm text-white/60 mb-1">/<?php _e('room_detail.night'); ?></span>
                                    </div>
                                </div>

                                <form action="../booking/index.php" method="get" class="space-y-4 !block">
                                    <input type="hidden" name="room_type" value="premium-twin">

                                    <div class="space-y-2">
                                        <label
                                            class="text-sm font-bold text-white ml-1"><?php _e('room_detail.check_in_date'); ?></label>
                                        <div class="relative">
                                            <input type="date" name="check_in" class="glass-input w-full" required>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label
                                            class="text-sm font-bold text-white ml-1"><?php _e('room_detail.check_out_date'); ?></label>
                                        <div class="relative">
                                            <input type="date" name="check_out" class="glass-input w-full" required>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label
                                            class="text-sm font-bold text-white ml-1"><?php _e('room_detail.num_guests'); ?></label>
                                        <div class="relative">
                                            <select name="guests" class="glass-input glass-select w-full text-white">
                                                <option value="1" class="text-slate-800">1
                                                    <?php _e('room_detail.person'); ?>
                                                </option>
                                                <option value="2" selected class="text-slate-800">2
                                                    <?php _e('room_detail.person'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="btn-glass-primary w-full justify-center !mt-6 shadow-lg shadow-accent/20">
                                        <?php _e('room_detail.book_now'); ?>
                                    </button>
                                </form>

                                <div class="text-center pt-4 border-t border-white/10">
                                    <p class="text-sm text-white/60 mb-2"><?php _e('room_detail.or_call'); ?></p>
                                    <a href="tel:+842513918888"
                                        class="text-lg font-bold text-white hover:text-accent transition-colors flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">call</span>
                                        (+84-251) 391.8888
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Related Rooms
                require_once __DIR__ . '/../helpers/room-helper.php';
                $currentRoom = getRoomBySlug('premium-twin');
                $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
                $sectionTitle = __('room_detail.other_rooms');
                include '../includes/related-rooms.php';
                ?>

            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="../assets/js/room-detail-validation.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>