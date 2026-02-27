<?php
/**
 * Delete Customer API
 * Xóa vĩnh viễn khách hàng và tất cả dữ liệu liên quan
 * CHỈ ADMIN MỚI CÓ QUYỀN
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';
require_once '../../helpers/activity-logger.php';

// Chỉ admin mới được xóa user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền xóa khách hàng']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = (int) ($_POST['user_id'] ?? $_REQUEST['user_id'] ?? 0);

// QUAN TRỌNG: Không cho phép xóa user_id = 0 vì có thể xóa nhiều users
if ($user_id === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể xóa user với ID = 0. Database cần được sửa trước (thiếu AUTO_INCREMENT). Vui lòng kiểm tra cấu trúc bảng users trong phpMyAdmin.'
    ]);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();

    // Kiểm tra user tồn tại và không phải admin
    $stmt = $db->prepare("SELECT user_id, email, full_name, user_role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
        exit;
    }

    // Không cho phép xóa admin hoặc staff
    if (in_array($user['user_role'], ['admin', 'receptionist', 'sale'])) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản nhân viên/admin']);
        exit;
    }

    // Không cho phép tự xóa chính mình
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Không thể tự xóa tài khoản của mình']);
        exit;
    }

    // Bắt đầu transaction
    $db->beginTransaction();

    // Tắt foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Danh sách các bảng cần xóa dữ liệu liên quan đến user
    $deleted_counts = [];

    // 1. Xóa service_bookings liên quan đến bookings của user
    try {
        $stmt = $db->prepare("
            DELETE sb FROM service_bookings sb
            INNER JOIN bookings b ON sb.booking_id = b.booking_id
            WHERE b.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $deleted_counts['service_bookings'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['service_bookings'] = 0;
    }

    // 2. Xóa booking_services liên quan đến bookings của user (nếu bảng tồn tại)
    try {
        $stmt = $db->prepare("
            DELETE bs FROM booking_services bs
            INNER JOIN bookings b ON bs.booking_id = b.booking_id
            WHERE b.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $deleted_counts['booking_services'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['booking_services'] = 0;
    }

    // 3. Xóa payments liên quan đến bookings của user
    try {
        $stmt = $db->prepare("
            DELETE p FROM payments p
            INNER JOIN bookings b ON p.booking_id = b.booking_id
            WHERE b.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $deleted_counts['payments'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['payments'] = 0;
    }

    // 4. Xóa bookings của user
    try {
        $stmt = $db->prepare("DELETE FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['bookings'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['bookings'] = 0;
    }

    // 5. Xóa user_loyalty
    try {
        $stmt = $db->prepare("DELETE FROM user_loyalty WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['user_loyalty'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['user_loyalty'] = 0;
    }

    // 6. Xóa reviews của user
    try {
        $stmt = $db->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['reviews'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['reviews'] = 0;
    }

    // 7. Xóa notifications của user
    try {
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['notifications'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['notifications'] = 0;
    }

    // 8. Xóa contact_submissions của user (nếu có cột user_id)
    try {
        $stmt = $db->prepare("DELETE FROM contact_submissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['contact_submissions'] = $stmt->rowCount();
    } catch (Exception $e) {
        // Bỏ qua nếu cột user_id không tồn tại
        $deleted_counts['contact_submissions'] = 0;
    }

    // 9. Xóa blog_comments của user
    try {
        $stmt = $db->prepare("DELETE FROM blog_comments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['blog_comments'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['blog_comments'] = 0;
    }

    // 10. Cập nhật activity_logs - set user_id = NULL thay vì xóa (để giữ lịch sử)
    try {
        $stmt = $db->prepare("UPDATE activity_logs SET user_id = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_counts['activity_logs_updated'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['activity_logs_updated'] = 0;
    }

    // 11. Cuối cùng, xóa user
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $deleted_counts['users'] = $stmt->rowCount();

    // Bật lại foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Commit transaction
    $db->commit();

    // Log activity
    if (function_exists('logActivity')) {
        $log_details = json_encode([
            'deleted_user_id' => $user_id,
            'deleted_email' => $user['email'],
            'deleted_name' => $user['full_name'],
            'deleted_counts' => $deleted_counts
        ]);
        logActivity(
            'delete_customer',
            'user',
            $user_id,
            "Xóa vĩnh viễn khách hàng: {$user['full_name']} ({$user['email']}). Chi tiết: {$log_details}"
        );
    }

    echo json_encode([
        'success' => true,
        'message' => "Đã xóa khách hàng {$user['full_name']} và tất cả dữ liệu liên quan",
        'deleted_counts' => $deleted_counts
    ]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Delete customer error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa khách hàng. Vui lòng thử lại.'
    ]);
}
