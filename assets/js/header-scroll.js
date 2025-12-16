// Smart Header Scroll Effect
// Handles transparent header on hero pages and solid header on other pages

(function () {
    const header = document.getElementById('main-header');
    const logo = document.getElementById('header-logo');
    const hasHero = header.dataset.hasHero === 'true';
    const forceScrolled = header.dataset.forceScrolled === 'true';
    const fixedTransparent = header.dataset.fixedTransparent === 'true';

    function updateHeader() {
        // Fixed Transparent: Always transparent with white logo (e.g. Blog page)
        if (fixedTransparent) {
            header.classList.remove('header-solid', 'header-scrolled');
            header.classList.add('header-transparent');
            logo.src = logo.dataset.logoWhite;
            return;
        }

        const scrolled = window.scrollY > 50;

        // Pages with force-scrolled (profile, etc.): Always keep scrolled state
        if (forceScrolled) {
            header.classList.remove('header-transparent');
            header.classList.add('header-solid', 'header-scrolled');
            logo.src = logo.dataset.logoWhite;
            return;
        }

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
    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                updateHeader();
                ticking = false;
            });
            ticking = true;
        }
    });

    // Handle window resize
    window.addEventListener('resize', updateHeader);
})();


// Language Switcher Toggle - Liquid Glass Animation
function toggleLangMenu() {
    const menu = document.getElementById('langMenu');
    const btn = document.getElementById('langBtn');

    if (menu && btn) {
        const isOpen = menu.classList.contains('show');

        if (isOpen) {
            menu.classList.remove('show');
            btn.classList.remove('active');
        } else {
            menu.classList.add('show');
            btn.classList.add('active');
        }
    }
}

// Close language menu when clicking outside
document.addEventListener('click', function (e) {
    const langWrapper = document.querySelector('.lang-switcher-wrapper');
    const langMenu = document.getElementById('langMenu');
    const langBtn = document.getElementById('langBtn');

    if (langWrapper && langMenu && !langWrapper.contains(e.target)) {
        langMenu.classList.remove('show');
        if (langBtn) langBtn.classList.remove('active');
    }
});

// Close on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const langMenu = document.getElementById('langMenu');
        const langBtn = document.getElementById('langBtn');
        if (langMenu && langMenu.classList.contains('show')) {
            langMenu.classList.remove('show');
            if (langBtn) langBtn.classList.remove('active');
        }
    }
});

// Preserve current URL params when switching language
document.addEventListener('DOMContentLoaded', function () {
    const langOptions = document.querySelectorAll('.lang-option');
    langOptions.forEach(function (option) {
        option.addEventListener('click', function (e) {
            e.preventDefault();
            const lang = this.href.split('lang=')[1];
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        });
    });
});
