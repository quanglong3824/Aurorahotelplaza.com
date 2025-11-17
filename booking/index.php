<?php
session_start();
require_once '../config/database.php';

// Get room types for selection
$db = getDB();
$stmt = $db->prepare("SELECT * FROM room_types WHERE is_active = 1 ORDER BY sort_order, base_price");
$stmt->execute();
$room_types = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đặt phòng - Aurora Hotel Plaza</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

<!-- Tailwind Configuration -->
<script src="../assets/js/tailwind-config.js"></script>

<!-- Custom CSS -->
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="./assets/css/booking.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20">
    <!-- Booking Form Section -->
    <section class="w-full justify-center py-16">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4">
            <div class="flex flex-col gap-2 text-center">
                <h1 class="font-display text-4xl font-bold text-text-primary-light dark:text-text-primary-dark">Đặt phòng</h1>
                <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Hoàn tất thông tin để đặt phòng tại Aurora Hotel Plaza</p>
            </div>

            <!-- Booking Form -->
            <form id="bookingForm" class="flex flex-col gap-6 rounded-xl bg-surface-light p-8 shadow-lg dark:bg-surface-dark">
                
                <!-- Step Indicator -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2 step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <span class="hidden sm:inline">Chọn phòng</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-gray-600 mx-2"></div>
                    <div class="flex items-center gap-2 step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <span class="hidden sm:inline">Thông tin</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-gray-600 mx-2"></div>
                    <div class="flex items-center gap-2 step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <span class="hidden sm:inline">Thanh toán</span>
                    </div>
                </div>

                <!-- Step 1: Room Selection -->
                <div class="form-step active" id="step1">
                    <h3 class="text-xl font-bold mb-4">Chọn phòng và ngày</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Room Type -->
                        <div class="form-group">
                            <label class="form-label">Loại phòng *</label>
                            <select name="room_type_id" id="room_type_id" class="form-input" required>
                                <option value="">-- Chọn loại phòng --</option>
                                <?php foreach($room_types as $room): ?>
                                <option value="<?php echo $room['id']; ?>" 
                                        data-price="<?php echo $room['base_price']; ?>"
                                        data-max-guests="<?php echo $room['max_guests']; ?>">
                                    <?php echo $room['name']; ?> - <?php echo number_format($room['base_price']); ?> VNĐ/đêm
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Number of Guests -->
                        <div class="form-group">
                            <label class="form-label">Số khách *</label>
                            <input type="number" name="num_guests" id="num_guests" class="form-input" min="1" max="10" value="2" required>
                        </div>

                        <!-- Check-in Date -->
                        <div class="form-group">
                            <label class="form-label">Ngày nhận phòng *</label>
                            <input type="date" name="check_in_date" id="check_in_date" class="form-input" required>
                        </div>

                        <!-- Check-out Date -->
                        <div class="form-group">
                            <label class="form-label">Ngày trả phòng *</label>
                            <input type="date" name="check_out_date" id="check_out_date" class="form-input" required>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="mt-6 p-4 bg-primary-light/20 dark:bg-gray-700 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Số đêm:</span>
                            <span id="num_nights">0</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="font-semibold">Tổng tiền tạm tính:</span>
                            <span id="estimated_total" class="text-xl font-bold text-accent">0 VNĐ</span>
                        </div>
                    </div>

                    <button type="button" class="btn-primary mt-4" onclick="nextStep(2)">Tiếp tục</button>
                </div>

                <!-- Step 2: Guest Information -->
                <div class="form-step" id="step2">
                    <h3 class="text-xl font-bold mb-4">Thông tin khách hàng</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label class="form-label">Họ và tên *</label>
                            <input type="text" name="guest_name" id="guest_name" class="form-input" required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="guest_phone" id="guest_phone" class="form-input" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Email *</label>
                            <input type="email" name="guest_email" id="guest_email" class="form-input" required>
                        </div>

                        <!-- Special Requests -->
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Yêu cầu đặc biệt</label>
                            <textarea name="special_requests" id="special_requests" class="form-input" rows="3" placeholder="Ví dụ: Phòng tầng cao, giường đôi..."></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-4">
                        <button type="button" class="btn-secondary" onclick="prevStep(1)">Quay lại</button>
                        <button type="button" class="btn-primary flex-1" onclick="nextStep(3)">Tiếp tục</button>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div class="form-step" id="step3">
                    <h3 class="text-xl font-bold mb-4">Xác nhận và thanh toán</h3>
                    
                    <!-- Booking Summary -->
                    <div class="p-6 bg-surface-light dark:bg-gray-700 rounded-lg mb-6">
                        <h4 class="font-bold text-lg mb-4">Thông tin đặt phòng</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Loại phòng:</span>
                                <span id="summary_room_type" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Số khách:</span>
                                <span id="summary_guests" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Nhận phòng:</span>
                                <span id="summary_checkin" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Trả phòng:</span>
                                <span id="summary_checkout" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Số đêm:</span>
                                <span id="summary_nights" class="font-semibold"></span>
                            </div>
                            <hr class="my-3 border-gray-300 dark:border-gray-600">
                            <div class="flex justify-between">
                                <span>Họ tên:</span>
                                <span id="summary_name" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Email:</span>
                                <span id="summary_email" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Điện thoại:</span>
                                <span id="summary_phone" class="font-semibold"></span>
                            </div>
                            <hr class="my-3 border-gray-300 dark:border-gray-600">
                            <div class="flex justify-between text-lg font-bold text-accent">
                                <span>Tổng thanh toán:</span>
                                <span id="summary_total"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group mb-6">
                        <label class="form-label">Phương thức thanh toán *</label>
                        <div class="space-y-3">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="vnpay" checked>
                                <div class="payment-option-content">
                                    <img src="./assets/img/vnpay-logo.png" alt="VNPay" class="h-8">
                                    <span>Thanh toán qua VNPay</span>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cash">
                                <div class="payment-option-content">
                                    <span class="material-symbols-outlined text-2xl">payments</span>
                                    <span>Thanh toán tại khách sạn</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="form-group">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="agree_terms" id="agree_terms" class="mt-1" required>
                            <span class="text-sm">Tôi đồng ý với <a href="#" class="text-accent hover:underline">điều khoản và điều kiện</a> của Aurora Hotel Plaza</span>
                        </label>
                    </div>

                    <div class="flex gap-4 mt-4">
                        <button type="button" class="btn-secondary" onclick="prevStep(2)">Quay lại</button>
                        <button type="submit" class="btn-primary flex-1" id="submitBtn">
                            <span class="material-symbols-outlined">lock</span>
                            Xác nhận đặt phòng
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
