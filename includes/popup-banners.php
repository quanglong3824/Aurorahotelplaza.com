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
<div id="auroraPopupOverlay" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-md opacity-0 pointer-events-none transition-all duration-500">
    <div id="auroraPopupContainer" class="relative w-[92%] max-w-md md:max-w-lg bg-slate-900 border border-white/10 rounded-3xl shadow-[0_32px_64px_-12px_rgba(0,0,0,0.6)] overflow-hidden translate-y-8 scale-95 transition-all duration-500">
        
        <!-- Close Button -->
        <button id="auroraPopupClose" class="absolute top-5 right-5 z-[30] w-10 h-10 flex items-center justify-center bg-white/10 hover:bg-white/20 text-white rounded-2xl transition-all shadow-lg cursor-pointer group">
            <span class="material-symbols-outlined text-xl group-hover:rotate-90 transition-transform duration-300">close</span>
        </button>

        <div id="auroraPopupSlider" class="flex transition-transform duration-700 cubic-bezier(0.4, 0, 0.2, 1) h-full">
            <?php 
            // If no banners found but in preview, show a placeholder
            $displayBanners = !empty($popupBanners) ? $popupBanners : [
                ['banner_id' => 0, 'title' => 'Popup Preview', 'subtitle' => 'Đây là nội dung hiển thị mẫu của popup sự kiện.', 'image_desktop' => 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg', 'link_url' => '#', 'link_text' => 'Xem chi tiết']
            ];
            foreach ($displayBanners as $banner): 
            ?>
                <div class="min-w-full flex-shrink-0 relative group/slide" data-banner-id="<?php echo $banner['banner_id']; ?>">
                    <?php if ($banner['link_url']): ?>
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" target="_blank" rel="noopener" class="block w-full h-full">
                    <?php endif; ?>
                    
                    <div class="w-full aspect-[4/5] md:aspect-[1/1] bg-slate-800 relative overflow-hidden">
                        <img src="<?php echo imgUrl($banner['image_desktop']); ?>"
                             alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                             class="w-full h-full object-cover transition-transform duration-10000 group-hover/slide:scale-110">
                        <!-- Modern Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent"></div>
                    </div>
                    
                    <!-- Floating Content -->
                    <div class="absolute inset-0 p-8 text-white flex flex-col items-center justify-end text-center">
                        <div class="transform translate-y-4 opacity-0 animate-content-up">
                            <h3 class="text-3xl md:text-4xl font-black mb-3 leading-tight tracking-tight uppercase"><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <?php if ($banner['subtitle']): ?>
                                <p class="text-base text-slate-200 mb-6 max-w-[85%] mx-auto font-medium leading-relaxed"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($banner['link_url'] && $banner['link_text']): ?>
                                <div class="inline-flex items-center gap-2 px-10 py-4 bg-gradient-to-r from-[#d4af37] to-[#f5d061] text-black font-black text-sm uppercase tracking-widest rounded-2xl hover:shadow-[0_20px_40px_-10px_rgba(212,175,55,0.5)] hover:-translate-y-1 transition-all duration-300">
                                    <?php echo htmlspecialchars($banner['link_text']); ?>
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
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
        
        <!-- Pagination Indicators -->
        <?php if (count($displayBanners) > 1): ?>
        <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-2.5 z-20">
            <?php foreach ($displayBanners as $i => $banner): ?>
                <button class="w-2.5 h-1.5 rounded-full bg-white/30 transition-all duration-300 aurora-popup-indicator <?php echo $i === 0 ? 'w-8 bg-white' : ''; ?>" data-index="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes contentUp {
    to { transform: translateY(0); opacity: 1; }
}
.animate-content-up {
    animation: contentUp 0.8s cubic-bezier(0.2, 1, 0.3, 1) forwards;
    animation-delay: 0.5s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalPopups = <?php echo count($displayBanners); ?>;
    const isPreview = <?php echo $isPreview ? 'true' : 'false'; ?>;
    
    const overlay = document.getElementById('auroraPopupOverlay');
    const container = document.getElementById('auroraPopupContainer');
    const slider = document.getElementById('auroraPopupSlider');
    const indicators = document.querySelectorAll('.aurora-popup-indicator');
    const closeBtn = document.getElementById('auroraPopupClose');
    
    let currentIndex = 0;
    let slideInterval;

    // Show logic
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
    overlay.addEventListener('click', (e) => e.target === overlay && closePopup());
    document.addEventListener('keydown', (e) => e.key === 'Escape' && closePopup());

    function goToSlide(index) {
        currentIndex = index;
        slider.style.transform = `translateX(-${currentIndex * 100}%)`;
        indicators.forEach((ind, idx) => {
            if (idx === currentIndex) {
                ind.classList.add('w-8', 'bg-white');
                ind.classList.remove('w-2.5', 'bg-white/30');
            } else {
                ind.classList.remove('w-8', 'bg-white');
                ind.classList.add('w-2.5', 'bg-white/30');
            }
        });
    }

    function startSlider() {
        if (totalPopups <= 1) return;
        slideInterval = setInterval(() => {
            goToSlide((currentIndex + 1) % totalPopups);
        }, 5000);
    }

    // Manual navigation
    indicators.forEach(btn => {
        btn.addEventListener('click', () => {
            clearInterval(slideInterval);
            goToSlide(parseInt(btn.dataset.index));
            startSlider();
        });
    });
});
</script>
<?php endif; ?>