<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

// Get user information if logged in
$user_info = null;
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_info = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error fetching user info: " . $e->getMessage());
    }
}

// Get room types for selection with room availability count
$db = getDB();
$stmt = $db->prepare("
    SELECT 
        rt.*,
        COALESCE(total.total_rooms, 0) as total_rooms,
        COALESCE(available.available_rooms, 0) as available_rooms
    FROM room_types rt
    LEFT JOIN (
        SELECT room_type_id, COUNT(*) as total_rooms 
        FROM rooms 
        GROUP BY room_type_id
    ) total ON rt.room_type_id = total.room_type_id
    LEFT JOIN (
        SELECT room_type_id, COUNT(*) as available_rooms 
        FROM rooms 
        WHERE status = 'available' 
        GROUP BY room_type_id
    ) available ON rt.room_type_id = available.room_type_id
    WHERE rt.status = 'active' 
    ORDER BY rt.sort_order, rt.base_price
");
$stmt->execute();
$room_types = $stmt->fetchAll();

// Get pre-selected room type from URL (by slug or id)
$selected_room_type_id = null;
$selected_room_slug = null;
if (isset($_GET['room_type'])) {
    $room_type_param = trim($_GET['room_type']);
    // Check if it's a numeric ID or slug
    if (is_numeric($room_type_param)) {
        $selected_room_type_id = (int)$room_type_param;
    } else {
        // Find by slug
        $selected_room_slug = $room_type_param;
        foreach ($room_types as $room) {
            if ($room['slug'] === $room_type_param) {
                $selected_room_type_id = $room['room_type_id'];
                break;
            }
        }
    }
}

// Get pre-filled dates and guests from URL
$prefilled_check_in = isset($_GET['check_in']) ? htmlspecialchars($_GET['check_in']) : '';
$prefilled_check_out = isset($_GET['check_out']) ? htmlspecialchars($_GET['check_out']) : '';
$prefilled_guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 2;

// Debug: Log room types
error_log("Room types loaded: " . count($room_types));
foreach ($room_types as $room) {
    error_log("Room: " . $room['type_name'] . " - Price: " . $room['base_price'] . " - Available: " . $room['available_rooms'] . "/" . $room['total_rooms']);
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('booking_page.title'); ?></title>

<!-- Tailwind CSS -->
<script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>

<!-- Google Fonts -->
<link href="../assets/css/fonts.css" rel="stylesheet"/>

<!-- Tailwind Configuration -->
<script src="../assets/js/tailwind-config.js"></script>

<!-- Custom CSS -->
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/liquid-glass.css">
<link rel="stylesheet" href="./assets/css/booking.css">

</head>
<body class="booking-page">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

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

                <!-- Step 1: Room Selection -->
                <div class="form-step active" id="step1">
                    <h3 class="text-xl font-bold mb-4"><?php _e('booking_page.select_room_date'); ?></h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Room Type -->
                        <div class="form-group">
                            <label class="form-label"><?php _e('booking_page.room_type'); ?> *</label>
                            <select name="room_type_id" id="room_type_id" class="form-input" required
                                        data-preselected="<?php echo $selected_room_type_id ?? 'null'; ?>"
                                        data-slug="<?php echo $selected_room_slug ?? 'null'; ?>">
                                <option value="">-- <?php _e('booking_page.select_room_type'); ?> --</option>
                                <?php foreach($room_types as $room): 
                                    $is_available = $room['available_rooms'] > 0;
                                    // Apartments are always "available" for inquiry
                                    $is_inquiry = isset($room['booking_type']) && $room['booking_type'] === 'inquiry';
                                    if ($is_inquiry) $is_available = true;
                                    
                                    $availability_text = $is_available 
                                        ? ($is_inquiry ? "" : "({$room['available_rooms']} " . __('booking_page.rooms_available') . ")")
                                        : "(" . __('booking_page.out_of_stock') . ")";
                                ?>
                                <option value="<?php echo $room['room_type_id']; ?>" 
                                        data-price="<?php echo $room['base_price']; ?>"
                                        data-max-guests="<?php echo $room['max_occupancy']; ?>"
                                        data-available="<?php echo $room['available_rooms']; ?>"
                                        data-category="<?php echo $room['category']; ?>"
                                        data-booking-type="<?php echo $room['booking_type'] ?? 'instant'; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>
                                        <?php echo ($selected_room_type_id !== null && (int)$selected_room_type_id === (int)$room['room_type_id'] && $is_available) ? 'selected' : ''; ?>>
                                    <?php echo $room['type_name']; ?> - <?php echo $is_inquiry ? __('inquiry.contact_btn') : number_format($room['base_price']) . ' VNĐ/đêm ' . $availability_text; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Number of Guests -->
                        <div class="form-group">
                            <label class="form-label"><?php _e('booking_page.num_guests'); ?> *</label>
                            <input type="number" name="num_guests" id="num_guests" class="form-input" min="1" max="10" value="<?php echo $prefilled_guests; ?>" required>
                        </div>

                        <!-- Check-in Date -->
                        <div class="form-group">
                            <label class="form-label"><?php _e('booking_page.check_in_date'); ?> *</label>
                            <input type="date" name="check_in_date" id="check_in_date" class="form-input" value="<?php echo $prefilled_check_in; ?>" required>
                        </div>

                        <!-- Check-out Date -->
                        <div class="form-group">
                            <label class="form-label"><?php _e('booking_page.check_out_date'); ?> *</label>
                            <input type="date" name="check_out_date" id="check_out_date" class="form-input" value="<?php echo $prefilled_check_out; ?>" required>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="mt-6 p-4 bg-primary-light/20 dark:bg-gray-700 rounded-lg transition-all duration-300" id="price_summary_box">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold"><?php _e('booking_page.price_per_night'); ?>:</span>
                            <span id="room_price_display">0 VNĐ</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="font-semibold"><?php _e('booking_page.num_nights'); ?>:</span>
                            <span id="num_nights">0</span>
                        </div>
                        <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-300 dark:border-gray-600">
                            <span class="font-semibold"><?php _e('booking_page.estimated_total'); ?>:</span>
                            <span id="estimated_total_display" class="text-xl font-bold text-accent">0 VNĐ</span>
                            <input type="hidden" id="estimated_total" value="0">
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="button" class="btn-primary" onclick="nextStep(2)"><?php _e('booking_page.continue'); ?></button>
                    </div>
                </div>

                <!-- Step 2: Guest Information -->
                <div class="form-step" id="step2">
                    <h3 class="text-xl font-bold mb-4"><?php _e('booking_page.guest_info'); ?></h3>
                    
                    <?php if (!$user_info): ?>
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 mt-0.5">info</span>
                            <div>
                                <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-1"><?php _e('booking_page.login_for_better'); ?></h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                    <?php _e('booking_page.login_benefit'); ?>
                                </p>
                                <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-sm">login</span>
                                    <?php _e('booking_page.login_now'); ?>
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
                                   value="<?php echo $user_info ? htmlspecialchars($user_info['full_name']) : ''; ?>" required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label class="form-label"><?php _e('booking_page.phone'); ?> *</label>
                            <input type="tel" name="guest_phone" id="guest_phone" class="form-input" 
                                   value="<?php echo $user_info ? htmlspecialchars($user_info['phone'] ?? '') : ''; ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group md:col-span-2">
                            <label class="form-label"><?php _e('booking_page.email'); ?> *</label>
                            <input type="email" name="guest_email" id="guest_email" class="form-input" 
                                   value="<?php echo $user_info ? htmlspecialchars($user_info['email']) : ''; ?>" required>
                            <?php if ($user_info): ?>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                <span class="material-symbols-outlined text-sm">info</span>
                                <?php _e('booking_page.info_from_account'); ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- INQUIRY FIELDS (Hidden by default) -->
                        <div class="form-group md:col-span-2 hidden" id="inquiry_fields">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Duration Type -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('inquiry.duration_type'); ?></label>
                                    <select name="duration_type" id="duration_type" class="form-input">
                                        <option value="short_term"><?php _e('inquiry.short_term'); ?></option>
                                        <option value="long_term"><?php _e('inquiry.long_term'); ?></option>
                                        <option value="monthly"><?php _e('inquiry.monthly'); ?></option>
                                        <option value="yearly"><?php _e('inquiry.yearly'); ?></option>
                                    </select>
                                </div>
                                <!-- Message -->
                                <div class="form-group md:col-span-2">
                                    <label class="form-label"><?php _e('inquiry.message'); ?></label>
                                    <textarea name="message" id="inquiry_message" class="form-input" rows="3" 
                                              placeholder="<?php _e('inquiry.message_placeholder'); ?>"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- INQUIRY FIELDS (Hidden by default) -->
                        <div class="form-group md:col-span-2 hidden" id="inquiry_fields">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Duration Type -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('inquiry.duration_type'); ?></label>
                                    <select name="duration_type" id="duration_type" class="form-input">
                                        <option value="short_term"><?php _e('inquiry.short_term'); ?></option>
                                        <option value="long_term"><?php _e('inquiry.long_term'); ?></option>
                                        <option value="monthly"><?php _e('inquiry.monthly'); ?></option>
                                        <option value="yearly"><?php _e('inquiry.yearly'); ?></option>
                                    </select>
                                </div>
                                <!-- Message -->
                                <div class="form-group md:col-span-2">
                                    <label class="form-label"><?php _e('inquiry.message'); ?></label>
                                    <textarea name="message" id="inquiry_message" class="form-input" rows="3" 
                                              placeholder="<?php _e('inquiry.message_placeholder'); ?>"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests -->
                        <div class="form-group md:col-span-2">
                            <label class="form-label"><?php _e('booking_page.special_requests'); ?></label>
                            <textarea name="special_requests" id="special_requests" class="form-input" rows="3" placeholder="<?php _e('booking_page.special_requests_placeholder'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-4">
                        <button type="button" class="btn-secondary" onclick="prevStep(1)"><?php _e('booking_page.back'); ?></button>
                        <button type="button" class="btn-primary flex-1" onclick="nextStep(3)"><?php _e('booking_page.continue'); ?></button>
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
                                <span><?php _e('booking_page.check_in'); ?>:</span>
                                <span id="summary_checkin" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?php _e('booking_page.check_out'); ?>:</span>
                                <span id="summary_checkout" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?php _e('booking_page.num_nights'); ?>:</span>
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
                                    <span><?php _e('booking_page.subtotal'); ?>:</span>
                                    <span id="summary_subtotal" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between text-green-600" id="discount_row" style="display: none;">
                                    <span><?php _e('booking_page.discount'); ?>:</span>
                                    <span id="summary_discount" class="font-semibold"></span>
                                </div>
                                <hr class="my-3 border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between text-lg font-bold text-accent">
                                    <span><?php _e('booking_page.total_payment'); ?>:</span>
                                    <span id="summary_total"></span>
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
                                       placeholder="<?php _e('booking_page.enter_promo_code'); ?>" style="text-transform: uppercase;">
                                <button type="button" onclick="applyPromoCode()" class="btn-primary whitespace-nowrap">
                                    <?php _e('booking_page.apply'); ?>
                                </button>
                            </div>
                            <div id="promo_message" class="mt-3 text-sm"></div>
                            <input type="hidden" name="promotion_code" id="promotion_code_input">
                            <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                        </div>
    
                        <!-- Payment Method -->
                        <div class="form-group mb-6">
                            <label class="form-label"><?php _e('booking_page.payment_method'); ?> *</label>
                            <div class="space-y-3">
                                <label class="payment-option opacity-60 cursor-not-allowed" onclick="alert('Tính năng thanh toán Online đang được phát triển. Vui lòng chọn Thanh toán tại khách sạn.'); return false;">
                                    <input type="radio" name="payment_method" value="vnpay" disabled>
                                    <div class="payment-option-content">
                                        <img src="./assets/img/vnpay-logo.png" alt="VNPay" class="h-8 grayscale">
                                        <span><?php _e('booking_page.pay_vnpay'); ?> (Đang phát triển)</span>
                                    </div>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="cash" checked>
                                    <div class="payment-option-content">
                                        <span class="material-symbols-outlined text-2xl">payments</span>
                                        <span><?php _e('booking_page.pay_at_hotel'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- INQUIRY CONFIRM SECTION (Shown if Inquiry) -->
                    <div id="inquiry_confirm_section" class="hidden mb-6">
                        <div class="p-4 bg-accent/10 border border-accent/20 rounded-lg">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-accent text-xl mt-1">contact_support</span>
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
                            <span class="text-sm"><?php _e('booking_page.agree_terms'); ?> <a href="#" class="text-accent hover:underline"><?php _e('booking_page.terms_conditions'); ?></a> <?php _e('booking_page.of_aurora'); ?></span>
                        </label>
                    </div>

                    <div class="flex gap-4 mt-4">
                        <button type="button" class="btn-secondary" onclick="prevStep(2)"><?php _e('booking_page.back'); ?></button>
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

<!-- Main JavaScript -->
<script src="../assets/js/main.js"></script>
<script src="./assets/js/booking.js"></script>

</body>
</html>
