<?php
/**
 * API: Test gửi Messenger alert
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

require_once '../../config/database.php';
require_once '../../helpers/error-tracker.php';

$db = getDB();
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('fb_messenger_psid','fb_page_access_token')");
$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$psid = $settings['fb_messenger_psid'] ?? '';
$token = $settings['fb_page_access_token'] ?? '';

if (empty($psid) || empty($token)) {
    echo '<pre style="font-family:monospace;padding:20px;background:#fee;border:1px solid #f99;border-radius:8px">';
    echo "[FAILED] Chưa cấu hình PSID hoặc Page Access Token.\n";
    echo "Vào Admin > AI Bug > Settings để nhập thông tin.\n";
    echo '</pre>';
    exit;
}

$payload = json_encode([
    'recipient' => ['id' => $psid],
    'message' => ['text' => "[TEST] Aurora Bug Tracker hoạt động bình thường. " . date('d/m/Y H:i:s')],
    'messaging_type' => 'MESSAGE_TAG',
    'tag' => 'ACCOUNT_UPDATE',
]);

$ch = curl_init("https://graph.facebook.com/v19.0/me/messages?access_token=$token");
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
$err = curl_error($ch);
curl_close($ch);

echo '<pre style="font-family:monospace;padding:20px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px">';
if ($httpCode === 200) {
    echo "[SUCCESS] Đã gửi tin nhắn tới messenger thành công!\n";
    echo "HTTP: $httpCode\n";
    echo "Response: $result\n";
} else {
    echo "[FAILED] Gui that bai.\n";
    echo "HTTP Code: $httpCode\n";
    if ($err)
        echo "cURL Error: $err\n";
    echo "Response: $result\n";
}
echo '</pre>';
echo '<p style="font-family:sans-serif;"><a href="../ai-bug.php">← Quay lai AI Bug</a></p>';
