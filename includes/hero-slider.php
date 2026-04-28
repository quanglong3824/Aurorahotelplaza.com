<!-- Hero Slider Section -->
<?php
// Fetch active banners from database
$banners = [];
$useFallback = false;

try {
    if (!defined('DB_NAME')) {
        require_once __DIR__ . '/../config/database.php';
    }
    $db = getDB();
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, image_mobile, link_url FROM banners WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($banners)) {
        $useFallback = true;
    }
} catch (Throwable $e) {
    $useFallback = true;
    error_log('Hero slider banners error: ' . $e->getMessage());
}

// Fallback images if no banners in database
$fallbackImages = [
    imgUrl('assets/img/classical-family-apartment/classical-family-apartment6.jpg'),
    imgUrl('assets/img/classical-premium-apartment/classical-premium-apartment-2.jpg'),
    imgUrl('assets/img/indochine-family-apartment/indochine-family-apartment-12.jpg'),
    imgUrl('assets/img/indochine-studio-apartment/indochine-studio-apartment-3.jpg'),
    imgUrl('assets/img/modern-premium-apartment/modern-premium-apartment-4.jpg'),
    imgUrl('assets/img/modern-studio-apartment/modern-studio-apartment-5.jpg'),
    imgUrl('assets/img/restaurant/nha-hang-aurora-hotel-4.jpg'),
    imgUrl('assets/img/restaurant/nha-hang-aurora-hotel-6.jpg'),
    imgUrl('assets/img/post/wedding/tiec-cuoi-tai-aurora-5.jpg'),
    imgUrl('assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg'),
];
?>
<section class="hero-slider relative flex min-h-screen w-full items-center justify-center">
    <!-- Slider Images -->
    <?php if ($useFallback): ?>
        <!-- Fallback: Hardcoded images -->
        <div class="hero-slide active"
            style="background-image: url('<?php echo $fallbackImages[0]; ?>');"></div>
        <?php for ($i = 1; $i < count($fallbackImages); $i++): ?>
            <div class="hero-slide" data-bg="<?php echo $fallbackImages[$i]; ?>"></div>
        <?php endfor; ?>
    <?php else: ?>
        <!-- Dynamic banners from database -->
        <?php $first = true; foreach ($banners as $banner): ?>
            <?php $imageUrl = imgUrl($banner['image_desktop']); ?>
            <div class="hero-slide <?php echo $first ? 'active' : ''; ?>" 
                 <?php echo $first ? 'style="background-image: url(\'' . $imageUrl . '\');"' : 'data-bg="' . $imageUrl . '"'; ?>
                 data-title="<?php echo htmlspecialchars($banner['title']); ?>"
                 data-subtitle="<?php echo htmlspecialchars($banner['subtitle'] ?? ''); ?>"
                 data-link="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>">
            </div>
            <?php $first = false; endforeach; ?>
        
        <?php if (count($banners) < 3): ?>
            <?php for ($i = count($banners); $i < 3 && $i < count($fallbackImages); $i++): ?>
                <div class="hero-slide" data-bg="<?php echo $fallbackImages[$i]; ?>"></div>
            <?php endfor; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Previous Arrow -->
    <div class="slider-arrow prev">
        <span class="material-symbols-outlined arrow-icon">chevron_left</span>
    </div>

    <!-- Next Arrow -->
    <div class="slider-arrow next">
        <span class="material-symbols-outlined arrow-icon">chevron_right</span>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 flex flex-col items-center gap-8 text-center text-white px-4">
        <div class="flex flex-col gap-4">
            <span class="glass-badge-accent mx-auto">
                <span class="text-accent">★★★★</span>
                <?php _e('hero.badge'); ?>
            </span>
            <h1 class="font-display text-4xl font-black leading-tight tracking-tight md:text-6xl">Aurora Hotel Plaza
            </h1>
            <p class="text-xl font-light text-white/90 max-w-2xl"><?php _e('hero.subtitle'); ?></p>
        </div>

        <!-- Quick Booking Form - Liquid Glass -->
        <div class="mt-4 w-full max-w-4xl glass-booking-form">
            <form action="booking/index.php" method="GET" class="grid grid-cols-1 items-end gap-4 md:grid-cols-5">
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkin">
                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                        <?php _e('hero.check_in'); ?>
                    </label>
                    <input class="glass-input-solid h-12" id="checkin" name="check_in" type="date"
                        min="<?php echo date('Y-m-d'); ?>" max="2030-12-31" value="<?php echo date('Y-m-d'); ?>" />
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkout">
                        <span class="material-symbols-outlined text-sm">event</span>
                        <?php _e('hero.check_out'); ?>
                    </label>
                    <input class="glass-input-solid h-12" id="checkout" name="check_out" type="date"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" max="2030-12-31"
                        value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" />
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="adults">
                        <span class="material-symbols-outlined text-sm">person</span>
                        <?php _e('hero.adults'); ?>
                    </label>
                    <select class="glass-input-solid glass-select h-12" id="adults" name="adults">
                        <option value="1">1 <?php _e('hero.person'); ?></option>
                        <option value="2" selected>2 <?php _e('hero.person'); ?></option>
                        <option value="3">3 <?php _e('hero.person'); ?></option>
                        <option value="4">4 <?php _e('hero.person'); ?></option>
                    </select>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="children">
                        <span class="material-symbols-outlined text-sm">child_care</span>
                        <?php _e('hero.children'); ?>
                    </label>
                    <select class="glass-input-solid glass-select h-12" id="children" name="children">
                        <option value="0" selected>0 <?php _e('hero.child'); ?></option>
                        <option value="1">1 <?php _e('hero.child'); ?></option>
                        <option value="2">2 <?php _e('hero.child'); ?></option>
                        <option value="3">3 <?php _e('hero.child'); ?></option>
                    </select>
                </div>
                <button type="submit" class="btn-glass-primary h-12 w-full whitespace-nowrap">
                    <span class="material-symbols-outlined">search</span>
                    <span><?php _e('hero.search'); ?></span>
                </button>
            </form>
        </div>

        <!-- Trust Badges - Liquid Glass -->
        <div class="flex flex-wrap justify-center gap-4 mt-6">
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">verified</span>
                <span><?php _e('hero.best_price'); ?></span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">credit_card_off</span>
                <span><?php _e('hero.no_prepayment'); ?></span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">event_available</span>
                <span><?php _e('hero.free_cancel'); ?></span>
            </div>
        </div>
    </div>

    <!-- Slider Navigation Dots -->
    <div class="slider-dots" id="slider-dots-container">
        <!-- Dots will be generated dynamically by JavaScript -->
    </div>

    <!-- Scroll Down Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <a href="#about" class="flex flex-col items-center gap-2 text-white/70 hover:text-white transition-colors">
            <span class="text-sm"><?php _e('hero.explore_more'); ?></span>
            <span class="material-symbols-outlined text-2xl">keyboard_arrow_down</span>
        </a>
    </div>
</section>

<script>
    // Set minimum dates for booking form
    document.addEventListener('DOMContentLoaded', function () {
        const checkinInput = document.getElementById('checkin');
        const checkoutInput = document.getElementById('checkout');

        if (checkinInput && checkoutInput) {
            checkinInput.addEventListener('change', function () {
                const checkinDate = new Date(this.value);
                checkinDate.setDate(checkinDate.getDate() + 1);
                const minCheckout = checkinDate.toISOString().split('T')[0];
                checkoutInput.min = minCheckout;

                if (checkoutInput.value && checkoutInput.value <= this.value) {
                    checkoutInput.value = minCheckout;
                }
            });
        }

        // Lazy load slider images
        const lazySlides = document.querySelectorAll('.hero-slide[data-bg]');
        if ('IntersectionObserver' in window) {
            const loadImages = () => {
                lazySlides.forEach(slide => {
                    slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
                    slide.removeAttribute('data-bg');
                });
            };

            // Load rest of images 3 seconds after load to not block initial render
            setTimeout(loadImages, 3000);
        } else {
            // Fallback for older browsers
            lazySlides.forEach(slide => {
                slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
            });
        }
    });
</script>