<?php
/**
 * Debug Session - Kiểm tra thông tin session hiện tại
 * XÓA FILE NÀY SAU KHI DEBUG XONG
 */
session_start();

echo "<h1>Debug Session</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";

echo "<hr>";
echo "<h2>Actions</h2>";
echo "<a href='?action=clear'>Xóa toàn bộ Session</a> | ";
echo "<a href='../auth/logout.php'>Logout</a> | ";
echo "<a href='../auth/login.php'>Login Page</a> | ";
echo "<a href='../contact.php'>Contact Page</a>";

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    session_unset();
    session_destroy();
    echo "<p style='color:green'>Session đã được xóa! <a href='debug-session.php'>Refresh</a></p>";
}
?>
