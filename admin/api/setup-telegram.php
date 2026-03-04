<?php
/**
 * One-time setup: Lưu Telegram credentials trực tiếp vào DB
 * XÓA FILE NÀY SAU KHI CHẠY XONG
 */
session_start();

// Bảo vệ đơn giản
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied. Must be admin.');
}

require_once '../../config/database.php';

$token = $_POST['token'] ?? '';
$chatId = $_POST['chat_id'] ?? '';
$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token && $chatId) {
    try {
        $db = getDB();
        $pairs = [
            'telegram_bot_token' => trim($token),
            'telegram_chat_id' => trim($chatId),
        ];
        foreach ($pairs as $key => $val) {
            $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                VALUES (?, ?, 'string', ?)
                ON DUPLICATE KEY UPDATE setting_value = ?
            ")->execute([$key, $val, $key, $val]);
        }

        // Verify lại
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('telegram_bot_token','telegram_chat_id')");
        $saved = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $saved[$r['setting_key']] = $r['setting_value'];
        }

        $tokenOk = !empty($saved['telegram_bot_token']);
        $chatIdOk = !empty($saved['telegram_chat_id']);
        $result = $tokenOk && $chatIdOk ? 'OK' : 'FAIL';

        // Test gửi Telegram ngay
        $testMsg = '';
        if ($tokenOk && $chatIdOk) {
            $payload = json_encode([
                'chat_id' => $saved['telegram_chat_id'],
                'text' => '[SETUP OK] Aurora Bug Tracker da ket noi Telegram thanh cong! ' . date('d/m/Y H:i:s'),
                'parse_mode' => 'HTML',
            ]);
            $ch = curl_init("https://api.telegram.org/bot{$saved['telegram_bot_token']}/sendMessage");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $data = json_decode($res, true);
            $testMsg = ($code === 200 && ($data['ok'] ?? false)) ? 'GUI TELEGRAM THANH CONG!' : 'GUI THAT BAI: ' . ($data['description'] ?? $res);
        }
    } catch (\Throwable $e) {
        $result = 'ERROR: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Telegram Setup</title>
    <style>
        body {
            font-family: monospace;
            padding: 30px;
            background: #0f172a;
            color: #e2e8f0;
            max-width: 600px;
            margin: 0 auto;
        }

        h2 {
            color: #f59e0b;
        }

        label {
            display: block;
            margin-top: 16px;
            font-size: 13px;
            color: #94a3b8;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #f1f5f9;
            font-family: monospace;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 12px 24px;
            background: #f59e0b;
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }

        .ok {
            margin-top: 20px;
            padding: 16px;
            background: #14532d;
            border: 1px solid #16a34a;
            border-radius: 8px;
            color: #86efac;
        }

        .err {
            margin-top: 20px;
            padding: 16px;
            background: #7f1d1d;
            border: 1px solid #dc2626;
            border-radius: 8px;
            color: #fca5a5;
        }

        .warn {
            margin-top: 12px;
            padding: 10px;
            background: #422006;
            border-radius: 6px;
            color: #fb923c;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <h2>⚙️ Telegram Setup — Aurora Bug Tracker</h2>
    <p style="color:#64748b;font-size:13px;">Trang này chỉ dùng một lần. Xóa file sau khi setup xong.</p>

    <?php if ($result === 'OK'): ?>
        <div class="ok">
            ✅ LUU THANH CONG!<br>
            Bot Token:
            <?php echo substr($saved['telegram_bot_token'] ?? '', 0, 15); ?>...<br>
            Chat ID:
            <?php echo $saved['telegram_chat_id'] ?? ''; ?><br><br>
            📨
            <?php echo $testMsg ?? ''; ?>
        </div>
        <div class="warn">⚠️ XOA FILE NAY NGAY: <code>/admin/api/setup-telegram.php</code></div>
        <p><a href="../ai-bug.php" style="color:#f59e0b">← Quay lại AI Bug Tracker</a></p>
    <?php elseif ($result): ?>
        <div class="err">❌
            <?php echo htmlspecialchars($result); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Telegram Bot Token (từ @BotFather)</label>
        <input type="text" name="token" value="<?php echo htmlspecialchars($_POST['token'] ?? ''); ?>"
            placeholder="7589180138:AAG..." autocomplete="off">

        <label>Telegram Chat ID (ID cá nhân của bạn)</label>
        <input type="text" name="chat_id" value="<?php echo htmlspecialchars($_POST['chat_id'] ?? '5513249927'); ?>"
            placeholder="5513249927">

        <button type="submit">💾 Lưu và Test Telegram</button>
    </form>
</body>

</html>