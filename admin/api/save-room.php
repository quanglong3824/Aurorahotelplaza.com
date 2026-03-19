<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../rooms.php');
    exit;
}

$room_id = $_POST['room_id'] ?? null;
$room_number = trim($_POST['room_number'] ?? '');
$room_type_id = $_POST['room_type_id'] ?? null;
$floor = $_POST['floor'] ?? null;
$building = trim($_POST['building'] ?? '');
$status = $_POST['status'] ?? 'available';
$last_cleaned = $_POST['last_cleaned'] ?? null;
$notes = trim($_POST['notes'] ?? '');

if (empty($room_number) || empty($room_type_id)) {
    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin';
    header('Location: ../room-form.php' . ($room_id ? '?id=' . $room_id : ''));
    exit;
}

try {
    $db = getDB();
    
    // Check duplicate room number
    if ($room_id) {
        $stmt = $db->prepare("SELECT room_id FROM rooms WHERE room_number = :number AND room_id != :id");
        $stmt->execute([':number' => $room_number, ':id' => $room_id]);
    } else {
        $stmt = $db->prepare("SELECT room_id FROM rooms WHERE room_number = :number");
        $stmt->execute([':number' => $room_number]);
    }
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Số phòng đã tồn tại';
        header('Location: ../room-form.php' . ($room_id ? '?id=' . $room_id : ''));
        exit;
    }
    
    if ($room_id) {
        // Update
        $stmt = $db->prepare("
            UPDATE rooms SET
                room_number = :room_number,
                room_type_id = :room_type_id,
                floor = :floor,
                building = :building,
                status = :status,
                last_cleaned = :last_cleaned,
                notes = :notes,
                updated_at = NOW()
            WHERE room_id = :room_id
        ");
        
        $stmt->execute([
            ':room_number' => $room_number,
            ':room_type_id' => $room_type_id,
            ':floor' => $floor,
            ':building' => $building,
            ':status' => $status,
            ':last_cleaned' => $last_cleaned,
            ':notes' => $notes,
            ':room_id' => $room_id
        ]);
        
        $_SESSION['success'] = 'Cập nhật phòng thành công';
        
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO rooms (
                room_number, room_type_id, floor, building, status, last_cleaned, notes, created_at
            ) VALUES (
                :room_number, :room_type_id, :floor, :building, :status, :last_cleaned, :notes, NOW()
            )
        ");
        
        $stmt->execute([
            ':room_number' => $room_number,
            ':room_type_id' => $room_type_id,
            ':floor' => $floor,
            ':building' => $building,
            ':status' => $status,
            ':last_cleaned' => $last_cleaned,
            ':notes' => $notes
        ]);
        
        $_SESSION['success'] = 'Thêm phòng thành công';
    }
    
    header('Location: ../rooms.php');
    exit;
    
} catch (Exception $e) {
    error_log("Save room error: " . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: ../room-form.php' . ($room_id ? '?id=' . $room_id : ''));
    exit;
}
