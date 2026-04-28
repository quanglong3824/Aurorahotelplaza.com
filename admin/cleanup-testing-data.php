<?php
require_once __DIR__ . '/../config/database.php';

session_start();

$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$isAdmin) {
    die('<h2>Access Denied</h2><p>Bạn cần đăng nhập admin để chạy script này.</p><a href="/auth/login.php">Đăng nhập</a>');
}

$results = [];
$conn = getDB();
if (!$conn) {
    die('<h2>Database Connection Failed</h2>');
}

$steps = [
    'Step 1: Update rooms to available' => [
        'sql' => "UPDATE `rooms` SET `status` = 'available', `updated_at` = NOW() WHERE `room_id` IN (38, 39, 40, 42, 44, 45, 49, 50, 60, 61, 71, 72, 82, 83, 93, 94)",
        'type' => 'update'
    ],
    'Step 2: Delete booking_history' => [
        'sql' => "DELETE FROM `booking_history` WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18)",
        'type' => 'delete'
    ],
    'Step 3: Delete booking_extra_guests' => [
        'sql' => "DELETE FROM `booking_extra_guests` WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18)",
        'type' => 'delete'
    ],
    'Step 4: Delete test bookings' => [
        'sql' => "DELETE FROM `bookings` WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18) OR `special_requests` LIKE '%Testing Auto-fill%' OR `inquiry_message` LIKE '%TEST MAIL%' OR `inquiry_message` LIKE '%IT TEST%'",
        'type' => 'delete'
    ],
    'Step 5: Delete activity_logs for test users' => [
        'sql' => "DELETE FROM `activity_logs` WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29) OR `entity_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18)",
        'type' => 'delete'
    ],
    'Step 6: Delete contact_submissions test' => [
        'sql' => "DELETE FROM `contact_submissions` WHERE `email` LIKE '%tester%@%.com' OR `subject` LIKE '%TEST MAIL%' OR `subject` LIKE '%IT TEST%' OR `message` LIKE '%IT TEST%' OR `id` IN (2, 3)",
        'type' => 'delete'
    ],
    'Step 7: Delete test users' => [
        'sql' => "DELETE FROM `users` WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29) OR `email` LIKE 'tester%@%.com' OR `user_name` LIKE 'Khách Hàng Testing%'",
        'type' => 'delete'
    ],
    'Step 8: Delete csrf_tokens for deleted users' => [
        'sql' => "DELETE FROM `csrf_tokens` WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29)",
        'type' => 'delete'
    ]
];

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cleanup Testing Data - Aurora Hotel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; border-bottom: 2px solid #e74c3c; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #e74c3c; color: white; border-radius: 5px; text-decoration: none; margin: 10px 5px; }
        .btn:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; }
        pre { background: #282c34; color: #abb2bf; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Cleanup Testing Data - Aurora Hotel Plaza</h1>
    
    <?php if (!$confirm): ?>
        <div class="warning">
            <strong>WARNING:</strong> Script này sẽ xóa tất cả dữ liệu testing khỏi database!
            <ul>
                <li><strong>8 Users:</strong> user_id 16, 21-24, 27-29</li>
                <li><strong>10 Bookings:</strong> booking_id 1-5, 8-11, 18</li>
                <li><strong>16 Rooms:</strong> sẽ set lại thành available</li>
                <li><strong>Logs & History:</strong> activity_logs, booking_history liên quan</li>
                <li><strong>Contact submissions:</strong> test submissions</li>
            </ul>
        </div>
        
        <p>Database: <strong><?= DB_NAME ?></strong> (<?= DB_ENVIRONMENT ?>)</p>
        
        <a href="?confirm=yes" class="btn" onclick="return confirm('Bạn chắc chắn muốn xóa tất cả dữ liệu testing?')">RUN CLEANUP</a>
        <a href="/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
        
        <h3>Preview - Data to be deleted:</h3>
        
        <?php
        $previewQueries = [
            'Test Users' => "SELECT user_id, email, user_name FROM `users` WHERE user_id IN (16, 21, 22, 23, 24, 27, 28, 29) OR email LIKE 'tester%@%.com'",
            'Test Bookings' => "SELECT booking_id, booking_code, user_id, guest_name FROM `bookings` WHERE booking_id IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18) OR special_requests LIKE '%Testing Auto-fill%'",
            'Occupied Rooms (will be available)' => "SELECT room_id, room_number, status FROM `rooms` WHERE room_id IN (38, 39, 40, 42, 44, 45, 49, 50, 60, 61, 71, 72, 82, 83, 93, 94)"
        ];
        
        foreach ($previewQueries as $title => $sql) {
            echo "<h4>{$title}</h4>";
            try {
                $stmt = $conn->query($sql);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($data) {
                    echo "<table>";
                    echo "<tr>";
                    foreach (array_keys($data[0]) as $col) echo "<th>{$col}</th>";
                    echo "</tr>";
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $val) echo "<td>{$val}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "<p>Total: <strong>" . count($data) . "</strong> records</p>";
                } else {
                    echo "<p>No records found.</p>";
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
                } else {
                    echo "<div class='success'>Deleted <strong>{$affected}</strong> rows</div>";
                }
                $results[$stepName] = ['status' => 'success', 'affected' => $affected];
            } catch (PDOException $e) {
                echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
                $results[$stepName] = ['status' => 'error', 'message' => $e->getMessage()];
            }
            echo "</div>";
        }
        
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        ?>
        
        <h2>Verification Results:</h2>
        
        <?php
        $verifyQueries = [
            'Remaining test users' => "SELECT COUNT(*) as count FROM `users` WHERE email LIKE 'tester%@%.com'",
            'Remaining test bookings' => "SELECT COUNT(*) as count FROM `bookings` WHERE special_requests LIKE '%Testing%'",
            'Occupied rooms' => "SELECT COUNT(*) as count FROM `rooms` WHERE status = 'occupied'",
            'Total users now' => "SELECT COUNT(*) as count FROM `users`",
            'Total bookings now' => "SELECT COUNT(*) as count FROM `bookings`"
        ];
        
        echo "<table>";
        echo "<tr><th>Check</th><th>Result</th></tr>";
        foreach ($verifyQueries as $check => $sql) {
            try {
                $stmt = $conn->query($sql);
                $count = $stmt->fetchColumn();
                echo "<tr><td>{$check}</td><td><strong>{$count}</strong></td></tr>";
            } catch (PDOException $e) {
                echo "<tr><td>{$check}</td><td class='error'>Error</td></tr>";
            }
        }
        echo "</table>";
        ?>
        
        <div class="success">
            <h3>Cleanup Completed!</h3>
            <p>All testing data has been removed from the database.</p>
        </div>
        
        <a href="/admin/dashboard.php" class="btn">Back to Dashboard</a>
        <a href="/admin/bookings.php" class="btn btn-secondary">View Bookings</a>
        
    <?php endif; ?>
</body>
</html>