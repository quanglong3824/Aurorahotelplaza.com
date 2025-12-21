<?php
/**
 * API for Apartment Inquiries Management
 */
session_start();
require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';

header('Content-Type: application/json');

// Check authentication
AuthMiddleware::requireStaff();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {
        case 'get':
            // Get single inquiry details
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT b.*, rt.type_name, rt.thumbnail,
                       u.full_name as user_name, u.phone as user_phone
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
                LEFT JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ? AND b.booking_type = 'inquiry'
            ");
            $stmt->execute([$id]);
            $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inquiry) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu']);
                exit;
            }

            // Format dates for display
            $inquiry['check_in_date'] = date('d/m/Y', strtotime($inquiry['check_in_date']));
            $inquiry['created_at'] = date('d/m/Y H:i', strtotime($inquiry['created_at']));

            echo json_encode(['success' => true, 'inquiry' => $inquiry]);
            break;

        case 'update_status':
            // Update inquiry status
            $booking_id = (int) ($_POST['booking_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }

            $valid_statuses = ['pending', 'contacted', 'confirmed', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
                exit;
            }

            // Get current status
            $stmt = $db->prepare("SELECT status FROM bookings WHERE booking_id = ? AND booking_type = 'inquiry'");
            $stmt->execute([$booking_id]);
            $current = $stmt->fetch();

            if (!$current) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu']);
                exit;
            }

            $old_status = $current['status'];

            // Update status
            $stmt = $db->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE booking_id = ?");
            $stmt->execute([$status, $booking_id]);

            // Add to booking history
            $user_id = $_SESSION['user_id'] ?? null;
            $history_notes = $notes ? "Ghi chú: $notes" : null;

            $stmt = $db->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$booking_id, $old_status, $status, $user_id, $history_notes]);

            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            break;

        case 'list':
            // Get list of inquiries (for AJAX loading)
            $status_filter = $_GET['status'] ?? 'all';
            $page = (int) ($_GET['page'] ?? 1);
            $per_page = 20;
            $offset = ($page - 1) * $per_page;

            $where = "booking_type = 'inquiry'";
            $params = [];

            if ($status_filter !== 'all') {
                $where .= " AND status = ?";
                $params[] = $status_filter;
            }

            $stmt = $db->prepare("
                SELECT b.*, rt.type_name
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
                WHERE $where
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $per_page;
            $params[] = $offset;
            $stmt->execute($params);
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'inquiries' => $inquiries]);
            break;

        case 'stats':
            // Get inquiry statistics
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM bookings
                WHERE booking_type = 'inquiry'
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }

} catch (Exception $e) {
    error_log("Apartment inquiries API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
