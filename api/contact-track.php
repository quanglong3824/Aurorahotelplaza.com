<?php
/**
 * Contact Tracking API
 * Tra cứu trạng thái yêu cầu liên hệ bằng mã liên hệ
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ yêu cầu
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$code = trim($data['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => __('contact_track.error_empty')]);
    exit;
}

try {
    $db = getDB();
    
    // Tìm kiếm liên hệ theo mã
    $stmt = $db->prepare("
        SELECT * FROM contact_submissions 
        WHERE contact_code = :code OR id = :id 
        LIMIT 1
    ");
    
    // Thử tìm theo mã hoặc ID (để tương thích ngược)
    $id_search = is_numeric($code) ? (int)$code : 0;
    $stmt->execute([':code' => $code, ':id' => $id_search]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contact) {
        echo json_encode(['success' => false, 'message' => __('contact_track.error_not_found')]);
        exit;
    }

    // Trả về thông tin trạng thái
    echo json_encode([
        'success' => true,
        'data' => [
            'contact_code' => $contact['contact_code'] ?? str_pad($contact['id'], 8, '0', STR_PAD_LEFT),
            'name' => $contact['name'],
            'subject' => $contact['subject'],
            'status' => $contact['status'], // 'new', 'processing', 'replied', 'closed'
            'created_at' => date('m/d/Y H:i', strtotime($contact['created_at'])),
            'message_preview' => mb_substr($contact['message'], 0, 100) . '...'
        ]
    ]);

} catch (Exception $e) {
    error_log("Contact tracking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => __('contact_track.error_system')]);
}
