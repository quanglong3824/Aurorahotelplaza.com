<?php
/**
 * API: Test gửi Telegram alert
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

require_once '../../config/database.php';

$db = getDB();
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('telegram_bot_token','telegram_chat_id')");
$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$token = trim($settings['telegram_bot_token'] ?? '');
$chatId = trim($settings['telegram_chat_id'] ?? '');

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Test Telegram</title>
<style>
  body { font-family: monospace; padding: 30px; background:#f9f9f9; }
  pre { padding: 20px; border-radius: 8px; white-space: pre-wrap; word-break: break-all; }
  .ok  { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
  .err { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }
  a { display:inline-block; margin-top: 16px; color: #4f46e5; }
</style></head><body>';

if (empty($token) || empty($chatId)) {
    echo '<pre class="err">[FAILED] Chua cau hinh Bot Token hoac Chat ID.
Vao Admin > AI Bug > Settings de nhap thong tin.</pre>';
    echo '<a href="../ai-bug.php">← Quay lai AI Bug</a>';
    exit;
}

$time = date('d/m/Y H:i:s');
$payload = json_encode([
    'chat_id' => $chatId,
    'text' => "[TEST] Aurora Bug Tracker hoat dong binh thuong.\nThoi gian: $time",
    'parse_mode' => 'HTML',
]);

$ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

$data = json_decode($result, true);

if ($httpCode === 200 && ($data['ok'] ?? false)) {
    echo '<pre class="ok">[SUCCESS] Da gui tin nhan test toi Telegram thanh cong!
Bot Token : ' . substr($token, 0, 10) . '...' . substr($token, -5) . '
Chat ID   : ' . $chatId . '
HTTP Code : ' . $httpCode . '</pre>';
} else {
    $errDesc = $data['description'] ?? $curlErr ?: 'Unknown error';
    echo '<pre class="err">[FAILED] Gui that bai.
HTTP Code   : ' . $httpCode . '
Loi Telegram: ' . htmlspecialchars($errDesc) . '

---
Cac loi thuong gap:
- "chat not found": Chat ID sai, can nhan tin cho bot truoc
- "Unauthorized"  : Bot Token sai  
- "blocked"       : Ban da block bot nay</pre>';
}

echo '<a href="../ai-bug.php">← Quay lai AI Bug</a>';
echo '</body></html>';
