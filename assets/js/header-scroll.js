// Smart Header Scroll Effect
// Handles transparent header on hero pages and solid header on other pages

(function() {
    const header = document.getElementById('main-header');
    const logo = document.getElementById('header-logo');
    const hasHero = header.dataset.hasHero === 'true';
    
    function updateHeader() {
        const scrolled = window.scrollY > 50;
        
        if (hasHero) {
            // Pages with hero banner
            if (scrolled) {
                // Scrolled: Solid white background, dark text, white logo
                header.classList.remove('header-transparent');
                header.classList.add('header-solid', 'header-scrolled');
                logo.src = logo.dataset.logoWhite;
            } else {
                // Top: Transparent background, white text, dark logo
                header.classList.remove('header-solid', 'header-scrolled');
                header.classList.add('header-transparent');
                logo.src = logo.dataset.logoDark;
            }
        } else {
            // Pages without hero banner: Always solid with white logo
            header.classList.remove('header-transparent');
            header.classList.add('header-solid', 'header-scrolled');
            logo.src = logo.dataset.logoWhite;
        }
    }
    
    // Initial check
    updateHeader();
    
    // Listen to scroll
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateHeader();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', updateHeader);
})();
