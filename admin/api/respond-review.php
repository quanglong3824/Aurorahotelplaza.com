<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$review_id = $_POST['review_id'] ?? null;
$response = trim($_POST['response'] ?? '');

if (!$review_id || empty($response)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO review_responses (review_id, user_id, response, created_at)
        VALUES (:review_id, :user_id, :response, NOW())
    ");
    
    $stmt->execute([
        ':review_id' => $review_id,
        ':user_id' => $_SESSION['user_id'],
        ':response' => $response
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Phản hồi thành công']);
    
} catch (Exception $e) {
    error_log("Respond review error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
