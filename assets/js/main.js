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
        
        function updateLogo() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
                // Change to dark logo when scrolled
                if (logoImage.getAttribute('src') !== logoDark) {
                    isChangingLogo = true;
                    logoImage.setAttribute('src', logoDark);
                    setTimeout(() => { isChangingLogo = false; }, 50);
                }
            } else {
                header.classList.remove('scrolled');
                // Change to white logo at top
                if (logoImage.getAttribute('src') !== logoWhite) {
                    isChangingLogo = true;
                    logoImage.setAttribute('src', logoWhite);
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
});
