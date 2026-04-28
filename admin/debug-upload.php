<?php
/**
 * Debug Upload Issues
 */
session_start();
require_once '../config/database.php';
require_once '../config/environment.php';

echo '<pre>';
echo '=== DEBUG UPLOAD ===\n\n';

// 1. BASE_URL
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
    echo '  Writable: ' . (is_writable($path) ? 'YES' : 'NO') . '\n';
    if (is_dir($path)) {
        $files = scandir($path);
        echo '  Files: ' . count($files) - 2 . ' (excluding . and ..)\n';
    }
    echo '\n';
}

// 3. Gallery images in DB
echo '=== GALLERY IMAGES IN DB ===\n';
try {
    $db = getDB();
    $stmt = $db->query("SELECT gallery_id, title, image_url, category FROM gallery LIMIT 10");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($images as $img) {
        echo 'ID: ' . $img['gallery_id'] . '\n';
        echo 'Title: ' . $img['title'] . '\n';
        echo 'URL: ' . $img['image_url'] . '\n';
        echo 'Category: ' . $img['category'] . '\n';
        
        // Check if file exists
        $localPath = parse_url($img['image_url'], PHP_URL_PATH);
        $fullPath = __DIR__ . '/../' . $localPath;
        echo 'Local path: ' . $fullPath . '\n';
        echo 'File exists: ' . (file_exists($fullPath) ? 'YES' : 'NO') . '\n';
        echo '\n---\n\n';
    }
} catch (Throwable $e) {
    echo 'DB Error: ' . $e->getMessage() . '\n';
}

// 4. Test upload folder writable
echo '=== TEST UPLOAD ===\n';
$testDir = __DIR__ . '/../uploads/test/';
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
    echo 'Created test folder: ' . (is_dir($testDir) ? 'YES' : 'NO') . '\n';
}
$testFile = $testDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo 'Write test: SUCCESS\n';
    echo 'File: ' . $testFile . '\n';
    unlink($testFile);
} else {
    echo 'Write test: FAILED\n';
}

echo '</pre>';