<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/environment.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/error-tracker.php';
AuroraErrorTracker::init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? trim($input['query']) : '';
$mode = isset($input['mode']) ? $input['mode'] : 'latest'; // 'latest' or 'all'

if (empty($query)) {
    echo json_encode(['success' => false, 'error_code' => 'empty']);
    exit;
}

try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu");
    }

    // Search by booking code, email or phone
    $limitSql = ($mode === 'all') ? "" : "LIMIT 1";
    $stmt = $conn->prepare("
        SELECT booking_code, status, total_amount, created_at, check_in_date, check_out_date, 
               guest_name, guest_email, guest_phone
        FROM bookings
        WHERE booking_code = ? OR guest_email = ? OR guest_phone = ?
        ORDER BY created_at DESC
        $limitSql
    ");
    $stmt->execute([$query, $query, $query]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        $bookings = [];
        foreach ($rows as $row) {
            $customer_name = trim($row['guest_name']);
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

            $bookings[] = [
                'booking_code' => $row['booking_code'],
                'status' => $status_text,
                'status_raw' => $row['status'],
                'customer_name' => $customer_name,
                'email' => $row['guest_email'],
                'phone' => $row['guest_phone'],
                'check_in' => date('m/d/Y', strtotime($row['check_in_date'])),
                'check_out' => date('m/d/Y', strtotime($row['check_out_date'])),
                'total_amount' => $row['total_amount'],
                'created_at' => date('m/d/Y H:i', strtotime($row['created_at']))
            ];
        }

        echo json_encode(['success' => true, 'bookings' => $bookings]);
    } else {
        echo json_encode(['success' => false, 'error_code' => 'not_found']);
    }
} catch (Exception $e) {
    // Ghi lỗi vào AI Bug Tracker và gửi Telegram
    if (class_exists('AuroraErrorTracker')) {
        AuroraErrorTracker::captureDbError(
            $e->getMessage(),
            "SELECT ... guest_nickname FROM bookings WHERE ...",
            ['query_input' => substr($query ?? '', 0, 50)]
        );
    }
    error_log("Tracking Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error_code' => 'system', 'message' => $e->getMessage()]);
}
