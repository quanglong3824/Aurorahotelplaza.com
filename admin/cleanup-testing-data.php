<?php
/**
 * Cleanup Testing Data - Aurora Hotel Plaza
 * Xóa TẤT CẢ dữ liệu có chứa từ khóa TEST/test/testing trên mọi bảng
 * CHỈ ADMIN MỚI CÓ QUYỀN
 */

require_once __DIR__ . '/../config/database.php';
session_start();

$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$isAdmin) {
    die('<h2>Access Denied</h2><p>Bạn cần đăng nhập admin để chạy script này.</p><a href="/auth/login.php">Đăng nhập</a>');
}

$conn = getDB();
if (!$conn) {
    die('<h2>Database Connection Failed</h2>');
}

// Pattern tìm kiếm TEST (case-insensitive)
$testPattern  = '%test%';      // khớp: test, TEST, testing, tester, test123...
$testPattern2 = '%testing%';   // khớp: testing, Testing, TESTING
$testingWords = '%test%';      // dùng chung

// =============================================
// CÁC BƯỚC XÓA THEO THỨ TỰ (tôn trọng ràng buộc)
// =============================================
$steps = [

    // ──────────── BƯỚC 1: Tìm booking_id liên quan ────────────
    // (Không xóa, chỉ lấy danh sách để xóa bảng con trước)

    // ──────────── BƯỚC 2: Xóa bảng con của bookings ────────────
    'Xóa service_bookings của booking TEST' => [
        'sql' => "DELETE sb FROM service_bookings sb
                  INNER JOIN bookings b ON sb.booking_id = b.booking_id
                  WHERE b.guest_name LIKE :p
                     OR b.guest_email LIKE :p
                     OR b.guest_phone LIKE :p
                     OR b.booking_code LIKE :p
                     OR b.special_requests LIKE :p
                     OR b.inquiry_message LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa booking_services của booking TEST' => [
        'sql' => "DELETE bs FROM booking_services bs
                  INNER JOIN bookings b ON bs.booking_id = b.booking_id
                  WHERE b.guest_name LIKE :p
                     OR b.guest_email LIKE :p
                     OR b.special_requests LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa payments của booking TEST' => [
        'sql' => "DELETE p FROM payments p
                  INNER JOIN bookings b ON p.booking_id = b.booking_id
                  WHERE b.guest_name LIKE :p
                     OR b.guest_email LIKE :p
                     OR b.special_requests LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa booking_history của booking TEST' => [
        'sql' => "DELETE bh FROM booking_history bh
                  INNER JOIN bookings b ON bh.booking_id = b.booking_id
                  WHERE b.guest_name LIKE :p
                     OR b.guest_email LIKE :p
                     OR b.special_requests LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa booking_extra_guests của booking TEST' => [
        'sql' => "DELETE eg FROM booking_extra_guests eg
                  INNER JOIN bookings b ON eg.booking_id = b.booking_id
                  WHERE b.guest_name LIKE :p
                     OR b.guest_email LIKE :p
                     OR b.special_requests LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 3: Xóa bookings chứa TEST ────────────
    'Xóa bookings TEST (tên, email, notes, code, request)' => [
        'sql' => "DELETE FROM bookings
                  WHERE guest_name LIKE :p
                     OR guest_email LIKE :p
                     OR guest_phone LIKE :p
                     OR booking_code LIKE :p
                     OR special_requests LIKE :p
                     OR inquiry_message LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 4: Xóa bảng con của users ────────────
    'Xóa service_bookings của user TEST' => [
        'sql' => "DELETE sb FROM service_bookings sb
                  INNER JOIN users u ON sb.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p
                     OR u.user_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa payments của user TEST' => [
        'sql' => "DELETE p FROM payments p
                  INNER JOIN bookings b ON p.booking_id = b.booking_id
                  INNER JOIN users u ON b.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa bookings của user TEST' => [
        'sql' => "DELETE b FROM bookings b
                  INNER JOIN users u ON b.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p
                     OR u.user_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa reviews của user TEST' => [
        'sql' => "DELETE FROM reviews
                  WHERE reviewer_name LIKE :p
                     OR reviewer_email LIKE :p
                     OR comment LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa user_loyalty của user TEST' => [
        'sql' => "DELETE ul FROM user_loyalty ul
                  INNER JOIN users u ON ul.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p
                     OR u.user_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa notifications của user TEST' => [
        'sql' => "DELETE n FROM notifications n
                  INNER JOIN users u ON n.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p
                     OR u.user_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa blog_comments của user TEST' => [
        'sql' => "DELETE FROM blog_comments
                  WHERE author_name LIKE :p
                     OR author_email LIKE :p
                     OR content LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa csrf_tokens của user TEST' => [
        'sql' => "DELETE ct FROM csrf_tokens ct
                  INNER JOIN users u ON ct.user_id = u.user_id
                  WHERE u.email LIKE :p
                     OR u.full_name LIKE :p
                     OR u.user_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 5: Xóa users TEST ────────────
    'Xóa users TEST (email/tên/username)' => [
        'sql' => "DELETE FROM users
                  WHERE (email LIKE :p OR full_name LIKE :p OR user_name LIKE :p)
                    AND user_role NOT IN ('admin')
                    AND user_id != :me",
        'params' => [':p' => $testPattern, ':me' => $_SESSION['user_id']],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 6: Xóa contact_submissions TEST ────────────
    'Xóa contact_submissions TEST' => [
        'sql' => "DELETE FROM contact_submissions
                  WHERE name LIKE :p
                     OR email LIKE :p
                     OR subject LIKE :p
                     OR message LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 7: Xóa activity_logs TEST ────────────
    'Xóa activity_logs chứa từ TEST' => [
        'sql' => "DELETE FROM activity_logs
                  WHERE description LIKE :p
                     OR details LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 8: Xóa ai_leads TEST ────────────
    'Xóa ai_leads TEST' => [
        'sql' => "DELETE FROM ai_leads
                  WHERE name LIKE :p
                     OR email LIKE :p
                     OR phone LIKE :p
                     OR notes LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 9: Xóa apartment_inquiries TEST ────────────
    'Xóa apartment_inquiries TEST' => [
        'sql' => "DELETE FROM apartment_inquiries
                  WHERE name LIKE :p
                     OR email LIKE :p
                     OR message LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 10: Xóa notifications TEST ────────────
    'Xóa notifications nội dung TEST' => [
        'sql' => "DELETE FROM notifications
                  WHERE title LIKE :p
                     OR message LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 11: Xóa chat TEST ────────────
    'Xóa chat_messages TEST' => [
        'sql' => "DELETE FROM chat_messages
                  WHERE message LIKE :p
                     OR sender_name LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    'Xóa chat_conversations TEST' => [
        'sql' => "DELETE FROM chat_conversations
                  WHERE guest_name LIKE :p
                     OR guest_email LIKE :p
                     OR subject LIKE :p",
        'params' => [':p' => $testPattern],
        'type' => 'delete'
    ],

    // ──────────── BƯỚC 12: Khôi phục phòng bị giữ sai ────────────
    'Cập nhật lại phòng occupied không còn booking nào về available' => [
        'sql' => "UPDATE rooms SET status = 'available', updated_at = NOW()
                  WHERE status = 'occupied'
                    AND room_id NOT IN (
                        SELECT DISTINCT room_id FROM bookings
                        WHERE room_id IS NOT NULL
                          AND status IN ('confirmed', 'checked_in')
                    )",
        'params' => [],
        'type' => 'update'
    ],
];

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cleanup Testing Data - Aurora Hotel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #c0392b; border-bottom: 3px solid #c0392b; padding-bottom: 10px; }
        h2 { color: #333; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 12px 15px; border-radius: 5px; margin: 8px 0; }
        .error   { background: #f8d7da; border: 1px solid #dc3545; padding: 12px 15px; border-radius: 5px; margin: 8px 0; }
        .step    { background: #fff; border: 1px solid #dee2e6; padding: 12px 15px; border-radius: 5px; margin: 10px 0; }
        .step strong { display: block; margin-bottom: 6px; }
        .skipped { background: #e9ecef; border: 1px solid #adb5bd; padding: 10px 15px; border-radius: 5px; margin: 8px 0; font-size: 13px; color: #666; }
        .btn { display: inline-block; padding: 10px 22px; background: #c0392b; color: white; border-radius: 6px; text-decoration: none; margin: 8px 5px; font-weight: bold; cursor: pointer; border: none; font-size: 15px; }
        .btn:hover { background: #922b21; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .summary-box { background: #fff; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .summary-box h2 { color: #155724; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; text-align: left; }
        th { background: #343a40; color: #fff; }
        tr:nth-child(even) { background: #f8f9fa; }
        .badge-red { background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .badge-green { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        code { background: #f8f9fa; border: 1px solid #dee2e6; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
    </style>
</head>
<body>
    <h1>🧹 Cleanup Testing Data - Aurora Hotel Plaza</h1>
    <p style="color:#666;">Xóa tất cả dữ liệu chứa từ khóa: <code>test</code>, <code>TEST</code>, <code>testing</code>, <code>tester</code> trên <strong>tất cả các bảng</strong>.</p>

<?php if (!$confirm): ?>

    <div class="warning">
        <strong>⚠️ CẢNH BÁO:</strong> Script này sẽ xóa vĩnh viễn tất cả dữ liệu có chứa từ khóa TEST/test/testing!
        <ul style="margin-top:10px;">
            <li>Bookings có tên/email/notes chứa "test"</li>
            <li>Users có email/tên chứa "test" (trừ admin hiện tại)</li>
            <li>Contact submissions chứa "test"</li>
            <li>Reviews, blog comments chứa "test"</li>
            <li>Chat messages/conversations chứa "test"</li>
            <li>AI leads, apartment inquiries chứa "test"</li>
            <li>Notifications chứa "test"</li>
            <li>Activity logs chứa "test"</li>
        </ul>
    </div>

    <p>Database: <strong><?= DB_NAME ?></strong> (<span style="color:<?= DB_ENVIRONMENT === 'PRODUCTION' ? 'red' : 'green' ?>"><?= DB_ENVIRONMENT ?></span>)</p>

    <a href="?confirm=yes" class="btn" onclick="return confirm('BẠN CHẮC CHẮN MUỐN XÓA TẤT CẢ DỮ LIỆU TEST?\n\nHành động này KHÔNG THỂ HOÀN TÁC!')">🗑️ CHẠY CLEANUP</a>
    <a href="/admin/dashboard.php" class="btn btn-secondary">← Hủy</a>

    <h2 style="margin-top:30px;">📋 Preview - Dữ liệu sẽ bị xóa:</h2>

    <?php
    $previewQueries = [
        'Bookings có chứa "test"' => "SELECT booking_id, booking_code, guest_name, guest_email, status FROM bookings WHERE guest_name LIKE '%test%' OR guest_email LIKE '%test%' OR special_requests LIKE '%test%' OR inquiry_message LIKE '%test%' ORDER BY booking_id",
        'Users có chứa "test" (sẽ bị xóa)' => "SELECT user_id, email, full_name, user_name, user_role FROM users WHERE (email LIKE '%test%' OR full_name LIKE '%test%' OR user_name LIKE '%test%') AND user_role NOT IN ('admin') ORDER BY user_id",
        'Contact submissions có "test"' => "SELECT id, name, email, subject FROM contact_submissions WHERE name LIKE '%test%' OR email LIKE '%test%' OR subject LIKE '%test%' OR message LIKE '%test%' ORDER BY id",
        'Reviews có "test"' => "SELECT review_id, reviewer_name, reviewer_email, comment FROM reviews WHERE reviewer_name LIKE '%test%' OR reviewer_email LIKE '%test%' OR comment LIKE '%test%'",
        'Blog comments có "test"' => "SELECT id, author_name, author_email, content FROM blog_comments WHERE author_name LIKE '%test%' OR author_email LIKE '%test%' OR content LIKE '%test%'",
        'Chat messages có "test"' => "SELECT message_id, sender_name, message FROM chat_messages WHERE message LIKE '%test%' OR sender_name LIKE '%test%' LIMIT 20",
        'AI Leads có "test"' => "SELECT id, name, email, phone FROM ai_leads WHERE name LIKE '%test%' OR email LIKE '%test%' OR phone LIKE '%test%'",
    ];

    foreach ($previewQueries as $title => $sql) {
        echo "<h3 style='margin-bottom:5px;'>{$title}</h3>";
        try {
            $stmt = $conn->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data) {
                echo "<table><tr>";
                foreach (array_keys($data[0]) as $col) echo "<th>{$col}</th>";
                echo "</tr>";
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table><p><span class='badge-red'>" . count($data) . " dòng sẽ bị xóa</span></p>";
            } else {
                echo "<p><span class='badge-green'>✅ Không có dữ liệu TEST</span></p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:#999;font-size:13px;'>Bảng không tồn tại hoặc lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    ?>

<?php else: ?>

    <h2>⚙️ Đang thực thi cleanup...</h2>

    <?php
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    $totalDeleted = 0;
    $totalUpdated = 0;

    foreach ($steps as $stepName => $stepData) {
        echo "<div class='step'><strong>{$stepName}</strong>";
        try {
            $stmt = $conn->prepare($stepData['sql']);

            // Bind params
            foreach ($stepData['params'] as $key => $val) {
                $stmt->bindValue($key, $val);
            }

            $stmt->execute();
            $affected = $stmt->rowCount();

            if ($stepData['type'] === 'update') {
                echo "<div class='success'>✅ Đã cập nhật <strong>{$affected}</strong> dòng</div>";
                $totalUpdated += $affected;
            } else {
                if ($affected > 0) {
                    echo "<div class='success'>🗑️ Đã xóa <strong>{$affected}</strong> dòng</div>";
                    $totalDeleted += $affected;
                } else {
                    echo "<div class='skipped'>ℹ️ Không có dữ liệu TEST cần xóa (0 dòng)</div>";
                }
            }
        } catch (PDOException $e) {
            // Bảng không tồn tại - bỏ qua
            if (strpos($e->getMessage(), "doesn't exist") !== false ||
                strpos($e->getMessage(), "Unknown table") !== false) {
                echo "<div class='skipped'>⏭️ Bỏ qua - bảng không tồn tại</div>";
            } else {
                echo "<div class='error'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        echo "</div>";
    }

    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    ?>

    <div class="summary-box">
        <h2>✅ Cleanup hoàn tất!</h2>
        <p>Tổng số dòng đã xóa: <strong style="font-size:20px;color:#c0392b;"><?= $totalDeleted ?></strong></p>
        <p>Tổng số dòng đã cập nhật: <strong style="font-size:20px;color:#2ecc71;"><?= $totalUpdated ?></strong></p>
    </div>

    <h2>📊 Kiểm tra sau cleanup:</h2>
    <?php
    $verifyQueries = [
        'Bookings TEST còn lại' => "SELECT COUNT(*) FROM bookings WHERE guest_name LIKE '%test%' OR guest_email LIKE '%test%' OR special_requests LIKE '%test%'",
        'Users TEST còn lại' => "SELECT COUNT(*) FROM users WHERE (email LIKE '%test%' OR full_name LIKE '%test%') AND user_role NOT IN ('admin')",
        'Contact TEST còn lại' => "SELECT COUNT(*) FROM contact_submissions WHERE name LIKE '%test%' OR email LIKE '%test%' OR message LIKE '%test%'",
        'Tổng bookings còn lại' => "SELECT COUNT(*) FROM bookings",
        'Tổng users còn lại' => "SELECT COUNT(*) FROM users",
        'Phòng trạng thái occupied' => "SELECT COUNT(*) FROM rooms WHERE status = 'occupied'",
    ];

    echo "<table><tr><th>Kiểm tra</th><th>Kết quả</th></tr>";
    foreach ($verifyQueries as $label => $sql) {
        try {
            $count = $conn->query($sql)->fetchColumn();
            $isTest = strpos($label, 'TEST') !== false;
            $badge = ($isTest && $count > 0) ? 'badge-red' : 'badge-green';
            echo "<tr><td>{$label}</td><td><span class='{$badge}'>{$count}</span></td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>{$label}</td><td style='color:#999;'>N/A</td></tr>";
        }
    }
    echo "</table>";
    ?>

    <div style="margin-top:20px;">
        <a href="/admin/dashboard.php" class="btn btn-secondary">← Dashboard</a>
        <a href="/admin/bookings.php" class="btn btn-secondary">📋 Xem Bookings</a>
        <a href="/admin/customers.php" class="btn btn-secondary">👥 Xem Customers</a>
    </div>

<?php endif; ?>
</body>
</html>