<?php
class SettingsController {
    public function getData() {
        require_once '../config/database.php';
        
        $success_message = null;
        $error_message = null;

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

        return [
            'settings' => $settings,
            'success_message' => $success_message,
            'error_message' => $error_message
        ];
    }
}
