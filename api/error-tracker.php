<?php
/**
 * Aurora Hotel Plaza - Error Tracker API
 * Nhận lỗi JS từ frontend và lưu vào DB
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/error-tracker.php';

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Thêm thông tin browser
$input['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (session_status() !== PHP_SESSION_NONE || session_start()) {
    $input['session_id'] = session_id();
    $input['user_id'] = $_SESSION['user_id'] ?? null;
}

// Ghi lỗi
$errorId = AuroraErrorTracker::captureJsError($input);

echo json_encode([
    'success' => true,
    'error_id' => $errorId,
    'message' => 'Error logged successfully',
]);
