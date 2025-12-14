// Aurora Hotel Plaza - Main JavaScript
// Optimized for smooth 60fps performance

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // ============================================
    // PERFORMANCE UTILITIES
    // ============================================
    
    // RAF throttle for smooth animations
    let ticking = false;
    const rafThrottle = (callback) => {
        return function(...args) {
            if (!ticking) {
                requestAnimationFrame(() => {
                    callback.apply(this, args);
                    ticking = false;
                });
                ticking = true;
            }
        };
    };
    
    // Passive event listener option
    const passiveOption = { passive: true };

    // ============================================
    // HERO SLIDER - Optimized
    // ============================================
    
    const slides = document.querySelectorAll('.hero-slide');
    const dotsContainer = document.getElementById('slider-dots-container');
    
    if (slides.length > 0 && dotsContainer) {
        let currentSlide = 0;
        const totalSlides = slides.length;
        
        // Generate dots with event delegation
        function generateDots() {
            const fragment = document.createDocumentFragment();
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('span');
                dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('data-slide', i);
                fragment.appendChild(dot);
            }
            dotsContainer.innerHTML = '';
            dotsContainer.appendChild(fragment);
        }
        
        // Event delegation for dots
        dotsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('slider-dot')) {
                currentSlide = parseInt(e.target.getAttribute('data-slide'));
                showSlide(currentSlide);
            }
        });

        function showSlide(index) {
            requestAnimationFrame(() => {
                const dots = dotsContainer.querySelectorAll('.slider-dot');
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                slides[index].classList.add('active');
                dots[index].classList.add('active');
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Initialize dots
        generateDots();

        // Auto advance slides every 5 seconds
        let autoSlide = setInterval(nextSlide, 5000);

        // Arrow navigation with event delegation
        const sliderContainer = document.querySelector('.hero-slider');
        if (sliderContainer) {
            sliderContainer.addEventListener('click', (e) => {
                const arrow = e.target.closest('.slider-arrow');
                if (arrow) {
                    clearInterval(autoSlide);
                    if (arrow.classList.contains('prev')) {
                        prevSlide();
                    } else if (arrow.classList.contains('next')) {
                        nextSlide();
                    }
                    autoSlide = setInterval(nextSlide, 5000);
                }
            });
        }
    }

    // ============================================
    // STICKY HEADER - Optimized with RAF
    // ============================================
    
    const header = document.querySelector('header');
    const logoImage = document.querySelector('.logo-image');
    
    if (header && logoImage) {
        const logoWhite = logoImage.getAttribute('data-logo-white');
        const logoDark = logoImage.getAttribute('data-logo-dark');
        
        let isChangingLogo = false;
        let lastScrollY = 0;
        let headerState = null; // 'scrolled' or 'top'
        
        const hasHeroSection = document.querySelector('.hero-section, .hero-slider, .hero-banner');
        const hasLightBackground = !hasHeroSection;
        
        // Batch DOM updates with RAF
        const updateHeader = rafThrottle(() => {
            const currentScroll = window.pageYOffset;
            
            // Skip if scroll position hasn't changed significantly
            if (Math.abs(currentScroll - lastScrollY) < 5 && headerState !== null) return;
            lastScrollY = currentScroll;
            
            const shouldBeScrolled = hasLightBackground || currentScroll > 100;
            const newState = shouldBeScrolled ? 'scrolled' : 'top';
            
            // Only update DOM if state changed
            if (newState !== headerState) {
                headerState = newState;
                
                if (shouldBeScrolled) {
                    header.classList.add('scrolled');
                    if (logoImage.getAttribute('src') !== logoWhite) {
                        isChangingLogo = true;
                        logoImage.setAttribute('src', logoWhite);
                        requestAnimationFrame(() => { isChangingLogo = false; });
                    }
                } else {
                    header.classList.remove('scrolled');
                    if (logoImage.getAttribute('src') !== logoDark) {
                        isChangingLogo = true;
                        logoImage.setAttribute('src', logoDark);
                        requestAnimationFrame(() => { isChangingLogo = false; });
                    }
                }
            }
        });
        
        // Initial check
        updateHeader();
        
        // Use passive scroll listener
        window.addEventListener('scroll', updateHeader, passiveOption);
        
        // MutationObserver for logo protection
        const observer = new MutationObserver((mutations) => {
            if (!isChangingLogo) {
                const currentSrc = logoImage.getAttribute('src');
                if (currentSrc !== logoWhite && currentSrc !== logoDark && currentSrc !== '') {
                    updateHeader();
                }
            }
        });
        
        observer.observe(logoImage, {
            attributes: true,
            attributeFilter: ['src']
        });
    }

    // ============================================
    // USER MENU - Optimized
    // ============================================
    
    const userMenuWrapper = document.querySelector('.user-menu-wrapper');
    const userMenuBtn = document.querySelector('.user-menu-btn');
    const userMenu = document.querySelector('.user-menu');
    
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            requestAnimationFrame(() => {
                userMenu.classList.toggle('show');
            });
        });
        
        // Single document click handler
        document.addEventListener('click', function(e) {
            if (userMenuWrapper && !userMenuWrapper.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
        
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userMenu.classList.remove('show');
            }
        });
    }
    
    // ============================================
    // MOBILE MENU - Optimized
    // ============================================
    
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            requestAnimationFrame(() => {
                mobileMenu.classList.toggle('open');
                document.body.classList.toggle('menu-open');
            });
        });
    }
    
    // ============================================
    // SMOOTH ANCHOR LINKS
    // ============================================
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
