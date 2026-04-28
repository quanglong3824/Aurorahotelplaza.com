<?php
/**
 * Debug Banner Display Issue
 */
require_once 'config/database.php';
require_once 'helpers/image-helper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Banner Debug</h2>";

try {
    $db = getDB();
    
    // 1. Check all banners
    echo "<h3>1. Tất cả banners trong database:</h3>";
    $stmt = $db->query("SELECT banner_id, title, status, image_desktop, sort_order FROM banners ORDER BY sort_order ASC, created_at DESC");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all)) {
        echo "<p style='color:red'><strong>Không có banner nào trong database!</strong></p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Status (raw bytes)</th><th>Image Desktop</th><th>Sort Order</th></tr>";
        foreach ($all as $b) {
            $statusRaw = bin2hex($b['status']);
            $statusCheck = ($b['status'] === 'active') ? '✅ MATCH' : '❌ NOT MATCH';
            echo "<tr>";
            echo "<td>{$b['banner_id']}</td>";
            echo "<td>{$b['title']}</td>";
            echo "<td>{$b['status']}</td>";
            echo "<td>{$statusRaw} {$statusCheck}</td>";
            echo "<td>" . htmlspecialchars($b['image_desktop']) . "</td>";
            echo "<td>{$b['sort_order']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Check banners with status = 'active' (frontend query)
    echo "<h3>2. Banners với status = 'active' (query frontend):</h3>";
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, image_mobile, link_url FROM banners WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
    $active = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($active)) {
        echo "<p style='color:red'><strong>Không có banner active nào!</strong></p>";
    } else {
        echo "<p style='color:green'><strong>Tìm thấy " . count($active) . " banner active:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Title</th><th>Image Desktop</th><th>Image URL (processed)</th></tr>";
        foreach ($active as $b) {
            $imgUrl = imgUrl($b['image_desktop']);
            echo "<tr>";
            echo "<td>{$b['banner_id']}</td>";
            echo "<td>{$b['title']}</td>";
            echo "<td>" . htmlspecialchars($b['image_desktop']) . "</td>";
            echo "<td><a href='{$imgUrl}' target='_blank'>{$imgUrl}</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check enum values
    echo "<h3>3. Enum status column info:</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM banners LIKE 'status'");
    $enumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($enumInfo, true) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/banners.php'>Quay lại Admin Banners</a></p>";