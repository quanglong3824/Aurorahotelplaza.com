/**
 * footer.js — Aurora Hotel Plaza
 * Footer-related interactions and utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    // ── Footer Popup Modal ──────────────────────────────────────────
    const footerPopupLinks = document.querySelectorAll('.footer-link-popup');
    const footerPopupModal = document.getElementById('footerPopupModal');
    const footerPopupTitle = document.getElementById('footerPopupTitle');
    const footerPopupContent = document.getElementById('footerPopupContent');

    footerPopupLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const title = this.dataset.title || 'Notification';
            const content = this.dataset.content || 'Content coming soon';
            
            if (footerPopupTitle) footerPopupTitle.textContent = title;
            if (footerPopupContent) footerPopupContent.textContent = content;
            if (footerPopupModal) {
                footerPopupModal.classList.remove('hidden');
                footerPopupModal.classList.add('flex');
            }
        });
    });

    window.closeFooterPopup = function() {
        if (footerPopupModal) {
            footerPopupModal.classList.add('hidden');
            footerPopupModal.classList.remove('flex');
        }
    };

    // Close on backdrop click
    footerPopupModal?.addEventListener('click', function (e) {
        if (e.target === this) window.closeFooterPopup();
    });

    // ── Back To Top Button ──────────────────────────────────────────
    const backToTopBtn = document.getElementById('backToTopBtn');

    function updateBackToTopVisibility() {
        if (!backToTopBtn) return;
        if (window.scrollY > 300) {
            backToTopBtn.classList.remove('hidden');
            backToTopBtn.classList.add('flex');
        } else {
            backToTopBtn.classList.add('hidden');
            backToTopBtn.classList.remove('flex');
        }
    }

    updateBackToTopVisibility();
    window.addEventListener('scroll', updateBackToTopVisibility, { passive: true });

    backToTopBtn?.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ── Scroll Progress Bar ─────────────────────────────────────────
    window.addEventListener('scroll', function () {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        const progressBar = document.getElementById("scroll-progress");
        if (progressBar) {
            progressBar.style.width = scrolled + "%";
        }
    }, { passive: true });

    // ── Booking Type Selection ──────────────────────────────────────
    let pendingUrl = null;
    const show = id => { const m = document.getElementById(id); if (m) { m.classList.remove('hidden'); m.classList.add('flex'); } };
    const hide = id => { const m = document.getElementById(id); if (m) { m.classList.add('hidden'); m.classList.remove('flex'); } };

    // Intercept booking links
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href*="booking/index.php"], a[href*="booking/"]');
        if (!link || link.closest('.hero-slider') || link.classList.contains('booking-bypass')) return;
        const href = link.getAttribute('href');
        if (!href || (!href.includes('booking/index.php') && !href.endsWith('/booking/'))) return;
        
        e.preventDefault();
        pendingUrl = href;
        show('bookingTypeModal');
    });

    document.getElementById('btn-individual')?.addEventListener('click', () => { 
        hide('bookingTypeModal'); 
        if (pendingUrl) window.location.href = pendingUrl; 
    });
    
    document.getElementById('btn-group')?.addEventListener('click', () => { 
        hide('bookingTypeModal'); 
        show('groupContactModal'); 
    });
    
    document.getElementById('close-type-modal')?.addEventListener('click', () => hide('bookingTypeModal'));
    document.getElementById('close-group-modal')?.addEventListener('click', () => hide('groupContactModal'));

    ['bookingTypeModal', 'groupContactModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function (e) { 
            if (e.target === this) hide(id); 
        });
    });
});
