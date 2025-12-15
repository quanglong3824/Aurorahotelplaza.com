/**
 * Room Detail Page - Interactive Features
 * Handles background image, parallax, gallery lightbox, and animations
 */

(function() {
    'use strict';

    // ============================================
    // 1. BACKGROUND IMAGE LOADER
    // ============================================
    const header = document.querySelector('.page-header-room[data-bg-image]');
    if (header) {
        header.style.backgroundImage = 'url(' + header.dataset.bgImage + ')';
    }

    // ============================================
    // 2. PARALLAX SCROLL EFFECT
    // ============================================
    function initParallax() {
        if (!header) return;
        
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrolled = window.pageYOffset;
                    const parallaxSpeed = 0.5;
                    
                    if (scrolled < header.offsetHeight) {
                        header.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
                    }
                    
                    ticking = false;
                });
                
                ticking = true;
            }
        });
    }

    // ============================================
    // 3. GALLERY LIGHTBOX
    // ============================================
    function initGalleryLightbox() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        if (galleryItems.length === 0) return;

        // Create lightbox overlay
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox-overlay';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <button class="lightbox-close" aria-label="Close">&times;</button>
                <button class="lightbox-prev" aria-label="Previous">‹</button>
                <button class="lightbox-next" aria-label="Next">›</button>
                <img src="" alt="Gallery Image" class="lightbox-image">
                <div class="lightbox-counter"></div>
            </div>
        `;
        document.body.appendChild(lightbox);

        const lightboxImg = lightbox.querySelector('.lightbox-image');
        const lightboxCounter = lightbox.querySelector('.lightbox-counter');
        const closeBtn = lightbox.querySelector('.lightbox-close');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');

        let currentIndex = 0;
        const images = Array.from(galleryItems).map(item => item.querySelector('img').src);

        function showImage(index) {
            currentIndex = index;
            lightboxImg.src = images[index];
            lightboxCounter.textContent = `${index + 1} / ${images.length}`;
        }

        function openLightbox(index) {
            showImage(index);
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        function showNext() {
            const nextIndex = (currentIndex + 1) % images.length;
            showImage(nextIndex);
        }

        function showPrev() {
            const prevIndex = (currentIndex - 1 + images.length) % images.length;
            showImage(prevIndex);
        }

        // Event listeners
        galleryItems.forEach((item, index) => {
            item.addEventListener('click', () => openLightbox(index));
        });

        closeBtn.addEventListener('click', closeLightbox);
        nextBtn.addEventListener('click', showNext);
        prevBtn.addEventListener('click', showPrev);

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') showNext();
            if (e.key === 'ArrowLeft') showPrev();
        });
    }

    // ============================================
    // 4. SMOOTH SCROLL ANIMATIONS
    // ============================================
    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Animate elements on scroll
        const animateElements = document.querySelectorAll('.spec-item, .amenity-item, .gallery-item');
        animateElements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = `all 0.6s ease ${index * 0.05}s`;
            observer.observe(el);
        });
    }

    // ============================================
    // 5. BOOKING FORM VALIDATION
    // ============================================
    function initFormValidation() {
        const bookingForm = document.querySelector('.booking-form');
        if (!bookingForm) return;

        const checkInInput = bookingForm.querySelector('input[name="check_in"]');
        const checkOutInput = bookingForm.querySelector('input[name="check_out"]');

        if (checkInInput && checkOutInput) {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            checkInInput.setAttribute('min', today);
            checkOutInput.setAttribute('min', today);

            // Update checkout min date when checkin changes
            checkInInput.addEventListener('change', function() {
                const checkInDate = new Date(this.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOut = checkInDate.toISOString().split('T')[0];
                checkOutInput.setAttribute('min', minCheckOut);
                
                // Reset checkout if it's before new minimum
                if (checkOutInput.value && checkOutInput.value <= this.value) {
                    checkOutInput.value = '';
                }
            });
        }

        // Form submission
        bookingForm.addEventListener('submit', function(e) {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);

            if (checkOut <= checkIn) {
                e.preventDefault();
                alert('Ngày trả phòng phải sau ngày nhận phòng!');
                return false;
            }
        });
    }

    // ============================================
    // 6. STICKY BOOKING CARD
    // ============================================
    function initStickyBookingCard() {
        const bookingCard = document.querySelector('.booking-card');
        if (!bookingCard) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    bookingCard.classList.add('is-visible');
                } else {
                    bookingCard.classList.remove('is-visible');
                }
            },
            { threshold: 0.1 }
        );

        observer.observe(bookingCard);
    }

    // ============================================
    // INITIALIZE ALL FEATURES
    // ============================================
    function init() {
        // Check if DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        initParallax();
        initGalleryLightbox();
        initScrollAnimations();
        initFormValidation();
        initStickyBookingCard();
    }

    // Start initialization
    init();

})();
