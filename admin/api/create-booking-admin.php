<?php
/**
 * API: Admin Create Booking
 * Admin tạo booking cho khách hàng với đầy đủ tính năng
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    // Get and sanitize input
    $user_id = Security::sanitizeInt($_POST['user_id'] ?? 0);
    $guest_name = Security::sanitizeString($_POST['guest_name'] ?? '');
    $guest_email = Security::sanitizeEmail($_POST['guest_email'] ?? '');
    $guest_phone = Security::sanitizeString($_POST['guest_phone'] ?? '');
    $guest_id_number = Security::sanitizeString($_POST['guest_id_number'] ?? '');
    
    $room_type_id = Security::sanitizeInt($_POST['room_type_id'] ?? 0);
    $num_rooms = Security::sanitizeInt($_POST['num_rooms'] ?? 1);
    $check_in_date = Security::sanitizeString($_POST['check_in_date'] ?? '');
    $check_out_date = Security::sanitizeString($_POST['check_out_date'] ?? '');
    $num_adults = Security::sanitizeInt($_POST['num_adults'] ?? 1);
    $num_children = Security::sanitizeInt($_POST['num_children'] ?? 0);
    
    $total_nights = Security::sanitizeInt($_POST['total_nights'] ?? 0);
    $room_price = Security::sanitizeFloat($_POST['room_price'] ?? 0);
    $discount_amount = Security::sanitizeFloat($_POST['discount_amount'] ?? 0);
    $total_amount = Security::sanitizeFloat($_POST['total_amount'] ?? 0);
    
    $special_requests = Security::sanitizeString($_POST['special_requests'] ?? '');
    $payment_status = Security::sanitizeString($_POST['payment_status'] ?? 'unpaid');
    $status = Security::sanitizeString($_POST['status'] ?? 'confirmed');
    
    // Validation
    if (empty($guest_name) || empty($guest_email) || empty($guest_phone)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin khách hàng');
    }
    
    if (!$room_type_id || !$check_in_date || !$check_out_date) {
        throw new Exception('Vui lòng chọn loại phòng và ngày');
    }
    
    if ($total_nights <= 0) {
        throw new Exception('Số đêm phải lớn hơn 0');
    }
    
    if ($total_amount <= 0) {
        throw new Exception('Tổng tiền không hợp lệ');
    }
    
    // Validate dates
    $checkIn = new DateTime($check_in_date);
    $checkOut = new DateTime($check_out_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($checkIn < $today) {
        throw new Exception('Ngày nhận phòng không được trong quá khứ');
    }
    
    if ($checkOut <= $checkIn) {
        throw new Exception('Ngày trả phòng phải sau ngày nhận phòng');
    }
    
    $db->beginTransaction();
    
    // If no user_id, try to find or create user
    if (!$user_id) {
        // Check if email exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $guest_email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            $user_id = $existing_user['user_id'];
        } else {
            // Create new user account
            $temp_password = bin2hex(random_bytes(8));
            $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (
                    email, password_hash, full_name, phone,
                    user_role, status, created_at
                ) VALUES (
                    :email, :password_hash, :full_name, :phone,
                    'customer', 'active', NOW()
                )
            ");
            
            $stmt->execute([
                ':email' => $guest_email,
                ':password_hash' => $password_hash,
                ':full_name' => $guest_name,
                ':phone' => $guest_phone
            ]);
            
            $user_id = $db->lastInsertId();
            
            // TODO: Send email with temporary password
        }
    }
    
    // Generate booking code
    $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    // Create booking
    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_code, user_id, room_type_id,
            check_in_date, check_out_date,
            num_adults, num_children, num_rooms,
            total_nights, room_price, discount_amount, total_amount,
            guest_name, guest_email, guest_phone, guest_id_number,
            special_requests, status, payment_status,
            created_at, updated_at
        ) VALUES (
            :booking_code, :user_id, :room_type_id,
            :check_in_date, :check_out_date,
            :num_adults, :num_children, :num_rooms,
            :total_nights, :room_price, :discount_amount, :total_amount,
            :guest_name, :guest_email, :guest_phone, :guest_id_number,
            :special_requests, :status, :payment_status,
            NOW(), NOW()
        )
    ");
    
    $stmt->execute([
        ':booking_code' => $booking_code,
        ':user_id' => $user_id,
        ':room_type_id' => $room_type_id,
        ':check_in_date' => $check_in_date,
        ':check_out_date' => $check_out_date,
        ':num_adults' => $num_adults,
        ':num_children' => $num_children,
        ':num_rooms' => $num_rooms,
        ':total_nights' => $total_nights,
        ':room_price' => $room_price,
        ':discount_amount' => $discount_amount,
        ':total_amount' => $total_amount,
        ':guest_name' => $guest_name,
        ':guest_email' => $guest_email,
        ':guest_phone' => $guest_phone,
        ':guest_id_number' => $guest_id_number,
        ':special_requests' => $special_requests,
        ':status' => $status,
        ':payment_status' => $payment_status
    ]);
    
    $booking_id = $db->lastInsertId();
    
    // Add booking history
    $stmt = $db->prepare("
        INSERT INTO booking_history (
            booking_id, old_status, new_status,
            changed_by, notes, created_at
        ) VALUES (
            :booking_id, NULL, :status,
            :changed_by, 'Đơn được tạo bởi admin', NOW()
        )
    ");
    
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':status' => $status,
        ':changed_by' => $_SESSION['user_id']
    ]);
    
    // If paid, create payment record and award points
    if ($payment_status === 'paid') {
        $stmt = $db->prepare("
            INSERT INTO payments (
                booking_id, payment_method, amount,
                currency, status, paid_at, created_at
            ) VALUES (
                :booking_id, 'cash', :amount,
                'VND', 'completed', NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':booking_id' => $booking_id,
            ':amount' => $total_amount
        ]);
        
        // Award loyalty points
        $points_earned = floor($total_amount / 100);
        if ($status === 'confirmed') {
            $points_earned = floor($points_earned * 1.1);
        }
        
        if ($points_earned > 0) {
            // Check loyalty record
            $stmt = $db->prepare("SELECT loyalty_id FROM user_loyalty WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $loyalty = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$loyalty) {
                $stmt = $db->prepare("
                    INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
                    VALUES (:user_id, :points, :points, NOW())
                ");
                $stmt->execute([':user_id' => $user_id, ':points' => $points_earned]);
            } else {
                $stmt = $db->prepare("
                    UPDATE user_loyalty 
                    SET current_points = current_points + :points,
                        lifetime_points = lifetime_points + :points,
                        updated_at = NOW()
                    WHERE user_id = :user_id
                ");
                $stmt->execute([':points' => $points_earned, ':user_id' => $user_id]);
            }
            
            // Record transaction
            $stmt = $db->prepare("
                INSERT INTO points_transactions (
                    user_id, points, transaction_type,
                    reference_type, reference_id, description,
                    created_by, created_at
                ) VALUES (
                    :user_id, :points, 'earn',
                    'booking_payment', :booking_id, :description,
                    :created_by, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $user_id,
                ':points' => $points_earned,
                ':booking_id' => $booking_id,
                ':description' => "Nhận {$points_earned} điểm từ booking {$booking_code}",
                ':created_by' => $_SESSION['user_id']
            ]);
        }
    }
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, 'create_booking_admin', 'booking', :booking_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':booking_id' => $booking_id,
        ':description' => sprintf(
            'Admin tạo booking %s cho khách %s. Tổng tiền: %s VND',
            $booking_code,
            $guest_name,
            number_format($total_amount)
        ),
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Create notification for customer
    $stmt = $db->prepare("
        INSERT INTO notifications (
            user_id, type, title, message, link, icon, created_at
        ) VALUES (
            :user_id, 'booking_created', :title, :message, :link, 'hotel', NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => 'Đặt phòng mới',
        ':message' => sprintf(
            'Đơn đặt phòng %s đã được tạo. Check-in: %s. Tổng tiền: %s VND',
            $booking_code,
            date('d/m/Y', strtotime($check_in_date)),
            number_format($total_amount)
        ),
        ':link' => '/profile/bookings.php?id=' . $booking_id
    ]);
    
    // Notify all admin/receptionist
    $stmt = $db->query("
        SELECT user_id FROM users 
        WHERE user_role IN ('admin', 'receptionist', 'sale') 
        AND user_id != {$_SESSION['user_id']}
    ");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($admins as $admin_id) {
        $stmt = $db->prepare("
            INSERT INTO notifications (
                user_id, type, title, message, link, icon, created_at
            ) VALUES (
                :user_id, 'new_booking', 'Đơn đặt phòng mới', :message, :link, 'notifications', NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $admin_id,
            ':message' => sprintf(
                '%s vừa tạo đơn %s cho khách %s',
                $_SESSION['full_name'] ?? 'Admin',
                $booking_code,
                $guest_name
            ),
            ':link' => '/admin/booking-detail.php?id=' . $booking_id
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo đặt phòng thành công',
        'booking_id' => $booking_id,
        'booking_code' => $booking_code
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Create booking error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
