<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/environment.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? trim($input['query']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập thông tin tìm kiếm.']);
    exit;
}

try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu");
    }

    // Search by booking code, email or phone
    $stmt = $conn->prepare("
        SELECT id, booking_code, status, total_amount, created_at, check_in, check_out, 
               first_name, last_name, email, phone
        FROM bookings
        WHERE booking_code = ? OR email = ? OR phone = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$query, $query, $query]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $customer_name = trim($row['last_name'] . ' ' . $row['first_name']);

        $status_text = $row['status'];
        switch ($row['status']) {
            case 'pending':
                $status_text = 'Chờ xác nhận';
                break;
            case 'confirmed':
                $status_text = 'Đã xác nhận';
                break;
            case 'checked_in':
                $status_text = 'Đang ở';
                break;
            case 'checked_out':
                $status_text = 'Đã trả phòng';
                break;
            case 'cancelled':
                $status_text = 'Đã hủy';
                break;
            case 'no_show':
                $status_text = 'Không đến';
                break;
        }

        echo json_encode([
            'success' => true,
            'booking' => [
                'booking_code' => $row['booking_code'],
                'status' => $status_text,
                'status_raw' => $row['status'],
                'customer_name' => $customer_name,
                'email' => $row['email'],
                'phone' => $row['phone'],
                'check_in' => date('d/m/Y', strtotime($row['check_in'])),
                'check_out' => date('d/m/Y', strtotime($row['check_out'])),
                'total_amount' => $row['total_amount'],
                'created_at' => date('d/m/Y H:i', strtotime($row['created_at']))
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin đặt phòng phù hợp.']);
    }
} catch (Exception $e) {
    error_log("Tracking Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống.']);
}
