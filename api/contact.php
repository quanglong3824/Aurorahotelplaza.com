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

    // Generate contact code/submission id beforehand
    $random_id = mt_rand(10000000, 99999999);
    $contact_code = str_pad($random_id, 8, '0', STR_PAD_LEFT);
    $submission_id_val = $random_id;

    // Kiểm tra xem cột user_id và submission_id có tồn tại không
    // (Checked dynamically to prevent breaking on older schemas)
    $columns_query = $db->query("SHOW COLUMNS FROM contact_submissions");
    $columns = $columns_query->fetchAll(PDO::FETCH_COLUMN);

    $has_user_id = in_array('user_id', $columns);
    $has_contact_code = in_array('contact_code', $columns);
    $has_submission_id = in_array('submission_id', $columns);

    // Build Dynamic Query
    $fields = ['name', 'email', 'phone', 'subject', 'message', 'ip_address', 'status', 'created_at'];
    $values = [
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ip_address
    ];

    if ($has_user_id) {
        $fields[] = 'user_id';
        $values[':user_id'] = $user_id;
    }

    if ($has_contact_code) {
        $fields[] = 'contact_code';
        $values[':contact_code'] = $contact_code;
    }

    // Fix: submission_id is NOT NULL in some schemas
    if ($has_submission_id) {
        $fields[] = 'submission_id';
        $values[':submission_id'] = $submission_id_val;
    }

    $field_str = implode(', ', $fields);
    $value_str = implode(', ', array_keys($values));

    $stmt = $db->prepare("INSERT INTO contact_submissions ($field_str) VALUES ($value_str, NOW())");
    $stmt->execute($values);

    $db_id = $db->lastInsertId();

    // Nếu bảng không có contact_code, dùng ID làm mã
    if (!$has_contact_code) {
        $contact_code = str_pad($db_id, 8, '0', STR_PAD_LEFT);
    }

    // Dùng contact_code cho email và response
    $submission_id = $contact_code;

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
