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
    // Fetch active popup banners within date range
    $stmt = $db->query("
        SELECT banner_id, title, subtitle, image_desktop, link_url, link_text 
        FROM banners 
        WHERE status = 'active' 
        AND position = 'popup' 
        AND (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date >= NOW()) 
        ORDER BY sort_order ASC, created_at DESC
    ");
    $popupBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Popup banners error: ' . $e->getMessage());
}

$isPreview = isset($_GET['preview_popup']) && $_GET['preview_popup'] == '1';
?>

<?php if (!empty($popupBanners) || $isPreview): ?>
<div id="auroraPopupOverlay" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-500">
    <div id="auroraPopupContainer" class="relative w-[92%] max-w-md bg-gray-900 border border-white border-opacity-10 rounded-2xl shadow-2xl overflow-hidden transform translate-y-8 scale-95 transition-all duration-500">
        
        <!-- Close Button -->
        <button id="auroraPopupClose" class="absolute top-4 right-4 z-[30] w-8 h-8 flex items-center justify-center bg-black bg-opacity-40 hover:bg-opacity-60 text-white rounded-full transition-all cursor-pointer">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>

        <!-- Navigation Arrows (If more than 1) -->
        <?php if (count($displayBanners) > 1): ?>
        <button id="auroraPopupPrev" class="absolute left-4 top-1/2 -translate-y-1/2 z-[30] w-10 h-10 flex items-center justify-center bg-white bg-opacity-10 hover:bg-opacity-20 text-white rounded-full transition-all cursor-pointer backdrop-blur-sm">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button id="auroraPopupNext" class="absolute right-4 top-1/2 -translate-y-1/2 z-[30] w-10 h-10 flex items-center justify-center bg-white bg-opacity-10 hover:bg-opacity-20 text-white rounded-full transition-all cursor-pointer backdrop-blur-sm">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
        <?php endif; ?>

        <div id="auroraPopupSlider" class="flex transition-transform duration-700 ease-in-out h-full">
            <?php 
            $displayBanners = !empty($popupBanners) ? $popupBanners : [
                ['banner_id' => 0, 'title' => 'Popup Preview', 'subtitle' => 'Đây là nội dung hiển thị mẫu của popup sự kiện.', 'image_desktop' => '', 'link_url' => '#', 'link_text' => 'Xem chi tiết']
            ];
            foreach ($displayBanners as $banner): 
            ?>
                <div class="min-w-full flex-shrink-0 relative group" data-banner-id="<?php echo $banner['banner_id']; ?>">
                    <?php if ($banner['link_url']): ?>
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" target="_blank" rel="noopener" class="block w-full h-full">
                    <?php endif; ?>
                    
                    <div class="w-full relative bg-gray-800" style="padding-top: 100%;">
                        <img <?php echo imgSrcWithFallback($banner['image_desktop']); ?>
                             alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                             class="absolute inset-0 w-full h-full object-cover">
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="absolute inset-0 p-6 text-white flex flex-col items-center justify-end text-center">
                        <div class="animate-popup-content">
                            <h3 class="text-xl md:text-2xl font-bold mb-2 leading-tight uppercase"><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <?php if ($banner['subtitle']): ?>
                                <p class="text-sm text-gray-300 mb-4 max-w-[90%] mx-auto"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($banner['link_url'] && $banner['link_text']): ?>
                                <div class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-black font-bold text-xs uppercase tracking-widest rounded-full hover:scale-105 transition-all duration-300">
                                    <?php echo htmlspecialchars($banner['link_text']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($banner['link_url']): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (count($displayBanners) > 1): ?>
        <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-20">
            <?php foreach ($displayBanners as $i => $banner): ?>
                <button class="w-2 h-2 rounded-full bg-white bg-opacity-30 transition-all duration-300 aurora-popup-indicator <?php echo $i === 0 ? 'bg-opacity-100 px-3' : ''; ?>" data-index="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes popupContentUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-popup-content {
    animation: popupContentUp 0.6s ease-out forwards;
    animation-delay: 0.3s;
    opacity: 0;
}
.bg-accent { background-color: #d4af37; }
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const totalPopups = <?php echo count($displayBanners); ?>;
        const isPreview = <?php echo $isPreview ? 'true' : 'false'; ?>;
        
        const overlay = document.getElementById('auroraPopupOverlay');
        const container = document.getElementById('auroraPopupContainer');
        const slider = document.getElementById('auroraPopupSlider');
        const indicators = document.querySelectorAll('.aurora-popup-indicator');
        const closeBtn = document.getElementById('auroraPopupClose');
        const prevBtn = document.getElementById('auroraPopupPrev');
        const nextBtn = document.getElementById('auroraPopupNext');
        
        if (!overlay || !container) return;

        let currentIndex = 0;
        let slideInterval;

        const lastSeenDate = localStorage.getItem('aurora_popup_seen_date');
        const today = new Date().toDateString();
        
        if (isPreview || lastSeenDate !== today) {
            setTimeout(() => {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                container.classList.remove('translate-y-8', 'scale-95');
                startSlider();
            }, isPreview ? 200 : 2000);
        }

        function closePopup() {
            overlay.classList.add('opacity-0', 'pointer-events-none');
            container.classList.add('translate-y-8', 'scale-95');
            if (!isPreview) {
                localStorage.setItem('aurora_popup_seen_date', today);
            }
            clearInterval(slideInterval);
        }

        closeBtn.addEventListener('click', closePopup);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closePopup();
        });
        
        container.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                goToSlide((currentIndex - 1 + totalPopups) % totalPopups);
                startSlider();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                goToSlide((currentIndex + 1) % totalPopups);
                startSlider();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.classList.contains('opacity-0')) closePopup();
        });

        function goToSlide(index) {
            if (!slider) return;
            currentIndex = index;
            slider.style.transform = `translateX(-${currentIndex * 100}%)`;
            indicators.forEach((ind, idx) => {
                if (idx === currentIndex) {
                    ind.classList.add('bg-opacity-100', 'px-3');
                } else {
                    ind.classList.remove('bg-opacity-100', 'px-3');
                }
            });
        }

        function startSlider() {
            if (totalPopups <= 1 || !slider) return;
            slideInterval = setInterval(() => {
                goToSlide((currentIndex + 1) % totalPopups);
            }, 5000);
        }

        indicators.forEach(btn => {
            btn.addEventListener('click', () => {
                clearInterval(slideInterval);
                goToSlide(parseInt(btn.dataset.index));
                startSlider();
            });
        });
    });
})();
</script>
<?php endif; ?>