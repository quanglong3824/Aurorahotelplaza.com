<?php
session_start();
require_once '../config/database.php';

$page_title = 'Tạo đặt phòng mới';
$page_subtitle = 'Admin tạo booking cho khách hàng';

try {
    $db = getDB();
    
    // Get room types
    $stmt = $db->query("SELECT * FROM room_types WHERE status = 'active' ORDER BY category, type_name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get customers
    $stmt = $db->query("SELECT user_id, full_name, email, phone FROM users WHERE user_role = 'customer' ORDER BY full_name");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active promotions
    $stmt = $db->query("
        SELECT * FROM promotions 
        WHERE status = 'active' 
        AND start_date <= NOW() 
        AND end_date >= NOW()
        ORDER BY promotion_name
    ");
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Create booking page error: " . $e->getMessage());
    $room_types = [];
    $customers = [];
    $promotions = [];
}

include 'includes/admin-header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg">Thông tin đặt phòng</h3>
        </div>
        <form id="createBookingForm" onsubmit="createBooking(event)">
            <div class="card-body space-y-6">
                
                <!-- Customer Selection -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined">person</span>
                        Thông tin khách hàng
                    </h4>
                    
                    <div class="form-group">
                        <label class="form-label">Chọn khách hàng có sẵn</label>
                        <select id="existing_customer" class="form-select" onchange="fillCustomerInfo(this.value)">
                            <option value="">-- Hoặc nhập thông tin mới bên dưới --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['user_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($customer['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($customer['phone']); ?>">
                                    <?php echo htmlspecialchars($customer['full_name']); ?> - <?php echo htmlspecialchars($customer['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="user_id" id="user_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="form-group">
                            <label class="form-label">Họ tên *</label>
                            <input type="text" name="guest_name" id="guest_name" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="guest_email" id="guest_email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="guest_phone" id="guest_phone" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CMND/CCCD</label>
                            <input type="text" name="guest_id_number" id="guest_id_number" class="form-input">
                        </div>
                    </div>
                </div>
                
                <!-- Room Selection -->
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined">hotel</span>
                        Thông tin phòng
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Loại phòng *</label>
                            <select name="room_type_id" id="room_type_id" class="form-select" required onchange="updateRoomPrice()">
                                <option value="">-- Chọn loại phòng --</option>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?php echo $type['room_type_id']; ?>" 
                                            data-price="<?php echo $type['base_price']; ?>"
                                            data-name="<?php echo htmlspecialchars($type['type_name']); ?>">
                                        <?php echo htmlspecialchars($type['type_name']); ?> - 
                                        <?php echo number_format($type['base_price'], 0, ',', '.'); ?>đ/đêm
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số phòng *</label>
                            <input type="number" name="num_rooms" id="num_rooms" class="form-input" 
                                   min="1" value="1" required onchange="calculateTotal()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ngày nhận phòng *</label>
                            <input type="date" name="check_in_date" id="check_in_date" class="form-input" 
                                   required onchange="calculateNights()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ngày trả phòng *</label>
                            <input type="date" name="check_out_date" id="check_out_date" class="form-input" 
                                   required onchange="calculateNights()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số người lớn *</label>
                            <input type="number" name="num_adults" class="form-input" min="1" value="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số trẻ em</label>
                            <input type="number" name="num_children" class="form-input" min="0" value="0">
                        </div>
                    </div>
                </div>
                
                <!-- Pricing & Discounts -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined">payments</span>
                        Giá & Khuyến mãi
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Mã khuyến mãi</label>
                            <select name="promotion_code" id="promotion_code" class="form-select" onchange="applyPromotion()">
                                <option value="">-- Không áp dụng --</option>
                                <?php foreach ($promotions as $promo): ?>
                                    <option value="<?php echo htmlspecialchars($promo['promotion_code']); ?>"
                                            data-type="<?php echo $promo['discount_type']; ?>"
                                            data-value="<?php echo $promo['discount_value']; ?>"
                                            data-max="<?php echo $promo['max_discount'] ?? 0; ?>">
                                        <?php echo htmlspecialchars($promo['promotion_name']); ?> - 
                                        <?php echo $promo['discount_type'] === 'percentage' ? 
                                            $promo['discount_value'] . '%' : 
                                            number_format($promo['discount_value']) . 'đ'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Giảm giá thêm (VNĐ)</label>
                            <input type="number" name="extra_discount" id="extra_discount" class="form-input" 
                                   min="0" value="0" onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <!-- Price Summary -->
                    <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Số đêm:</span>
                            <span id="display_nights" class="font-semibold">0 đêm</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Giá phòng:</span>
                            <span id="display_room_price" class="font-semibold">0đ</span>
                        </div>
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Giảm giá:</span>
                            <span id="display_discount" class="font-semibold">0đ</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>Tổng cộng:</span>
                            <span id="display_total" style="color: #d4af37;">0đ</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="total_nights" id="total_nights" value="0">
                    <input type="hidden" name="room_price" id="room_price" value="0">
                    <input type="hidden" name="discount_amount" id="discount_amount" value="0">
                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                </div>
                
                <!-- Special Requests -->
                <div class="form-group">
                    <label class="form-label">Yêu cầu đặc biệt</label>
                    <textarea name="special_requests" class="form-textarea" rows="3" 
                              placeholder="Nhập yêu cầu đặc biệt của khách..."></textarea>
                </div>
                
                <!-- Payment Status -->
                <div class="form-group">
                    <label class="form-label">Trạng thái thanh toán</label>
                    <select name="payment_status" class="form-select">
                        <option value="unpaid">Chưa thanh toán</option>
                        <option value="paid">Đã thanh toán</option>
                        <option value="partial">Thanh toán 1 phần</option>
                    </select>
                </div>
                
                <!-- Booking Status -->
                <div class="form-group">
                    <label class="form-label">Trạng thái đơn</label>
                    <select name="status" class="form-select">
                        <option value="pending">Chờ xác nhận</option>
                        <option value="confirmed" selected>Đã xác nhận</option>
                    </select>
                </div>
            </div>
            
            <div class="card-footer flex gap-3">
                <a href="bookings.php" class="btn btn-secondary flex-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Hủy
                </a>
                <button type="submit" class="btn btn-primary flex-1">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Tạo đặt phòng
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Set minimum date to today
document.getElementById('check_in_date').min = new Date().toISOString().split('T')[0];
document.getElementById('check_out_date').min = new Date().toISOString().split('T')[0];

function fillCustomerInfo(userId) {
    if (!userId) {
        document.getElementById('user_id').value = '';
        document.getElementById('guest_name').value = '';
        document.getElementById('guest_email').value = '';
        document.getElementById('guest_phone').value = '';
        return;
    }
    
    const select = document.getElementById('existing_customer');
    const option = select.options[select.selectedIndex];
    
    document.getElementById('user_id').value = userId;
    document.getElementById('guest_name').value = option.dataset.name;
    document.getElementById('guest_email').value = option.dataset.email;
    document.getElementById('guest_phone').value = option.dataset.phone;
}

function updateRoomPrice() {
    calculateNights();
}

function calculateNights() {
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    
    if (!checkIn || !checkOut) return;
    
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    
    if (nights <= 0) {
        alert('Ngày trả phòng phải sau ngày nhận phòng');
        document.getElementById('check_out_date').value = '';
        return;
    }
    
    document.getElementById('total_nights').value = nights;
    document.getElementById('display_nights').textContent = nights + ' đêm';
    
    calculateTotal();
}

function calculateTotal() {
    const roomTypeSelect = document.getElementById('room_type_id');
    if (!roomTypeSelect.value) return;
    
    const basePrice = parseFloat(roomTypeSelect.options[roomTypeSelect.selectedIndex].dataset.price);
    const nights = parseInt(document.getElementById('total_nights').value) || 0;
    const numRooms = parseInt(document.getElementById('num_rooms').value) || 1;
    
    const roomPrice = basePrice * nights * numRooms;
    document.getElementById('room_price').value = roomPrice;
    document.getElementById('display_room_price').textContent = formatMoney(roomPrice);
    
    applyPromotion();
}

function applyPromotion() {
    const roomPrice = parseFloat(document.getElementById('room_price').value) || 0;
    const extraDiscount = parseFloat(document.getElementById('extra_discount').value) || 0;
    
    let promoDiscount = 0;
    const promoSelect = document.getElementById('promotion_code');
    
    if (promoSelect.value) {
        const option = promoSelect.options[promoSelect.selectedIndex];
        const type = option.dataset.type;
        const value = parseFloat(option.dataset.value);
        const maxDiscount = parseFloat(option.dataset.max) || 0;
        
        if (type === 'percentage') {
            promoDiscount = roomPrice * (value / 100);
            if (maxDiscount > 0) {
                promoDiscount = Math.min(promoDiscount, maxDiscount);
            }
        } else {
            promoDiscount = value;
        }
    }
    
    const totalDiscount = promoDiscount + extraDiscount;
    const totalAmount = Math.max(0, roomPrice - totalDiscount);
    
    document.getElementById('discount_amount').value = totalDiscount;
    document.getElementById('display_discount').textContent = formatMoney(totalDiscount);
    
    document.getElementById('total_amount').value = totalAmount;
    document.getElementById('display_total').textContent = formatMoney(totalAmount);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function createBooking(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Đang tạo...';
    
    fetch('api/create-booking-admin.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Tạo đặt phòng thành công!', 'success');
            setTimeout(() => {
                window.location.href = 'booking-detail.php?id=' + data.booking_id;
            }, 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm">add</span> Tạo đặt phòng';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm">add</span> Tạo đặt phòng';
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>
