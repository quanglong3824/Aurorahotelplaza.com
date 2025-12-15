<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File quá lớn (vượt quá giới hạn server).',
        UPLOAD_ERR_FORM_SIZE => 'File quá lớn.',
        UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần.',
        UPLOAD_ERR_NO_FILE => 'Không có file nào được upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm.',
        UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file.',
        UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension.',
    ];
    $error_code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $error_messages[$error_code] ?? 'Lỗi upload không xác định.';
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng file không được hỗ trợ. Chỉ chấp nhận: JPG, PNG, GIF, WebP, SVG.']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File quá lớn! Tối đa 5MB.']);
    exit;
}

// Generate unique filename
$ext_map = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];
$ext = $ext_map[$mime_type] ?? 'jpg';
$filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

// Upload directory
$upload_dir = realpath(__DIR__ . '/../../uploads');
if (!$upload_dir) {
    // Create uploads directory if not exists
    $upload_dir = __DIR__ . '/../../uploads';
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo thư mục uploads.']);
        exit;
    }
    $upload_dir = realpath($upload_dir);
}

$target_path = $upload_dir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu file. Vui lòng thử lại.']);
    exit;
}

// Return success with URL
$url = '../uploads/' . $filename;

echo json_encode([
    'success' => true,
    'message' => 'Upload thành công!',
    'url' => $url,
    'filename' => $filename,
]);
