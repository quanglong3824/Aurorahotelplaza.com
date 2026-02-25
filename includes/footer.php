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
                            href="<?php echo $base_path; ?>service-detail.php?slug=aurora-restaurant"><?php _e('footer.restaurant'); ?></a>
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
                            href="<?php echo $base_path; ?>service-detail.php?slug=aurora-restaurant"><?php _e('footer.restaurant_bar'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>service-detail.php?slug=conference-service"><?php _e('footer.conference_events'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>service-detail.php?slug=therapeutic-massage"><?php _e('footer.spa_massage'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>service-detail.php?slug=pool-gym"><?php _e('footer.swimming_pool'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>service-detail.php?slug=pool-gym"><?php _e('footer.gym'); ?></a>
                    </li>
                    <li><a class="text-white/70 hover:text-accent transition-colors"
                            href="<?php echo $base_path; ?>service-detail.php?slug=airport-transfer"><?php _e('footer.shuttle_service'); ?></a>
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

<!-- ── Chat Widget ─────────────────────────────────────── -->
<?php require_once __DIR__ . '/chat-widget.php'; ?>

<button id="backToTopBtn" type="button" aria-label="Back to top"
    class="fixed bottom-6 right-[90px] z-[10000] hidden h-10 w-10 items-center justify-center rounded-full bg-[#d4af37]/80 text-white shadow-lg hover:bg-[#b8941f] focus:outline-none focus:ring-2 focus:ring-[#d4af37]/50"
    style="-webkit-transform:translateZ(0);transform:translateZ(0);will-change:transform;-webkit-backface-visibility:hidden;backface-visibility:hidden;-webkit-transition:-webkit-transform .3s cubic-bezier(.34,1.56,.64,1);transition:transform .3s cubic-bezier(.34,1.56,.64,1);">
    <span class="material-symbols-outlined">arrow_upward</span>
</button>

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

    // Scroll Progress Bar Logic
    window.addEventListener('scroll', function () {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        const progressBar = document.getElementById("scroll-progress");
        if (progressBar) {
            progressBar.style.width = scrolled + "%";
        }
    }, { passive: true });

    // ============================================
    // BOOKING TYPE SELECTION
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        let pendingUrl = null;
        const show = id => { const m = document.getElementById(id); if (m) { m.classList.remove('hidden'); m.classList.add('flex'); } };
        const hide = id => { const m = document.getElementById(id); if (m) { m.classList.add('hidden'); m.classList.remove('flex'); } };

        // Intercept booking links (skip hero-slider)
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href*="booking/index.php"], a[href*="booking/"]');
            if (!link || link.closest('.hero-slider') || link.classList.contains('booking-bypass')) return;
            const href = link.getAttribute('href');
            if (!href || (!href.includes('booking/index.php') && !href.endsWith('/booking/'))) return;
            e.preventDefault();
            pendingUrl = href;
            show('bookingTypeModal');
        });

        document.getElementById('btn-individual')?.addEventListener('click', () => { hide('bookingTypeModal'); if (pendingUrl) window.location.href = pendingUrl; });
        document.getElementById('btn-group')?.addEventListener('click', () => { hide('bookingTypeModal'); show('groupContactModal'); });
        document.getElementById('close-type-modal')?.addEventListener('click', () => hide('bookingTypeModal'));
        document.getElementById('close-group-modal')?.addEventListener('click', () => hide('groupContactModal'));

        ['bookingTypeModal', 'groupContactModal'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', function (e) { if (e.target === this) hide(id); });
        });
    });
</script>

<!-- Booking Type Modal -->
<div id="bookingTypeModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4"
    style="background:rgba(0,0,0,0.7);">
    <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-white/10">
        <div
            class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-gradient-to-r from-[#d4af37] to-[#b8941f]">
            <h3 class="font-bold text-lg text-white">Chọn loại đặt phòng</h3>
            <button id="close-type-modal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <button id="btn-individual"
                class="w-full p-4 rounded-xl bg-green-600/20 border border-green-500/30 hover:bg-green-600/30 transition-all text-left">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-green-400 text-3xl">person</span>
                    <div>
                        <div class="font-bold text-white">Cá nhân / Gia đình</div>
                        <div class="text-sm text-white/60">Đặt phòng 1-6 người, thanh toán online hoặc tại khách sạn.
                        </div>
                    </div>
                </div>
            </button>
            <button id="btn-group"
                class="w-full p-4 rounded-xl bg-amber-600/20 border border-amber-500/30 hover:bg-amber-600/30 transition-all text-left">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-amber-400 text-3xl">groups</span>
                    <div>
                        <div class="font-bold text-white">Đoàn / Sự kiện</div>
                        <div class="text-sm text-white/60">Đặt phòng số lượng lớn, công ty, đám cưới, hội nghị.</div>
                    </div>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Group Contact Modal -->
<div id="groupContactModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4"
    style="background:rgba(0,0,0,0.7);">
    <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-white/10">
        <div
            class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-gradient-to-r from-amber-500 to-orange-500">
            <h3 class="font-bold text-lg text-white"><?php _e('hero.group_booking_title'); ?></h3>
            <button id="close-group-modal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-500/20 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-amber-400 text-4xl">support_agent</span>
            </div>
            <p class="text-white/80 mb-6"><?php _e('hero.group_booking_desc'); ?></p>
            <a href="tel:0909123456"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                <span class="material-symbols-outlined">call</span>
                <?php _e('hero.call_hotline'); ?>
            </a>
        </div>
    </div>
</div>