/**
 * hero-slider.js — Aurora Hotel Plaza
 * Hero slider initialization and lazy loading
 */

document.addEventListener('DOMContentLoaded', function () {
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');

    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', function () {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckout = checkinDate.toISOString().split('T')[0];
            checkoutInput.min = minCheckout;

            if (checkoutInput.value && checkoutInput.value <= this.value) {
                checkoutInput.value = minCheckout;
            }
        });
    }

    // Lazy load slider images
    const lazySlides = document.querySelectorAll('.hero-slide[data-bg]');
    if ('IntersectionObserver' in window) {
        const loadImages = () => {
            lazySlides.forEach(slide => {
                slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
                slide.removeAttribute('data-bg');
            });
        };

        // Load rest of images 3 seconds after load to not block initial render
        setTimeout(loadImages, 3000);
    } else {
        // Fallback for older browsers
        lazySlides.forEach(slide => {
            slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
        });
    }
});
