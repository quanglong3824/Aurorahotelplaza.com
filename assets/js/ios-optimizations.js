/**
 * iOS Performance & Smooth Scroll Optimizations
 * Fixes smooth scrolling issues on iPhone Safari
 * MINIMAL INTERVENTION - Giữ nguyên hiệu ứng gốc
 */

(function() {
    'use strict';

    // Detect iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (!isIOS) return;

    console.log('[iOS Optimizations] Loaded');

    // 1. Fix smooth scroll for anchor links - CHỈ khi cần
    document.addEventListener('DOMContentLoaded', function() {
        // Handle anchor links với smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#' || targetId === '') return;

                const target = document.querySelector(targetId);
                if (!target) return;

                // Chỉ prevent nếu là internal link
                e.preventDefault();
                
                const offset = target.offsetTop - 80; // Offset cho header
                
                // iOS optimized scroll
                window.scrollTo({
                    top: offset,
                    behavior: 'smooth'
                });
            });
        });

        // 2. Fix 100vh on iOS Safari
        function setVH() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }

        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);

        // 3. Touch optimization cho buttons - GIỮ NGUYÊN HIỆU ỨNG
        document.querySelectorAll('button, a, [role="button"]').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.transition = 'opacity 0.2s';
                this.style.opacity = '0.7';
            }, { passive: true });

            el.addEventListener('touchend', function() {
                this.style.opacity = '';
            }, { passive: true });
        });
    });

    console.log('[iOS Optimizations] Initialized');

})();
