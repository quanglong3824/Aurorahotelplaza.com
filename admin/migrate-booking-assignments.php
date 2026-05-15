<?php
/**
 * Migration Helper: Booking Assignment System
 * Run via browser: https://aurorahotelplaza.com/admin/migrate-booking-assignments.php
 * 
 * This script safely creates the booking_assignments table and indexes
 * WITHOUT affecting existing booking data.
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration: Booking Assignments</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; max-width: 800px; margin: 0 auto; }
        h1 { color: #d4af37; border-bottom: 2px solid #d4af37; padding-bottom: 0.5rem; }
        .step { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .step h3 { margin: 0 0 0.5rem; color: #94a3b8; font-size: 0.875rem; text-transform: uppercase; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        pre { background: #0f172a; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.8rem; }
        .btn { display: inline-block; background: #d4af37; color: #000; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 1rem; }
        .btn:hover { background: #e5c04a; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1rem 0; }
        .stat { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 1rem; text-align: center; }
        .stat .num { font-size: 2rem; font-weight: 700; color: #d4af37; }
        .stat .label { font-size: 0.875rem; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>🔧 Migration: Booking Assignment System</h1>
    <p>Tạo bảng <code>booking_assignments</code> và index an toàn — không ảnh hưởng dữ liệu bookings hiện có.</p>

    <?php
    $results = [];
    $hasError = false;

    try {
        $db = getDB();

        // ── STEP 1: Check bookings table health ──────────────────────────────
        $stmt = $db->query("SELECT COUNT(*) as total FROM bookings");
        $bookingCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE user_role IN ('admin', 'sale', 'receptionist') AND status = 'active'");
        $staffCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $results[] = [
            'step' => 'Kiểm tra dữ liệu hiện tại',
            'status' => 'success',
            'message' => "Bookings: <strong>{$bookingCount}</strong> đơn | Staff active: <strong>{$staffCount}</strong> người"
        ];

        // ── STEP 2: Drop old table if exists (with bad foreign keys) ──────────
        $stmt = $db->query("SHOW TABLES LIKE 'booking_assignments'");
        $tableExists = $stmt->fetch();

        if ($tableExists) {
            // Check if it has foreign keys
            $stmt = $db->query("
                SELECT COUNT(*) as fk_count 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'booking_assignments' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $fkCount = $stmt->fetch(PDO::FETCH_ASSOC)['fk_count'];

            if ($fkCount > 0) {
                $db->exec("DROP TABLE IF EXISTS `booking_assignments`");
                $results[] = [
                    'step' => 'Xóa bảng cũ (có foreign keys)',
                    'status' => 'warning',
                    'message' => "Đã xóa bảng cũ có {$fkCount} foreign key constraints để tạo lại an toàn hơn"
                ];
            } else {
                $results[] = [
                    'step' => 'Bảng đã tồn tại',
                    'status' => 'info',
                    'message' => 'Bảng booking_assignments đã tồn tại (không có foreign keys) — bỏ qua'
                ];
            }
        } else {
            $results[] = [
                'step' => 'Kiểm tra bảng cũ',
                'status' => 'info',
                'message' => 'Bảng booking_assignments chưa tồn tại — sẽ tạo mới'
            ];
        }

        // ── STEP 3: Create table (NO foreign keys, safe mode) ─────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS `booking_assignments` (
              `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
              `booking_id` INT(11) NOT NULL,
              `assigned_to` INT(11) NOT NULL COMMENT 'User who accepted the booking',
              `assigned_by` INT(11) NOT NULL COMMENT 'User who made the assignment',
              `accepted_at` TIMESTAMP NULL DEFAULT NULL,
              `transferred_at` TIMESTAMP NULL DEFAULT NULL,
              `transfer_reason` TEXT DEFAULT NULL,
              `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`assignment_id`),
              KEY `idx_booking_id` (`booking_id`),
              KEY `idx_assigned_to` (`assigned_to`),
              KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Tracks which staff member handles each pending booking'
        ");

        $results[] = [
            'step' => 'Tạo bảng booking_assignments',
            'status' => 'success',
            'message' => 'Table created successfully — KHÔNG có foreign key constraints (an toàn)'
        ];

        // ── STEP 4: Add index to bookings table (safe) ────────────────────────
        $stmt = $db->query("SHOW INDEX FROM bookings WHERE Key_name = 'idx_status_created'");
        $indexExists = $stmt->fetch();

        if (!$indexExists) {
            try {
                $db->exec("ALTER TABLE `bookings` ADD INDEX `idx_status_created` (`status`, `created_at`)");
                $results[] = [
                    'step' => 'Thêm index bookings',
                    'status' => 'success',
                    'message' => 'Index idx_status_created added successfully'
                ];
            } catch (Exception $e) {
                $results[] = [
                    'step' => 'Thêm index bookings',
                    'status' => 'warning',
                    'message' => 'Index đã tồn tại hoặc không thể thêm: ' . $e->getMessage()
                ];
            }
        } else {
            $results[] = [
                'step' => 'Kiểm tra index bookings',
                'status' => 'info',
                'message' => 'Index idx_status_created đã tồn tại — bỏ qua'
            ];
        }

        // ── STEP 5: Verify bookings still load correctly ──────────────────────
        $stmt = $db->query("
            SELECT b.*, u.full_name as user_name, rt.type_name, r.room_number
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $testBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results[] = [
            'step' => 'Kiểm tra load bookings',
            'status' => count($testBookings) > 0 ? 'success' : 'error',
            'message' => count($testBookings) > 0 
                ? "✅ Load thành công " . count($testBookings) . " đơn gần nhất" 
                : "❌ Không load được booking nào!"
        ];

        // ── STEP 6: Verify booking_assignments table ──────────────────────────
        $stmt = $db->query("SELECT COUNT(*) as total FROM booking_assignments");
        $assignCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_assignments'
            ORDER BY ORDINAL_POSITION
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results[] = [
            'step' => 'Xác nhận bảng booking_assignments',
            'status' => 'success',
            'message' => "Bảng có {$assignCount} records, " . count($columns) . " columns"
        ];

    } catch (Exception $e) {
        $hasError = true;
        $results[] = [
            'step' => 'LỖI MIGRATION',
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
    ?>

    <!-- Display Results -->
    <?php foreach ($results as $r): ?>
        <div class="step">
            <h3><?php echo $r['step']; ?></h3>
            <p class="<?php echo $r['status']; ?>"><?php echo $r['message']; ?></p>
        </div>
    <?php endforeach; ?>

    <?php if (!$hasError): ?>
        <div class="stats">
            <div class="stat">
                <div class="num"><?php echo $bookingCount ?? 0; ?></div>
                <div class="label">Bookings</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo $staffCount ?? 0; ?></div>
                <div class="label">Staff Active</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo $assignCount ?? 0; ?></div>
                <div class="label">Assignments</div>
            </div>
        </div>
    <?php endif; ?>

    <h2 style="color: #94a3b8; margin-top: 2rem;">Cấu trúc bảng booking_assignments:</h2>
    <?php if (!empty($columns)): ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <tr style="border-bottom: 1px solid #334155;">
                <th style="text-align: left; padding: 0.5rem; color: #94a3b8;">Column</th>
                <th style="text-align: left; padding: 0.5rem; color: #94a3b8;">Type</th>
                <th style="text-align: left; padding: 0.5rem; color: #94a3b8;">Nullable</th>
            </tr>
            <?php foreach ($columns as $col): ?>
                <tr style="border-bottom: 1px solid #1e293b;">
                    <td style="padding: 0.5rem; color: #d4af37;"><?php echo htmlspecialchars($col['COLUMN_NAME']); ?></td>
                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($col['COLUMN_TYPE']); ?></td>
                    <td style="padding: 0.5rem;"><?php echo $col['IS_NULLABLE'] === 'YES' ? 'YES' : '<span class="error">NO</span>'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #334155;">
        <a href="index.php" class="btn">← Quay lại Admin</a>
        <a href="bookings.php" class="btn" style="background: #22c55e; margin-left: 1rem;">Xem Bookings →</a>
    </div>
</body>
</html>
