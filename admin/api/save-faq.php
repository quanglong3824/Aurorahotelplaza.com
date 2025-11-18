<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$faq_id = $_POST['faq_id'] ?? null;
$question = trim($_POST['question'] ?? '');
$answer = trim($_POST['answer'] ?? '');
$category = trim($_POST['category'] ?? '');
$sort_order = $_POST['sort_order'] ?? 0;
$status = $_POST['status'] ?? 'active';

if (empty($question) || empty($answer)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

try {
    $db = getDB();
    
    if ($faq_id) {
        // Update
        $stmt = $db->prepare("
            UPDATE faqs SET
                question = :question,
                answer = :answer,
                category = :category,
                sort_order = :sort_order,
                status = :status,
                updated_at = NOW()
            WHERE faq_id = :faq_id
        ");
        
        $stmt->execute([
            ':question' => $question,
            ':answer' => $answer,
            ':category' => $category,
            ':sort_order' => $sort_order,
            ':status' => $status,
            ':faq_id' => $faq_id
        ]);
        
        $message = 'Cập nhật câu hỏi thành công';
        
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO faqs (
                question, answer, category, sort_order, status, created_at
            ) VALUES (
                :question, :answer, :category, :sort_order, :status, NOW()
            )
        ");
        
        $stmt->execute([
            ':question' => $question,
            ':answer' => $answer,
            ':category' => $category,
            ':sort_order' => $sort_order,
            ':status' => $status
        ]);
        
        $faq_id = $db->lastInsertId();
        $message = 'Thêm câu hỏi thành công';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'faq_id' => $faq_id
    ]);
    
} catch (Exception $e) {
    error_log("Save FAQ error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
