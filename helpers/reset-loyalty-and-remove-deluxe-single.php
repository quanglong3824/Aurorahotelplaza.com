,<?php
/**
 * Helper: Ẩn Deluxe Single khỏi form booking + Reset điểm thưởng về 0
 * Chạy 1 lần trên production rồi XOÁ FILE NÀY ĐI.
 * URL: https://aurorahotelplaza.com/helpers/reset-loyalty-and-remove-deluxe-single.php
 */

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
    die('<h1>⚠️ Helper: Ẩn Deluxe Single & Reset điểm thưởng</h1>
         <p>Thêm <code>?confirm=1</code> vào URL để thực thi.</p>
         <p><a href="?confirm=1">Nhấn vào đây để xác nhận</a></p>');
}

header('Content-Type: text/html; charset=utf-8');
$db = getDB();
if (!$db) {
    die('❌ Không thể kết nối database.');
}

$results = [];

// =============================================
// 1. RESET TOÀN BỘ ĐIỂM THƯỞNG VỀ 0
// =============================================
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_loyalty");
    $total = $stmt->fetch()['total'];

    $stmt = $db->prepare("UPDATE user_loyalty SET current_points = 0, lifetime_points = 0");
    $stmt->execute();
    $affected = $stmt->rowCount();

    $results['loyalty_reset'] = "✅ Đã reset <strong>{$affected}</strong> user về 0 điểm (tổng {$total} user).";
} catch (PDOException $e) {
    $results['loyalty_reset'] = "❌ Lỗi reset điểm: " . htmlspecialchars($e->getMessage());
}

// =============================================
// 2. XOÁ GIAO DỊCH ĐIỂM (points_transactions)
// =============================================
try {
    $stmt = $db->prepare("DELETE FROM points_transactions");
    $stmt->execute();
    $deleted = $stmt->rowCount();

    $results['transactions_deleted'] = "✅ Đã xoá <strong>{$deleted}</strong> giao dịch điểm.";
} catch (PDOException $e) {
    $results['transactions_deleted'] = "❌ Lỗi xoá giao dịch: " . htmlspecialchars($e->getMessage());
}

// =============================================
// 3. ẨN DELUXE SINGLE KHỎI FORM BOOKING
//    (Set status = 'inactive' → query booking tự lọc ra)
// =============================================
try {
    $stmt = $db->prepare("SELECT room_type_id, type_name, slug, status FROM room_types WHERE LOWER(type_name) LIKE '%deluxe single%' OR LOWER(slug) LIKE '%deluxe-single%'");
    $stmt->execute();
    $deluxeSingleTypes = $stmt->fetchAll();

    if (count($deluxeSingleTypes) > 0) {
        $ids = [];
        foreach ($deluxeSingleTypes as $rt) {
            $ids[] = $rt['room_type_id'];
        }
        $idList = implode(',', $ids);

        // Chỉ set inactive, KHÔNG XOÁ dữ liệu
        $stmt = $db->prepare("UPDATE room_types SET status = 'inactive' WHERE room_type_id IN ($idList)");
        $stmt->execute();

        $results['deluxe_single'] = "✅ Đã ẩn <strong>" . count($deluxeSingleTypes) . "</strong> loại phòng Deluxe Single khỏi form booking (set status = 'inactive'):<br>";
        foreach ($deluxeSingleTypes as $rt) {
            $results['deluxe_single'] .= "&nbsp;&nbsp;• ID {$rt['room_type_id']}: <strong>{$rt['type_name']}</strong> (slug: {$rt['slug']}, status cũ: {$rt['status']})<br>";
        }
        $results['deluxe_single'] .= "<br><em>⚠️ Dữ liệu room_types và rooms vẫn giữ nguyên. Chỉ ẩn khỏi form booking. Có thể khôi phục bằng cách set status = 'active'.</em>";
    } else {
        $results['deluxe_single'] = "ℹ️ Không tìm thấy loại phòng nào tên 'Deluxe Single' trong CSDL.";
    }
} catch (PDOException $e) {
    $results['deluxe_single'] = "❌ Lỗi xử lý Deluxe Single: " . htmlspecialchars($e->getMessage());
}

// =============================================
// 4. VÔ HIỆU HOÁ TÍCH ĐIỂM (set points_per_vnd = 0)
// =============================================
try {
    $stmt = $db->prepare("UPDATE system_settings SET setting_value = '0' WHERE setting_key = 'points_per_vnd'");
    $stmt->execute();
    $results['points_disabled'] = "✅ Đã set points_per_vnd = 0 (không tích điểm nữa).";
} catch (PDOException $e) {
    $results['points_disabled'] = "ℹ️ Lỗi hoặc không tìm thấy setting points_per_vnd: " . htmlspecialchars($e->getMessage());
}

// =============================================
// OUTPUT KẾT QUẢ
// =============================================
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Ẩn Deluxe Single & Reset điểm thưởng</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1a1a2e; }
        h2 { color: #16213e; font-size: 18px; margin-top: 0; }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .info { color: #6b7280; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Kết quả thực thi</h1>

    <?php foreach ($results as $key => $message): ?>
        <div class="card">
            <h2><?php echo ucfirst(str_replace('_', ' ', $key)); ?></h2>
            <p class="<?php echo strpos($message, '❌') !== false ? 'error' : (strpos($message, 'ℹ️') !== false ? 'info' : 'success'); ?>">
                <?php echo $message; ?>
            </p>
        </div>
    <?php endforeach; ?>

    <div class="warning">
        <strong>⚠️ QUAN TRỌNG:</strong> Sau khi kiểm tra xong, hãy <strong>XOÁ FILE NÀY</strong> khỏi server để đảm bảo bảo mật.
    </div>
</body>
</html>
