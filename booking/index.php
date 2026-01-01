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

// Get room types for selection with room availability count and extended pricing
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
        $selected_room_type_id = (int) $room_type_param;
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

// Get pre-selected room details from map
$selected_room_id = isset($_GET['selected_room_id']) ? (int) $_GET['selected_room_id'] : null;
$selected_room_number = isset($_GET['selected_room_number']) ? htmlspecialchars($_GET['selected_room_number']) : null;

// Get pre-filled dates and guests from URL
$prefilled_check_in = isset($_GET['check_in']) ? htmlspecialchars($_GET['check_in']) : '';
$prefilled_check_out = isset($_GET['check_out']) ? htmlspecialchars($_GET['check_out']) : '';
$prefilled_guests = isset($_GET['guests']) ? (int) $_GET['guests'] : 2;

// Debug: Log room types
error_log("Room types loaded: " . count($room_types));
foreach ($room_types as $room) {
    error_log("Room: " . $room['type_name'] . " - Price: " . $room['base_price'] . " - Available: " . $room['available_rooms'] . "/" . $room['total_rooms']);
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('booking_page.title'); ?></title>

    <!-- Tailwind CSS -->
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="../assets/css/fonts.css" rel="stylesheet" />

    <!-- Tailwind Configuration -->
    <script src="../assets/js/tailwind-config.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="./assets/css/booking.css">

</head>

<body class="booking-page">
    <script>
        const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
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

                        <!-- Step 1: Room/Apartment Selection -->
                        <div class="form-step active" id="step1">
                            <h3 class="text-xl font-bold mb-4" id="step1_title">
                                <?php _e('booking_page.select_room_date'); ?>
                            </h3>

                            <!-- Room Type Selection (Common) -->
                            <div class="form-group mb-6">
                                <label class="form-label"><?php _e('booking_page.room_type'); ?> *</label>
                                <select name="room_type_id" id="room_type_id" class="form-input" required
                                    data-preselected="<?php echo $selected_room_type_id ?? 'null'; ?>"
                                    data-slug="<?php echo $selected_room_slug ?? 'null'; ?>">
                                    <option value="">-- <?php _e('booking_page.select_room_type'); ?> --</option>
                                    <?php foreach ($room_types as $room):
                                        $is_available = $room['available_rooms'] > 0;
                                        // Apartments are always "available" for inquiry
                                        $is_inquiry = isset($room['booking_type']) && $room['booking_type'] === 'inquiry';
                                        if ($is_inquiry)
                                            $is_available = true;

                                        $availability_text = $is_available
                                            ? ($is_inquiry ? "" : "({$room['available_rooms']} " . __('booking_page.rooms_available') . ")")
                                            : "(" . __('booking_page.out_of_stock') . ")";

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
                                            data-booking-type="<?php echo $room['booking_type'] ?? 'instant'; ?>" <?php echo !$is_available ? 'disabled' : ''; ?>     <?php echo ($selected_room_type_id !== null && (int) $selected_room_type_id === (int) $room['room_type_id'] && $is_available) ? 'selected' : ''; ?>>
                                            <?php echo $room['type_name']; ?> -
                                            <?php echo $is_inquiry ? __('inquiry.contact_btn') : number_format($display_price) . ' VNĐ/đêm ' . $availability_text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- ========== ROOM BOOKING FIELDS (instant) ========== -->
                            <div id="room_booking_fields">
                                <!-- Booking Type Selection -->
                                <div class="form-group mb-4">
                                    <label class="form-label">Loại hình đặt phòng *</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="booking-type-option selected" data-type="standard">
                                            <input type="radio" name="booking_type" value="standard" checked
                                                class="hidden">
                                            <div
                                                class="flex items-center gap-2 p-3 rounded-lg border-2 border-amber-500 bg-amber-500/10 cursor-pointer transition-all">
                                                <span class="material-symbols-outlined text-amber-500">hotel</span>
                                                <div>
                                                    <div class="font-semibold text-sm">Nghỉ qua đêm</div>
                                                    <div class="text-xs text-gray-400">Check-in 14:00 - Check-out 12:00
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
                                                    <div class="font-semibold text-sm">Nghỉ ngắn hạn</div>
                                                    <div class="text-xs text-gray-400">Dưới 4h, checkout trước 22h</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <p id="short_stay_note" class="text-xs text-blue-400 mt-2 hidden">
                                        <span class="material-symbols-outlined text-sm align-middle">info</span>
                                        Nghỉ ngắn hạn không bao gồm ăn sáng
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Number of Adults -->
                                    <div class="form-group">
                                        <label class="form-label">Số người lớn *</label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="adjustValue('num_adults', -1)"
                                                class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined">remove</span>
                                            </button>
                                            <input type="number" name="num_adults" id="num_adults"
                                                class="form-input text-center w-20" min="1" max="10" value="2" required
                                                readonly>
                                            <button type="button" onclick="adjustValue('num_adults', 1)"
                                                class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined">add</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Number of Children -->
                                    <div class="form-group">
                                        <label class="form-label">Số trẻ em (dưới 12 tuổi)</label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="adjustValue('num_children', -1)"
                                                class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined">remove</span>
                                            </button>
                                            <input type="number" name="num_children" id="num_children"
                                                class="form-input text-center w-20" min="0" max="5" value="0" readonly>
                                            <button type="button" onclick="adjustValue('num_children', 1)"
                                                class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined">add</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Check-in Date -->
                                    <div class="form-group">
                                        <label class="form-label"><?php _e('booking_page.check_in_date'); ?> *</label>
                                        <input type="date" name="check_in_date" id="check_in_date" class="form-input"
                                            value="<?php echo $prefilled_check_in; ?>" required>
                                    </div>

                                    <!-- Check-out Date -->
                                    <div class="form-group" id="checkout_group">
                                        <label class="form-label"><?php _e('booking_page.check_out_date'); ?> *</label>
                                        <input type="date" name="check_out_date" id="check_out_date" class="form-input"
                                            value="<?php echo $prefilled_check_out; ?>" required>
                                    </div>
                                </div>

                                <!-- Smart Suggestion Box -->
                                <div id="smart_suggestion_box" class="mt-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl hidden">
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-amber-400 mt-0.5">lightbulb</span>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-amber-400 mb-2">Gợi ý phụ thu</h4>
                                            <p id="suggestion_message" class="text-sm text-white/80 mb-3"></p>
                                            <div id="suggestion_actions" class="flex flex-wrap gap-2">
                                                <!-- Dynamic buttons will be added here -->
                                            </div>
                                        </div>
                                        <button type="button" onclick="dismissSuggestion()" class="text-white/50 hover:text-white/80">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Extra Guests Section -->
                                <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl"
                                    id="extra_guests_section">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-semibold flex items-center gap-2">
                                            <span class="material-symbols-outlined text-blue-400">person_add</span>
                                            Khách thêm (phụ thu)
                                        </h4>
                                        <button type="button" onclick="toggleExtraGuests()" id="toggle_extra_guests_btn"
                                            class="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">add_circle</span>
                                            Thêm khách
                                        </button>
                                    </div>

                                    <!-- Extra Guests Info -->
                                    <div class="text-xs text-gray-400 mb-3 grid grid-cols-3 gap-2">
                                        <div class="flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                            Dưới 1m: <span class="text-green-400">Miễn phí</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                            1m - dưới 1m3: <span class="text-yellow-400">200.000đ/đêm</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                            Từ 1m3 trở lên: <span class="text-orange-400">400.000đ/đêm</span>
                                        </div>
                                    </div>

                                    <!-- Extra Guests List -->
                                    <div id="extra_guests_list" class="hidden space-y-3">
                                        <!-- Dynamic entries will be added here -->
                                    </div>

                                    <p class="text-xs text-gray-500 mt-2">
                                        * Phí khách thêm tính theo đêm, bao gồm ăn sáng buffet
                                    </p>
                                </div>

                                <!-- Extra Bed Section (Rooms Only) -->
                                <div class="mt-4 p-4 bg-orange-500/10 border border-orange-500/20 rounded-xl"
                                    id="extra_bed_section">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-orange-400">single_bed</span>
                                            <div>
                                                <h4 class="font-semibold">Giường phụ</h4>
                                                <p class="text-xs text-gray-400">650.000đ/đêm</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="adjustValue('extra_beds', -1)"
                                                class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined text-sm">remove</span>
                                            </button>
                                            <input type="number" name="extra_beds" id="extra_beds"
                                                class="form-input text-center w-16 text-sm" min="0" max="2" value="0"
                                                readonly>
                                            <button type="button" onclick="adjustValue('extra_beds', 1)"
                                                class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                                                <span class="material-symbols-outlined text-sm">add</span>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-yellow-500 mt-2 hidden" id="extra_bed_warning">
                                        <span class="material-symbols-outlined text-sm align-middle">warning</span>
                                        Giường phụ không áp dụng cho căn hộ
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
                                                <span id="price_type_label">Giá 2 người</span>
                                            </span>
                                        </div>
                                        <span id="original_price_display"
                                            class="text-sm text-gray-500 line-through hidden">0 VNĐ</span>
                                    </div>

                                    <!-- Price Breakdown -->
                                    <div class="space-y-2 text-sm">
                                        <!-- Room Rate -->
                                        <div class="flex justify-between items-center">
                                            <span
                                                class="text-gray-400"><?php _e('booking_page.price_per_night'); ?>:</span>
                                            <span id="room_price_display" class="font-bold" style="color: #d4af37;">0
                                                VNĐ</span>
                                        </div>

                                        <!-- Number of nights -->
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-400"><?php _e('booking_page.num_nights'); ?>:</span>
                                            <span id="num_nights">0 đêm</span>
                                        </div>

                                        <!-- Room Subtotal -->
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-400">Tiền phòng:</span>
                                            <span id="room_subtotal_display">0 VNĐ</span>
                                        </div>

                                        <!-- Extra Guest Fee -->
                                        <div class="flex justify-between items-center hidden" id="extra_guest_fee_row">
                                            <span class="text-gray-400">Phụ thu khách thêm:</span>
                                            <span id="extra_guest_fee_display" class="text-blue-400">0 VNĐ</span>
                                        </div>

                                        <!-- Extra Bed Fee -->
                                        <div class="flex justify-between items-center hidden" id="extra_bed_fee_row">
                                            <span class="text-gray-400">Phí giường phụ:</span>
                                            <span id="extra_bed_fee_display" class="text-orange-400">0 VNĐ</span>
                                        </div>
                                    </div>

                                    <!-- Total -->
                                    <div
                                        class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300/50 dark:border-gray-600">
                                        <span class="font-semibold"><?php _e('booking_page.estimated_total'); ?>:</span>
                                        <span id="estimated_total_display" class="text-2xl font-bold text-accent">0
                                            VNĐ</span>
                                        <input type="hidden" id="estimated_total" name="estimated_total" value="0">
                                        <input type="hidden" id="price_type_used" name="price_type_used" value="double">
                                        <input type="hidden" id="extra_guest_fee" name="extra_guest_fee" value="0">
                                        <input type="hidden" id="extra_bed_fee" name="extra_bed_fee" value="0">
                                        <input type="hidden" id="num_guests" name="num_guests" value="2">
                                        <input type="hidden" id="extra_guests_data" name="extra_guests_data" value="[]">
                                    </div>

                                    <!-- Tax Info Note -->
                                    <div class="mt-3 pt-3 border-t border-gray-300/30 dark:border-gray-600">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <span
                                                class="material-symbols-outlined text-sm text-green-500">check_circle</span>
                                            Đã bao gồm 5% phí dịch vụ và 8% VAT
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
                                        <label class="form-label">Ngày dự kiến nhận phòng *</label>
                                        <input type="date" name="preferred_check_in" id="preferred_check_in"
                                            class="form-input">
                                    </div>

                                    <!-- Rent Mode Selection -->
                                    <div class="form-group">
                                        <label class="form-label">Hình thức thuê *</label>
                                        <select id="rent_mode" class="form-input" onchange="toggleRentMode()">
                                            <option value="by_month">Theo tháng</option>
                                            <option value="by_date">Theo ngày / Chọn ngày kết thúc</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- BY MONTH Options -->
                                <div id="rent_by_month" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div class="form-group">
                                        <label class="form-label">Số tháng thuê *</label>
                                        <select name="duration_months" id="duration_months" class="form-input"
                                            onchange="calculateEndDate()">
                                            <option value="1">1 tháng</option>
                                            <option value="2">2 tháng</option>
                                            <option value="3">3 tháng</option>
                                            <option value="4">4 tháng</option>
                                            <option value="5">5 tháng</option>
                                            <option value="6">6 tháng</option>
                                            <option value="9">9 tháng</option>
                                            <option value="12">12 tháng (1 năm)</option>
                                            <option value="24">24 tháng (2 năm)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ngày dự kiến trả phòng</label>
                                        <input type="text" id="calculated_end_date"
                                            class="form-input bg-gray-700 cursor-not-allowed" readonly
                                            placeholder="Tự động tính">
                                    </div>
                                </div>

                                <!-- BY DATE Options (hidden by default) -->
                                <div id="rent_by_date" class="hidden mt-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Số ngày thuê</label>
                                            <input type="number" id="duration_days" class="form-input" min="1" max="730"
                                                placeholder="VD: 45" onchange="calculateEndDateFromDays()">
                                            <p class="text-xs text-white/50 mt-1">Nhập số ngày hoặc chọn ngày kết thúc
                                                bên cạnh</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Hoặc chọn ngày kết thúc</label>
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
                                        <span class="font-semibold text-purple-300">Loại căn hộ:</span>
                                        <span id="inquiry_apartment_name" class="text-white">--</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-semibold text-purple-300">Thời gian thuê:</span>
                                        <span id="inquiry_duration_display" class="text-white">--</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-semibold text-purple-300">Dự kiến trả phòng:</span>
                                        <span id="inquiry_end_date_display" class="text-white">--</span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center mt-2 pt-2 border-t border-purple-500/20">
                                        <span class="font-semibold text-purple-300">Giá tham khảo:</span>
                                        <span class="text-accent font-bold">Liên hệ báo giá</span>
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

                            <?php if (!$user_info): ?>
                                <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-yellow-500 mt-0.5">warning</span>
                                        <div>
                                            <h4 class="font-semibold text-yellow-500 mb-1">
                                                Đặt phòng với tư cách Khách vãng lai
                                            </h4>
                                            <p class="text-sm text-gray-300 mb-3">
                                                Bạn đang đặt phòng mà không đăng nhập. Bạn sẽ <strong>không thể sử dụng Mã
                                                    giảm giá</strong> và <strong>không được tích điểm</strong>.
                                            </p>
                                            <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors shadow-lg">
                                                <span class="material-symbols-outlined text-sm">login</span>
                                                Đăng nhập để nhận ưu đãi
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
                                        value="<?php echo $user_info ? htmlspecialchars($user_info['full_name']) : ''; ?>"
                                        required>
                                </div>

                                <!-- Phone -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_page.phone'); ?> *</label>
                                    <input type="tel" name="guest_phone" id="guest_phone" class="form-input"
                                        value="<?php echo $user_info ? htmlspecialchars($user_info['phone'] ?? '') : ''; ?>"
                                        required>
                                </div>

                                <!-- Email -->
                                <div class="form-group md:col-span-2">
                                    <label class="form-label"><?php _e('booking_page.email'); ?> *</label>
                                    <input type="email" name="guest_email" id="guest_email" class="form-input"
                                        value="<?php echo $user_info ? htmlspecialchars($user_info['email']) : ''; ?>"
                                        required>
                                    <?php if ($user_info): ?>
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
                                        Mô tả nhu cầu cụ thể của bạn (VD: thời gian muốn xem phòng, yêu cầu đặc biệt...)
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
                                            <span>Tiền phòng:</span>
                                            <span id="summary_subtotal" class="font-semibold"></span>
                                        </div>
                                        <div class="flex justify-between text-blue-500" id="summary_extra_guest_row"
                                            style="display: none;">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">person_add</span>
                                                Phụ thu khách thêm:
                                            </span>
                                            <span id="summary_extra_guest_fee" class="font-semibold"></span>
                                        </div>
                                        <div class="flex justify-between text-orange-500" id="summary_extra_bed_row"
                                            style="display: none;">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">single_bed</span>
                                                Phí giường phụ:
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

                                <!-- Payment Method -->
                                <div class="form-group mb-6">
                                    <label class="form-label"><?php _e('booking_page.payment_method'); ?> *</label>
                                    <div class="space-y-3">
                                        <label class="payment-option opacity-60 cursor-not-allowed"
                                            onclick="alert('Tính năng thanh toán Online đang được phát triển. Vui lòng chọn Thanh toán tại khách sạn.'); return false;">
                                            <input type="radio" name="payment_method" value="vnpay" disabled>
                                            <div class="payment-option-content">
                                                <img src="./assets/img/vnpay-logo.png" alt="VNPay"
                                                    class="h-8 grayscale">
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

    <!-- Main JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script src="./assets/js/booking.js"></script>

</body>

</html>