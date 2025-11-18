// Aurora Hotel Plaza - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider Functionality (only if slider exists)
    const slides = document.querySelectorAll('.hero-slide');
    const dotsContainer = document.getElementById('slider-dots-container');
    
    if (slides.length > 0 && dotsContainer) {
        let currentSlide = 0;
        const totalSlides = slides.length;
        
        // Generate dots dynamically based on number of slides
        function generateDots() {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('span');
                dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('data-slide', i);
                dot.addEventListener('click', () => {
                    currentSlide = i;
                    showSlide(currentSlide);
                });
                dotsContainer.appendChild(dot);
            }
        }

        function showSlide(index) {
            const dots = document.querySelectorAll('.slider-dot');
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
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

        // Arrow navigation
        const prevArrow = document.querySelector('.slider-arrow.prev');
        const nextArrow = document.querySelector('.slider-arrow.next');
        
        if (prevArrow) {
            prevArrow.addEventListener('click', () => {
                clearInterval(autoSlide);
                prevSlide();
                autoSlide = setInterval(nextSlide, 5000);
            });
        }
        
        if (nextArrow) {
            nextArrow.addEventListener('click', () => {
                clearInterval(autoSlide);
                nextSlide();
                autoSlide = setInterval(nextSlide, 5000);
            });
        }
    }

    // Sticky Header on Scroll with Logo Change (works on all pages)
    const header = document.querySelector('header');
    const logoImage = document.querySelector('.logo-image');
    
    if (header && logoImage) {
        // Get logo paths from data attributes (set by PHP)
        const logoWhite = logoImage.getAttribute('data-logo-white');
        const logoDark = logoImage.getAttribute('data-logo-dark');
        
        // Track if we're currently changing the logo to prevent observer conflicts
        let isChangingLogo = false;
        
        // Check if page has hero section or dark background at top
        const hasHeroSection = document.querySelector('.hero-section, .hero-slider, .hero-banner');
        const hasLightBackground = !hasHeroSection; // If no hero, assume light background
        
        function updateLogo() {
            const currentScroll = window.pageYOffset;
            
            // If page has light background and at top, force scrolled state
            if (hasLightBackground && currentScroll <= 100) {
                header.classList.add('scrolled');
                // Use white logo for light background (reversed)
                if (logoImage.getAttribute('src') !== logoWhite) {
                    isChangingLogo = true;
                    logoImage.setAttribute('src', logoWhite);
                    setTimeout(() => { isChangingLogo = false; }, 50);
                }
            } else if (currentScroll > 100) {
                header.classList.add('scrolled');
                // Change to white logo when scrolled (reversed)
                if (logoImage.getAttribute('src') !== logoWhite) {
                    isChangingLogo = true;
                    logoImage.setAttribute('src', logoWhite);
                    setTimeout(() => { isChangingLogo = false; }, 50);
                }
            } else {
                header.classList.remove('scrolled');
                // Change to dark logo at top (only for pages with hero - reversed)
                if (logoImage.getAttribute('src') !== logoDark) {
                    isChangingLogo = true;
                    logoImage.setAttribute('src', logoDark);
                    setTimeout(() => { isChangingLogo = false; }, 50);
                }
            }
        }
        
        // Initial check
        updateLogo();
        
        // Update on scroll
        window.addEventListener('scroll', updateLogo);
        
        // Create a MutationObserver to prevent unwanted changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src' && !isChangingLogo) {
                    const currentSrc = logoImage.getAttribute('src');
                    // Only intervene if src is being changed to something unexpected
                    if (currentSrc !== logoWhite && currentSrc !== logoDark && currentSrc !== '') {
                        updateLogo();
                    }
                }
            });
        });
        
        observer.observe(logoImage, {
            attributes: true,
            attributeFilter: ['src']
        });
    }

    // User Menu Functionality
    const userMenuWrapper = document.querySelector('.user-menu-wrapper');
    const userMenuBtn = document.querySelector('.user-menu-btn');
    const userMenu = document.querySelector('.user-menu');
    
    if (userMenuBtn && userMenu) {
        // Toggle menu on button click
        userMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (userMenuWrapper && !userMenuWrapper.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
        
        // Prevent menu from closing when clicking inside
        if (userMenu) {
            userMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userMenu.classList.remove('show');
            }
        });
    }
    
    // Mobile Menu Toggle (if exists)
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mobileMenu.classList.toggle('show');
        });
    }
});
