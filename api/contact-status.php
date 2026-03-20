<?php
/**
 * Contact Status Lookup API
 * Tra cứu trạng thái yêu cầu liên hệ bằng mã liên hệ
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../helpers/language.php';

// Chỉ chấp nhận GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$code = trim($_GET['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã liên hệ']);
    exit;
}

try {
    $db = getDB();
    
    // Tìm theo contact_code hoặc ID (đề phòng trường hợp contact_code chưa được gán)
    $stmt = $db->prepare("
        SELECT status, created_at, subject, name, response_content, responded_at
        FROM contact_submissions 
        WHERE contact_code = :code OR id = :id_from_code
        LIMIT 1
    ");
    
    // Thử ép kiểu code về int nếu nó là số thuần túy để check ID
    $id_from_code = is_numeric($code) ? (int)$code : 0;
    
    $stmt->execute([
        ':code' => $code,
        ':id_from_code' => $id_from_code
    ]);
    
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($submission) {
        // Map status to readable text
        $status_map = [
            'new' => ['text' => 'Mới nhận', 'color' => 'blue', 'icon' => 'mark_email_unread'],
            'processing' => ['text' => 'Đang xử lý', 'color' => 'amber', 'icon' => 'pending'],
            'responded' => ['text' => 'Đã phản hồi', 'color' => 'emerald', 'icon' => 'mark_email_read'],
            'closed' => ['text' => 'Đã đóng', 'color' => 'slate', 'icon' => 'check_circle']
        ];
        
        $current_status = $status_map[$submission['status']] ?? ['text' => $submission['status'], 'color' => 'slate', 'icon' => 'info'];

        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $submission['name'],
                'subject' => $submission['subject'],
                'status' => $current_status['text'],
                'status_key' => $submission['status'],
                'color' => $current_status['color'],
                'icon' => $current_status['icon'],
                'created_at' => date('d/m/Y H:i', strtotime($submission['created_at'])),
                'has_response' => !empty($submission['response_content']),
                'response' => $submission['response_content'] ?? null,
                'responded_at' => $submission['responded_at'] ? date('d/m/Y H:i', strtotime($submission['responded_at'])) : null
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy yêu cầu liên hệ với mã này. Vui lòng kiểm tra lại.'
        ]);
    }

} catch (Exception $e) {
    error_log("Contact Status Lookup Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.'
    ]);
}
