<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật Database - Chat Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d4af37;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 10px;
        }
        .success {
            color: #22c55e;
            background: #f0fdf4;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fef2f2;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #3b82f6;
            background: #eff6ff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        button {
            background: #d4af37;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #b8941f;
        }
        pre {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cập nhật Database - Chat Messages</h1>
        
        <?php
        require_once 'config/database.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = getDB();
                
                // Kiểm tra xem cột guest_id đã tồn tại chưa
                $stmt = $db->query("SHOW COLUMNS FROM chat_messages LIKE 'guest_id'");
                $column_exists = $stmt->rowCount() > 0;
                
                if (!$column_exists) {
                    echo '<div class="info">Đang thêm cột guest_id vào bảng chat_messages...</div>';
                    
                    // Thêm cột guest_id
                    $db->exec("ALTER TABLE chat_messages ADD COLUMN guest_id VARCHAR(100) DEFAULT NULL AFTER sender_id");
                    echo '<div class="success">✓ Đã thêm cột guest_id</div>';
                    
                    // Tạo index cho guest_id
                    $db->exec("ALTER TABLE chat_messages ADD KEY idx_guest_messages (guest_id, created_at)");
                    echo '<div class="success">✓ Đã tạo index idx_guest_messages</div>';
                    
                    echo '<div class="success" style="margin-top:20px; font-size:18px;">
                        ✓ Cập nhật database thành công! Bây giờ khách vãng lai có thể gửi tin nhắn.
                    </div>';
                    
                } else {
                    echo '<div class="success">Cột guest_id đã tồn tại. Không cần cập nhật.</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="info">
                <p>Click nút bên dưới để cập nhật database hỗ trợ guest chat:</p>
                <ul>
                    <li>Thêm cột <code>guest_id</code> vào bảng <code>chat_messages</code></li>
                    <li>Tạo index cho cột <code>guest_id</code></li>
                </ul>
            </div>';
        }
        
        echo '<form method="POST">';
        echo '<button type="submit">Cập nhật Database</button>';
        echo '</form>';
        
        echo '<div style="margin-top:30px; padding-top:20px; border-top:1px solid #eee; font-size:14px; color:#666;">
            <p>File này sẽ tự động xóa sau khi cập nhật xong.</p>
        </div>';
        ?>
    </div>
</body>
</html>
