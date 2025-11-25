<?php
require_once 'config/database.php';
require_once 'helpers/image-helper.php';

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' ORDER BY sort_order ASC");
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Hiển Thị Ảnh Room Types</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #d4af37;
            margin-bottom: 20px;
            text-align: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .image-container {
            width: 100%;
            height: 180px;
            background: #e0e0e0;
            position: relative;
            overflow: hidden;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .error {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #f44336;
            font-size: 12px;
            padding: 10px;
            text-align: center;
        }
        .info {
            padding: 15px;
        }
        .name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .category {
            display: inline-block;
            padding: 3px 8px;
            background: #d4af37;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 8px;
        }
        .path {
            font-size: 11px;
            color: #666;
            word-break: break-all;
            margin-top: 5px;
            padding: 5px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .status {
            font-size: 10px;
            margin-top: 5px;
        }
        .status.success {
            color: #4caf50;
        }
        .status.error {
            color: #f44336;
        }
        .price {
            color: #d4af37;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>🖼️ Test Hiển Thị Ảnh Room Types</h1>
    
    <div class="grid">
        <?php foreach ($room_types as $room): 
            $thumbnail = normalizeImagePath($room['thumbnail']);
            
            // Tạo đường dẫn tuyệt đối cho file system
            $basePath = dirname($_SERVER['SCRIPT_FILENAME']);
            $fullPath = $basePath . $thumbnail;
            $fileExists = file_exists($fullPath);
            
            // Tạo URL tương đối cho browser
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            $imageUrl = $baseUrl . $thumbnail;
        ?>
            <div class="card">
                <div class="image-container">
                    <?php if ($fileExists): ?>
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                             alt="<?php echo htmlspecialchars($room['type_name']); ?>"
                             onerror="this.parentElement.innerHTML='<div class=\'error\'>❌ Ảnh không tải được<br>URL: <?php echo htmlspecialchars($imageUrl); ?></div>'">
                    <?php else: ?>
                        <div class="error">❌ File không tồn tại</div>
                    <?php endif; ?>
                </div>
                
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($room['type_name']); ?></div>
                    <span class="category"><?php echo $room['category'] === 'room' ? 'Phòng' : 'Căn hộ'; ?></span>
                    
                    <div class="price">
                        <?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ/đêm
                    </div>
                    
                    <div class="path">
                        <strong>DB:</strong> <?php echo htmlspecialchars($room['thumbnail']); ?><br>
                        <strong>Normalized:</strong> <?php echo htmlspecialchars($thumbnail); ?><br>
                        <strong>Image URL:</strong> <?php echo htmlspecialchars($imageUrl); ?>
                    </div>
                    
                    <div class="status <?php echo $fileExists ? 'success' : 'error'; ?>">
                        <?php if ($fileExists): ?>
                            ✅ File tồn tại: <?php echo $fullPath; ?>
                        <?php else: ?>
                            ❌ File không tồn tại: <?php echo $fullPath; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="max-width: 1400px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px;">
        <h2 style="color: #d4af37; margin-bottom: 15px;">📊 Thống kê</h2>
        <p><strong>Tổng số loại phòng/căn hộ:</strong> <?php echo count($room_types); ?></p>
        <p><strong>Phòng:</strong> <?php echo count(array_filter($room_types, fn($r) => $r['category'] === 'room')); ?></p>
        <p><strong>Căn hộ:</strong> <?php echo count(array_filter($room_types, fn($r) => $r['category'] === 'apartment')); ?></p>
        
        <h3 style="color: #d4af37; margin-top: 20px; margin-bottom: 10px;">🔍 Debug Info</h3>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        <p><strong>Script Filename:</strong> <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
        <p><strong>Script Path (PHP_SELF):</strong> <?php echo $_SERVER['PHP_SELF']; ?></p>
        <p><strong>Base Path (dirname):</strong> <?php echo dirname($_SERVER['PHP_SELF']); ?></p>
        <p><strong>Base URL:</strong> <?php echo $baseUrl ?? 'N/A'; ?></p>
        
        <h3 style="color: #d4af37; margin-top: 20px; margin-bottom: 10px;">🧪 Test URL</h3>
        <p>Thử truy cập trực tiếp: <a href="/quanglong3824/Aurorahotelplaza.com/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" target="_blank">/quanglong3824/Aurorahotelplaza.com/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg</a></p>
        <p>Hoặc: <a href="assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" target="_blank">assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg</a></p>
    </div>
</body>
</html>
