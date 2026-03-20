<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
initLanguage();

$room_slug = 'premium-apartment';
$room_price = 4200000;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT base_price FROM room_types WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$room_slug]);
    $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($room_data)
        $room_price = $room_data['base_price'];
} catch (Exception $e) {
    error_log("Apartment detail error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('apartment_detail.premium_title'); ?></title>
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
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
                    <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg'); ?>"
                        class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>

                <div class="relative z-10 text-center max-w-4xl mx-auto">
                    <span
                        class="glass-badge-pill mb-6 mx-auto bg-accent/20 border-accent/40 text-accent"><?php _e('apartment_detail.badge_premium'); ?></span>
                    <h1
                        class="text-4xl md:text-6xl font-bold text-white mb-4 font-display text-shadow-lg tracking-tight">
                        <?php _e('apartment_detail.premium_name'); ?>
                    </h1>
                    <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto font-light leading-relaxed">
                        <?php _e('apartment_detail.premium_subtitle'); ?>
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
                                    <?php _e('apartment_detail.premium_name'); ?>
                                </h2>
                                <p class="text-white/80 leading-relaxed text-lg">
                                    <?php _e('apartment_detail.premium_desc'); ?>
                                </p>
                            </div>

                            <!-- Specs Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">bed</span>
                                    <span class="text-sm text-white/60"><?php _e('apartment_detail.bed_type'); ?></span>
                                    <span class="font-bold text-accent"><?php _e('apartment_detail.premium_bed'); ?></span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">square_foot</span>
                                    <span class="text-sm text-white/60"><?php _e('apartment_detail.area'); ?></span>
                                    <span class="font-bold text-accent">70 m²</span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">group</span>
                                    <span class="text-sm text-white/60"><?php _e('apartment_detail.capacity'); ?></span>
                                    <span class="font-bold text-accent">2-4 <?php _e('apartment_detail.persons'); ?></span>
                                </div>
                                <div class="glass-amenity-card">
                                    <span class="material-symbols-outlined text-3xl mb-2">countertops</span>
                                    <span class="text-sm text-white/60"><?php _e('apartment_detail.kitchen'); ?></span>
                                    <span class="font-bold text-accent"><?php _e('apartment_detail.premium_kitchen'); ?></span>
                                </div>
                            </div>

                            <!-- Amenities Grid -->
                            <div class="glass-card p-8">
                                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">fact_check</span>
                                    <?php _e('apartment_detail.amenities'); ?>
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-y-4 gap-x-6">
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_wifi'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_tv'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_ac'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_stove'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_fridge'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_washer'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_desk'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_cookware'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_dishes'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_bathroom'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_hairdryer'); ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-white/80">
                                        <span class="material-symbols-outlined text-accent text-sm">check_circle</span>
                                        <?php _e('apartment_detail.amenity_toiletries'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Sidebar Booking -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-24 space-y-6">
                                <!-- Price Glass Card -->
                                <div class="glass-card p-8 border-t-4 border-t-accent">
                                    <div class="flex items-baseline justify-between mb-6">
                                        <span class="text-white/60"><?php _e('apartment_detail.apartment_price'); ?></span>
                                        <div class="text-right">
                                            <div class="text-3xl font-bold text-accent">
                                                <?php echo number_format($room_price, 0, ',', '.'); ?>VND</div>
                                            <div class="text-sm text-white/60"><?php _e('apartment_detail.per_night'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-accent/10 border border-accent/20 rounded-lg p-3 mb-6 flex items-center gap-3 text-sm text-accent">
                                        <span class="material-symbols-outlined">auto_awesome</span>
                                        <?php _e('apartment_detail.discount_25_7days'); ?>
                                    </div>

                                    <form class="space-y-4" action="../booking/index.php" method="get">
                                        <input type="hidden" name="room_type" value="<?php echo $room_slug; ?>">
                                        
                                        <div class="space-y-2">
                                            <label class="text-sm text-white/60 ml-1"><?php _e('apartment_detail.check_in_date'); ?></label>
                                            <input type="date" name="check_in" 
                                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent transition-colors" required>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <label class="text-sm text-white/60 ml-1"><?php _e('apartment_detail.check_out_date'); ?></label>
                                            <input type="date" name="check_out" 
                                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent transition-colors" required>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm text-white/60 ml-1"><?php _e('apartment_detail.num_guests'); ?></label>
                                            <select name="guests" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent transition-colors">
                                                <option value="1" class="bg-slate-900">1 <?php _e('apartment_detail.persons'); ?></option>
                                                <option value="2" class="bg-slate-900">2 <?php _e('apartment_detail.persons'); ?></option>
                                                <option value="3" class="bg-slate-900">3 <?php _e('apartment_detail.persons'); ?></option>
                                                <option value="4" selected class="bg-slate-900">4 <?php _e('apartment_detail.persons'); ?></option>
                                            </select>
                                        </div>

                                        <button type="submit" class="w-full bg-accent hover:bg-accent-light text-slate-900 font-bold py-4 rounded-xl shadow-lg shadow-accent/20 transition-all active:scale-[0.98]">
                                            <?php _e('inquiry.contact_btn'); ?>
                                        </button>
                                    </form>

                                    <div class="mt-8 pt-6 border-t border-white/10 text-center">
                                        <p class="text-white/60 text-sm mb-2"><?php _e('apartment_detail.or_call'); ?></p>
                                        <a href="tel:+842513918888" class="text-xl font-bold text-white hover:text-accent transition-colors">
                                            (+84-251) 391.8888
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Section -->
                    <div class="mt-16">
                        <h2 class="text-3xl font-bold text-white mb-8 text-center"><?php _e('apartment_detail.gallery'); ?></h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="glass-card overflow-hidden group aspect-video md:col-span-2 md:row-span-2">
                                <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg'); ?>" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="glass-card overflow-hidden group aspect-video">
                                <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-2.jpg'); ?>" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="glass-card overflow-hidden group aspect-video">
                                <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-3.jpg'); ?>" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="glass-card overflow-hidden group aspect-video">
                                <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-7.jpg'); ?>" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="glass-card overflow-hidden group aspect-video">
                                <img src="<?php echo imgUrl('assets/img/premium-apartment/can-ho-premium-aurora-hotel-8.jpg'); ?>" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                        </div>
                    </div>

                    <!-- Related Rooms -->
                    <div class="mt-16 pt-16 border-t border-white/10">
                        <?php
                        require_once __DIR__ . '/../helpers/room-helper.php';
                        $currentRoom = getRoomBySlug($room_slug);
                        $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
                        $sectionTitle = __('apartment_detail.other_apartments');
                        include '../includes/related-rooms.php';
                        ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="../assets/js/main.js"></script>
</body>

</html>
