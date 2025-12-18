<?php
/**
 * Create Apartment Inquiry API
 * Handles apartment consultation/inquiry form submissions
 * 
 * Aurora Hotel Plaza
 */

session_start();
ob_start();
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../models/ApartmentInquiry.php';
    require_once '../../helpers/language.php';
    initLanguage();

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['room_type_id', 'guest_name', 'guest_email', 'guest_phone'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception(__('inquiry.field_required') . ': ' . $field);
        }
    }

    // Validate email format
    if (!filter_var($data['guest_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception(__('inquiry.invalid_email'));
    }

    // Validate phone format (Vietnamese)
    $phone = preg_replace('/[\s\-\(\)]/', '', $data['guest_phone']);
    if (!preg_match('/^(0|\+84|84)[1-9][0-9]{8,9}$/', $phone)) {
        throw new Exception(__('inquiry.invalid_phone'));
    }

    $db = getDB();
    $inquiryModel = new ApartmentInquiry($db);

    // Verify room type exists and is an apartment with inquiry booking type
    $stmt = $db->prepare("SELECT room_type_id, type_name, category, booking_type FROM room_types WHERE room_type_id = ? AND status = 'active'");
    $stmt->execute([$data['room_type_id']]);
    $roomType = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roomType) {
        throw new Exception(__('inquiry.apartment_not_found'));
    }

    // Optional: Check if this is an inquiry-type room (apartment)
    // if ($roomType['booking_type'] !== 'inquiry') {
    //     throw new Exception(__('inquiry.not_inquiry_type'));
    // }

    // Prepare inquiry data
    $inquiryData = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'room_type_id' => $data['room_type_id'],
        'guest_name' => trim($data['guest_name']),
        'guest_email' => trim($data['guest_email']),
        'guest_phone' => $phone,
        'preferred_check_in' => !empty($data['check_in_date']) ? $data['check_in_date'] : null,
        'preferred_check_out' => !empty($data['check_out_date']) ? $data['check_out_date'] : null,
        'duration_type' => $data['duration_type'] ?? 'short_term',
        'num_adults' => $data['num_adults'] ?? 1,
        'num_children' => $data['num_children'] ?? 0,
        'message' => $data['message'] ?? null,
        'special_requests' => $data['special_requests'] ?? null
    ];

    // Create inquiry
    $result = $inquiryModel->create($inquiryData);

    if (!$result) {
        throw new Exception(__('inquiry.create_failed'));
    }

    // Send notification email to admin (optional)
    try {
        if (function_exists('sendEmail')) {
            $adminEmail = 'info@aurorahotelplaza.com';
            $subject = "[Aurora Hotel] Yêu cầu tư vấn căn hộ mới - {$result['inquiry_code']}";
            $body = "
                <h2>Yêu cầu tư vấn căn hộ mới</h2>
                <p><strong>Mã yêu cầu:</strong> {$result['inquiry_code']}</p>
                <p><strong>Căn hộ:</strong> {$roomType['type_name']}</p>
                <p><strong>Khách hàng:</strong> {$inquiryData['guest_name']}</p>
                <p><strong>Email:</strong> {$inquiryData['guest_email']}</p>
                <p><strong>Điện thoại:</strong> {$inquiryData['guest_phone']}</p>
                <p><strong>Ngày nhận phòng mong muốn:</strong> {$inquiryData['preferred_check_in']}</p>
                <p><strong>Số người lớn:</strong> {$inquiryData['num_adults']}</p>
                <p><strong>Số trẻ em:</strong> {$inquiryData['num_children']}</p>
                <p><strong>Tin nhắn:</strong> {$inquiryData['message']}</p>
            ";
            sendEmail($adminEmail, $subject, $body);
        }
    } catch (Throwable $e) {
        error_log("Failed to send admin notification: " . $e->getMessage());
    }

    // Send confirmation email to customer (optional)
    try {
        if (function_exists('sendEmail')) {
            $subject = "Xác nhận yêu cầu tư vấn - {$result['inquiry_code']} | Aurora Hotel Plaza";
            $body = "
                <h2>Cảm ơn quý khách đã quan tâm đến căn hộ tại Aurora Hotel Plaza!</h2>
                <p>Kính chào <strong>{$inquiryData['guest_name']}</strong>,</p>
                <p>Chúng tôi đã nhận được yêu cầu tư vấn của quý khách về căn hộ <strong>{$roomType['type_name']}</strong>.</p>
                <p><strong>Mã yêu cầu:</strong> {$result['inquiry_code']}</p>
                <p>Đội ngũ tư vấn của chúng tôi sẽ liên hệ với quý khách trong thời gian sớm nhất (trong vòng 24 giờ làm việc).</p>
                <p>Nếu có bất kỳ câu hỏi nào, vui lòng liên hệ:</p>
                <ul>
                    <li>Hotline: (0251) 123 4567</li>
                    <li>Email: info@aurorahotelplaza.com</li>
                </ul>
                <p>Trân trọng,<br>Aurora Hotel Plaza</p>
            ";
            sendEmail($inquiryData['guest_email'], $subject, $body);
        }
    } catch (Throwable $e) {
        error_log("Failed to send customer confirmation: " . $e->getMessage());
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => __('inquiry.success_message'),
        'data' => [
            'inquiry_code' => $result['inquiry_code'],
            'apartment_name' => $roomType['type_name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
