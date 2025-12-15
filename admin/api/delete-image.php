<?php
/**
 * API: Delete image from server
 */
session_start();
header('Content-Type: application/json');

// Check admin login - same as upload-image.php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
    exit;
}

$filename = $_POST['filename'] ?? '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Filename required']);
    exit;
}

// Sanitize filename - prevent directory traversal
$filename = basename($filename);
$filepath = '../../uploads/' . $filename;

if (!file_exists($filepath)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Check if it's actually in uploads folder
$realpath = realpath($filepath);
$uploadsDir = realpath('../../uploads');

if (strpos($realpath, $uploadsDir) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid file path']);
    exit;
}

// Delete file
if (unlink($filepath)) {
    echo json_encode(['success' => true, 'message' => 'File deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
}
