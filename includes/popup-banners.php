<!-- Popup Banners (Redesigned) -->
<?php
require_once __DIR__ . '/../helpers/image-helper.php';

$popupBanners = [];

try {
    if (!defined('DB_NAME')) {
        require_once __DIR__ . '/../config/database.php';
    }
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/environment.php';
    }
    $db = getDB();
    // Only fetch active popup banners
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, link_url, link_text FROM banners WHERE status = 'active' AND position = 'popup' ORDER BY sort_order ASC, created_at DESC");
    $popupBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Popup banners error: ' . $e->getMessage());
}
?>

<?php if (!empty($popupBanners)): ?>
<div id="auroraPopupOverlay" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
    <div id="auroraPopupContainer" class="relative w-[90%] max-w-md md:max-w-lg bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl overflow-hidden scale-95 transition-transform duration-300">
        
        <!-- Close Button -->
        <button id="auroraPopupClose" class="absolute top-4 right-4 z-[20] w-8 h-8 flex items-center justify-center bg-black/40 hover:bg-black/60 text-white rounded-full transition-colors shadow-lg cursor-pointer">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>

        <div id="auroraPopupSlider" class="flex transition-transform duration-500 ease-in-out h-full">
            <?php foreach ($popupBanners as $banner): ?>
                <div class="min-w-full flex-shrink-0 relative" data-banner-id="<?php echo $banner['banner_id']; ?>">
                    <?php if ($banner['link_url']): ?>
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" target="_blank" rel="noopener" class="block w-full h-full">
                    <?php endif; ?>
                    
                    <div class="w-full aspect-[4/5] md:aspect-[4/3] bg-gray-900 relative">
                        <img <?php echo imgSrcWithFallback($banner['image_desktop']); ?>
                             alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                             class="w-full h-full object-cover">
                        <!-- Gradient Overlay for text readability -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/10"></div>
                    </div>
                    
                    <!-- Content overlaying the image -->
                    <div class="absolute bottom-0 left-0 w-full p-6 text-white text-center z-10 flex flex-col items-center justify-end">
                        <h3 class="text-2xl font-bold mb-2 drop-shadow-lg leading-tight"><?php echo htmlspecialchars($banner['title']); ?></h3>
                        <?php if ($banner['subtitle']): ?>
                            <p class="text-sm text-white/90 mb-5 drop-shadow-md max-w-[90%] mx-auto"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($banner['link_url'] && $banner['link_text']): ?>
                            <div class="inline-block px-8 py-2.5 bg-gradient-to-r from-[#d4af37] to-[#e5c048] text-black font-semibold rounded-full hover:shadow-[0_0_15px_rgba(212,175,55,0.6)] hover:scale-105 transition-all duration-300 cursor-pointer">
                                <?php echo htmlspecialchars($banner['link_text']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($banner['link_url']): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Indicators (if more than 1) -->
        <?php if (count($popupBanners) > 1): ?>
        <div class="absolute top-4 left-0 right-0 flex justify-center gap-2 z-10">
            <?php foreach ($popupBanners as $i => $banner): ?>
                <div class="w-2 h-2 rounded-full bg-white/40 transition-colors aurora-popup-indicator <?php echo $i === 0 ? 'bg-white' : ''; ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalPopups = <?php echo count($popupBanners); ?>;
    if (totalPopups === 0) return;

    const overlay = document.getElementById('auroraPopupOverlay');
    const container = document.getElementById('auroraPopupContainer');
    const slider = document.getElementById('auroraPopupSlider');
    const indicators = document.querySelectorAll('.aurora-popup-indicator');
    const closeBtn = document.getElementById('auroraPopupClose');
    
    let currentIndex = 0;
    let slideInterval;

    // Check if user has seen popups today (once per 24 hours logic)
    const lastSeenDate = localStorage.getItem('aurora_popup_seen_date');
    const today = new Date().toDateString();
    
    if (lastSeenDate !== today) {
        // Show popup after 1.5 seconds
        setTimeout(() => {
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            startSlider();
        }, 1500);
    }

    function closePopup() {
        overlay.classList.add('opacity-0', 'pointer-events-none');
        container.classList.add('scale-95');
        localStorage.setItem('aurora_popup_seen_date', today);
        clearInterval(slideInterval);
    }

    closeBtn.addEventListener('click', closePopup);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closePopup();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !overlay.classList.contains('opacity-0')) {
            closePopup();
        }
    });

    function startSlider() {
        if (totalPopups <= 1) return;
        
        slideInterval = setInterval(() => {
            currentIndex = (currentIndex + 1) % totalPopups;
            slider.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // Update indicators
            indicators.forEach((ind, idx) => {
                ind.classList.toggle('bg-white', idx === currentIndex);
                ind.classList.toggle('bg-white/40', idx !== currentIndex);
            });
        }, 4000);
    }
});
</script>
<?php endif; ?>