<?php
/**
 * Contact Form API
 * Xử lý gửi liên hệ từ khách hàng
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../config/environment.php';
require_once '../helpers/mailer.php';
require_once '../helpers/activity-logger.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ form
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? 'Liên hệ chung');
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

// Validate dữ liệu
$errors = [];

if (empty($name)) {
    $errors[] = 'Vui lòng nhập họ và tên';
}

if (empty($email)) {
    $errors[] = 'Vui lòng nhập email';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email không hợp lệ';
}

if (empty($phone)) {
    $errors[] = 'Vui lòng nhập số điện thoại';
} elseif (!preg_match('/^[0-9]{10,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
    $errors[] = 'Số điện thoại không hợp lệ';
}

if (empty($message)) {
    $errors[] = 'Vui lòng nhập nội dung tin nhắn';
} elseif (strlen($message) < 10) {
    $errors[] = 'Nội dung tin nhắn quá ngắn (tối thiểu 10 ký tự)';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Lấy IP address
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

try {
    $db = getDB();
    
    // Kiểm tra xem cột user_id có tồn tại không
    $columns = $db->query("SHOW COLUMNS FROM contact_submissions LIKE 'user_id'")->fetchAll();
    $has_user_id = count($columns) > 0;
    
    // Lưu vào database
    if ($has_user_id) {
        $stmt = $db->prepare("
            INSERT INTO contact_submissions (name, email, phone, subject, message, ip_address, user_id, status, created_at)
            VALUES (:name, :email, :phone, :subject, :message, :ip_address, :user_id, 'new', NOW())
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':subject' => $subject,
            ':message' => $message,
            ':ip_address' => $ip_address,
            ':user_id' => $user_id
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO contact_submissions (name, email, phone, subject, message, ip_address, status, created_at)
            VALUES (:name, :email, :phone, :subject, :message, :ip_address, 'new', NOW())
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':subject' => $subject,
            ':message' => $message,
            ':ip_address' => $ip_address
        ]);
    }
    
    $submission_id = $db->lastInsertId();
    
    // Load contact email templates
    require_once '../includes/email-templates/contact-templates.php';
    
    // Gửi email xác nhận cho khách hàng
    $mailer = getMailer();
    
    $customerEmailData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message,
        'submission_id' => $submission_id,
        'created_at' => date('d/m/Y H:i'),
        'user_id' => $user_id
    ];
    
    $customerSubject = "Xác nhận liên hệ - Aurora Hotel Plaza";
    $customerBody = ContactEmailTemplates::getCustomerConfirmationTemplate($customerEmailData);
    $customerSent = $mailer->send($email, $customerSubject, $customerBody);
    
    // Gửi email thông báo cho khách sạn
    $hotelEmail = 'info@aurorahotelplaza.com';
    $hotelSubject = "[Liên hệ mới #{$submission_id}] {$subject} - {$name}";
    $hotelBody = ContactEmailTemplates::getHotelNotificationTemplate($customerEmailData);
    $hotelSent = $mailer->send($hotelEmail, $hotelSubject, $hotelBody);
    
    // Log activity
    if (function_exists('logActivity')) {
        logActivity('contact_submit', 'contact', $submission_id, "Khách hàng {$name} gửi liên hệ: {$subject}");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Gửi liên hệ thành công! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.',
        'submission_id' => $submission_id,
        'email_sent' => $customerSent
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi gửi liên hệ. Vui lòng thử lại sau.'
    ]);
}
