/**
 * Floating Mobile Menu - JavaScript Controller
 * Aurora Hotel Plaza
 */

(function () {
    'use strict';

    // DOM Elements
    let floatingMenu = null;
    let menuToggle = null;
    let menuOverlay = null;
    let submenuWrappers = [];

    /**
     * Initialize floating menu
     */
    function init() {
        floatingMenu = document.getElementById('floatingMenu');
        if (!floatingMenu) return;

        menuToggle = floatingMenu.querySelector('.floating-menu-toggle');
        menuOverlay = floatingMenu.querySelector('.floating-menu-overlay');
        submenuWrappers = floatingMenu.querySelectorAll('.floating-submenu-wrapper');

        bindEvents();
    }

    /**
     * Bind all event listeners
     */
    function bindEvents() {
        // Toggle menu on button click
        if (menuToggle) {
            menuToggle.addEventListener('click', toggleMenu);
        }

        // Close menu on overlay click
        if (menuOverlay) {
            menuOverlay.addEventListener('click', closeMenu);
        }

        // Handle submenu toggles on touch devices
        submenuWrappers.forEach(wrapper => {
            const btn = wrapper.querySelector('.floating-menu-btn');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    // Check if it's a link with submenu
                    const submenu = wrapper.querySelector('.floating-submenu');
                    if (submenu) {
                        const isOpen = wrapper.classList.contains('submenu-open');

                        if (!isOpen) {
                            // First click: Open submenu
                            e.preventDefault();
                            e.stopPropagation();
                            toggleSubmenu(wrapper);
                        } else {
                            // Second click: Navigate to the link (if has href)
                            const href = btn.getAttribute('href');
                            if (href && href !== '#') {
                                // Redirect to the page
                                e.preventDefault();
                                closeMenu();
                                window.location.href = href;
                            } else {
                                // No link, just toggle off
                                e.preventDefault();
                                wrapper.classList.remove('submenu-open');
                            }
                        }
                    }
                });
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && floatingMenu.classList.contains('active')) {
                closeMenu();
            }
        });

        // Close menu when clicking on menu items (except submenu triggers)
        // Không đóng menu khi click vào nút có submenu
        floatingMenu.querySelectorAll('.floating-menu-item:not(.floating-submenu-wrapper) .floating-menu-btn[href], .floating-submenu-item').forEach(link => {
            link.addEventListener('click', function () {
                // Small delay to allow navigation
                setTimeout(closeMenu, 100);
            });
        });

        // Close submenus when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.floating-submenu-wrapper')) {
                closeAllSubmenus();
            }
        });
    }

    /**
     * Toggle main menu
     */
    function toggleMenu() {
        if (floatingMenu.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    /**
     * Open menu
     */
    function openMenu() {
        floatingMenu.classList.add('active');
        document.body.style.overflow = 'hidden';
        menuToggle.setAttribute('aria-expanded', 'true');

        // Announce to screen readers
        announceToScreenReader('Menu đã mở');
    }

    /**
     * Close menu
     */
    function closeMenu() {
        floatingMenu.classList.remove('active');
        document.body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        closeAllSubmenus();

        // Announce to screen readers
        announceToScreenReader('Menu đã đóng');
    }

    /**
     * Toggle submenu
     */
    function toggleSubmenu(wrapper) {
        const isOpen = wrapper.classList.contains('submenu-open');

        // Close all other submenus first
        closeAllSubmenus();

        if (!isOpen) {
            wrapper.classList.add('submenu-open');
        }
    }

    /**
     * Close all submenus
     */
    function closeAllSubmenus() {
        submenuWrappers.forEach(wrapper => {
            wrapper.classList.remove('submenu-open');
        });
    }

    /**
     * Announce message to screen readers
     */
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);

        setTimeout(() => {
            announcement.remove();
        }, 1000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose functions globally if needed
    window.floatingMenuToggle = toggleMenu;
    window.floatingMenuClose = closeMenu;
    window.floatingMenuOpen = openMenu;

})();

/**
 * Floating Booking Form Toggle
 */
function toggleFloatingBookingForm() {
    const popup = document.getElementById('floatingBookingForm');
    if (!popup) return;

    if (popup.classList.contains('active')) {
        popup.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialize date inputs
        initFloatingBookingDates();
    }
}

/**
 * Initialize date validation for floating booking form
 */
function initFloatingBookingDates() {
    const checkinInput = document.getElementById('floating-checkin');
    const checkoutInput = document.getElementById('floating-checkout');

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
}

// Close popup on escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const popup = document.getElementById('floatingBookingForm');
        if (popup && popup.classList.contains('active')) {
            toggleFloatingBookingForm();
        }
    }
});
