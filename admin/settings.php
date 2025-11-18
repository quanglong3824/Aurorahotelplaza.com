<?php
session_start();
require_once '../config/database.php';

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Cài đặt hệ thống';
$page_subtitle = 'Cấu hình và quản lý hệ thống';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
                VALUES (:key, :value, :user_id, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = :value,
                    updated_by = :user_id,
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':user_id' => $_SESSION['user_id']
            ]);
        }
        
        $success_message = 'Cập nhật cài đặt thành công!';
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        $error_message = 'Có lỗi xảy ra khi cập nhật cài đặt';
    }
}

// Load current settings
try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Default settings
    $settings = array_merge([
        'site_name' => 'Aurora Hotel Plaza',
        'site_email' => 'info@aurorahotelplaza.com',
        'site_phone' => '+84 123 456 789',
        'site_address' => 'Hà Nội, Việt Nam',
        'booking_advance_days' => '365',
        'booking_min_nights' => '1',
        'booking_max_nights' => '30',
        'cancellation_hours' => '24',
        'late_checkout_fee' => '50000',
        'early_checkin_fee' => '50000',
        'tax_rate' => '10',
        'service_charge_rate' => '5',
        'points_per_vnd' => '1000',
        'points_expiry_days' => '365',
        'email_notifications' => '1',
        'sms_notifications' => '0',
        'maintenance_mode' => '0',
        'allow_guest_booking' => '1',
        'require_payment_upfront' => '0',
        'auto_confirm_booking' => '0',
    ], $settings_raw);
    
} catch (Exception $e) {
    error_log("Settings load error: " . $e->getMessage());
    $settings = [];
}

include 'includes/admin-header.php';
?>

<?php if (isset($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        <span class="material-symbols-outlined text-sm align-middle mr-2">check_circle</span>
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <span class="material-symbols-outlined text-sm align-middle mr-2">error</span>
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <!-- General Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">settings</span>
                Cài đặt chung
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Tên khách sạn</label>
                    <input type="text" name="settings[site_name]" value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                           class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email liên hệ</label>
                    <input type="email" name="settings[site_email]" value="<?php echo htmlspecialchars($settings['site_email']); ?>" 
                           class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="settings[site_phone]" value="<?php echo htmlspecialchars($settings['site_phone']); ?>" 
                           class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="settings[site_address]" value="<?php echo htmlspecialchars($settings['site_address']); ?>" 
                           class="form-input" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">book_online</span>
                Cài đặt đặt phòng
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Đặt trước tối đa (ngày)</label>
                    <input type="number" name="settings[booking_advance_days]" 
                           value="<?php echo htmlspecialchars($settings['booking_advance_days']); ?>" 
                           class="form-input" min="1" required>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                        Khách có thể đặt phòng trước bao nhiêu ngày
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Số đêm tối thiểu</label>
                    <input type="number" name="settings[booking_min_nights]" 
                           value="<?php echo htmlspecialchars($settings['booking_min_nights']); ?>" 
                           class="form-input" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Số đêm tối đa</label>
                    <input type="number" name="settings[booking_max_nights]" 
                           value="<?php echo htmlspecialchars($settings['booking_max_nights']); ?>" 
                           class="form-input" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hủy miễn phí trước (giờ)</label>
                    <input type="number" name="settings[cancellation_hours]" 
                           value="<?php echo htmlspecialchars($settings['cancellation_hours']); ?>" 
                           class="form-input" min="0" required>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                        Khách có thể hủy miễn phí trước check-in bao nhiêu giờ
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phí check-out muộn (VNĐ)</label>
                    <input type="number" name="settings[late_checkout_fee]" 
                           value="<?php echo htmlspecialchars($settings['late_checkout_fee']); ?>" 
                           class="form-input" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phí check-in sớm (VNĐ)</label>
                    <input type="number" name="settings[early_checkin_fee]" 
                           value="<?php echo htmlspecialchars($settings['early_checkin_fee']); ?>" 
                           class="form-input" min="0" required>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark space-y-3">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="settings[allow_guest_booking]" value="1" 
                           <?php echo $settings['allow_guest_booking'] ? 'checked' : ''; ?>>
                    <span>Cho phép đặt phòng không cần đăng ký</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="settings[require_payment_upfront]" value="1" 
                           <?php echo $settings['require_payment_upfront'] ? 'checked' : ''; ?>>
                    <span>Yêu cầu thanh toán trước khi đặt phòng</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="settings[auto_confirm_booking]" value="1" 
                           <?php echo $settings['auto_confirm_booking'] ? 'checked' : ''; ?>>
                    <span>Tự động xác nhận đơn đặt phòng</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Pricing Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">payments</span>
                Cài đặt giá & phí
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Thuế VAT (%)</label>
                    <input type="number" name="settings[tax_rate]" 
                           value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" 
                           class="form-input" min="0" max="100" step="0.1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phí dịch vụ (%)</label>
                    <input type="number" name="settings[service_charge_rate]" 
                           value="<?php echo htmlspecialchars($settings['service_charge_rate']); ?>" 
                           class="form-input" min="0" max="100" step="0.1" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Loyalty Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">loyalty</span>
                Cài đặt điểm thưởng
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Tích điểm (VNĐ/điểm)</label>
                    <input type="number" name="settings[points_per_vnd]" 
                           value="<?php echo htmlspecialchars($settings['points_per_vnd']); ?>" 
                           class="form-input" min="1" required>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                        Mỗi <?php echo number_format($settings['points_per_vnd']); ?>đ = 1 điểm
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Điểm hết hạn sau (ngày)</label>
                    <input type="number" name="settings[points_expiry_days]" 
                           value="<?php echo htmlspecialchars($settings['points_expiry_days']); ?>" 
                           class="form-input" min="0" required>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                        0 = không hết hạn
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">notifications</span>
                Cài đặt thông báo
            </h3>
        </div>
        <div class="card-body">
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="settings[email_notifications]" value="1" 
                           <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                    <span>Bật thông báo qua Email</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="settings[sms_notifications]" value="1" 
                           <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>>
                    <span>Bật thông báo qua SMS</span>
                </label>
            </div>
        </div>
    </div>

    <!-- System Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                Cài đặt hệ thống
            </h3>
        </div>
        <div class="card-body">
            <div class="space-y-4">
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="settings[maintenance_mode]" value="1" 
                               <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <span class="font-medium text-red-600">Bật chế độ bảo trì</span>
                    </label>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark ml-6">
                        Khi bật, website sẽ hiển thị thông báo bảo trì cho khách hàng
                    </p>
                </div>
                
                <div class="pt-4 border-t border-gray-200 dark:border-slate-700">
                    <a href="reset-database.php" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors font-medium">
                        <span class="material-symbols-outlined text-sm">delete_forever</span>
                        Reset Database (Xóa toàn bộ dữ liệu)
                    </a>
                    <p class="text-sm text-gray-500 mt-2">
                        ⚠️ Xóa toàn bộ dữ liệu, chỉ giữ lại tài khoản admin
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end gap-3">
        <a href="dashboard.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">close</span>
            Hủy
        </a>
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">save</span>
            Lưu cài đặt
        </button>
    </div>
</form>

<?php include 'includes/admin-footer.php'; ?>
