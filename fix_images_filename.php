<?php
/**
 * Script đổi tên file ảnh về lowercase và thay khoảng trắng bằng dấu gạch ngang
 * Aurora Hotel Plaza
 */

// Cấu hình
$targetDir = __DIR__ . '/assets/img';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

// Kết quả
$renamed = [];
$errors = [];
$skipped = [];

/**
 * Chuẩn hóa tên file
 */
function normalizeFilename($filename) {
    $filename = strtolower($filename);
    $filename = str_replace(' ', '-', $filename);
    $filename = preg_replace('/\s+/', '-', $filename);
    return $filename;
}

/**
 * Tạo tên file unique nếu đã tồn tại
 */
function getUniqueFilename($dir, $filename) {
    $path = $dir . '/' . $filename;
    if (!file_exists($path)) {
        return $filename;
    }
    
    $info = pathinfo($filename);
    $name = $info['filename'];
    $ext = $info['extension'];
    $counter = 1;
    
    while (file_exists($dir . '/' . $name . '-' . $counter . '.' . $ext)) {
        $counter++;
    }
    
    return $name . '-' . $counter . '.' . $ext;
}

/**
 * Quét và đổi tên file đệ quy
 */
function processDirectory($dir, $allowedExtensions, &$renamed, &$errors, &$skipped) {
    if (!is_dir($dir)) {
        $errors[] = "Thư mục không tồn tại: $dir";
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $filesToRename = [];
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowedExtensions)) {
                $filesToRename[] = $file->getPathname();
            }
        }
    }
    
    foreach ($filesToRename as $filepath) {
        $dir = dirname($filepath);
        $oldFilename = basename($filepath);
        $newFilename = normalizeFilename($oldFilename);
        
        // Nếu tên không thay đổi, bỏ qua
        if ($oldFilename === $newFilename) {
            $skipped[] = $filepath;
            continue;
        }
        
        // Kiểm tra file đích đã tồn tại chưa
        $newFilename = getUniqueFilename($dir, $newFilename);
        $newFilepath = $dir . '/' . $newFilename;
        
        // Đổi tên file
        if (rename($filepath, $newFilepath)) {
            $renamed[] = [
                'old' => $filepath,
                'new' => $newFilepath
            ];
        } else {
            $errors[] = "Không thể đổi tên: $filepath";
        }
    }
}

// Chạy script
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix Images Filename</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee}";
echo ".success{color:#4ade80}.error{color:#f87171}.skip{color:#fbbf24}";
echo "h2{color:#d4af37}pre{background:#0f0f1a;padding:10px;border-radius:8px;overflow-x:auto}</style></head><body>";

echo "<h1>🔧 Fix Images Filename</h1>";
echo "<p>Thư mục: <code>$targetDir</code></p>";

processDirectory($targetDir, $allowedExtensions, $renamed, $errors, $skipped);

// Hiển thị kết quả
echo "<h2>✅ Đã đổi tên thành công (" . count($renamed) . " file)</h2>";
if (!empty($renamed)) {
    echo "<pre>";
    foreach ($renamed as $item) {
        $oldName = str_replace($targetDir, '', $item['old']);
        $newName = str_replace($targetDir, '', $item['new']);
        echo "<span class='success'>$oldName → $newName</span>\n";
    }
    echo "</pre>";
} else {
    echo "<p>Không có file nào cần đổi tên.</p>";
}

echo "<h2>⚠️ Lỗi (" . count($errors) . ")</h2>";
if (!empty($errors)) {
    echo "<pre>";
    foreach ($errors as $err) {
        echo "<span class='error'>$err</span>\n";
    }
    echo "</pre>";
} else {
    echo "<p>Không có lỗi.</p>";
}

echo "<h2>⏭️ Bỏ qua (" . count($skipped) . " file đã chuẩn)</h2>";

echo "<hr><p><strong>Hoàn tất!</strong> Tổng: " . (count($renamed) + count($skipped)) . " file ảnh được quét.</p>";
echo "</body></html>";
