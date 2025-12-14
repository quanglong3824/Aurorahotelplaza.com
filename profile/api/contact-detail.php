<?php
/**
 * API lấy chi tiết liên hệ của user
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_id = (int)($_GET['id'] ?? 0);

if (!$contact_id) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    // Lấy contact của user hiện tại
    $stmt = $db->prepare("
        SELECT 
            c.*,
            COALESCE(c.contact_code, LPAD(c.id, 8, '0')) as display_code
        FROM contact_submissions c
        WHERE c.id = :id AND c.user_id = :user_id
    ");
    $stmt->execute([':id' => $contact_id, ':user_id' => $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy liên hệ']);
        exit;
    }
    
    // Format date
    $contact['created_at'] = date('d/m/Y H:i', strtotime($contact['created_at']));
    if ($contact['updated_at']) {
        $contact['updated_at'] = date('d/m/Y H:i', strtotime($contact['updated_at']));
    }
    
    echo json_encode(['success' => true, 'contact' => $contact]);
    
} catch (Exception $e) {
    error_log("Contact detail API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
