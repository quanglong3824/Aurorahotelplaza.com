/**
 * iOS Performance & Smooth Scroll Optimizations
 * Fixes smooth scrolling issues on iPhone Safari
 */

(function() {
    'use strict';

    // Detect iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

    if (!isIOS && !isSafari) return;

    console.log('[iOS Optimizations] Loaded for', navigator.userAgent);

    // 1. Fix smooth scroll for anchor links
    document.addEventListener('DOMContentLoaded', function() {
        // Handle anchor links with smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const target = document.querySelector(targetId);
                if (!target) return;

                e.preventDefault();
                
                // iOS optimized scroll
                const offset = target.offsetTop;
                window.scrollTo({
                    top: offset,
                    behavior: 'smooth'
                });

                // Fallback for older iOS
                setTimeout(() => {
                    if (window.scrollY < offset - 10 || window.scrollY > offset + 10) {
                        window.scrollTo(0, offset);
                    }
                }, 100);
            });
        });

        // 2. Optimize scroll performance
        let ticking = false;
        let lastScrollY = window.scrollY;

        function updateScroll() {
            const currentScrollY = window.scrollY;
            
            // Add/remove scroll class for optimizations
            if (currentScrollY > lastScrollY) {
                document.body.classList.add('scrolling-down');
                document.body.classList.remove('scrolling-up');
            } else {
                document.body.classList.add('scrolling-up');
                document.body.classList.remove('scrolling-down');
            }

            lastScrollY = currentScrollY;
            ticking = false;
        }

        function requestScrollUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(updateScroll);
                ticking = true;
            }
        }

        // Use passive listener for better scroll performance
        window.addEventListener('scroll', requestScrollUpdate, { passive: true });

        // 3. Fix 100vh on iOS Safari
        function setVH() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }

        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);

        // 4. Optimize images loading
        const images = document.querySelectorAll('img[data-src]');
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            }, { rootMargin: '50px 0px' });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older iOS
            images.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }

        // 5. Reduce motion for users who prefer it
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (prefersReducedMotion.matches) {
            document.documentElement.style.scrollBehavior = 'auto';
        }

        // 6. Touch optimization for buttons
        document.querySelectorAll('button, a, [role="button"]').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            }, { passive: true });

            el.addEventListener('touchend', function() {
                this.style.opacity = '';
            }, { passive: true });
        });

        // 7. Prevent double-tap zoom on iOS < 10
        if (navigator.userAgent.match(/OS \d_3_/)) {
            document.addEventListener('touchend', function(e) {
                e.target.click();
            }, true);
        }
    });

    // 8. Fix for iOS modal scroll lock
    window.disableBodyScroll = function() {
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.overflow = 'hidden';
        document.body.style.width = '100%';
    };

    window.enableBodyScroll = function() {
        const scrollY = document.body.style.top;
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.overflow = '';
        document.body.style.width = '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
    };

    console.log('[iOS Optimizations] All optimizations initialized');

})();
