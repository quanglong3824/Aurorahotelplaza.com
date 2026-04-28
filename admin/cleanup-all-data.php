<?php
require_once __DIR__ . '/../config/database.php';

session_start();

$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$isAdmin) {
    die('<h2>Access Denied</h2><p>Bạn cần đăng nhập admin để chạy script này.</p><a href="/auth/login.php">Đăng nhập</a>');
}

$conn = getDB();
if (!$conn) {
    die('<h2>Database Connection Failed</h2>');
}

$keepUserIds = [7, 15, 30, 31, 33, 34, 36, 37];
$deleteUserIds = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 32, 35];

$keepBookingIds = [13, 14, 16, 17];
$deleteBookingIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 15, 18, 19];

$roomsToAvailable = [38, 39, 40, 41, 42, 43, 44, 45, 49, 50, 60, 61, 71, 72, 82, 83, 93, 94];

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cleanup All Data - Aurora Hotel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 30px auto; padding: 20px; }
        h1, h2, h3 { color: #333; }
        h1 { border-bottom: 3px solid #e74c3c; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e7f3ff; border: 1px solid #17a2b8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 25px; background: #e74c3c; color: white; border-radius: 5px; text-decoration: none; margin: 10px 5px; font-weight: bold; }
        .btn:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        pre { background: #282c34; color: #abb2bf; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .keep { background: #d4edda; }
        .delete { background: #f8d7da; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
    <h1>Cleanup All Data - Aurora Hotel Plaza</h1>
    
    <?php if (!$confirm): ?>
        <div class="warning">
            <strong>CẢNH BÁO:</strong> Script này sẽ xóa dữ liệu production! Vui lòng kiểm tra kỹ trước chạy.
        </div>
        
        <h2>Dữ liệu sẽ GIỮ:</h2>
        <div class="info">
            <h3>Users (8 người):</h3>
            <ul>
                <li><strong>7</strong> - Administrator (admin@aurorahotelplaza.com)</li>
                <li><strong>15</strong> - Developer (longdev.08@gmail.com)</li>
                <li><strong>30</strong> - Shamseer Dhanya (shamseer.dhanya@provisionllc.com)</li>
                <li><strong>31</strong> - CHUMING CHEN (akon.chan@provisionllc.com)</li>
                <li><strong>33</strong> - Donald Quach (quachd1012@gmail.com)</li>
                <li><strong>34</strong> - Murali (muralirs3@gmail.com)</li>
                <li><strong>36</strong> - Sale 01 (info@aurorahotelplaza.com)</li>
                <li><strong>37</strong> - Sale 02 (booking@aurorahotelplaza.com)</li>
            </ul>
            
            <h3>Bookings (4 bookings):</h3>
            <ul>
                <li><strong>13</strong> - Shamseer Dhanya (24/4 - 30/4)</li>
                <li><strong>14</strong> - CHUMING CHEN (24/4 - 30/4)</li>
                <li><strong>16</strong> - Donald Quach (6/9 - 9/9)</li>
                <li><strong>17</strong> - Murali (26/4 - 26/5)</li>
            </ul>
        </div>
        
        <h2>Dữ liệu sẽ XÓA:</h2>
        <div class="error">
            <h3>Users (14 người):</h3>
            <ul>
                <li>16-29: Testing users (đã xóa ở bước 1 nếu chạy script trước)</li>
                <li><strong>32</strong> - ThanhBuj (thanhbuj@gmail.com)</li>
                <li><strong>35</strong> - QUANG LONG (quanglong.3824@gmail.com - test)</li>
            </ul>
            
            <h3>Bookings (15 bookings):</h3>
            <ul>
                <li>1-12, 15, 18, 19: Test bookings + booking của user bị xóa</li>
            </ul>
            
            <h3>Chat (ALL):</h3>
            <ul>
                <li>17 conversations</li>
                <li>97 messages</li>
            </ul>
            
            <h3>Rooms (18 rooms):</h3>
            <ul>
                <li>Set lại thành 'available'</li>
            </ul>
            
            <h3>Other:</h3>
            <ul>
                <li>Activity logs liên quan</li>
                <li>Booking history liên quan</li>
                <li>Chat typing records</li>
            </ul>
        </div>
        
        <p>Database: <strong><?= DB_NAME ?></strong> (<?= DB_ENVIRONMENT ?>)</p>
        
        <a href="?confirm=yes" class="btn" onclick="return confirm('BẠN CHẮC CHẮN MUỐN XÓA TẤT CẢ DỮ LIỆU NÀY?')">RUN CLEANUP</a>
        <a href="/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
        
        <h2>Preview - Current Data:</h2>
        
        <?php
        $previewQueries = [
            'Users - GIỮ' => "SELECT user_id, email, user_name, role FROM `users` WHERE user_id IN (7, 15, 30, 31, 33, 34, 36, 37) ORDER BY user_id",
            'Users - XÓA' => "SELECT user_id, email, user_name, role FROM `users` WHERE user_id IN (16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 32, 35) ORDER BY user_id",
            'Bookings - GIỮ' => "SELECT booking_id, booking_code, user_id, guest_name, check_in_date, status FROM `bookings` WHERE booking_id IN (13, 14, 16, 17)",
            'Bookings - XÓA' => "SELECT booking_id, booking_code, user_id, guest_name, check_in_date, status FROM `bookings` WHERE booking_id IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 15, 18, 19)",
            'Chat Conversations' => "SELECT conversation_id, subject, status, last_message_at FROM `chat_conversations` ORDER BY conversation_id",
            'Occupied Rooms' => "SELECT room_id, room_number, room_type_id, status FROM `rooms` WHERE status = 'occupied'"
        ];
        
        foreach ($previewQueries as $title => $sql) {
            $isKeep = strpos($title, 'GIỮ') !== false;
            $class = $isKeep ? 'keep' : (strpos($title, 'XÓA') !== false ? 'delete' : '');
            
            echo "<h3>{$title}</h3>";
            try {
                $stmt = $conn->query($sql);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($data) {
                    echo "<table class='{$class}'>";
                    echo "<tr>";
                    foreach (array_keys($data[0]) as $col) echo "<th>{$col}</th>";
                    echo "</tr>";
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "<p>Total: <strong>" . count($data) . "</strong> records</p>";
                } else {
                    echo "<p>No records (already deleted or empty).</p>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
            }
        }
        ?>
        
    <?php else: ?>
        <h2>Executing Cleanup...</h2>
        
        <?php
        $conn->exec("SET FOREIGN_KEY_CHECKS=0");
        
        $steps = [
            'Step 1: Delete ALL chat_messages' => [
                'sql' => "DELETE FROM `chat_messages`",
                'type' => 'delete'
            ],
            'Step 2: Delete ALL chat_conversations' => [
                'sql' => "DELETE FROM `chat_conversations`",
                'type' => 'delete'
            ],
            'Step 3: Delete ALL chat_typing' => [
                'sql' => "DELETE FROM `chat_typing`",
                'type' => 'delete'
            ],
            'Step 4: Update rooms to available' => [
                'sql' => "UPDATE `rooms` SET `status` = 'available', `updated_at` = NOW() WHERE `room_id` IN (" . implode(',', $roomsToAvailable) . ")",
                'type' => 'update'
            ],
            'Step 5: Delete booking_history for deleted bookings' => [
                'sql' => "DELETE FROM `booking_history` WHERE `booking_id` IN (" . implode(',', $deleteBookingIds) . ")",
                'type' => 'delete'
            ],
            'Step 6: Delete booking_extra_guests for deleted bookings' => [
                'sql' => "DELETE FROM `booking_extra_guests` WHERE `booking_id` IN (" . implode(',', $deleteBookingIds) . ")",
                'type' => 'delete'
            ],
            'Step 7: Delete bookings of deleted users' => [
                'sql' => "DELETE FROM `bookings` WHERE `booking_id` IN (" . implode(',', $deleteBookingIds) . ")",
                'type' => 'delete'
            ],
            'Step 8: Delete activity_logs for deleted users' => [
                'sql' => "DELETE FROM `activity_logs` WHERE `user_id` IN (" . implode(',', $deleteUserIds) . ") OR `entity_id` IN (" . implode(',', $deleteBookingIds) . ")",
                'type' => 'delete'
            ],
            'Step 9: Delete contact_submissions test' => [
                'sql' => "DELETE FROM `contact_submissions` WHERE `email` LIKE '%tester%@%' OR `id` IN (2, 3, 5)",
                'type' => 'delete'
            ],
            'Step 10: Delete users (except kept ones)' => [
                'sql' => "DELETE FROM `users` WHERE `user_id` IN (" . implode(',', $deleteUserIds) . ")",
                'type' => 'delete'
            ],
            'Step 11: Delete csrf_tokens for deleted users' => [
                'sql' => "DELETE FROM `csrf_tokens` WHERE `user_id` IN (" . implode(',', $deleteUserIds) . ")",
                'type' => 'delete'
            ],
            'Step 12: Delete contact_submissions from AI collected' => [
                'sql' => "DELETE FROM `contact_submissions` WHERE `email` LIKE '%ai_collected@%'",
                'type' => 'delete'
            ]
        ];
        
        $totalDeleted = 0;
        $totalUpdated = 0;
        
        foreach ($steps as $stepName => $stepData) {
            echo "<div class='step'>";
            echo "<strong>{$stepName}</strong>";
            echo "<pre>" . htmlspecialchars($stepData['sql']) . "</pre>";
            
            try {
                $stmt = $conn->prepare($stepData['sql']);
                $stmt->execute();
                $affected = $stmt->rowCount();
                
                if ($stepData['type'] === 'update') {
                    echo "<div class='success'>Updated <strong>{$affected}</strong> rows</div>";
                    $totalUpdated += $affected;
                } else {
                    echo "<div class='success'>Deleted <strong>{$affected}</strong> rows</div>";
                    $totalDeleted += $affected;
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
            }
            echo "</div>";
        }
        
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        ?>
        
        <div class="success">
            <h2>Cleanup Completed!</h2>
            <p>Total deleted: <strong><?= $totalDeleted ?></strong> rows</p>
            <p>Total updated: <strong><?= $totalUpdated ?></strong> rows</p>
        </div>
        
        <h2>Verification - Remaining Data:</h2>
        
        <?php
        $verifyQueries = [
            'Users remaining' => "SELECT user_id, email, user_name, role FROM `users` ORDER BY user_id",
            'Bookings remaining' => "SELECT booking_id, booking_code, guest_name, check_in_date, status FROM `bookings` ORDER BY booking_id",
            'Rooms occupied' => "SELECT room_id, room_number, status FROM `rooms` WHERE status = 'occupied'",
            'Chat conversations' => "SELECT COUNT(*) as count FROM `chat_conversations`",
            'Chat messages' => "SELECT COUNT(*) as count FROM `chat_messages`"
        ];
        
        foreach ($verifyQueries as $title => $sql) {
            echo "<h3>{$title}</h3>";
            try {
                $stmt = $conn->query($sql);
                if (strpos($sql, 'COUNT') !== false) {
                    $count = $stmt->fetchColumn();
                    echo "<p>Count: <strong>{$count}</strong></p>";
                } else {
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($data) {
                        echo "<table>";
                        echo "<tr>";
                        foreach (array_keys($data[0]) as $col) echo "<th>{$col}</th>";
                        echo "</tr>";
                        foreach ($data as $row) {
                            echo "<tr>";
                            foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>No records.</p>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
            }
        }
        ?>
        
        <div class="info">
            <h3>Remaining Users (Should be 8):</h3>
            <ul>
                <li>Administrator (admin)</li>
                <li>Developer (admin)</li>
                <li>Shamseer Dhanya (customer)</li>
                <li>CHUMING CHEN (customer)</li>
                <li>Donald Quach (customer)</li>
                <li>Murali (customer)</li>
                <li>Sale 01 (sale)</li>
                <li>Sale 02 (sale)</li>
            </ul>
            
            <h3>Remaining Bookings (Should be 4):</h3>
            <ul>
                <li>BK13 - Shamseer Dhanya</li>
                <li>BK14 - CHUMING CHEN</li>
                <li>BK16 - Donald Quach</li>
                <li>BK17 - Murali</li>
            </ul>
        </div>
        
        <a href="/admin/dashboard.php" class="btn btn-success">Back to Dashboard</a>
        <a href="/admin/bookings.php" class="btn btn-secondary">View Bookings</a>
        
    <?php endif; ?>
</body>
</html>