<?php
/**
 * admin/chat-install.php
 * ─────────────────────────────────────────────────────────
 * Chạy migration CHAT SYSTEM an toàn (idempotent):
 *  - Tạo / nâng cấp bảng chat_conversations, chat_messages
 *  - Tạo chat_typing, chat_quick_replies, chat_settings
 *  - Thêm role_permissions nếu chưa có
 *
 * ⚠  Chỉ chạy 1 lần. Sau khi xong, XÓA file này.
 * URL: /2025/admin/chat-install.php (đăng nhập admin trước)
 * ─────────────────────────────────────────────────────────
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/environment.php';

// Chỉ admin mới chạy được
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('<h2 style="font-family:monospace;color:red">403: Chỉ admin mới được chạy file này.</h2>');
}

$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];
$ok = true;

function run(PDO $db, string $label, string $sql, array &$log, bool &$ok): void
{
    try {
        $db->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        // Bỏ qua lỗi "Duplicate column name" / "Duplicate key name" — idempotent
        $msg = $e->getMessage();
        if (
            stripos($msg, 'Duplicate column') !== false ||
            stripos($msg, 'Duplicate key') !== false ||
            stripos($msg, 'Multiple primary') !== false
        ) {
            $log[] = "⏭  $label (đã tồn tại — bỏ qua)";
        } else {
            $log[] = "❌ $label → " . $msg;
            $ok = false;
        }
    }
}

// ─── BƯỚC 1: chat_conversations — thêm column mới ────────────────────────────

run($db, 'ADD booking_id to chat_conversations', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `booking_id` int(11) DEFAULT NULL AFTER `customer_id`
", $log, $ok);

run($db, 'ADD subject to chat_conversations', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `subject` varchar(255) DEFAULT 'Hỗ trợ khách hàng' AFTER `booking_id`
", $log, $ok);

run($db, 'ADD unread_customer', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `unread_customer` int(11) NOT NULL DEFAULT 0 AFTER `status`
", $log, $ok);

run($db, 'ADD unread_staff', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `unread_staff` int(11) NOT NULL DEFAULT 0 AFTER `unread_customer`
", $log, $ok);

run($db, 'ADD last_message_preview', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `last_message_preview` varchar(255) DEFAULT NULL AFTER `last_message_at`
", $log, $ok);

run($db, 'ADD source', "
    ALTER TABLE `chat_conversations`
    ADD COLUMN `source` enum('website','booking','profile') DEFAULT 'website' AFTER `last_message_preview`
", $log, $ok);

run($db, 'ADD idx_customer_status', "
    ALTER TABLE `chat_conversations` ADD INDEX `idx_customer_status` (`customer_id`, `status`)
", $log, $ok);

run($db, 'ADD idx_booking_conv', "
    ALTER TABLE `chat_conversations` ADD INDEX `idx_booking_conv` (`booking_id`)
", $log, $ok);

// ─── BƯỚC 2: chat_messages ────────────────────────────────────────────────────

run($db, 'ADD sender_type to chat_messages', "
    ALTER TABLE `chat_messages`
    ADD COLUMN `sender_type` enum('customer','staff','system') NOT NULL DEFAULT 'customer' AFTER `sender_id`
", $log, $ok);

run($db, 'ADD message_type', "
    ALTER TABLE `chat_messages`
    ADD COLUMN `message_type` enum('text','image','file','system_note') NOT NULL DEFAULT 'text' AFTER `message`
", $log, $ok);

run($db, 'ADD is_internal', "
    ALTER TABLE `chat_messages`
    ADD COLUMN `is_internal` tinyint(1) NOT NULL DEFAULT 0 AFTER `message_type`
", $log, $ok);

run($db, 'ADD idx_conv_id_msg', "
    ALTER TABLE `chat_messages` ADD INDEX `idx_conv_id_msg` (`conversation_id`, `message_id`)
", $log, $ok);

// ─── BƯỚC 3: chat_typing (create) ────────────────────────────────────────────

run($db, 'CREATE chat_typing', "
    CREATE TABLE IF NOT EXISTS `chat_typing` (
        `id`              int(11) NOT NULL AUTO_INCREMENT,
        `conversation_id` int(11) NOT NULL,
        `user_id`         int(11) NOT NULL,
        `user_type`       enum('customer','staff') NOT NULL,
        `is_typing`       tinyint(1) NOT NULL DEFAULT 1,
        `updated_at`      timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_typer` (`conversation_id`, `user_id`),
        KEY `idx_conv_typing` (`conversation_id`, `updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", $log, $ok);

// ─── BƯỚC 4: chat_quick_replies (create) ─────────────────────────────────────

run($db, 'CREATE chat_quick_replies', "
    CREATE TABLE IF NOT EXISTS `chat_quick_replies` (
        `reply_id`   int(11) NOT NULL AUTO_INCREMENT,
        `category`   varchar(50) NOT NULL DEFAULT 'Chung',
        `shortcut`   varchar(50) DEFAULT NULL,
        `title`      varchar(100) NOT NULL,
        `content`    text NOT NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_active`  tinyint(1) DEFAULT 1,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`reply_id`),
        KEY `idx_category` (`category`, `sort_order`),
        KEY `idx_shortcut` (`shortcut`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", $log, $ok);

// Seed quick replies nếu bảng mới tạo (trống)
$cnt = (int) $db->query("SELECT COUNT(*) FROM chat_quick_replies")->fetchColumn();
if ($cnt === 0) {
    run($db, 'Seed quick replies', "
        INSERT INTO `chat_quick_replies` (`category`,`shortcut`,`title`,`content`,`sort_order`) VALUES
        ('Chung','/xin-chao','Chào mừng',
         'Xin chào! Chào mừng Quý khách đến với Aurora Hotel Plaza. Em có thể hỗ trợ gì ạ?',1),
        ('Chung','/cam-on','Cảm ơn',
         'Cảm ơn Quý khách đã liên hệ Aurora Hotel Plaza. Chúc Quý khách một ngày tốt lành!',2),
        ('Đặt phòng','/gia-phong','Hỏi giá phòng',
         'Quý khách dự định lưu trú từ ngày nào đến ngày nào để em tư vấn giá phòng phù hợp ạ?',3),
        ('Đặt phòng','/check-in','Giờ check-in/out',
         'Check-in: 14:00 | Check-out: 12:00. Nhận phòng sớm / trả phòng muộn có phụ phí nhỏ ạ.',4),
        ('Đặt phòng','/huy-phong','Hủy phòng',
         'Hủy miễn phí trước 24h. Hủy trong 24h tính phí 1 đêm đầu. Quý khách cần hỗ trợ hủy mã nào ạ?',5),
        ('Thanh toán','/thanh-toan','Phương thức TT',
         'Aurora chấp nhận VNPay, tiền mặt, chuyển khoản. Quý khách cần hỗ trợ thanh toán gì ạ?',6),
        ('Chung','/cho-doi','Yêu cầu chờ',
         'Em đang kiểm tra thông tin, vui lòng chờ em 1-2 phút ạ!',7),
        ('Khiếu nại','/xin-loi','Xin lỗi khách',
         'Em xin chân thành xin lỗi Quý khách. Em sẽ chuyển ngay đến bộ phận phụ trách để xử lý sớm nhất ạ.',8)
    ", $log, $ok);
}

// ─── BƯỚC 5: chat_settings (create) ──────────────────────────────────────────

run($db, 'CREATE chat_settings', "
    CREATE TABLE IF NOT EXISTS `chat_settings` (
        `setting_id`    int(11) NOT NULL AUTO_INCREMENT,
        `setting_key`   varchar(100) NOT NULL,
        `setting_value` text NOT NULL,
        `description`   varchar(255) DEFAULT NULL,
        `updated_at`    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`setting_id`),
        UNIQUE KEY `unique_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", $log, $ok);

$cnt2 = (int) $db->query("SELECT COUNT(*) FROM chat_settings")->fetchColumn();
if ($cnt2 === 0) {
    run($db, 'Seed chat_settings', "
        INSERT INTO `chat_settings` (`setting_key`,`setting_value`,`description`) VALUES
        ('chat_enabled',       '1', 'Bật/tắt widget chat'),
        ('working_hours_start','07:00', 'Giờ bắt đầu hỗ trợ'),
        ('working_hours_end',  '23:00', 'Giờ kết thúc hỗ trợ'),
        ('auto_reply_enabled', '1', 'Tự động chào khi mở hội thoại'),
        ('auto_reply_message', 'Xin chào! Cảm ơn Quý khách đã liên hệ Aurora Hotel Plaza 🌟. Nhân viên sẽ phản hồi trong vài phút. Hotline: 0251 3918 888', 'Tin nhắn chào tự động'),
        ('offline_message',    'Hiện ngoài giờ làm việc (7:00 - 23:00). Quý khách để lại tin nhắn, chúng tôi phản hồi sớm nhất. Hotline: 0251 3918 888', 'Tin nhắn ngoài giờ'),
        ('max_conversations',  '10', 'Max hội thoại/nhân viên'),
        ('sse_interval_global','3', 'SSE global interval (giây)'),
        ('sse_interval_conv',  '2', 'SSE conv interval (giây)'),
        ('sound_enabled',      '1', 'Âm thanh thông báo')
    ", $log, $ok);
}

// ─── BƯỚC 6: role_permissions ────────────────────────────────────────────────

try {
    $pCount = (int) $db->query("SELECT COUNT(*) FROM role_permissions WHERE module='chat'")->fetchColumn();
    if ($pCount === 0) {
        run($db, 'Insert role_permissions chat', "
            INSERT INTO `role_permissions` (`role`,`module`,`action`,`allowed`) VALUES
            ('admin','chat','view',1),('admin','chat','reply',1),('admin','chat','assign',1),
            ('admin','chat','close',1),('admin','chat','manage_settings',1),
            ('receptionist','chat','view',1),('receptionist','chat','reply',1),('receptionist','chat','close',1),
            ('sale','chat','view',1),('sale','chat','reply',1),('sale','chat','close',1),
            ('customer','chat','view',1),('customer','chat','send',1)
        ", $log, $ok);
    } else {
        $log[] = "⏭  role_permissions chat (đã tồn tại)";
    }
} catch (PDOException $e) {
    $log[] = "⏭  role_permissions: bảng không tồn tại, bỏ qua";
}

// ─── OUTPUT ───────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Chat Migration — Aurora Hotel Plaza</title>
    <style>
        body {
            font-family: 'Consolas', monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 32px;
        }

        h1 {
            color: #d4af37;
        }

        pre {
            background: #1e293b;
            padding: 20px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.8;
        }

        .ok {
            color: #4ade80;
        }

        .err {
            color: #f87171;
        }

        .warn {
            color: #fbbf24;
        }

        a {
            color: #60a5fa;
        }
    </style>
</head>

<body>
    <h1>🗄 Chat System — Database Migration</h1>
    <pre>
<?php foreach ($log as $line): ?>
    <span class="<?= str_starts_with($line, '✅') ? 'ok' : (str_starts_with($line, '❌') ? 'err' : 'warn') ?>">
    <?= htmlspecialchars($line) ?>
    </span>
<?php endforeach; ?>
</pre>

    <?php if ($ok): ?>
        <p class="ok" style="font-size:18px;font-weight:bold">✅ Migration hoàn tất!</p>
        <p style="color:#94a3b8">⚠ Hãy <strong style="color:#f87171">XÓA file này</strong> sau khi chạy xong.</p>
        <p><a href="chat.php">→ Vào trang Chat Admin</a> &nbsp;|&nbsp; <a href="chat-settings.php">→ Cài đặt Chat</a></p>
    <?php else: ?>
        <p class="err" style="font-size:18px;font-weight:bold">❌ Có lỗi xảy ra. Xem log ở trên.</p>
    <?php endif; ?>
</body>

</html>