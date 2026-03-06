// Smart Header Scroll Effect
// Handles transparent header, solid header, and smart show/hide on scroll direction

(function () {
    const header = document.getElementById('main-header');
    const logo = document.getElementById('header-logo');
    if (!header || !logo) return;

    const hasHero = header.dataset.hasHero === 'true';
    const forceScrolled = header.dataset.forceScrolled === 'true';
    const fixedTransparent = header.dataset.fixedTransparent === 'true';

    let lastScrollY = window.scrollY;
    let ticking = false;
    let lastState = null;

    function updateHeader() {
        const currentScrollY = window.scrollY;
        const scrollDelta = currentScrollY - lastScrollY;
        const scrolled = currentScrollY > 50;

        // 1. Handle Background & Logo State (Transparent vs Scrolled)
        if (fixedTransparent) {
            if (lastState !== 'fixed-transparent') {
                header.classList.remove('header-solid', 'header-scrolled');
                header.classList.add('header-transparent');
                logo.src = logo.dataset.logoDark;
                lastState = 'fixed-transparent';
            }
        } else if (forceScrolled) {
            if (lastState !== 'force-scrolled') {
                header.classList.remove('header-transparent');
                header.classList.add('header-solid', 'header-scrolled');
                logo.src = logo.dataset.logoWhite;
                lastState = 'force-scrolled';
            }
        } else if (hasHero) {
            if (scrolled) {
                if (lastState !== 'scrolled') {
                    header.classList.remove('header-transparent');
                    header.classList.add('header-solid', 'header-scrolled');
                    logo.src = logo.dataset.logoWhite;
                    lastState = 'scrolled';
                }
            } else {
                if (lastState !== 'top') {
                    header.classList.remove('header-solid', 'header-scrolled');
                    header.classList.add('header-transparent');
                    logo.src = logo.dataset.logoDark;
                    lastState = 'top';
                }
            }
        } else {
            if (lastState !== 'solid-default') {
                header.classList.remove('header-transparent');
                header.classList.add('header-solid', 'header-scrolled');
                logo.src = logo.dataset.logoWhite;
                lastState = 'solid-default';
            }
        }

        // 2. Handle Smart Show/Hide (Directional Scroll)
        // Only trigger hide effect after scrolling down a certain distance (e.g. 200px)
        if (currentScrollY < 100) {
            // Always show at the top
            header.classList.remove('header-hidden');
            header.classList.add('header-visible');
        } else if (scrollDelta > 10) {
            // Scrolling Down - Hide Header
            header.classList.remove('header-visible');
            header.classList.add('header-hidden');
        } else if (scrollDelta < -15) {
            // Scrolling Up - Show Header
            header.classList.remove('header-hidden');
            header.classList.add('header-visible');
        }

        lastScrollY = currentScrollY;
    }

    // Initial check
    updateHeader();

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                updateHeader();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    window.addEventListener('resize', updateHeader, { passive: true });
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
            const urlParts = this.href.split('lang=');
            if (urlParts.length > 1) {
                const lang = urlParts[1];
                const url = new URL(window.location.href);
                url.searchParams.set('lang', lang);
                window.location.href = url.toString();
            }
        });
    });
});
