<?php
// Determine base path based on current directory (if not already set)
if (!isset($base_path)) {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $base_path = ($current_dir == 'room-details' || $current_dir == 'apartment-details' || $current_dir == 'services-pages') ? '../' : '';
}

// Load language helper if not loaded
if (!function_exists('__')) {
    require_once __DIR__ . '/../helpers/language.php';
    initLanguage();
}
?>
<!-- Footer -->
<footer class="w-full bg-surface-dark text-white/80">
    <div class="mx-auto max-w-7xl px-4 py-20">
        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-6">
            <!-- Logo & Description -->
            <div class="lg:col-span-2">
                <a href="<?php echo $base_path; ?>index.php">
                    <img src="<?php echo $base_path; ?>assets/img/src/logo/logo-dark-ui.png"
                        alt="Aurora Hotel Plaza Logo" class="h-14 w-auto mb-6">
                </a>
                <p class="mt-2 text-base text-white/70 leading-relaxed">
                    <?php _e('footer.description'); ?>
                </p>
                <div class="mt-6">
                    <h4 class="font-bold text-white mb-4"><?php _e('footer.follow_us'); ?></h4>
                    <div class="flex gap-3">
                        <a class="glass-social-btn" href="https://www.facebook.com/aurorahotelplaza" target="_blank"
                            aria-label="Facebook">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24">
                                <path clip-rule="evenodd"
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                                    fill-rule="evenodd"></path>
                            </svg>
                        </a>
                        <a class="glass-social-btn" href="https://www.instagram.com/aurorahotelplaza" target="_blank"
                            aria-label="Instagram">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24">
                                <path clip-rule="evenodd"
                                    d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.345 2.525c.636-.247 1.363-.416 2.427-.465C9.793 2.013 10.147 2 12.315 2zm-1.161 1.545a.972.972 0 01.972.972c0 .537-.435.972-.972.972s-.972-.435-.972-.972c0-.537.435-.972.972-.972zM12 7.163c-2.673 0-4.837 2.164-4.837 4.837s2.164 4.837 4.837 4.837 4.837-2.164 4.837-4.837-2.164-4.837-4.837-4.837zm0 7.828a2.99 2.99 0 110-5.98 2.99 2.99 0 010 5.98z"
                                    fill-rule="evenodd"></path>
                            </svg>
                        </a>
                        <a class="glass-social-btn" href="https://www.youtube.com/@aurorahotelplaza" target="_blank"
                            aria-label="YouTube">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24">
                                <path
                                    d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z">
                                </path>
                            </svg>
                        </a>
                        <a class="glass-social-btn" href="https://www.tripadvisor.com/Hotel_Review-Aurora_Hotel_Plaza"
                            target="_blank" aria-label="TripAdvisor">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24">
                                <path
                                    d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8zm0-14c-3.309 0-6 2.691-6 6s2.691 6 6 6 6-2.691 6-6-2.691-6-6-6zm0 10c-2.206 0-4-1.794-4-4s1.794-4 4-4 4 1.794 4 4-1.794 4-4 4z">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4"><?php _e('footer.quick_links'); ?></h4>
                <ul class="space-y-3 text-sm">
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>about.php"><?php _e('footer.about_us'); ?></a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>rooms.php"><?php _e('footer.rooms_suite'); ?></a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services.php"><?php _e('footer.services'); ?></a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/aurora-restaurant.php"><?php _e('footer.restaurant'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>gallery.php"><?php _e('footer.gallery'); ?></a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors footer-link-popup" href="#"
                            data-title="<?php _e('footer.events'); ?>"
                            data-content="<?php _e('footer.events_coming_soon'); ?>"><?php _e('footer.events'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>contact.php"><?php _e('footer.contact'); ?></a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4"><?php _e('footer.services_title'); ?></h4>
                <ul class="space-y-3 text-sm">
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>booking/"><?php _e('footer.online_booking'); ?></a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/aurora-restaurant.php"><?php _e('footer.restaurant_bar'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/conference-service.php"><?php _e('footer.conference_events'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/therapeutic-massage.php"><?php _e('footer.spa_massage'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/pool-gym.php"><?php _e('footer.swimming_pool'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/pool-gym.php"><?php _e('footer.gym'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>services-pages/dich-vu/airport-transfer.php"><?php _e('footer.shuttle_service'); ?></a>
                    </li>
                </ul>
            </div>

            <!-- Map -->
            <div class="lg:col-span-2 order-last lg:order-none">
                <h4 class="font-bold text-white text-lg mb-4"><?php _e('footer.our_location'); ?></h4>
                <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-lg">
                    <?php
                    $mapFile = __DIR__ . '/map-embed.html';
                    if (file_exists($mapFile)) {
                        echo file_get_contents($mapFile);
                    } else {
                        echo '<!-- Map content could not be loaded -->';
                    }
                    ?>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4"><?php _e('footer.contact_title'); ?></h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">location_on</span>
                        <a href="https://maps.app.goo.gl/XsdoqeYRinQfwVxv7" target="_blank"
                            class="text-white/70 hover:text-accent transition-colors">
                            Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai
                        </a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">phone</span>
                        <a href="tel:+842513918888" class="text-white/70 hover:text-accent transition-colors">(+84-251)
                            391.8888</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">email</span>
                        <div class="flex flex-col gap-1">
                            <a href="mailto:info@aurorahotelplaza.com"
                                class="text-white/70 hover:text-accent transition-colors">info@aurorahotelplaza.com</a>
                            <a href="mailto:booking@aurorahotelplaza.com"
                                class="text-white/70 hover:text-accent transition-colors">booking@aurorahotelplaza.com</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">schedule</span>
                        <span class="text-white/70"><?php _e('footer.reception_24_7'); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-12 pt-8 border-t border-white/20">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-white/60 text-center md:text-left">
                    <?php _e('footer.copyright', ['year' => date('Y')]); ?>
                </p>
                <div class="flex gap-6 text-sm">
                    <a href="<?php echo $base_path; ?>privacy.php"
                        class="text-white/60 hover:text-accent transition-colors"><?php _e('footer.privacy_policy'); ?></a>
                    <a href="<?php echo $base_path; ?>terms.php"
                        class="text-white/60 hover:text-accent transition-colors"><?php _e('footer.terms_of_service'); ?></a>
                    <a href="<?php echo $base_path; ?>cancellation-policy.php"
                        class="text-white/60 hover:text-accent transition-colors"><?php _e('footer.cancellation_policy'); ?></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<button id="backToTopBtn" type="button" aria-label="Back to top"
    class="fixed bottom-6 right-[90px] z-[10000] hidden h-10 w-10 items-center justify-center rounded-full bg-[#d4af37]/80 text-white shadow-lg transition-all hover:bg-[#b8941f] focus:outline-none focus:ring-2 focus:ring-[#d4af37]/50">
    <span class="material-symbols-outlined">arrow_upward</span>
</button>

<!-- Global Booking Type Selection Modal (For ALL Pages) -->
<div id="global-booking-type-modal"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl animate-fade-in-up">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white"><?php _e('booking.select_type'); ?></h3>
            <button id="close-global-type-modal" class="rounded-full bg-white/10 p-2 text-white hover:bg-white/20">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-4">
            <!-- Individual / Family -->
            <a href="#" id="global-btn-individual"
                class="flex items-center justify-between p-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-left group no-underline">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-white"><?php _e('booking.individual_family'); ?></h4>
                        <p class="text-xs text-white/60"><?php _e('booking.individual_desc'); ?></p>
                    </div>
                </div>
                <span
                    class="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
            </a>

            <!-- Large Group / Event -->
            <button id="global-btn-group"
                class="w-full flex items-center justify-between p-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-left group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-white"><?php _e('booking.large_group'); ?></h4>
                        <p class="text-xs text-white/60"><?php _e('booking.group_desc'); ?></p>
                    </div>
                </div>
                <span
                    class="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
            </button>
        </div>
    </div>
</div>

<!-- Global Group Booking Contact Modal -->
<div id="global-group-booking-modal"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80 backdrop-blur-sm px-4">
    <div class="w-full max-w-sm rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl text-center">
        <div
            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/20 text-amber-500">
            <span class="material-symbols-outlined text-4xl">groups</span>
        </div>
        <h3 class="mb-2 text-xl font-bold text-white"><?php _e('hero.group_booking_title'); ?></h3>
        <p class="mb-6 text-white/70 text-sm"><?php _e('hero.group_booking_desc'); ?></p>
        <div class="flex flex-col gap-3">
            <a href="tel:+842513918888"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent py-3 font-bold text-white transition-colors hover:bg-accent-dark">
                <span class="material-symbols-outlined">call</span>
                <?php _e('hero.call_hotline'); ?>
            </a>
            <button id="close-global-group-modal"
                class="w-full rounded-xl border border-white/10 bg-white/5 py-3 font-medium text-white transition-colors hover:bg-white/10">
                <?php _e('common.close'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Footer Popup Modal -->
<div id="footerPopupModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    style="background: rgba(0,0,0,0.5);">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div
            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-[#d4af37] to-[#b8941f]">
            <h3 class="font-bold text-lg text-white" id="footerPopupTitle">Thông báo</h3>
            <button onclick="closeFooterPopup()"
                class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-[#d4af37]/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#d4af37] text-2xl">info</span>
                </div>
                <p class="text-gray-700 dark:text-gray-300" id="footerPopupContent">Nội dung sẽ được cập nhật sớm.</p>
            </div>
            <button onclick="closeFooterPopup()" class="w-full btn btn-primary"><?php _e('common.close'); ?></button>
        </div>
    </div>
</div>

<script>
    // Footer popup for coming soon links
    document.querySelectorAll('.footer-link-popup').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const title = this.dataset.title || 'Thông báo';
            const content = this.dataset.content || 'Nội dung sẽ được cập nhật sớm.';
            document.getElementById('footerPopupTitle').textContent = title;
            document.getElementById('footerPopupContent').textContent = content;
            document.getElementById('footerPopupModal').classList.remove('hidden');
            document.getElementById('footerPopupModal').classList.add('flex');
        });
    });

    function closeFooterPopup() {
        document.getElementById('footerPopupModal').classList.add('hidden');
        document.getElementById('footerPopupModal').classList.remove('flex');
    }

    // Close on backdrop click
    document.getElementById('footerPopupModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeFooterPopup();
    });

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

    // ============================================
    // GLOBAL BOOKING LINK INTERCEPTOR
    // Intercepts ALL links to booking/index.php and shows Booking Type Modal first
    // ============================================
    (function () {
        const globalTypeModal = document.getElementById('global-booking-type-modal');
        const globalGroupModal = document.getElementById('global-group-booking-modal');
        const closeTypeBtn = document.getElementById('close-global-type-modal');
        const closeGroupBtn = document.getElementById('close-global-group-modal');
        const btnIndividual = document.getElementById('global-btn-individual');
        const btnGroup = document.getElementById('global-btn-group');

        if (!globalTypeModal) return; // Not on a page with footer

        let pendingBookingUrl = null;

        // Helper to show/hide modals
        const showModal = (modal) => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        const hideModal = (modal) => { modal.classList.add('hidden'); modal.classList.remove('flex'); };

        // Intercept clicks on booking links (except those already on hero-slider with their own modal)
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href*="booking/index.php"], a[href*="booking/"]');

            // Skip if: no link found, OR link is inside hero-slider (has own modal), OR link has bypass class
            if (!link) return;
            if (link.closest('.hero-slider')) return;
            if (link.classList.contains('booking-link-bypass')) return;

            // Check if it's actually going to booking page
            const href = link.getAttribute('href');
            if (!href || (!href.includes('booking/index.php') && !href.endsWith('/booking/'))) return;

            e.preventDefault();
            pendingBookingUrl = href;
            showModal(globalTypeModal);
        });

        // Individual/Family button - proceed to booking
        btnIndividual?.addEventListener('click', function (e) {
            e.preventDefault();
            hideModal(globalTypeModal);
            if (pendingBookingUrl) {
                window.location.href = pendingBookingUrl;
            }
        });

        // Group button - show contact modal
        btnGroup?.addEventListener('click', function () {
            hideModal(globalTypeModal);
            showModal(globalGroupModal);
        });

        // Close buttons
        closeTypeBtn?.addEventListener('click', () => hideModal(globalTypeModal));
        closeGroupBtn?.addEventListener('click', () => hideModal(globalGroupModal));

        // Click outside to close
        [globalTypeModal, globalGroupModal].forEach(modal => {
            modal?.addEventListener('click', function (e) {
                if (e.target === this) hideModal(this);
            });
        });
    })();
</script>