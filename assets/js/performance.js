/**
 * Performance Optimizations for Aurora Hotel Plaza
 * Tăng tốc độ tải trang, giảm giật lag
 */

(function() {
    'use strict';

    // 1. Lazy Load Images - Chỉ load khi visible
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src], img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        img.loading = 'eager'; // Stop lazy loading
                        imageObserver.unobserve(img);
                    }
                });
            }, { rootMargin: '50px 0px' });

            images.forEach(img => imageObserver.observe(img));
        }
    });

    // 2. Debounce Scroll Events - Giảm frequency
    let ticking = false;
    let lastScrollY = window.scrollY;
    let scrollTimeout;

    function updateScrollClass() {
        const header = document.getElementById('main-header');
        if (!header) return;

        if (window.scrollY > 100) {
            header.classList.add('scrolled');
            header.classList.remove('not-scrolled');
        } else {
            header.classList.add('not-scrolled');
            header.classList.remove('scrolled');
        }

        ticking = false;
    }

    function onScroll() {
        lastScrollY = window.scrollY;
        
        if (!ticking) {
            window.requestAnimationFrame(updateScrollClass);
            ticking = true;
        }
    }

    // Use passive listener for better performance
    window.addEventListener('scroll', onScroll, { passive: true });

    // 3. Throttle Resize Events
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Handle resize logic here
        }, 250);
    }, { passive: true });

    // 4. Reduce DOM reflows - Batch DOM reads/writes
    function batchDOMUpdates(callback) {
        requestAnimationFrame(() => {
            callback();
        });
    }

    // 5. Optimize event delegation
    document.addEventListener('click', function(e) {
        // Delegate click events to parent
        const link = e.target.closest('a[href^="#"]');
        if (link) {
            const targetId = link.getAttribute('href');
            if (targetId && targetId !== '#') {
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    });

    // 6. Remove unused event listeners on cleanup
    window.addEventListener('pagehide', function() {
        // Clean up listeners if needed
    });

    // 7. Preload critical resources
    function preloadCriticalResources() {
        // Preload critical images
        const criticalImages = document.querySelectorAll('link[rel="preload"][as="image"]');
        criticalImages.forEach(link => {
            if (!link.href) {
                const img = new Image();
                img.src = link.getAttribute('href');
            }
        });
    }

    // 8. Optimize animations - Reduce on low-end devices
    const isLowEndDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isLowEndDevice) {
        // Reduce animations on mobile
        document.documentElement.style.setProperty('--animation-duration', '0.2s');
        
        // Disable heavy animations
        const heavyAnimations = document.querySelectorAll('.animate-bounce, .animate-pulse, .animate-spin');
        heavyAnimations.forEach(el => {
            el.style.animation = 'none';
        });
    }

    // 9. Optimize touch events
    document.addEventListener('touchstart', function(e) {
        // Passive touch events for better scroll performance
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        // Allow default behavior
    }, { passive: true });

    // 10. Cleanup on unload
    window.addEventListener('beforeunload', function() {
        // Clear timeouts
        clearTimeout(resizeTimeout);
        clearTimeout(scrollTimeout);
    });

    console.log('[Performance] Optimizations loaded');

})();
