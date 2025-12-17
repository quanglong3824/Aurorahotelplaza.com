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

// Get room types for selection
$db = getDB();
$stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' ORDER BY sort_order, base_price");
$stmt->execute();
$room_types = $stmt->fetchAll();

// Require room helper for availability check
require_once '../helpers/room-helper.php';

// Get pre-filled dates and numbers
$prefilled_checkin = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$prefilled_checkout = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$prefilled_adults = isset($_GET['adults']) ? (int) $_GET['adults'] : 1;
$prefilled_children = isset($_GET['children']) ? (int) $_GET['children'] : 0;

// Get pre-filled number of rooms
// If explicit num_rooms is set, use it.
// Else, if adults > 2, auto-calculate: 1 room per 2 adults (approx).
if (isset($_GET['num_rooms'])) {
    $prefilled_num_rooms = (int) $_GET['num_rooms'];
} else {
    $prefilled_num_rooms = ($prefilled_adults > 2) ? ceil($prefilled_adults / 2) : 1;
}

if ($prefilled_num_rooms < 1)
    $prefilled_num_rooms = 1;

// Check availability if dates are provided
$available_room_types = [];
if (!empty($prefilled_checkin) && !empty($prefilled_checkout)) {
    foreach ($room_types as &$room) {
        $available_count = checkRoomAvailability($room['room_type_id'], $prefilled_checkin, $prefilled_checkout);
        $room['available_count'] = $available_count;
        if ($available_count > 0) {
            $available_room_types[] = $room['room_type_id'];
        }
    }
    unset($room); // Break reference
} else {
    // If no dates, assume all are available (at least for display purposes)
    foreach ($room_types as &$room) {
        $room['available_count'] = 999;
    }
}

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
} else {
    // Logic: If more than two room types are vacant, suggest all available vacant room types.
    // We achieve this by *not* pre-selecting a single one if many are available, 
    // letting the user see the dropdown with available options.
    // However, if we need to default to something, we can default to the first available one.
    if (!empty($available_room_types) && count($available_room_types) > 0) {
        // $selected_room_type_id = $available_room_types[0]; // Optional: auto-select first available
    }
}



// Debug: Log room types
error_log("Room types loaded: " . count($room_types));
foreach ($room_types as $room) {
    error_log("Room: " . $room['type_name'] . " - Price: " . $room['base_price']);
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

    <!-- Inline CSS fallback for hero section -->
    <style>
        .booking-hero {
            position: relative;
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.7)),
                url('../assets/img/hero-banner/aurora-hotel-bien-hoa-6.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 120px 20px 80px;
        }

        .booking-hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to top, #f8fafc, transparent);
            pointer-events: none;
        }

        .dark .booking-hero::before {
            background: linear-gradient(to top, #0f172a, transparent);
        }

        .hero-glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 2rem;
            padding: 3rem;
            max-width: 800px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 3rem;
            padding: 1.5rem 0;
            margin-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #d4af37;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Hero Section -->
            <section class="booking-hero">
                <div class="hero-glass-card">
                    <div class="glass-badge mb-4 inline-flex">
                        <span class="material-symbols-outlined text-accent text-sm">calendar_month</span>
                        <?php _e('booking_page.online_booking'); ?>
                    </div>
                    <h1 class="font-display text-4xl md:text-5xl font-bold text-white mb-4">
                        <?php _e('booking_page.page_title'); ?>
                    </h1>
                    <p class="text-white/80 text-lg max-w-xl mx-auto mb-8">
                        <?php _e('booking_page.page_subtitle'); ?>
                    </p>

                    <!-- Quick Stats -->
                    <div class="stats-bar">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($room_types); ?>+</div>
                            <div class="stat-label"><?php _e('booking_page.room_types'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">5★</div>
                            <div class="stat-label"><?php _e('booking_page.standard'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">24/7</div>
                            <div class="stat-label"><?php _e('booking_page.support'); ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Booking Form Section -->
            <section class="w-full justify-center py-16">
                <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4">

                    <!-- Booking Form - Liquid Glass -->
                    <form id="bookingForm" class="booking-form-glass">

                        <!-- Step Indicator -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-2 step-item active" data-step="1">
                                <div class="step-circle">1</div>
                                <span class="hidden sm:inline"><?php _e('booking_page.step_select_room'); ?></span>
                            </div>
                            <div class="flex-1 h-1 bg-gray-300 dark:bg-gray-600 mx-2"></div>
                            <div class="flex items-center gap-2 step-item" data-step="2">
                                <div class="step-circle">2</div>
                                <span class="hidden sm:inline"><?php _e('booking_page.step_info'); ?></span>
                            </div>
                            <div class="flex-1 h-1 bg-gray-300 dark:bg-gray-600 mx-2"></div>
                            <div class="flex items-center gap-2 step-item" data-step="3">
                                <div class="step-circle">3</div>
                                <span class="hidden sm:inline"><?php _e('booking_page.step_payment'); ?></span>
                            </div>
                        </div>


                        <?php
                        // Server-side large group check (fallback for direct URL access)
                        // Only count ADULTS (children stay with family, 2-1 rule)
                        $is_large_group = ($prefilled_adults > 6);
                        ?>

                        <?php if ($is_large_group): ?>
                            <!-- Large Group Warning Banner -->
                            <div class="mb-6 p-4 bg-amber-500/20 border border-amber-500/40 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-amber-500 text-2xl">groups</span>
                                    <div>
                                        <h4 class="font-bold text-amber-500"><?php _e('booking.large_group_title'); ?></h4>
                                        <p class="text-sm text-white/80"><?php _e('booking.large_group_warning'); ?></p>
                                        <a href="tel:+842513918888"
                                            class="inline-flex items-center gap-1 mt-2 text-accent font-bold">
                                            <span class="material-symbols-outlined text-sm">call</span>
                                            <?php _e('hero.call_hotline'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Step 1: Room Selection -->
                        <div class="form-step active" id="step1">
                            <h3 class="text-xl font-bold mb-4"><?php _e('booking_page.select_room_date'); ?></h3>

                            <!-- Global Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-white/10">
                                <!-- Check-in Date -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_page.check_in_date'); ?> *</label>
                                    <input type="date" name="check_in_date" id="check_in_date" class="form-input"
                                        value="<?php echo htmlspecialchars($prefilled_checkin); ?>" required>
                                </div>
                                <!-- Check-out Date -->
                                <div class="form-group">
                                    <label class="form-label"><?php _e('booking_page.check_out_date'); ?> *</label>
                                    <input type="date" name="check_out_date" id="check_out_date" class="form-input"
                                        value="<?php echo htmlspecialchars($prefilled_checkout); ?>" required>
                                </div>
                            </div>

                            <!-- Room List Container -->
                            <div id="room-list-container">
                                <?php for ($i = 0; $i < $prefilled_num_rooms; $i++): ?>
                                    <div class="room-row bg-white/5 p-4 rounded-xl mb-4 border border-white/10 relative"
                                        data-index="<?php echo $i; ?>">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-bold text-accent"><?php _e('booking.room'); ?>
                                                <?php echo $i + 1; ?>
                                            </h4>
                                            <?php if ($i > 0): ?>
                                                <button type="button" class="text-red-400 hover:text-red-300 remove-room-btn">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Room Type -->
                                            <div class="form-group">
                                                <label class="form-label"><?php _e('booking_page.room_type'); ?>
                                                    *</label>
                                                <select name="room_type_id[]" class="form-input room-select" required
                                                    data-preselected="<?php echo $selected_room_type_id ?? 'null'; ?>">
                                                    <option value="">-- <?php _e('booking_page.select_room_type'); ?> --
                                                    </option>
                                                    <?php foreach ($room_types as $room):
                                                        $is_disabled = isset($room['available_count']) && $room['available_count'] <= 0;
                                                        $label = $room['type_name'] . ' - ' . number_format($room['base_price']) . ' VNĐ';
                                                        if ($is_disabled) {
                                                            $label .= ' (Het phong)';
                                                        }
                                                        // Room capacity - use new columns if exist, fallback to defaults
                                                        $max_adults = isset($room['max_adults']) ? $room['max_adults'] : 2;
                                                        $max_children = isset($room['max_children']) ? $room['max_children'] : 1;
                                                        $is_twin = isset($room['is_twin']) ? $room['is_twin'] : (strpos($room['bed_type'] ?? '', '2') !== false ? 1 : 0);
                                                        ?>
                                                        <option value="<?php echo $room['room_type_id']; ?>"
                                                            data-price="<?php echo $room['base_price']; ?>"
                                                            data-max-guests="<?php echo $room['max_occupancy']; ?>"
                                                            data-max-adults="<?php echo $max_adults; ?>"
                                                            data-max-children="<?php echo $max_children; ?>"
                                                            data-is-twin="<?php echo $is_twin; ?>"
                                                            <?php echo ($selected_room_type_id !== null && (int) $selected_room_type_id === (int) $room['room_type_id'] && $i === 0) ? 'selected' : ''; ?>
                                                            <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Number of Adults -->
                                            <div class="form-group">
                                                <label class="form-label"><?php _e('hero.adults'); ?> *</label>
                                                <?php 
                                                // Distribute adults evenly across rooms
                                                $adults_per_room = floor($prefilled_adults / $prefilled_num_rooms);
                                                $extra_adults = $prefilled_adults % $prefilled_num_rooms;
                                                $this_room_adults = $adults_per_room + ($i < $extra_adults ? 1 : 0);
                                                $this_room_adults = max(1, min(2, $this_room_adults)); // Clamp 1-2
                                                ?>
                                                <select name="num_adults[]" class="form-input adults-input">
                                                    <?php for ($a = 1; $a <= 2; $a++): ?>
                                                        <option value="<?php echo $a; ?>" <?php echo $a == $this_room_adults ? 'selected' : ''; ?>>
                                                            <?php echo $a; ?> <?php _e('hero.person'); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            
                                            <!-- Number of Children -->
                                            <div class="form-group">
                                                <label class="form-label"><?php _e('hero.children'); ?></label>
                                                <?php 
                                                // Distribute children evenly across rooms
                                                $children_per_room = floor($prefilled_children / $prefilled_num_rooms);
                                                $extra_children = $prefilled_children % $prefilled_num_rooms;
                                                $this_room_children = $children_per_room + ($i < $extra_children ? 1 : 0);
                                                $this_room_children = max(0, min(2, $this_room_children)); // Clamp 0-2
                                                ?>
                                                <select name="num_children[]" class="form-input children-input">
                                                    <?php for ($c = 0; $c <= 2; $c++): ?>
                                                        <option value="<?php echo $c; ?>" <?php echo $c == $this_room_children ? 'selected' : ''; ?>>
                                                            <?php echo $c; ?> <?php _e('hero.child'); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <!-- Capacity indicator will be added by JS -->
                                                <div class="capacity-indicator text-xs text-white/60 mt-1 hidden"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <!-- Add Room Button -->
                            <div class="flex justify-center mb-6">
                                <button type="button" id="add-room-btn"
                                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600/20 text-green-400 border border-green-600/30 hover:bg-green-600/30 transition-all">
                                    <span class="material-symbols-outlined">add_circle</span>
                                    <span><?php _e('booking.add_room'); ?></span>
                                </button>
                            </div>

                            <!-- Price Summary -->
                            <div
                                class="mt-6 p-4 bg-primary-light/20 dark:bg-gray-700/50 rounded-lg border border-white/5">
                                <div class="flex justify-between items-center mt-2">
                                    <span class="font-semibold"><?php _e('booking_page.num_nights'); ?>:</span>
                                    <span id="num_nights">0</span>
                                </div>
                                <div
                                    class="flex justify-between items-center mt-2 pt-2 border-t border-gray-300 dark:border-gray-600">
                                    <span class="font-semibold"><?php _e('booking_page.estimated_total'); ?>:</span>
                                    <span id="estimated_total_display" class="text-xl font-bold text-accent">0
                                        VNĐ</span>
                                    <input type="hidden" id="estimated_total" value="0">
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
                                <div
                                    class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="material-symbols-outlined text-blue-600 dark:text-blue-400 mt-0.5">info</span>
                                        <div>
                                            <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-1">
                                                <?php _e('booking_page.login_for_better'); ?>
                                            </h4>
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
                                    <div class="flex justify-between">
                                        <span><?php _e('booking_page.subtotal'); ?>:</span>
                                        <span id="summary_subtotal" class="font-semibold"></span>
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
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="vnpay" checked>
                                        <div class="payment-option-content">
                                            <img src="./assets/img/vnpay-logo.png" alt="VNPay" class="h-8">
                                            <span><?php _e('booking_page.pay_vnpay'); ?></span>
                                        </div>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cash">
                                        <div class="payment-option-content">
                                            <span class="material-symbols-outlined text-2xl">payments</span>
                                            <span><?php _e('booking_page.pay_at_hotel'); ?></span>
                                        </div>
                                    </label>
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
                                    <span class="material-symbols-outlined">lock</span>
                                    <?php _e('booking_page.confirm_booking'); ?>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </section>
        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <!-- Main JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script src="./assets/js/booking.js"></script>

</body>

</html>