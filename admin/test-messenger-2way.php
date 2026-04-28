<?php
/**
 * Admin: Test Messenger 2 chiều
 * Test Email và Telegram (không lưu CSDL)
 * 
 * ALL IN ONE - Telegram đã cấu hình sẵn
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/mailer.php';
require_once '../helpers/telegram.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Test Messenger 2 chiều';
$page_subtitle = 'Test Email & Telegram (không lưu CSDL) - All in One';

include 'includes/admin-header.php';

$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testType = $_POST['test_type'] ?? '';

    switch ($testType) {
        case 'email':
            $to = trim($_POST['email_to'] ?? '');
            $subject = trim($_POST['email_subject'] ?? 'Test Email - Aurora Hotel Plaza');
            $body = trim($_POST['email_body'] ?? 'Đây là email test từ Aurora Hotel Plaza Admin.');

            if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $actionResult = ['type' => 'error', 'message' => 'Email không hợp lệ'];
            } else {
                $mailer = getMailer();
                $htmlBody = '<div style="font-family:Arial;padding:20px;background:#f5f5f5;">
                    <h2 style="color:#d4af37;">Aurora Hotel Plaza - Test Email</h2>
                    <p>' . htmlspecialchars($body) . '</p>
                    <hr style="border-color:#d4af37;margin:20px 0;">
                    <p style="color:#666;font-size:12px;">Gửi từ Admin Test Messenger lúc ' . date('d/m/Y H:i:s') . '</p>
                </div>';
                $sent = $mailer->send($to, $subject, $htmlBody);
                $actionResult = $sent
                    ? ['type' => 'success', 'message' => 'Email đã gửi thành công đến ' . $to]
                    : ['type' => 'error', 'message' => 'Lỗi gửi email: ' . $mailer->getLastError()];
            }
            break;

        case 'telegram':
            $message = trim($_POST['telegram_message'] ?? 'Test Telegram từ Aurora Hotel Plaza Admin');
            if (empty($message)) {
                $actionResult = ['type' => 'error', 'message' => 'Tin nhắn không được trống'];
            } else {
                $result = TelegramHelper::sendMessage($message);
                $actionResult = $result['success']
                    ? ['type' => 'success', 'message' => 'Telegram đã gửi thành công! (Msg ID: ' . ($result['message_id'] ?? 'N/A') . ')']
                    : ['type' => 'error', 'message' => 'Lỗi Telegram: ' . ($result['error'] ?? 'Unknown')];
            }
            break;

        case 'telegram_booking':
            $bookingData = [
                'booking_code' => 'TEST-' . str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_LEFT),
                'type_name' => 'Premium Deluxe',
                'guest_name' => 'Nguyễn Văn Test',
                'guest_phone' => '0901234567',
                'guest_email' => 'test@example.com',
                'check_in' => date('d/m/Y', strtotime('+1 day')),
                'check_out' => date('d/m/Y', strtotime('+2 days')),
                'nights' => 1,
                'total_amount' => mt_rand(500000, 2000000),
                'status' => 'pending',
                'booking_id' => 99999
            ];
            $result = TelegramHelper::sendBookingNotification($bookingData);
            $actionResult = $result['success']
                ? ['type' => 'success', 'message' => 'Booking notification đã gửi thành công!']
                : ['type' => 'error', 'message' => 'Lỗi: ' . ($result['error'] ?? 'Unknown')];
            break;

        case 'telegram_chat':
            $convData = [
                'conversation_id' => mt_rand(100, 999),
                'guest_name' => 'Khách Test'
            ];
            $msgData = [
                'message' => 'Xin chào, tôi muốn hỏi về phòng Premium Deluxe. Có sẵn cho ngày ' . date('d/m/Y', strtotime('+3 days')) . ' không?'
            ];
            $result = TelegramHelper::sendChatNotification($convData, $msgData);
            $actionResult = $result['success']
                ? ['type' => 'success', 'message' => 'Chat notification đã gửi thành công!']
                : ['type' => 'error', 'message' => 'Lỗi: ' . ($result['error'] ?? 'Unknown')];
            break;

        case 'set_webhook':
            $webhookUrl = BASE_URL . '/api/telegram-webhook.php';
            $result = TelegramHelper::setWebhook($webhookUrl);
            $actionResult = $result['success']
                ? ['type' => 'success', 'message' => 'Webhook đã được set: ' . $webhookUrl]
                : ['type' => 'error', 'message' => 'Lỗi set webhook: ' . json_encode($result['data'])];
            break;

        case 'delete_webhook':
            $result = TelegramHelper::deleteWebhook();
            $actionResult = $result['success']
                ? ['type' => 'success', 'message' => 'Webhook đã được xóa']
                : ['type' => 'error', 'message' => 'Lỗi delete webhook'];
            break;

        case 'webhook_info':
            $result = TelegramHelper::getWebhookInfo();
            $actionResult = $result['success']
                ? ['type' => 'info', 'message' => 'Webhook Info', 'data' => $result['data']]
                : ['type' => 'error', 'message' => 'Lỗi get webhook info'];
            break;

        case 'create_tables':
            try {
                $db = getDB();
                $sql = "
                    CREATE TABLE IF NOT EXISTS telegram_message_mapping (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        conversation_id INT NOT NULL,
                        telegram_message_id BIGINT NOT NULL,
                        message_type ENUM('notification', 'reply') DEFAULT 'notification',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_conversation (conversation_id),
                        INDEX idx_telegram_msg (telegram_message_id),
                        UNIQUE KEY unique_conv_msg (conversation_id, telegram_message_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                $db->exec($sql);
                $actionResult = ['type' => 'success', 'message' => 'Bảng telegram_message_mapping đã tạo thành công!'];
            } catch (PDOException $e) {
                $actionResult = ['type' => 'error', 'message' => 'Lỗi tạo bảng: ' . $e->getMessage()];
            }
            break;
    }
}

$webhookInfo = null;
$webhookResult = TelegramHelper::getWebhookInfo();
$webhookInfo = $webhookResult['data'] ?? null;
?>

<?php if ($actionResult): ?>
    <div
        class="mb-6 p-4 rounded-lg <?php echo $actionResult['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : ($actionResult['type'] === 'info' ? 'bg-blue-100 border border-blue-400 text-blue-700' : 'bg-red-100 border border-red-400 text-red-700'); ?>">
        <span
            class="material-symbols-outlined text-sm align-middle mr-2"><?php echo $actionResult['type'] === 'success' ? 'check_circle' : ($actionResult['type'] === 'info' ? 'info' : 'error'); ?></span>
        <?php echo htmlspecialchars($actionResult['message']); ?>
        <?php if (isset($actionResult['data'])): ?>
            <pre
                class="mt-2 text-xs bg-white p-2 rounded overflow-auto"><?php echo htmlspecialchars(json_encode($actionResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Email Test -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">mail</span>
                Test Email (Không lưu CSDL)
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="test_type" value="email">

                <div class="form-group">
                    <label class="form-label">Email nhận</label>
                    <input type="email" name="email_to"
                        value="<?php echo htmlspecialchars($_POST['email_to'] ?? ''); ?>" class="form-input"
                        placeholder="email@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="email_subject"
                        value="<?php echo htmlspecialchars($_POST['email_subject'] ?? 'Test Email - Aurora Hotel Plaza'); ?>"
                        class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Nội dung</label>
                    <textarea name="email_body" rows="4" class="form-input"
                        placeholder="Nội dung email test..."><?php echo htmlspecialchars($_POST['email_body'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <span class="material-symbols-outlined text-sm">send</span>
                    Gửi Email Test
                </button>
            </form>
        </div>
    </div>

    <!-- Telegram Test - ALL IN ONE -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">chat</span>
                Test Telegram (All in One)
            </h3>
            <div class="ml-auto">
                <span class="badge badge-success">✅ Sẵn sàng</span>
            </div>
        </div>
        <div class="card-body">
            <!-- Telegram Config Info -->
            <div class="mb-4 p-3 bg-green-50 dark:bg-slate-800 rounded-lg border border-green-200">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <span class="font-medium text-green-700">Telegram Bot đã cấu hình sẵn</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">Bot Token:</span>
                        <span class="font-mono text-xs">8772642373:AAG...</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Chat ID:</span>
                        <span class="font-mono">xxxTELExxx</span>
                    </div>
                </div>
                <?php if ($webhookInfo): ?>
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="text-sm">
                            <span class="text-gray-500">Webhook:</span>
                            <span
                                class="font-mono truncate block text-xs"><?php echo $webhookInfo['url'] ?? 'None'; ?></span>
                            <span
                                class="text-xs <?php echo ($webhookInfo['url'] ?? '') ? 'text-green-600' : 'text-orange-600'; ?>">
                                <?php echo ($webhookInfo['url'] ?? '') ? '✅ Active' : '⚠️ Click "Set Webhook" để bật 2 chiều'; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Telegram Message Test -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="test_type" value="telegram">

                <div class="form-group">
                    <label class="form-label">Tin nhắn test</label>
                    <textarea name="telegram_message" rows="3" class="form-input"
                        placeholder="Test Telegram từ Aurora Hotel Plaza..."><?php echo htmlspecialchars($_POST['telegram_message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <span class="material-symbols-outlined text-sm">send</span>
                    Gửi Telegram Test
                </button>
            </form>

            <!-- Booking & Chat Notification Tests -->
            <div class="mt-4 grid grid-cols-2 gap-3">
                <form method="POST">
                    <input type="hidden" name="test_type" value="telegram_booking">
                    <button type="submit" class="btn btn-secondary w-full text-sm">
                        <span class="material-symbols-outlined text-sm">book_online</span>
                        Test Booking Notif
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="test_type" value="telegram_chat">
                    <button type="submit" class="btn btn-secondary w-full text-sm">
                        <span class="material-symbols-outlined text-sm">forum</span>
                        Test Chat Notif
                    </button>
                </form>
            </div>

            <!-- Webhook 2 chiều -->
            <div class="mt-4 p-3 bg-indigo-50 dark:bg-slate-700 rounded-lg">
                <h4 class="font-medium mb-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">sync</span>
                    Tin nhắn 2 chiều (Webhook)
                </h4>
                <p class="text-xs text-gray-600 mb-3">
                    Bật webhook để nhận tin nhắn từ Telegram và phản hồi khách qua web.
                </p>
                <div class="grid grid-cols-4 gap-2">
                    <form method="POST">
                        <input type="hidden" name="test_type" value="set_webhook">
                        <button type="submit" class="btn btn-sm bg-green-600 text-white w-full">
                            Set
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="test_type" value="delete_webhook">
                        <button type="submit" class="btn btn-sm bg-red-600 text-white w-full">
                            Delete
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="test_type" value="webhook_info">
                        <button type="submit" class="btn btn-sm bg-blue-600 text-white w-full">
                            Info
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="test_type" value="create_tables">
                        <button type="submit" class="btn btn-sm bg-purple-600 text-white w-full">
                            Tạo bảng
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hướng dẫn sử dụng -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">help</span>
            Hướng dẫn sử dụng
        </h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-3 bg-green-50 rounded-lg">
                <h4 class="font-medium mb-1 text-green-700">✅ Gửi tin nhắn</h4>
                <p class="text-sm text-gray-600">Nhập tin nhắn và click "Gửi Telegram Test" hoặc test Booking/Chat
                    notification.</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
                <h4 class="font-medium mb-1 text-blue-700">🔄 Bật 2 chiều</h4>
                <p class="text-sm text-gray-600">Click "Tạo bảng" → Click "Set Webhook" để nhận tin nhắn từ Telegram.
                </p>
            </div>
            <div class="p-3 bg-purple-50 rounded-lg">
                <h4 class="font-medium mb-1 text-purple-700">💬 Phản hồi khách</h4>
                <p class="text-sm text-gray-600">Reply tin nhắn notification trên Telegram → Phản hồi vào hệ thống chat.
                </p>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <h4 class="font-medium mb-1">Cách hoạt động 2 chiều:</h4>
            <ol class="text-sm text-gray-600 space-y-1">
                <li>1. <strong>Khi khách đặt phòng</strong> → Bot gửi notification booking mới</li>
                <li>2. <strong>Khi khách nhắn trên web</strong> → Bot gửi notification tin nhắn mới</li>
                <li>3. <strong>Reply notification</strong> → Tin nhắn gửi vào hệ thống chat của khách</li>
            </ol>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>