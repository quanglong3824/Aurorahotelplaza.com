<?php
/**
 * Debug Database & Upload Issues
 */
session_start();
require_once '../config/database.php';
require_once '../config/environment.php';
require_once '../helpers/image-helper.php';

echo '<pre style="font-family:monospace;background:#1a1a2e;color:#eee;padding:20px;border-radius:8px">';
echo '=== DEBUG DATABASE & UPLOAD ===\n\n';

// 1. BASE_URL
echo '=== BASE_URL ===\n';
echo 'BASE_URL: ' . BASE_URL . '\n';
echo 'getBaseUrl(): ' . getBaseUrl() . '\n\n';

// 2. Upload folders
echo '=== UPLOAD FOLDERS ===\n';
$folders = [
    'uploads/' => __DIR__ . '/../uploads/',
    'uploads/banners/' => __DIR__ . '/../uploads/banners/',
    'uploads/gallery/' => __DIR__ . '/../uploads/gallery/',
];
foreach ($folders as $name => $path) {
    echo $name . '\n';
    echo '  Path: ' . $path . '\n';
    echo '  Exists: ' . (is_dir($path) ? 'YES' : 'NO') . '\n';
    echo '  Writable: ' . (is_writable($path) ? 'YES' : 'NO') . '\n\n';
}

// 3. BANNERS table structure
echo '=== BANNERS TABLE ===\n';
try {
    $db = getDB();
    $stmt = $db->query('DESCRIBE banners');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' (' . $col['Type'] . ')\n';
    }
    
    echo '\nActive banners:\n';
    $stmt = $db->query("SELECT banner_id, title, image_desktop, status FROM banners WHERE status = 'active' ORDER BY sort_order LIMIT 5");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($banners as $b) {
        echo '  #' . $b['banner_id'] . ': ' . $b['title'] . '\n';
        echo '    image_desktop: ' . $b['image_desktop'] . '\n';
        echo '    URL: ' . imgUrl($b['image_desktop']) . '\n';
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . '\n';
}

// 4. GALLERY table structure
echo '\n=== GALLERY TABLE ===\n';
try {
    $stmt = $db->query('DESCRIBE gallery');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' (' . $col['Type'] . ')\n';
    }
    
    echo '\nRecent gallery images:\n';
    $stmt = $db->query("SELECT gallery_id, title, image_url, category FROM gallery ORDER BY created_at DESC LIMIT 5");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $img) {
        echo '  #' . $img['gallery_id'] . ': ' . $img['title'] . '\n';
        echo '    image_url: ' . $img['image_url'] . '\n';
        echo '    URL: ' . imgUrl($img['image_url']) . '\n';
        
        $localPath = parse_url($img['image_url'], PHP_URL_PATH);
        if ($localPath) {
            $fullPath = __DIR__ . '/../' . ltrim($localPath, '/');
            echo '    Local: ' . $fullPath . '\n';
            echo '    Exists: ' . (file_exists($fullPath) ? 'YES' : 'NO') . '\n';
        }
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . '\n';
}

// 5. Test upload folder writable
echo '\n=== TEST WRITE ===\n';
$testDir = __DIR__ . '/../uploads/test/';
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}
$testFile = $testDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo 'Write test: SUCCESS\n';
    echo 'File: ' . $testFile . '\n';
    unlink($testFile);
    rmdir($testDir);
} else {
    echo 'Write test: FAILED\n';
}

echo '</pre>';