<!-- Popup Banners -->
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
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, link_url, link_text FROM banners WHERE status = 'active' AND position = 'popup' ORDER BY sort_order ASC, created_at DESC");
    $popupBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Popup banners error: ' . $e->getMessage());
}
?>

<?php if (!empty($popupBanners)): ?>
<div id="popupBannerOverlay" class="popup-banner-overlay">
    <?php foreach ($popupBanners as $i => $banner): ?>
    <div class="popup-banner-modal" data-banner-id="<?php echo $banner['banner_id']; ?>" data-index="<?php echo $i; ?>" <?php echo $i > 0 ? 'style="display:none;"' : ''; ?>>
        <button class="popup-banner-close" onclick="closePopupBanner(<?php echo $i; ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="popup-banner-content">
            <?php if ($banner['link_url']): ?>
                <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" target="_blank" rel="noopener">
            <?php endif; ?>
                <img src="<?php echo imgUrl($banner['image_desktop']); ?>" 
                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                     class="popup-banner-image"
                     loading="lazy">
            <?php if ($banner['link_url']): ?>
                </a>
            <?php endif; ?>
            <div class="popup-banner-info">
                <h3 class="popup-banner-title"><?php echo htmlspecialchars($banner['title']); ?></h3>
                <?php if ($banner['subtitle']): ?>
                    <p class="popup-banner-subtitle"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                <?php endif; ?>
                <?php if ($banner['link_url'] && $banner['link_text']): ?>
                    <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="popup-banner-btn" target="_blank" rel="noopener">
                        <?php echo htmlspecialchars($banner['link_text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.popup-banner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.popup-banner-modal {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: popupFadeIn 0.3s ease-out;
}

@keyframes popupFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.popup-banner-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    transition: all 0.2s;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.popup-banner-close:hover {
    background: #fff;
    transform: scale(1.1);
    color: #000;
}

.popup-banner-content {
    display: flex;
    flex-direction: column;
}

.popup-banner-image {
    width: 100%;
    max-height: 60vh;
    object-fit: cover;
    display: block;
}

.popup-banner-info {
    padding: 20px;
    text-align: center;
}

.popup-banner-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.popup-banner-subtitle {
    font-size: 1rem;
    color: #666;
    margin-bottom: 16px;
}

.popup-banner-btn {
    display: inline-block;
    padding: 12px 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
}

.popup-banner-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .popup-banner-modal {
        max-width: 95vw;
        border-radius: 12px;
    }
    
    .popup-banner-image {
        max-height: 50vh;
    }
    
    .popup-banner-info {
        padding: 16px;
    }
    
    .popup-banner-title {
        font-size: 1.25rem;
    }
    
    .popup-banner-subtitle {
        font-size: 0.9rem;
    }
}
</style>

<script>
const totalPopups = <?php echo count($popupBanners); ?>;
let currentPopup = 0;

function closePopupBanner(index) {
    const modals = document.querySelectorAll('.popup-banner-modal');
    const overlay = document.getElementById('popupBannerOverlay');
    
    // Save dismissed state for this session
    sessionStorage.setItem('popup_banner_' + index + '_dismissed', 'true');
    
    // Hide current popup
    modals[index].style.display = 'none';
    
    // Check if there's next popup
    const nextIndex = index + 1;
    if (nextIndex < totalPopups) {
        currentPopup = nextIndex;
        modals[nextIndex].style.display = 'flex';
    } else {
        // No more popups, hide overlay
        overlay.style.display = 'none';
    }
}

// Check if user already dismissed all popups in this session
(function() {
    const dismissed = [];
    for (let i = 0; i < totalPopups; i++) {
        if (sessionStorage.getItem('popup_banner_' + i + '_dismissed') === 'true') {
            dismissed.push(i);
        }
    }
    
    // If all dismissed, hide overlay
    if (dismissed.length === totalPopups) {
        document.getElementById('popupBannerOverlay').style.display = 'none';
    } else {
        // Show first non-dismissed popup
        const modals = document.querySelectorAll('.popup-banner-modal');
        dismissed.forEach(i => modals[i].style.display = 'none');
        
        const firstVisible = dismissed.length;
        if (firstVisible < totalPopups) {
            currentPopup = firstVisible;
        }
    }
})();

// Close on overlay click (outside modal)
document.getElementById('popupBannerOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closePopupBanner(currentPopup);
    }
});

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('popupBannerOverlay');
        if (overlay && overlay.style.display !== 'none') {
            closePopupBanner(currentPopup);
        }
    }
});
</script>
<?php endif; ?>