<!-- Hero Slider Section -->
<section class="hero-slider relative flex min-h-screen w-full items-center justify-center">
    <!-- Slider Images - Optimized for Lazy Loading -->
    <!-- First image loads immediately -->
    <div class="hero-slide active"
        style="background-image: url('assets/img/classical-family-apartment/classical-family-apartment6.jpg');"></div>
    <!-- Subsequent images load via JS -->
    <div class="hero-slide" data-bg="assets/img/classical-premium-apartment/classical-premium-apartment-2.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/indochine-family-apartment/indochine-family-apartment-12.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/indochine-studio-apartment/indochine-studio-apartment-3.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/modern-premium-apartment/modern-premium-apartment-4.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/modern-studio-apartment/modern-studio-apartment-5.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/restaurant/nha-hang-aurora-hotel-4.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/restaurant/nha-hang-aurora-hotel-6.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/post/wedding/tiec-cuoi-tai-aurora-5.jpg"></div>
    <div class="hero-slide" data-bg="assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg"></div>

    <!-- Previous Arrow -->
    <div class="slider-arrow prev">
        <span class="material-symbols-outlined arrow-icon">chevron_left</span>
    </div>

    <!-- Next Arrow -->
    <div class="slider-arrow next">
        <span class="material-symbols-outlined arrow-icon">chevron_right</span>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 flex flex-col items-center gap-8 text-center text-white px-4">
        <div class="flex flex-col gap-4">
            <span class="glass-badge-accent mx-auto">
                <span class="text-accent">★★★★★</span>
                <?php _e('hero.badge'); ?>
            </span>
            <h1 class="font-display text-4xl font-black leading-tight tracking-tight md:text-6xl">Aurora Hotel Plaza
            </h1>
            <p class="text-xl font-light text-white/90 max-w-2xl"><?php _e('hero.subtitle'); ?></p>
        </div>

        <!-- Quick Booking Form - Liquid Glass (Desktop Only) -->
        <div class="mt-4 w-full max-w-4xl glass-booking-form hidden md:block">
            <form action="booking/index.php" method="GET" id="desktop-search-form"
                class="booking-search-form grid grid-cols-1 items-end gap-4 md:grid-cols-5">
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkin">
                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                        <?php _e('hero.check_in'); ?>
                    </label>
                    <input class="glass-input-solid h-12 checkin-input" id="checkin" name="check_in" type="date"
                        min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkout">
                        <span class="material-symbols-outlined text-sm">event</span>
                        <?php _e('hero.check_out'); ?>
                    </label>
                    <input class="glass-input-solid h-12 checkout-input" id="checkout" name="check_out" type="date"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                        value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" />
                </div>
                <!-- Adults with Room Auto-Calc -->
                <div class="flex flex-col text-left relative group">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="adults">
                        <span class="material-symbols-outlined text-sm">person</span>
                        <?php _e('hero.adults'); ?>
                    </label>
                    <select class="glass-input-solid glass-select h-12 adults-select" id="adults" name="adults">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == 2 ? 'selected' : ''; ?>>
                                <?php echo $i; ?>     <?php _e('hero.person'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <!-- Desktop Room Indicator Tooltip -->
                    <div id="desktop-room-indicator"
                        class="absolute -top-10 left-0 right-0 hidden bg-accent text-white text-xs py-1 px-2 rounded shadow-lg text-center z-50">
                        <?php _e('booking.suggested_rooms'); ?>: <span id="desktop-room-count">1</span>
                    </div>
                </div>

                <input type="hidden" name="num_rooms" id="desktop_num_rooms_input" value="1">

                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="children">
                        <span class="material-symbols-outlined text-sm">child_care</span>
                        <?php _e('hero.children'); ?>
                    </label>
                    <select class="glass-input-solid glass-select h-12 children-select" id="children" name="children">
                        <?php for ($c = 0; $c <= 6; $c++): ?>
                            <option value="<?php echo $c; ?>" <?php echo $c == 0 ? 'selected' : ''; ?>>
                                <?php echo $c; ?>     <?php _e('hero.child'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn-glass-primary h-12 w-full whitespace-nowrap">
                    <span class="material-symbols-outlined">search</span>
                    <span><?php _e('hero.search'); ?></span>
                </button>
            </form>
        </div>

        <!-- Mobile Floating Booking Button -->
        <div class="fixed bottom-24 right-4 z-50 md:hidden">
            <button id="mobile-booking-btn"
                class="flex items-center gap-2 rounded-full bg-accent px-6 py-3 text-white shadow-lg shadow-accent/40 backdrop-blur-md transition-transform hover:scale-105 active:scale-95">
                <span class="material-symbols-outlined animate-pulse">search</span>
                <span class="font-bold"><?php _e('hero.search'); ?></span>
            </button>
        </div>

        <!-- NEW: Booking Type Selection Modal -->
        <div id="booking-type-modal"
            class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm transition-all duration-300 md:hidden">
            <div
                class="w-full max-w-sm rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl animate-fade-in-up">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-white"><?php _e('booking.select_type'); ?></h3>
                    <button id="close-type-modal" class="rounded-full bg-white/10 p-2 text-white hover:bg-white/20">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Individual / Small Group -->
                    <button id="btn-type-individual"
                        class="flex items-center justify-between p-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-left group">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-white"><?php _e('booking.individual_family'); ?></h4>
                                <p class="text-xs text-white/60"><?php _e('booking.individual_desc'); ?></p>
                            </div>
                        </div>
                        <span
                            class="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
                    </button>

                    <!-- Large Group / Event -->
                    <button id="btn-type-group"
                        class="flex items-center justify-between p-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-left group">
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

        <!-- Mobile Booking Modal (Personal/Small Group) -->
        <div id="mobile-booking-modal"
            class="fixed inset-0 z-[9999] hidden items-end justify-center bg-black/60 backdrop-blur-sm transition-all duration-300 md:hidden">
            <div class="w-full rounded-t-2xl bg-slate-900 p-6 shadow-2xl ring-1 ring-white/10 animate-fade-in-up">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">manage_search</span>
                        <?php _e('hero.find_room'); ?>
                    </h3>
                    <button id="close-mobile-modal" class="rounded-full bg-white/10 p-2 text-white hover:bg-white/20">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form action="booking/index.php" method="GET" class="booking-search-form flex flex-col gap-4"
                    id="mobile-search-form">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm text-white/80"><?php _e('hero.check_in'); ?></label>
                            <input class="glass-input-solid h-12 w-full checkin-input" name="check_in" type="date"
                                min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
                        </div>
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm text-white/80"><?php _e('hero.check_out'); ?></label>
                            <input class="glass-input-solid h-12 w-full checkout-input" name="check_out" type="date"
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm text-white/80"><?php _e('hero.adults'); ?></label>
                            <select class="glass-input-solid glass-select h-12 w-full adults-select" name="adults"
                                id="mobile-adults">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == 2 ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>     <?php _e('hero.person'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm text-white/80"><?php _e('hero.children'); ?></label>
                            <select class="glass-input-solid glass-select h-12 w-full children-select" name="children">
                                <?php for ($c = 0; $c <= 6; $c++): ?>
                                    <option value="<?php echo $c; ?>" <?php echo $c == 0 ? 'selected' : ''; ?>>
                                        <?php echo $c; ?>     <?php _e('hero.child'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Room Quantity Indicator -->
                    <div id="room-quantity-indicator"
                        class="hidden p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-300"><?php _e('booking.rooms_required'); ?>:</span>
                            <span class="font-bold text-white flex items-center gap-2">
                                <span id="room-count-display">1</span>
                                <span class="material-symbols-outlined text-sm">bedroom_parent</span>
                            </span>
                        </div>
                        <input type="hidden" name="num_rooms" id="num_rooms_input" value="1">
                    </div>

                    <button type="submit"
                        class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-accent py-3.5 font-bold text-white shadow-lg shadow-accent/30 transition-all active:scale-95">
                        <span class="material-symbols-outlined">search</span>
                        <?php _e('hero.search_now'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Contact For Group Modal (Works on Both Desktop & Mobile) -->
        <div id="group-booking-modal"
            class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80 backdrop-blur-sm px-4">
            <div
                class="w-full max-w-sm rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl text-center transform transition-all scale-100">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/20 text-amber-500">
                    <span class="material-symbols-outlined text-4xl">groups</span>
                </div>
                <h3 class="mb-2 text-xl font-bold text-white"><?php _e('hero.group_booking_title'); ?></h3>
                <p class="mb-6 text-white/70 text-sm">
                    <?php _e('hero.group_booking_desc'); ?>
                </p>
                <div class="flex flex-col gap-3">
                    <a href="tel:+842513918888"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent py-3 font-bold text-white transition-colors hover:bg-accent-dark">
                        <span class="material-symbols-outlined">call</span>
                        <?php _e('hero.call_hotline'); ?>
                    </a>
                    <button id="close-group-modal"
                        class="w-full rounded-xl border border-white/10 bg-white/5 py-3 font-medium text-white transition-colors hover:bg-white/10">
                        <?php _e('common.close'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Trust Badges - Liquid Glass -->
        <div class="flex flex-wrap justify-center gap-4 mt-6">
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">verified</span>
                <span><?php _e('hero.best_price'); ?></span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">credit_card_off</span>
                <span><?php _e('hero.no_prepayment'); ?></span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">event_available</span>
                <span><?php _e('hero.free_cancel'); ?></span>
            </div>
        </div>
    </div>

    <!-- Slider Navigation Dots -->
    <div class="slider-dots" id="slider-dots-container">
        <!-- Dots will be generated dynamically by JavaScript -->
    </div>

    <!-- Scroll Down Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden md:block">
        <a href="#about" class="flex flex-col items-center gap-2 text-white/70 hover:text-white transition-colors">
            <span class="text-sm"><?php _e('hero.explore_more'); ?></span>
            <span class="material-symbols-outlined text-2xl">keyboard_arrow_down</span>
        </a>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. Date Logic Section ---
        const setupDateInputs = (prefix = '') => {
            const checkin = document.querySelector(prefix ? `${prefix} .checkin-input` : '.checkin-input');
            const checkout = document.querySelector(prefix ? `${prefix} .checkout-input` : '.checkout-input');

            if (checkin && checkout) {
                checkin.addEventListener('change', function () {
                    const checkinDate = new Date(this.value);
                    if (!isNaN(checkinDate.getTime())) {
                        checkinDate.setDate(checkinDate.getDate() + 1);
                        const minCheckout = checkinDate.toISOString().split('T')[0];
                        checkout.min = minCheckout;

                        if (checkout.value && checkout.value <= this.value) {
                            checkout.value = minCheckout;
                        }
                    }
                });
            }
        };

        // Initialize for desktop and mobile forms (all instances)
        document.querySelectorAll('.booking-search-form').forEach(form => {
            const checkin = form.querySelector('.checkin-input');
            const checkout = form.querySelector('.checkout-input');
            if (checkin && checkout) {
                checkin.addEventListener('change', function () {
                    const date = new Date(this.value);
                    date.setDate(date.getDate() + 1);
                    checkout.min = date.toISOString().split('T')[0];
                    if (checkout.value <= this.value) checkout.value = checkout.min;
                });
            }
        });

        // --- 2. Mobile Modal Logic (Updated for Type Selection) ---
        const mobileBtn = document.getElementById('mobile-booking-btn');
        const typeModal = document.getElementById('booking-type-modal');
        const mobileModal = document.getElementById('mobile-booking-modal');
        const groupModal = document.getElementById('group-booking-modal');

        const btnIndiv = document.getElementById('btn-type-individual');
        const btnGroup = document.getElementById('btn-type-group');

        const closeTypeModal = document.getElementById('close-type-modal');
        const closeMobileModal = document.getElementById('close-mobile-modal');
        const closeGroupModal = document.getElementById('close-group-modal');

        // Show "Type Selection" when clicking Find Room
        if (mobileBtn && typeModal) {
            mobileBtn.addEventListener('click', () => {
                typeModal.classList.remove('hidden');
                typeModal.classList.add('flex');
            });
        }

        // Handle Type Selection
        if (btnIndiv) {
            btnIndiv.addEventListener('click', () => {
                typeModal.classList.add('hidden');
                typeModal.classList.remove('flex');
                mobileModal.classList.remove('hidden');
                mobileModal.classList.add('flex');
            });
        }

        if (btnGroup) {
            btnGroup.addEventListener('click', () => {
                typeModal.classList.add('hidden');
                typeModal.classList.remove('flex');
                groupModal.classList.remove('hidden');
                groupModal.classList.add('flex');
            });
        }

        // Close handlers
        const closeHandler = (modal) => {
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };

        if (closeTypeModal) closeTypeModal.addEventListener('click', () => closeHandler(typeModal));
        if (closeMobileModal) closeMobileModal.addEventListener('click', () => closeHandler(mobileModal));
        if (closeGroupModal) closeGroupModal.addEventListener('click', () => closeHandler(groupModal));

        // Click outside to close
        [typeModal, mobileModal, groupModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeHandler(modal);
                });
            }
        });

        // --- 3. Dynamic Room Calculation (Mobile Form) ---
        const mobileAdults = document.getElementById('mobile-adults');
        const roomIndicator = document.getElementById('room-quantity-indicator');
        const roomCountDisplay = document.getElementById('room-count-display');
        const numRoomsInput = document.getElementById('num_rooms_input');

        if (mobileAdults) {
            mobileAdults.addEventListener('change', function () {
                const adults = parseInt(this.value);
                // Rule: 1 room for every 2 adults (ceil)
                const roomsNeeded = Math.ceil(adults / 2);

                if (numRoomsInput) numRoomsInput.value = roomsNeeded;
                if (roomCountDisplay) roomCountDisplay.textContent = roomsNeeded;

                if (roomsNeeded > 1) {
                    roomIndicator.classList.remove('hidden');
                } else {
                    roomIndicator.classList.add('hidden');
                }
            });
        }

        // --- 3b. Dynamic Room Calculation (Desktop Form) ---
        const desktopForm = document.getElementById('desktop-search-form');
        if (desktopForm) {
            const desktopAdults = desktopForm.querySelector('#adults');
            const desktopIndicator = document.getElementById('desktop-room-indicator');
            const desktopCountDisplay = document.getElementById('desktop-room-count');
            const desktopNumRoomsInput = document.getElementById('desktop_num_rooms_input');

            if (desktopAdults) {
                desktopAdults.addEventListener('change', function () {
                    const adults = parseInt(this.value);
                    const roomsNeeded = Math.ceil(adults / 2);

                    if (desktopNumRoomsInput) desktopNumRoomsInput.value = roomsNeeded;
                    if (desktopCountDisplay) desktopCountDisplay.textContent = roomsNeeded;

                    if (roomsNeeded > 1) {
                        desktopIndicator.classList.remove('hidden');
                    } else {
                        desktopIndicator.classList.add('hidden');
                    }
                });
            }

            // --- Desktop Form Submission: Check Group Size > 6 ADULTS ---
            desktopForm.addEventListener('submit', function (e) {
                const adults = parseInt(desktopForm.querySelector('#adults').value) || 0;
                // Only count adults (children stay with family, don't add to group count)

                if (adults > 6) {
                    e.preventDefault();
                    // Show Group Booking Modal
                    const groupModal = document.getElementById('group-booking-modal');
                    if (groupModal) {
                        groupModal.classList.remove('hidden');
                        groupModal.classList.add('flex');
                    }
                }
                // Else: Allow form to submit normally
            });
        }

        // --- 3c. Mobile Form Submission: Check Group Size > 6 ADULTS ---
        const mobileForm = document.getElementById('mobile-search-form');
        if (mobileForm) {
            mobileForm.addEventListener('submit', function (e) {
                const adults = parseInt(mobileForm.querySelector('#mobile-adults').value) || 0;
                // Only count adults

                if (adults > 6) {
                    e.preventDefault();
                    // Close Mobile Modal, Show Group Booking Modal
                    const mobileModal = document.getElementById('mobile-booking-modal');
                    const groupModal = document.getElementById('group-booking-modal');
                    if (mobileModal) {
                        mobileModal.classList.add('hidden');
                        mobileModal.classList.remove('flex');
                    }
                    if (groupModal) {
                        groupModal.classList.remove('hidden');
                        groupModal.classList.add('flex');
                    }
                }
                // Else: Allow form to submit normally
            });
        }

        // --- 4. Lazy Load Images ---
        const lazySlides = document.querySelectorAll('.hero-slide[data-bg]');
        if ('IntersectionObserver' in window) {
            const loadImages = () => {
                lazySlides.forEach(slide => {
                    slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
                    slide.removeAttribute('data-bg');
                });
            };
            setTimeout(loadImages, 3000);
        } else {
            lazySlides.forEach(slide => {
                slide.style.backgroundImage = `url('${slide.dataset.bg}')`;
            });
        }
    });
</script>