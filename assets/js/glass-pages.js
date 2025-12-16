document.addEventListener('DOMContentLoaded', () => {

    // --- Header Scroll Effect (for Fixed Transparent Header) ---
    const header = document.querySelector('header');

    function updateHeader() {
        if (window.scrollY > 50) {
            header.classList.add('header-scrolled');
        } else {
            // Only remove if not forced (though persistent pages usually have force logic handled in PHP or CSS)
            // But for safety:
            header.classList.remove('header-scrolled');
        }
    }

    if (header) {
        window.addEventListener('scroll', updateHeader);
        updateHeader();
    }


    // --- Smooth Scroll for Anchor Links ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Offset for fixed header
                const headerOffset = 100;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: "smooth"
                });
            }
        });
    });


    // --- Intersection Observer for Animations ---
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');

                // Add staggered delay to children if grid
                if (entry.target.classList.contains('values-grid-glass') ||
                    entry.target.classList.contains('facilities-grid') ||
                    entry.target.classList.contains('stats-grid-glass')) {

                    const children = entry.target.children;
                    Array.from(children).forEach((child, index) => {
                        child.style.animationDelay = `${index * 0.1}s`;
                        child.classList.add('animate-fade-in-up');
                    });
                }

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Elements to animate
    const glassElements = document.querySelectorAll(
        '.glass-card, ' +
        '.hero-glass-card, ' +
        '.story-split-card, ' +
        '.value-card, ' +
        '.info-card-glass, ' +
        '.form-glass-card, ' +
        '.utility-card-glass, ' +
        '.stat-card-glass, ' +
        '.package-detail-card, ' +
        '.glass-cta-box'
    );

    glassElements.forEach(el => observer.observe(el));
});
