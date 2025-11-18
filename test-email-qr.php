<?php
/**
 * Test Email and QR Code Functionality
 * This file is for testing purposes only - DELETE IN PRODUCTION
 */

session_start();
require_once 'config/database.php';
require_once 'models/Booking.php';
require_once 'includes/email-helper.php';
require_once 'includes/qrcode-helper.php';

// Set a test user if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

$action = $_GET['action'] ?? 'menu';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email & QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .menu {
            list-style: none;
            padding: 0;
        }
        .menu li {
            margin: 15px 0;
        }
        .menu a {
            display: block;
            padding: 15px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .menu a:hover {
            background: #5568d3;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        img {
            max-width: 100%;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Email & QR Code</h1>
        
        <?php if ($action === 'menu'): ?>
            <ul class="menu">
                <li><a href="?action=test-email">📧 Test Booking Confirmation Email</a></li>
                <li><a href="?action=test-qr">📱 Test QR Code Generation</a></li>
                <li><a href="?action=list-bookings">📋 List All Bookings</a></li>
                <li><a href="?action=test-status-email">✅ Test Status Update Email</a></li>
            </ul>
            
            <div class="result info">
                <strong>⚠️ Lưu ý:</strong> File này chỉ dùng để test. Hãy xóa file này khi deploy production!
            </div>
            
        <?php elseif ($action === 'test-email'): ?>
            <h2>Test Booking Confirmation Email</h2>
            
            <?php
            try {
                $db = getDB();
                $bookingModel = new Booking($db);
                
                // Get the latest booking
                $stmt = $db->query("
                    SELECT b.*, rt.type_name, rt.category 
                    FROM bookings b 
                    LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                    ORDER BY b.created_at DESC 
                    LIMIT 1
                ");
                $booking = $stmt->fetch();
                
                if (!$booking) {
                    echo '<div class="result error">Không tìm thấy booking nào trong database!</div>';
                } else {
                    echo '<div class="result info">';
                    echo '<strong>Booking được chọn:</strong><br>';
                    echo 'Mã: ' . $booking['booking_code'] . '<br>';
                    echo 'Khách hàng: ' . $booking['guest_name'] . '<br>';
                    echo 'Email: ' . $booking['guest_email'] . '<br>';
                    echo '</div>';
                    
                    // Send email
                    $result = sendBookingConfirmationEmail($booking);
                    
                    if ($result['success']) {
                        echo '<div class="result success">✅ Email đã được gửi thành công!</div>';
                    } else {
                        echo '<div class="result error">❌ Gửi email thất bại: ' . $result['message'] . '</div>';
                    }
                    
                    echo '<pre>' . print_r($result, true) . '</pre>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">Lỗi: ' . $e->getMessage() . '</div>';
            }
            ?>
            
            <a href="?action=menu" class="back-link">← Quay lại</a>
            
        <?php elseif ($action === 'test-qr'): ?>
            <h2>Test QR Code Generation</h2>
            
            <?php
            try {
                $db = getDB();
                
                // Get a confirmed booking
                $stmt = $db->query("
                    SELECT b.*, rt.type_name, rt.category 
                    FROM bookings b 
                    LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                    WHERE b.status IN ('confirmed', 'checked_in')
                    ORDER BY b.created_at DESC 
                    LIMIT 1
                ");
                $booking = $stmt->fetch();
                
                if (!$booking) {
                    echo '<div class="result error">Không tìm thấy booking đã xác nhận nào!</div>';
                } else {
                    echo '<div class="result info">';
                    echo '<strong>Booking:</strong> ' . $booking['booking_code'] . '<br>';
                    echo '<strong>Trạng thái:</strong> ' . $booking['status'];
                    echo '</div>';
                    
                    // Generate QR data
                    $qr_data = generateBookingQRData($booking);
                    
                    echo '<h3>QR Data (JSON):</h3>';
                    echo '<pre>' . $qr_data . '</pre>';
                    
                    // Generate QR image URL
                    $qr_url = generateQRCodeImage($qr_data, 400);
                    
                    echo '<h3>QR Code Image:</h3>';
                    echo '<img src="' . $qr_url . '" alt="QR Code">';
                    
                    echo '<div class="result success">✅ QR Code được tạo thành công!</div>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">Lỗi: ' . $e->getMessage() . '</div>';
            }
            ?>
            
            <a href="?action=menu" class="back-link">← Quay lại</a>
            
        <?php elseif ($action === 'list-bookings'): ?>
            <h2>Danh sách Bookings</h2>
            
            <?php
            try {
                $db = getDB();
                
                $stmt = $db->query("
                    SELECT b.booking_id, b.booking_code, b.guest_name, b.guest_email, 
                           b.status, b.check_in_date, b.check_out_date, rt.type_name
                    FROM bookings b
                    LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
                    ORDER BY b.created_at DESC
                    LIMIT 10
                ");
                $bookings = $stmt->fetchAll();
                
                if (empty($bookings)) {
                    echo '<div class="result error">Chưa có booking nào!</div>';
                } else {
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr style="background: #f8f9fa;">';
                    echo '<th style="padding: 10px; border: 1px solid #ddd;">Mã</th>';
                    echo '<th style="padding: 10px; border: 1px solid #ddd;">Khách hàng</th>';
                    echo '<th style="padding: 10px; border: 1px solid #ddd;">Loại phòng</th>';
                    echo '<th style="padding: 10px; border: 1px solid #ddd;">Trạng thái</th>';
                    echo '<th style="padding: 10px; border: 1px solid #ddd;">Check-in</th>';
                    echo '</tr>';
                    
                    foreach ($bookings as $booking) {
                        $status_color = [
                            'pending' => '#ffc107',
                            'confirmed' => '#28a745',
                            'checked_in' => '#17a2b8',
                            'checked_out' => '#6c757d',
                            'cancelled' => '#dc3545'
                        ];
                        $color = $status_color[$booking['status']] ?? '#6c757d';
                        
                        echo '<tr>';
                        echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $booking['booking_code'] . '</td>';
                        echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $booking['guest_name'] . '</td>';
                        echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $booking['type_name'] . '</td>';
                        echo '<td style="padding: 10px; border: 1px solid #ddd; color: ' . $color . '; font-weight: bold;">' . $booking['status'] . '</td>';
                        echo '<td style="padding: 10px; border: 1px solid #ddd;">' . date('d/m/Y', strtotime($booking['check_in_date'])) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">Lỗi: ' . $e->getMessage() . '</div>';
            }
            ?>
            
            <a href="?action=menu" class="back-link">← Quay lại</a>
            
        <?php elseif ($action === 'test-status-email'): ?>
            <h2>Test Status Update Email</h2>
            
            <?php
            try {
                $db = getDB();
                
                // Get a confirmed booking
                $stmt = $db->query("
                    SELECT b.*, rt.type_name, rt.category 
                    FROM bookings b 
                    LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                    WHERE b.status = 'confirmed'
                    ORDER BY b.created_at DESC 
                    LIMIT 1
                ");
                $booking = $stmt->fetch();
                
                if (!$booking) {
                    echo '<div class="result error">Không tìm thấy booking đã xác nhận!</div>';
                } else {
                    echo '<div class="result info">';
                    echo '<strong>Booking:</strong> ' . $booking['booking_code'] . '<br>';
                    echo '<strong>Email:</strong> ' . $booking['guest_email'];
                    echo '</div>';
                    
                    // Send status update email
                    $result = sendBookingStatusUpdateEmail($booking, 'pending', 'confirmed');
                    
                    if ($result['success']) {
                        echo '<div class="result success">✅ Email cập nhật trạng thái đã được gửi!</div>';
                    } else {
                        echo '<div class="result error">❌ Gửi email thất bại: ' . $result['message'] . '</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="result error">Lỗi: ' . $e->getMessage() . '</div>';
            }
            ?>
            
            <a href="?action=menu" class="back-link">← Quay lại</a>
            
        <?php endif; ?>
    </div>
</body>
</html>
