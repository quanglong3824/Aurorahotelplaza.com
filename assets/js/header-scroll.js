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


// Language Switcher Toggle
function toggleLangMenu() {
    const menu = document.getElementById('langMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Close language menu when clicking outside
document.addEventListener('click', function(e) {
    const langWrapper = document.querySelector('.lang-switcher-wrapper');
    const langMenu = document.getElementById('langMenu');
    
    if (langWrapper && langMenu && !langWrapper.contains(e.target)) {
        langMenu.classList.add('hidden');
    }
});

// Preserve current URL params when switching language
document.addEventListener('DOMContentLoaded', function() {
    const langOptions = document.querySelectorAll('.lang-option');
    langOptions.forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.href.split('lang=')[1];
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        });
    });
});
