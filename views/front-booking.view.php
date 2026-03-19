<!-- Booking Page View -->
<script>
    const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>

<div class="relative flex min-h-screen w-full flex-col">

    <?php include '../includes/header.php'; ?>

    <!-- ANTI-SPAM: Show block modal if user has pending bookings -->
    <?php if (!$data['spam_check_passed'] && !empty($data['booking_block_message'])): ?>
        <div id="bookingBlockModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="background:rgba(0,0,0,0.85);backdrop-filter:blur(10px);" role="dialog" aria-modal="true">
            <div class="relative bg-gradient-to-br from-slate-900/95 to-slate-800/95 rounded-3xl shadow-2xl w-full max-w-lg border border-white/10 max-h-[90vh] overflow-y-auto"
                onclick="event.stopPropagation()" style="box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
                <!-- Glass effect header -->
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-gradient-to-r from-red-600/80 to-red-700/80 sticky top-0 rounded-t-3xl backdrop-blur-md"
                    style="backdrop-filter:blur(10px);">
                    <h3 class="font-bold text-lg text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-300">block</span>
                        <?php _e('booking_page.block_title'); ?>
                    </h3>
                    <div class="text-white/60 text-xs bg-white/10 px-2 py-1 rounded-lg backdrop-blur-sm">
                        <?php _e('booking_page.anti_spam_title'); ?>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Warning Icon -->
                    <div class="text-center mb-4">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/20 border-2 border-red-500/30 mb-3 backdrop-blur-sm">
                            <span class="material-symbols-outlined text-3xl text-red-400">warning</span>
                        </div>
                        <p class="text-gray-200 text-base leading-relaxed">
                            <?php echo htmlspecialchars($data['booking_block_message']); ?>
                        </p>
                    </div>

                    <?php if (!empty($data['booking_block_bookings'])): ?>
                        <div class="mb-4">
                            <h4 class="font-semibold text-red-400 mb-3 flex items-center gap-2 text-sm">
                                <span class="material-symbols-outlined text-base">list</span>
                                <?php _e('booking_page.pending_bookings_title'); ?> (<?php echo count($data['booking_block_bookings']); ?>):
                            </h4>
                            <div
                                class="space-y-2 max-h-48 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-red-500/30 scrollbar-track-transparent">
                                <?php foreach ($data['booking_block_bookings'] as $booking): ?>
                                    <div
                                        class="bg-white/5 hover:bg-white/10 rounded-xl p-3 border border-white/10 transition-all duration-200 backdrop-blur-sm">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="font-semibold text-sm text-blue-300">
                                                <span
                                                    class="material-symbols-outlined text-xs align-middle mr-1">confirmation_number</span>
                                                <?php echo htmlspecialchars($booking['booking_code']); ?>
                                            </span>
                                            <?php
                                            $status_config = [
                                                'pending' => ['class' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30', 'label' => __('booking_status.pending')],
                                                'confirmed' => ['class' => 'bg-blue-500/20 text-blue-300 border-blue-500/30', 'label' => __('booking_status.confirmed')],
                                                'checked_in' => ['class' => 'bg-green-500/20 text-green-300 border-green-500/30', 'label' => __('booking_status.checked_in')]
                                            ];
                                            $config = $status_config[$booking['status']] ?? ['class' => 'bg-gray-500/20 text-gray-300 border-gray-500/30', 'label' => $booking['status']];
                                            ?>
                                            <span
                                                class="text-xs px-2 py-1 rounded-lg border <?php echo $config['class']; ?> backdrop-blur-sm font-medium">
                                                <?php echo $config['label']; ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-400 space-y-1.5 pl-6">
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    class="material-symbols-outlined text-xs text-amber-400">calendar_today</span>
                                                <span><?php echo htmlspecialchars($booking['check_in_date']); ?></span>
                                                <span class="text-gray-600">→</span>
                                                <span><?php echo htmlspecialchars($booking['check_out_date']); ?></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-xs text-green-400">payments</span>
                                                <span><?php echo number_format($booking['total_amount']); ?> VND</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Help Box - Liquid Glass -->
                    <div class="mt-6 p-4 bg-blue-500/10 rounded-xl border border-blue-500/20 backdrop-blur-sm">
                        <h5 class="font-semibold text-blue-300 mb-2 text-sm flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">help_outline</span>
                            <?php _e('booking_page.block_help_title'); ?>
                        </h5>
                        <ul class="text-sm text-gray-300 space-y-1.5 list-none pl-2">
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-xs text-blue-400 mt-0.5">check_circle</span>
                                <span><?php _e('booking_form.active_booking_pay'); ?></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-xs text-blue-400 mt-0.5">check_circle</span>
                                <span><?php _e('booking_form.active_booking_checkout'); ?></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-xs text-blue-400 mt-0.5">check_circle</span>
                                <span><?php _e('booking_form.active_booking_contact'); ?><strong
                                        class="text-amber-300">(0251) 391.8888</strong></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="px-6 py-4 border-t border-white/10 flex flex-col sm:flex-row justify-end gap-3 sticky bottom-0 bg-slate-900/90 backdrop-blur-md rounded-b-3xl">
                    <a href="../index.php"
                        class="px-5 py-2.5 text-gray-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all duration-200 text-sm font-medium text-center backdrop-blur-sm">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">home</span>
                        <?php _e('booking_page.home_link'); ?>
                    </a>
                    <a href="../profile/bookings.php"
                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl transition-all duration-200 text-sm font-medium inline-flex items-center justify-center gap-2 shadow-lg shadow-blue-500/25 backdrop-blur-sm border border-blue-400/20">
                        <span class="material-symbols-outlined text-sm">list</span>
                        <?php _e('booking_page.view_my_bookings'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        // Clear session after showing
        unset($_SESSION['booking_block_message']);
        unset($_SESSION['booking_block_bookings']);
        ?>
    <?php endif; ?>
    <!-- END ANTI-SPAM BLOCK MODAL -->

    <main class="booking-main">
        <div class="booking-container">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="icon-badge">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <h1 class="booking-title"><?php _e('booking_page.page_title'); ?></h1>
                <p class="booking-subtitle"><?php _e('booking_page.page_subtitle'); ?></p>
            </div>

            <!-- Booking Form - Liquid Glass -->
            <div class="booking-card">
                <form id="bookingForm" novalidate>

                    <!-- Step Indicator - Premium Glass Design -->
                    <div class="step-indicator-container mb-8">
                        <div class="step-item active" data-step="1">
                            <div class="step-circle">1</div>
                            <span><?php _e('booking_page.step_select_room'); ?></span>
                        </div>
                        <div class="step-connector" data-from="1" data-to="2"></div>
                        <div class="step-item" data-step="2">
                            <div class="step-circle">2</div>
                            <span><?php _e('booking_page.step_info'); ?></span>
                        </div>
                        <div class="step-connector" data-from="2" data-to="3"></div>
                        <div class="step-item" data-step="3">
                            <div class="step-circle">3</div>
                            <span><?php _e('booking_page.step_payment'); ?></span>
                        </div>
                    </div>

                    <!-- Step 1: Room/Apartment Selection -->
                    <div class="form-step active" id="step1">
                        <h3 class="text-xl font-bold mb-4" id="step1_title">
                            <?php _e('booking_page.select_room_date'); ?>
                        </h3>

                        <!-- Room Type Selection (Common) -->
                        <div class="form-group mb-6">
                            <label class="form-label"><?php _e('booking_page.room_type'); ?> *</label>
                            <select name="room_type_id" id="room_type_id" class="form-input" required
                                data-preselected="<?php echo $data['selected_room_type_id'] ?? 'null'; ?>"
                                data-slug="<?php echo $data['selected_room_slug'] ?? 'null'; ?>">
                                <option value="">-- <?php _e('booking_page.select_room_type'); ?> --</option>
                                <?php foreach ($data['room_types'] as $room):
                                    $is_available = $room['available_rooms'] > 0;
                                    // Apartments are always "available" for inquiry
                                    $is_inquiry = isset($room['booking_type']) && $room['booking_type'] === 'inquiry';
                                    if ($is_inquiry)
                                        $is_available = true;

                                    $availability_text = $is_available
                                        ? ($is_inquiry ? "" : "({$room['available_rooms']} " . __('booking_form.room_available') . ")")
                                        : "(" . __('booking_form.out_of_stock') . ")";

                                    // Get display price based on category
                                    $display_price = $room['category'] === 'room'
                                        ? ($room['price_double_occupancy'] ?? $room['base_price'])
                                        : ($room['price_daily_double'] ?? $room['base_price']);
                                    ?>
                                    <option value="<?php echo $room['room_type_id']; ?>"
                                        data-price="<?php echo $display_price; ?>"
                                        data-price-published="<?php echo $room['price_published'] ?? 0; ?>"
                                        data-price-single="<?php echo $room['price_single_occupancy'] ?? $room['base_price']; ?>"
                                        data-price-double="<?php echo $room['price_double_occupancy'] ?? $room['base_price']; ?>"
                                        data-price-short-stay="<?php echo $room['price_short_stay'] ?? 0; ?>"
                                        data-price-daily-single="<?php echo $room['price_daily_single'] ?? 0; ?>"
                                        data-price-daily-double="<?php echo $room['price_daily_double'] ?? 0; ?>"
                                        data-price-weekly-single="<?php echo $room['price_weekly_single'] ?? 0; ?>"
                                        data-price-weekly-double="<?php echo $room['price_weekly_double'] ?? 0; ?>"
                                        data-price-avg-weekly-single="<?php echo $room['price_avg_weekly_single'] ?? 0; ?>"
                                        data-price-avg-weekly-double="<?php echo $room['price_avg_weekly_double'] ?? 0; ?>"
                                        data-max-guests="<?php echo $room['max_occupancy']; ?>"
                                        data-max-adults="<?php echo $room['max_adults'] ?? 2; ?>"
                                        data-max-children="<?php echo $room['max_children'] ?? 1; ?>"
                                        data-available="<?php echo $room['available_rooms']; ?>"
                                        data-category="<?php echo $room['category']; ?>"
                                        data-size="<?php echo $room['size_sqm'] ?? 0; ?>"
                                        data-booking-type="<?php echo $room['booking_type'] ?? 'instant'; ?>" <?php echo !$is_available ? 'disabled' : ''; ?>     <?php echo ($data['selected_room_type_id'] !== null && (int) $data['selected_room_type_id'] === (int) $room['room_type_id'] && $is_available) ? 'selected' : ''; ?>>
                                        <?php echo _f($room, 'type_name'); ?> -
                                        <?php echo $is_inquiry ? __('inquiry.contact_btn') : number_format($display_price) . ' ' . __('common.currency') . __('common.per_night') . ' ' . $availability_text; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ========== ROOM BOOKING FIELDS (instant) ========== -->
                        <div id="room_booking_fields">
                            <!-- Booking Type Selection -->
                            <div class="form-group mb-4">
                                <label class="form-label"><?php _e('booking_form.booking_type'); ?> *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="booking-type-option selected" data-type="standard">
                                        <input type="radio" name="booking_type" value="standard" checked
                                            class="hidden">
                                        <div
                                            class="flex items-center gap-2 p-3 rounded-lg border-2 border-amber-500 bg-amber-500/10 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-amber-500">hotel</span>
                                            <div>
                                                <div class="font-semibold text-sm">
                                                    <?php _e('booking_form.overnight'); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?php _e('booking_form.overnight_desc'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="booking-type-option" data-type="short_stay"
                                        id="short_stay_option">
                                        <input type="radio" name="booking_type" value="short_stay" class="hidden">
                                        <div
                                            class="flex items-center gap-2 p-3 rounded-lg border-2 border-gray-600 bg-gray-700/30 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-blue-400">schedule</span>
                                            <div>
                                                <div class="font-semibold text-sm">
                                                    <?php _e('booking_form.short_stay'); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?php _e('booking_form.short_stay_desc'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p id="short_stay_note" class="text-xs text-blue-400 mt-2 hidden">
                                    <span class="material-symbols-outlined text-sm align-middle">info</span>
                                    <?php _e('booking_form.short_stay_no_breakfast'); ?>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Number of Adults -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.num_adults'); ?> *</label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="adjustValue('num_adults', -1)"
                                            class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined">remove</span>
                                        </button>
                                        <input type="number" name="num_adults" id="num_adults"
                                            class="form-input text-center w-20" min="1" max="3" value="2" required
                                            readonly>
                                        <button type="button" onclick="adjustValue('num_adults', 1)"
                                            class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined">add</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1"><?php _e('booking_page.max_adults_text'); ?></p>
                                </div>

                                <!-- Number of Children -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.num_children_age'); ?></label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="adjustValue('num_children', -1)"
                                            class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined">remove</span>
                                        </button>
                                        <input type="number" name="num_children" id="num_children"
                                            class="form-input text-center w-20" min="0" max="2" value="0" readonly>
                                        <button type="button" onclick="adjustValue('num_children', 1)"
                                            class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined">add</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1"><?php _e('booking_page.max_children_text'); ?></p>
                                </div>

                                    <!-- Check-in Date -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_page.check_in_date'); ?> *</label>
                                    <input type="date" name="check_in_date" id="check_in_date" class="form-input"
                                        value="<?php echo $data['prefilled_check_in']; ?>" min="<?php echo date('Y-m-d'); ?>" max="2030-12-31" required>
                                </div>

                                <!-- Check-out Date -->
                                <div class="form-group" id="checkout_group">
                                    <label class="form-label"><?php _e('booking_page.check_out_date'); ?> *</label>
                                    <input type="date" name="check_out_date" id="check_out_date" class="form-input"
                                        value="<?php echo $data['prefilled_check_out']; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" max="2030-12-31" required>
                                </div>
                            </div>

                            <!-- Smart Suggestion Box -->
                            <div id="smart_suggestion_box"
                                class="mt-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl hidden">
                                <div class="flex items-start gap-3">
                                    <span class="material-symbols-outlined text-amber-400 mt-0.5">lightbulb</span>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-amber-400 mb-2">
                                            <?php _e('booking_form.extra_charge_suggestion'); ?>
                                        </h4>
                                        <p id="suggestion_message" class="text-sm text-white/80 mb-3"></p>
                                        <div id="suggestion_actions" class="flex flex-wrap gap-2">
                                            <!-- Dynamic buttons will be added here -->
                                        </div>
                                    </div>
                                    <button type="button" onclick="dismissSuggestion()"
                                        class="text-white/50 hover:text-white/80">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Enhanced Extra Guests Section -->
                            <div class="mt-6 bg-slate-800/50 rounded-xl p-5 border border-slate-700/50"
                                id="extra_guests_section">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                            <span
                                                class="material-symbols-outlined text-blue-400 text-lg">person_add</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-white">
                                                <?php _e('booking_form.extra_guest'); ?>
                                            </h3>
                                            <p class="text-sm text-gray-400 mt-1"><?php _e('booking_page.child_height_note'); ?></p>
                                        </div>
                                    </div>
                                    <button type="button" id="toggle_extra_guests_btn"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-all flex items-center gap-2 font-medium shadow-lg hover:shadow-xl"
                                        onclick="toggleExtraGuests()">
                                        <span class="material-symbols-outlined text-base">add</span>
                                        <?php _e('booking_form.add_guest'); ?>
                                    </button>
                                </div>

                                <div id="extra_guests_list" class="hidden space-y-4">
                                    <!-- Dynamic entries will be added here -->
                                </div>

                                <div class="mt-4 pt-4 border-t border-slate-700/50">
                                    <p class="text-xs text-gray-400 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-blue-400">info</span>
                                        <?php _e('booking_form.extra_guest_note'); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Enhanced Extra Bed Section -->
                            <div class="mt-4 bg-slate-800/50 rounded-xl p-5 border border-slate-700/50"
                                id="extra_bed_section">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                                            <span
                                                class="material-symbols-outlined text-orange-400 text-lg">single_bed</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-white">
                                                <?php _e('booking_form.extra_bed'); ?>
                                            </h3>
                                            <p class="text-sm text-gray-400 mt-1"><?php _e('booking_page.extra_bed_for_room'); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <div class="text-orange-400 font-bold">
                                                650.000<?php _e('common.currency'); ?></div>
                                            <div class="text-xs text-gray-400"><?php _e('common.per_night'); ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="adjustValue('extra_beds', -1)"
                                                class="w-9 h-9 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors shadow-md hover:shadow-lg">
                                                <span class="material-symbols-outlined text-base">remove</span>
                                            </button>
                                            <input type="number" name="extra_beds" id="extra_beds"
                                                class="form-input text-center w-16 text-lg font-bold bg-slate-700 border-slate-600"
                                                min="0" max="2" value="0" readonly>
                                            <button type="button" onclick="adjustValue('extra_beds', 1)"
                                                class="w-9 h-9 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors shadow-md hover:shadow-lg">
                                                <span class="material-symbols-outlined text-base">add</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3 mb-3">
                                    <div class="flex items-center gap-2 text-amber-400 text-sm">
                                        <span class="material-symbols-outlined text-base">warning</span>
                                        <span><?php _e('booking_form.extra_bed_note'); ?></span>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-400 flex items-center gap-2" id="extra_bed_warning"
                                    style="display: none;">
                                    <span class="material-symbols-outlined text-sm text-red-400">error</span>
                                    <?php _e('booking_form.extra_bed_not_for_apt'); ?>
                                </p>
                            </div>

                            <!-- Enhanced Price Summary -->
                            <div class="mt-6 p-4 bg-gradient-to-br from-amber-500/10 to-amber-600/5 dark:from-gray-700 dark:to-gray-800 rounded-xl border border-amber-500/20 dark:border-gray-600 transition-all duration-300"
                                id="price_summary_box">
                                <!-- Room Info Header -->
                                <div
                                    class="flex items-center justify-between mb-3 pb-3 border-b border-gray-600/50">
                                    <div class="flex items-center gap-2">
                                        <span id="price_type_badge"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-amber-500/20 text-amber-400 text-xs font-medium rounded-full">
                                            <span class="material-symbols-outlined text-sm">hotel</span>
                                            <span
                                                id="price_type_label"><?php _e('booking_form.price_for_2'); ?></span>
                                        </span>
                                    </div>
                                    <span id="original_price_display"
                                        class="text-sm text-gray-500 line-through hidden">0
                                        <?php _e('common.currency'); ?></span>
                                </div>

                                <!-- Price Breakdown -->
                                <div class="space-y-2 text-sm">
                                    <!-- Room Rate -->
                                    <div class="flex justify-between items-center">
                                        <span
                                            class="text-gray-400"><?php _e('booking_page.price_per_night'); ?>:</span>
                                        <span id="room_price_display" class="font-bold" style="color: #d4af37;">0
                                            <?php _e('common.currency'); ?></span>
                                    </div>

                                    <!-- Number of nights -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400"><?php _e('booking_page.num_nights'); ?>:</span>
                                        <span id="num_nights">0 <?php _e('common.per_night'); ?></span>
                                    </div>

                                    <!-- Room Subtotal -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400"><?php _e('booking_form.room_charge'); ?>:</span>
                                        <span id="room_subtotal_display">0 <?php _e('common.currency'); ?></span>
                                    </div>

                                    <!-- Extra Guest Fee -->
                                    <div class="flex justify-between items-center hidden" id="extra_guest_fee_row">
                                        <span
                                            class="text-gray-400"><?php _e('booking_form.extra_guest_charge'); ?>:</span>
                                        <span id="extra_guest_fee_display" class="text-blue-400">0
                                            <?php _e('common.currency'); ?></span>
                                    </div>

                                    <!-- Extra Bed Fee -->
                                    <div class="flex justify-between items-center hidden" id="extra_bed_fee_row">
                                        <span
                                            class="text-gray-400"><?php _e('booking_form.extra_bed_charge'); ?>:</span>
                                        <span id="extra_bed_fee_display" class="text-orange-400">0
                                            <?php _e('common.currency'); ?></span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div
                                    class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300/50 dark:border-gray-600">
                                    <span class="font-semibold"><?php _e('booking_page.estimated_total'); ?>:</span>
                                    <span id="estimated_total_display" class="text-2xl font-bold text-accent">0
                                        <?php _e('common.currency'); ?></span>
                                    <input type="hidden" id="estimated_total" name="estimated_total" value="0">
                                    <input type="hidden" id="price_type_used" name="price_type_used" value="double">
                                    <input type="hidden" id="extra_guest_fee" name="extra_guest_fee" value="0">
                                    <input type="hidden" id="extra_bed_fee" name="extra_bed_fee" value="0">
                                    <input type="hidden" id="num_guests" name="num_guests" value="2">
                                    <input type="hidden" id="extra_guests_data" name="extra_guests_data" value="[]">
                                </div>

                                <!-- Tax Info Note -->
                                <div class="mt-3 pt-3 border-t border-gray-300/30 dark:border-gray-600">
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mb-2">
                                        <span
                                            class="material-symbols-outlined text-sm text-green-500">check_circle</span>
                                        <?php _e('booking_form.included_tax_service'); ?>
                                    </p>
                                    <p class="text-xs text-amber-500 dark:text-amber-400 flex items-start gap-1">
                                        <span class="material-symbols-outlined text-sm mt-0.5">info</span>
                                        <span><?php _e('booking_form.price_estimate_note'); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ========== APARTMENT INQUIRY FIELDS (inquiry) ========== -->
                        <div id="apartment_inquiry_fields" class="hidden">
                            <div class="p-4 mb-4 bg-purple-500/10 border border-purple-500/30 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <span class="material-symbols-outlined text-purple-400 mt-0.5">apartment</span>
                                    <div>
                                        <h4 class="font-semibold text-purple-400 mb-1"><?php _e('inquiry.title'); ?>
                                        </h4>
                                        <p class="text-sm text-gray-300">
                                            <?php _e('inquiry.subtitle'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Preferred Move-in Date -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.est_checkin_date'); ?>
                                        *</label>
                                    <input type="date" name="preferred_check_in" id="preferred_check_in"
                                        class="form-input">
                                </div>

                                <!-- Rent Mode Selection -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.rent_mode'); ?> *</label>
                                    <select id="rent_mode" class="form-input" onchange="toggleRentMode()">
                                        <option value="by_month"><?php _e('booking_form.by_month'); ?></option>
                                        <option value="by_date"><?php _e('booking_form.by_day'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- BY MONTH Options -->
                            <div id="rent_by_month" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.num_months'); ?> *</label>
                                    <select name="duration_months" id="duration_months" class="form-input"
                                        onchange="calculateEndDate()">
                                        <option value="1">1 <?php _e('booking_form.month'); ?></option>
                                        <option value="2">2 <?php _e('booking_form.month'); ?></option>
                                        <option value="3">3 <?php _e('booking_form.month'); ?></option>
                                        <option value="4">4 <?php _e('booking_form.month'); ?></option>
                                        <option value="5">5 <?php _e('booking_form.month'); ?></option>
                                        <option value="6">6 <?php _e('booking_form.month'); ?></option>
                                        <option value="9">9 <?php _e('booking_form.month'); ?></option>
                                        <option value="12">12 <?php _e('booking_form.month'); ?> (1
                                            <?php _e('booking_form.year'); ?>)
                                        </option>
                                        <option value="24">24 <?php _e('booking_form.month'); ?> (2
                                            <?php _e('booking_form.year'); ?>)
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_form.est_checkout_date'); ?></label>
                                    <input type="text" id="calculated_end_date"
                                        class="form-input bg-gray-700 cursor-not-allowed" readonly
                                        placeholder="<?php _e('booking_form.auto_calc'); ?>">
                                </div>
                            </div>

                            <!-- BY DATE Options (hidden by default) -->
                            <div id="rent_by_date" class="hidden mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label"><?php _e('booking_form.num_days'); ?></label>
                                        <input type="number" id="duration_days" class="form-input" min="1" max="730"
                                            placeholder="VD: 45" onchange="calculateEndDateFromDays()">
                                        <p class="text-xs text-white/50 mt-1"><?php _e('booking_form.days_hint'); ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label
                                            class="form-label"><?php _e('booking_form.or_select_end_date'); ?></label>
                                        <input type="date" id="manual_end_date" class="form-input"
                                            onchange="calculateDaysFromEndDate()">
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden field for duration_type (for backend) -->
                            <input type="hidden" name="duration_type" id="duration_type" value="1_month">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <!-- Number of Adults -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('inquiry.num_adults'); ?> *</label>
                                    <input type="number" name="num_adults" id="inquiry_num_adults"
                                        class="form-input" min="1" max="10" value="1">
                                </div>

                                <!-- Number of Children -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('inquiry.num_children'); ?></label>
                                    <input type="number" name="num_children" id="inquiry_num_children"
                                        class="form-input" min="0" max="10" value="0">
                                </div>
                            </div>

                            <!-- Inquiry Summary Box -->
                            <div class="mt-6 p-4 bg-purple-500/10 border border-purple-500/20 rounded-lg"
                                id="inquiry_summary_box">
                                <div class="flex justify-between items-center">
                                    <span
                                        class="font-semibold text-purple-300"><?php _e('booking_form.apt_type'); ?>:</span>
                                    <span id="inquiry_apartment_name" class="text-white">--</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span
                                        class="font-semibold text-purple-300"><?php _e('booking_form.rent_duration'); ?>:</span>
                                    <span id="inquiry_duration_display" class="text-white">--</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span
                                        class="font-semibold text-purple-300"><?php _e('booking_form.est_checkout_date'); ?>:</span>
                                    <span id="inquiry_end_date_display" class="text-white">--</span>
                                </div>
                                <div
                                    class="flex justify-between items-center mt-2 pt-2 border-t border-purple-500/20">
                                    <span
                                        class="font-semibold text-purple-300"><?php _e('booking_form.reference_price'); ?>:</span>
                                    <span
                                        class="text-accent font-bold"><?php _e('booking_form.contact_for_quote'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <button type="button" class="btn-primary"
                                onclick="nextStep(2)"><?php _e('booking_page.continue'); ?></button>
                        </div>
                    </div>

                    <!-- Step 2: Guest Information -->
                    <div class="form-step" id="step2">
                        <h3 class="text-xl font-bold mb-4"><?php _e('booking_page.guest_info'); ?></h3>

                        <?php if (!$data['user_info']): ?>
                            <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <span class="material-symbols-outlined text-yellow-500 mt-0.5">warning</span>
                                    <div>
                                        <h4 class="font-semibold text-yellow-500 mb-1">
                                            <?php _e('booking_form.guest_booking'); ?>
                                        </h4>
                                        <p class="text-sm text-gray-300 mb-3">
                                            <?php _e('booking_form.guest_booking_note'); ?>
                                        </p>
                                        <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors shadow-lg">
                                            <span class="material-symbols-outlined text-sm">login</span>
                                            <?php _e('booking_form.login_for_offers'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div class="form-group">
                                <label class="form-label"><?php _e('booking_page.full_name'); ?> *</label>
                                <input type="text" name="guest_name" id="guest_name" class="form-input"
                                    value="<?php echo $data['user_info'] ? htmlspecialchars($data['user_info']['full_name']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Phone -->
                            <div class="form-group">
                                <label class="form-label"><?php _e('booking_page.phone'); ?> *</label>
                                <input type="tel" name="guest_phone" id="guest_phone" class="form-input"
                                    value="<?php echo $data['user_info'] ? htmlspecialchars($data['user_info']['phone'] ?? '') : ''; ?>"
                                    required>
                            </div>

                            <!-- Email -->
                            <div class="form-group md:col-span-2">
                                <label class="form-label"><?php _e('booking_page.email'); ?> *</label>
                                <input type="email" name="guest_email" id="guest_email" class="form-input"
                                    value="<?php echo $data['user_info'] ? htmlspecialchars($data['user_info']['email']) : ''; ?>"
                                    required>
                                <?php if ($data['user_info']): ?>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                        <span class="material-symbols-outlined text-sm">info</span>
                                        <?php _e('booking_page.info_from_account'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- APARTMENT INQUIRY MESSAGE (Hidden by default, shown for apartments) -->
                            <div class="form-group md:col-span-2 hidden" id="inquiry_fields">
                                <label class="form-label"><?php _e('inquiry.message'); ?></label>
                                <textarea name="message" id="inquiry_message" class="form-input" rows="4"
                                    placeholder="<?php _e('inquiry.message_placeholder'); ?>"></textarea>
                                <p class="text-xs text-white/50 mt-1">
                                    <?php _e('inquiry.message_desc'); ?>
                                </p>
                            </div>

                            <!-- Special Requests -->
                            <div class="form-group md:col-span-2">
                                <label class="form-label"><?php _e('booking_page.special_requests'); ?></label>
                                <textarea name="special_requests" id="special_requests" class="form-input" rows="3"
                                    placeholder="<?php _e('booking_page.special_requests_placeholder'); ?>"></textarea>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-4">
                            <button type="button" class="btn-secondary"
                                onclick="prevStep(1)"><?php _e('booking_page.back'); ?></button>
                            <button type="button" class="btn-primary flex-1"
                                onclick="nextStep(3)"><?php _e('booking_page.continue'); ?></button>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div class="form-step" id="step3">
                        <h3 class="text-xl font-bold mb-4"><?php _e('booking_page.confirm_payment'); ?></h3>

                        <!-- Booking Summary -->
                        <div class="p-6 bg-surface-light dark:bg-gray-700 rounded-lg mb-6">
                            <h4 class="font-bold text-lg mb-4"><?php _e('booking_page.booking_info'); ?></h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span><?php _e('booking_page.room_type'); ?>:</span>
                                    <span id="summary_room_type" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><?php _e('booking_page.num_guests'); ?>:</span>
                                    <span id="summary_guests" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span id="summary_checkin_label"><?php _e('booking_page.check_in'); ?>:</span>
                                    <span id="summary_checkin" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between" id="summary_checkout_row">
                                    <span id="summary_checkout_label"><?php _e('booking_page.check_out'); ?>:</span>
                                    <span id="summary_checkout" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between" id="summary_nights_row">
                                    <span id="summary_nights_label"><?php _e('booking_page.num_nights'); ?>:</span>
                                    <span id="summary_nights" class="font-semibold"></span>
                                </div>
                                <hr class="my-3 border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span><?php _e('booking_page.full_name'); ?>:</span>
                                    <span id="summary_name" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><?php _e('booking_page.email'); ?>:</span>
                                    <span id="summary_email" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><?php _e('booking_page.phone'); ?>:</span>
                                    <span id="summary_phone" class="font-semibold"></span>
                                </div>
                                <hr class="my-3 border-gray-300 dark:border-gray-600">

                                <!-- PAYMENT SPECIFIC SUMMARY -->
                                <div id="payment_summary_rows">
                                    <div class="flex justify-between">
                                        <span><?php _e('booking_form.room_charge'); ?>:</span>
                                        <span id="summary_subtotal" class="font-semibold"></span>
                                    </div>
                                    <div class="flex justify-between text-blue-500" id="summary_extra_guest_row"
                                        style="display: none;">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">person_add</span>
                                            <?php _e('booking_form.extra_guest_charge'); ?>:
                                        </span>
                                        <span id="summary_extra_guest_fee" class="font-semibold"></span>
                                    </div>
                                    <div class="flex justify-between text-orange-500" id="summary_extra_bed_row"
                                        style="display: none;">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">single_bed</span>
                                            <?php _e('booking_form.extra_bed_charge'); ?>:
                                        </span>
                                        <span id="summary_extra_bed_fee" class="font-semibold"></span>
                                    </div>
                                    <div class="flex justify-between text-green-600" id="discount_row"
                                        style="display: none;">
                                        <span><?php _e('booking_page.discount'); ?>:</span>
                                        <span id="summary_discount" class="font-semibold"></span>
                                    </div>
                                    <hr class="my-3 border-gray-300 dark:border-gray-600">
                                    <div class="flex justify-between text-lg font-bold text-accent">
                                        <span><?php _e('booking_page.total_payment'); ?>:</span>
                                        <span id="summary_total"></span>
                                    </div>
                                    <div class="mt-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                                        <p
                                            class="text-xs text-amber-600 dark:text-amber-400 flex items-start gap-1">
                                            <span class="material-symbols-outlined text-sm mt-0.5">info</span>
                                            <span><?php _e('booking_form.note_price_estimate'); ?></span>
                                        </p>
                                        <p class="text-xs text-red-500 dark:text-red-400 mt-2 font-medium italic">
                                            * <?php _e('booking_page.price_estimate_note_final'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOOKING PAYMENT SECTION (Hidden if Inquiry) -->
                        <div id="booking_payment_section">
                            <!-- Promotion Code -->
                            <div class="p-6 bg-surface-light dark:bg-gray-700 rounded-lg mb-6">
                                <h4 class="font-bold text-lg mb-4"><?php _e('booking_page.promo_code'); ?></h4>
                                <div class="flex gap-3">
                                    <input type="text" id="promo_code" class="form-input flex-1"
                                        placeholder="<?php _e('booking_page.enter_promo_code'); ?>"
                                        style="text-transform: uppercase;">
                                    <button type="button" onclick="applyPromoCode()"
                                        class="btn-primary whitespace-nowrap">
                                        <?php _e('booking_page.apply'); ?>
                                    </button>
                                </div>
                                <div id="promo_message" class="mt-3 text-sm"></div>
                                <input type="hidden" name="promotion_code" id="promotion_code_input">
                                <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                            </div>

                            <!-- Payment Method - Only Manual Payment Supported -->
                            <div class="form-group mb-6">
                                <label class="form-label"><?php _e('booking_page.payment_method'); ?> *</label>
                                <div class="space-y-3">
                                    <label class="payment-option border-accent bg-accent/5">
                                        <input type="radio" name="payment_method" value="cash" checked class="hidden">
                                        <div class="payment-option-content flex items-center gap-3 p-4 rounded-xl border-2 border-accent/20">
                                            <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center text-accent">
                                                <span class="material-symbols-outlined text-2xl">payments</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-white"><?php _e('booking_page.pay_at_hotel'); ?></p>
                                                <p class="text-xs text-white/50"><?php _e('booking_page.pay_at_hotel_desc'); ?></p>
                                            </div>
                                            <div class="ml-auto">
                                                <span class="material-symbols-outlined text-accent">check_circle</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- INQUIRY CONFIRM SECTION (Shown if Inquiry) -->
                        <div id="inquiry_confirm_section" class="hidden mb-6">
                            <div class="p-4 bg-accent/10 border border-accent/20 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="material-symbols-outlined text-accent text-xl mt-1">contact_support</span>
                                    <div>
                                        <h4 class="font-bold text-accent mb-1"><?php _e('inquiry.title'); ?></h4>
                                        <p class="text-sm text-text-secondary dark:text-gray-300">
                                            <?php _e('inquiry.success_desc'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="form-group">
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="agree_terms" id="agree_terms" class="mt-1" required>
                                <span class="text-sm"><?php _e('booking_page.agree_terms'); ?> <a href="#"
                                        class="text-accent hover:underline"><?php _e('booking_page.terms_conditions'); ?></a>
                                    <?php _e('booking_page.of_aurora'); ?></span>
                            </label>
                        </div>

                        <div class="flex gap-4 mt-4">
                            <button type="button" class="btn-secondary"
                                onclick="prevStep(2)"><?php _e('booking_page.back'); ?></button>
                            <button type="submit" class="btn-primary flex-1" id="submitBtn">
                                <span class="material-symbols-outlined" id="submitBtnIcon">lock</span>
                                <span id="submitBtnText"><?php _e('booking_page.confirm_booking'); ?></span>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

</div>
