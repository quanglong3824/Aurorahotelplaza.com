<?php
require_once 'config/environment.php';

echo "<h1>Aurora Path Debugger</h1>";
echo "<b>DOCUMENT_ROOT:</b> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<b>SCRIPT_FILENAME:</b> " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "<b>SCRIPT_NAME:</b> " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "<b>REQUEST_URI:</b> " . $_SERVER['REQUEST_URI'] . "<br>";
echo "<hr>";
echo "<b>BASE_URL (Detected):</b> " . BASE_URL . "<br>";
echo "<b>ASSETS_URL (Detected):</b> " . ASSETS_URL . "<br>";
echo "<b>SITE_URL (Detected):</b> " . SITE_URL . "<br>";
echo "<hr>";
echo "<b>Checking assets folder:</b><br>";
$assets_path = __DIR__ . '/assets';
if (file_exists($assets_path)) {
    echo "✅ Thư mục assets tồn tại tại: $assets_path<br>";
} else {
    echo "❌ KHÔNG tìm thấy thư mục assets tại: $assets_path<br>";
}

echo "<hr>";
echo "<b>PHP Version:</b> " . phpversion() . "<br>";
echo "<b>OPcache enabled:</b> " . (function_exists('opcache_get_status') && opcache_get_status() ? "Yes" : "No") . "<br>";
?>
