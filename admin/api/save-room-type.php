<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../room-types.php');
    exit;
}

$room_type_id = $_POST['room_type_id'] ?? null;
$type_name = trim($_POST['type_name'] ?? '');
$category = $_POST['category'] ?? '';
$base_price = $_POST['base_price'] ?? 0;
$max_occupancy = $_POST['max_occupancy'] ?? 2;
$size_sqm = $_POST['size_sqm'] ?? null;
$bed_type = trim($_POST['bed_type'] ?? '');
$short_description = trim($_POST['short_description'] ?? '');
$description = trim($_POST['description'] ?? '');
$amenities = trim($_POST['amenities'] ?? '');
$thumbnail = trim($_POST['thumbnail'] ?? '');
$sort_order = $_POST['sort_order'] ?? 0;
$status = $_POST['status'] ?? 'active';

// Tạo slug từ type_name
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

$slug = createSlug($type_name);

if (empty($type_name) || empty($category) || $base_price <= 0) {
    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin';
    header('Location: ../room-type-form.php' . ($room_type_id ? '?id=' . $room_type_id : ''));
    exit;
}

try {
    $db = getDB();
    
    if ($room_type_id) {
        // Update
        $stmt = $db->prepare("
            UPDATE room_types SET
                type_name = :type_name,
                slug = :slug,
                category = :category,
                base_price = :base_price,
                max_occupancy = :max_occupancy,
                size_sqm = :size_sqm,
                bed_type = :bed_type,
                short_description = :short_description,
                description = :description,
                amenities = :amenities,
                thumbnail = :thumbnail,
                sort_order = :sort_order,
                status = :status,
                updated_at = NOW()
            WHERE room_type_id = :room_type_id
        ");
        
        $stmt->execute([
            ':type_name' => $type_name,
            ':slug' => $slug,
            ':category' => $category,
            ':base_price' => $base_price,
            ':max_occupancy' => $max_occupancy,
            ':size_sqm' => $size_sqm,
            ':bed_type' => $bed_type,
            ':short_description' => $short_description,
            ':description' => $description,
            ':amenities' => $amenities,
            ':thumbnail' => $thumbnail ?: null,
            ':sort_order' => $sort_order,
            ':status' => $status,
            ':room_type_id' => $room_type_id
        ]);
        
        $_SESSION['success'] = 'Cập nhật loại phòng thành công';
        
    } else {
        // Insert - Check slug uniqueness
        $stmt = $db->prepare("SELECT room_type_id FROM room_types WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        
        if ($stmt->fetch()) {
            // Slug exists, add number
            $counter = 1;
            $original_slug = $slug;
            while (true) {
                $slug = $original_slug . '-' . $counter;
                $stmt = $db->prepare("SELECT room_type_id FROM room_types WHERE slug = :slug");
                $stmt->execute([':slug' => $slug]);
                if (!$stmt->fetch()) break;
                $counter++;
            }
        }
        
        $stmt = $db->prepare("
            INSERT INTO room_types (
                type_name, slug, category, base_price, max_occupancy, size_sqm, bed_type,
                short_description, description, amenities, thumbnail, sort_order, status, created_at
            ) VALUES (
                :type_name, :slug, :category, :base_price, :max_occupancy, :size_sqm, :bed_type,
                :short_description, :description, :amenities, :thumbnail, :sort_order, :status, NOW()
            )
        ");
        
        $stmt->execute([
            ':type_name' => $type_name,
            ':slug' => $slug,
            ':category' => $category,
            ':base_price' => $base_price,
            ':max_occupancy' => $max_occupancy,
            ':size_sqm' => $size_sqm,
            ':bed_type' => $bed_type,
            ':short_description' => $short_description,
            ':description' => $description,
            ':amenities' => $amenities,
            ':thumbnail' => $thumbnail ?: null,
            ':sort_order' => $sort_order,
            ':status' => $status
        ]);
        
        $_SESSION['success'] = 'Thêm loại phòng thành công';
    }
    
    header('Location: ../room-types.php');
    exit;
    
} catch (Exception $e) {
    error_log("Save room type error: " . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../room-type-form.php' . ($room_type_id ? '?id=' . $room_type_id : ''));
    exit;
}
