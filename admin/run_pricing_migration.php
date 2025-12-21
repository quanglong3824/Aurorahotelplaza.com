<?php
/**
 * Aurora Hotel Plaza - Pricing Migration Runner
 * File: admin/run_pricing_migration.php
 * Date: 2025-12-21
 * Description: Chạy migration cập nhật giá phòng theo bảng giá lễ tân
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if running from admin
session_start();
require_once '../config/database.php';

// Verify admin access (for production)
$isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin']);
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI && !$isAdmin) {
    // For testing, allow access without login
    // In production, uncomment the following:
    // die('Access denied. Admin login required.');
}

// Get database connection using existing function
try {
    $pdo = getDB();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Migration results
$results = [
    'success' => [],
    'errors' => [],
    'warnings' => []
];

// Helper function to run SQL query
function runQuery($pdo, $sql, $description, &$results)
{
    try {
        $pdo->exec($sql);
        $results['success'][] = "✅ " . $description;
        return true;
    } catch (PDOException $e) {
        // Check if it's a duplicate column/table error (which is OK)
        if (
            strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'Duplicate key') !== false
        ) {
            $results['warnings'][] = "⚠️ " . $description . " (already exists)";
            return true;
        }
        $results['errors'][] = "❌ " . $description . ": " . $e->getMessage();
        return false;
    }
}

// Start migration
echo $isCLI ? "Starting Pricing Migration...\n" : "<h1>Aurora Hotel - Pricing Migration</h1><pre>";

// =====================================================
// 1. ADD NEW COLUMNS TO room_types
// =====================================================
echo "\n=== Adding new columns to room_types ===\n";

$newColumns = [
    ['price_published', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Giá công bố (giá niêm yết)"'],
    ['price_single_occupancy', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Giá phòng đơn (1 người)"'],
    ['price_double_occupancy', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Giá phòng đôi (2 người)"'],
    ['price_short_stay', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Giá nghỉ ngắn hạn (dưới 4h)"'],
    ['short_stay_description', 'VARCHAR(255) DEFAULT NULL COMMENT "Mô tả điều kiện nghỉ ngắn hạn"'],
    ['view_type', 'VARCHAR(100) DEFAULT "Thành phố" COMMENT "Loại view phòng"'],
    ['price_daily_single', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá ngày 1 người"'],
    ['price_daily_double', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá ngày 2 người"'],
    ['price_weekly_single', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá tuần 1 người"'],
    ['price_weekly_double', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá tuần 2 người"'],
    ['price_avg_weekly_single', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá TB/đêm tuần 1 người"'],
    ['price_avg_weekly_double', 'DECIMAL(12,2) DEFAULT NULL COMMENT "Căn hộ: Giá TB/đêm tuần 2 người"']
];

foreach ($newColumns as $column) {
    $sql = "ALTER TABLE `room_types` ADD COLUMN IF NOT EXISTS `{$column[0]}` {$column[1]}";
    runQuery($pdo, $sql, "Add column {$column[0]}", $results);
}

// =====================================================
// 2. UPDATE HOTEL ROOM PRICES
// =====================================================
echo "\n=== Updating Hotel Room Prices ===\n";

// Deluxe Room
$sql = "UPDATE `room_types` SET
    `type_name` = 'Deluxe',
    `size_sqm` = 32.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường đôi lớn (1m8x2m)',
    `price_published` = 1900000,
    `price_double_occupancy` = 1600000,
    `price_single_occupancy` = 1400000,
    `base_price` = 1600000,
    `price_short_stay` = 1100000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng'
WHERE `slug` = 'deluxe'";
runQuery($pdo, $sql, "Update Deluxe Room prices", $results);

// Premium Deluxe Double
$sql = "UPDATE `room_types` SET
    `type_name` = 'Premium Deluxe Double',
    `size_sqm` = 48.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường đôi lớn (1m8x2m)',
    `price_published` = 2200000,
    `price_double_occupancy` = 1900000,
    `price_single_occupancy` = 1700000,
    `base_price` = 1900000,
    `price_short_stay` = 1300000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng'
WHERE `slug` = 'premium-deluxe'";
runQuery($pdo, $sql, "Update Premium Deluxe Double prices", $results);

// Premium Deluxe Twin
$sql = "UPDATE `room_types` SET
    `type_name` = 'Premium Deluxe Twin',
    `size_sqm` = 48.00,
    `view_type` = 'Thành phố',
    `bed_type` = '2 giường đơn (1m4x2m)',
    `price_published` = 2200000,
    `price_double_occupancy` = 1900000,
    `price_single_occupancy` = 1700000,
    `base_price` = 1900000,
    `price_short_stay` = NULL,
    `short_stay_description` = NULL
WHERE `slug` = 'premium-twin'";
runQuery($pdo, $sql, "Update Premium Deluxe Twin prices", $results);

// Aurora Studio (VIP Suite)
$sql = "UPDATE `room_types` SET
    `type_name` = 'Aurora Studio',
    `size_sqm` = 54.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường siêu lớn (2mx2m)',
    `price_published` = 2950000,
    `price_double_occupancy` = 2300000,
    `price_single_occupancy` = 2200000,
    `base_price` = 2300000,
    `price_short_stay` = 1900000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng'
WHERE `slug` = 'vip-suite'";
runQuery($pdo, $sql, "Update Aurora Studio prices", $results);

// =====================================================
// 3. UPDATE APARTMENT PRICES
// =====================================================
echo "\n=== Updating Apartment Prices ===\n";

// Modern Studio Apartment (35m2)
$sql = "UPDATE `room_types` SET
    `size_sqm` = 35.00,
    `price_daily_single` = 1850000,
    `price_daily_double` = 2250000,
    `price_weekly_single` = 12250000,
    `price_weekly_double` = 15050000,
    `price_avg_weekly_single` = 1750000,
    `price_avg_weekly_double` = 2150000,
    `base_price` = 1850000
WHERE `slug` = 'modern-studio'";
runQuery($pdo, $sql, "Update Modern Studio Apartment prices", $results);

// Indochine Studio Apartment (35m2)
$sql = "UPDATE `room_types` SET
    `size_sqm` = 35.00,
    `price_daily_single` = 1850000,
    `price_daily_double` = 2250000,
    `price_weekly_single` = 12250000,
    `price_weekly_double` = 15050000,
    `price_avg_weekly_single` = 1750000,
    `price_avg_weekly_double` = 2150000,
    `base_price` = 1850000
WHERE `slug` = 'indochine-studio'";
runQuery($pdo, $sql, "Update Indochine Studio Apartment prices", $results);

// Modern Premium Apartment (60m2)
$sql = "UPDATE `room_types` SET
    `size_sqm` = 60.00,
    `price_daily_single` = 2050000,
    `price_daily_double` = 2450000,
    `price_weekly_single` = 13650000,
    `price_weekly_double` = 16450000,
    `price_avg_weekly_single` = 1950000,
    `price_avg_weekly_double` = 2350000,
    `base_price` = 2050000
WHERE `slug` = 'modern-premium'";
runQuery($pdo, $sql, "Update Modern Premium Apartment prices", $results);

// Classical Premium Apartment (60m2)
$sql = "UPDATE `room_types` SET
    `size_sqm` = 60.00,
    `price_daily_single` = 2050000,
    `price_daily_double` = 2450000,
    `price_weekly_single` = 13650000,
    `price_weekly_double` = 16450000,
    `price_avg_weekly_single` = 1950000,
    `price_avg_weekly_double` = 2350000,
    `base_price` = 2050000
WHERE `slug` = 'classical-premium'";
runQuery($pdo, $sql, "Update Classical Premium Apartment prices", $results);

// Family Apartments (82m2)
$sql = "UPDATE `room_types` SET
    `size_sqm` = 82.00,
    `price_daily_single` = NULL,
    `price_daily_double` = 2550000,
    `price_weekly_single` = NULL,
    `price_weekly_double` = 17150000,
    `price_avg_weekly_single` = NULL,
    `price_avg_weekly_double` = 2450000,
    `base_price` = 2550000
WHERE `slug` IN ('classical-family', 'indochine-family', 'family-apartment')";
runQuery($pdo, $sql, "Update Family Apartment prices", $results);

// =====================================================
// 4. CREATE PRICING POLICIES TABLE
// =====================================================
echo "\n=== Creating Pricing Policies Table ===\n";

$sql = "CREATE TABLE IF NOT EXISTS `pricing_policies` (
    `policy_id` INT(11) NOT NULL AUTO_INCREMENT,
    `policy_type` ENUM('extra_guest', 'extra_bed', 'early_checkin', 'late_checkout', 'short_stay') NOT NULL,
    `policy_name` VARCHAR(100) NOT NULL,
    `policy_name_en` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `description_en` TEXT DEFAULT NULL,
    `condition_type` VARCHAR(50) DEFAULT NULL,
    `condition_min` DECIMAL(10,2) DEFAULT NULL,
    `condition_max` DECIMAL(10,2) DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `is_percentage` TINYINT(1) DEFAULT 0,
    `applicable_to` ENUM('room', 'apartment', 'all') DEFAULT 'all',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
runQuery($pdo, $sql, "Create pricing_policies table", $results);

// Insert default policies
$sql = "INSERT IGNORE INTO `pricing_policies` 
    (`policy_type`, `policy_name`, `policy_name_en`, `description`, `condition_type`, `condition_min`, `condition_max`, `price`, `applicable_to`, `sort_order`) 
VALUES
    ('extra_guest', 'Trẻ em dưới 1m (bao gồm ăn sáng)', 'Children under 1m (with breakfast)', 'Miễn phí cho trẻ em dưới 1m', 'height', 0, 1.00, 0, 'all', 1),
    ('extra_guest', 'Trẻ em 1m - 1m3 (bao gồm ăn sáng)', 'Children 1m - 1.3m (with breakfast)', 'Phụ thu 200,000 VND', 'height', 1.00, 1.30, 200000, 'all', 2),
    ('extra_guest', 'Người lớn và trẻ trên 1m3 (bao gồm ăn sáng)', 'Adults and children over 1.3m (with breakfast)', 'Phụ thu 400,000 VND', 'height', 1.30, NULL, 400000, 'all', 3),
    ('extra_bed', 'Giường phụ', 'Extra Bed', 'Phí 650,000 VND - Không áp dụng cho căn hộ', NULL, NULL, NULL, 650000, 'room', 4)";
runQuery($pdo, $sql, "Insert default pricing policies", $results);

// =====================================================
// 5. ADD NEW COLUMNS TO BOOKINGS TABLE
// =====================================================
echo "\n=== Adding new columns to bookings table ===\n";

$bookingColumns = [
    ['booking_type', 'ENUM("standard", "short_stay", "weekly", "inquiry") DEFAULT "standard"'],
    ['occupancy_type', 'ENUM("single", "double", "family") DEFAULT "double"'],
    ['extra_guest_fee', 'DECIMAL(12,2) DEFAULT 0'],
    ['extra_bed_fee', 'DECIMAL(12,2) DEFAULT 0'],
    ['extra_beds', 'INT(11) DEFAULT 0'],
    ['short_stay_hours', 'INT(11) DEFAULT NULL'],
    ['expected_checkin_time', 'TIME DEFAULT NULL'],
    ['expected_checkout_time', 'TIME DEFAULT NULL'],
    ['price_type_used', 'ENUM("published", "single", "double", "short_stay", "daily", "weekly") DEFAULT "double"']
];

foreach ($bookingColumns as $column) {
    $sql = "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `{$column[0]}` {$column[1]}";
    runQuery($pdo, $sql, "Add column {$column[0]} to bookings", $results);
}

// =====================================================
// 6. CREATE BOOKING EXTRA GUESTS TABLE
// =====================================================
echo "\n=== Creating Booking Extra Guests Table ===\n";

$sql = "CREATE TABLE IF NOT EXISTS `booking_extra_guests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `guest_type` ENUM('adult', 'child_over_1m3', 'child_1m_1m3', 'child_under_1m', 'infant') NOT NULL,
    `guest_name` VARCHAR(255) DEFAULT NULL,
    `height_cm` DECIMAL(5,2) DEFAULT NULL,
    `age` INT(11) DEFAULT NULL,
    `fee` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `includes_breakfast` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
runQuery($pdo, $sql, "Create booking_extra_guests table", $results);

// =====================================================
// 7. UPDATE SYSTEM SETTINGS
// =====================================================
echo "\n=== Updating System Settings ===\n";

$settings = [
    ['hotel_star_rating', '4', 'Số sao khách sạn'],
    ['hotel_address', '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Tỉnh Đồng Nai', 'Địa chỉ khách sạn'],
    ['hotel_hotline', '0251 3918 888', 'Hotline khách sạn'],
    ['currency', 'VNĐ', 'Đơn vị tiền tệ'],
    ['tax_info', 'Đã bao gồm 5% phí dịch vụ và 8% VAT', 'Thông tin thuế và phí'],
    ['extra_bed_price', '650000', 'Giá giường phụ (VND)'],
    ['short_stay_max_hours', '4', 'Số giờ tối đa cho nghỉ ngắn'],
    ['short_stay_checkout_before', '22:00', 'Giờ checkout tối đa cho nghỉ ngắn']
];

foreach ($settings as $setting) {
    $sql = "INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) 
            VALUES ('{$setting[0]}', '{$setting[1]}', 'string', '{$setting[2]}')
            ON DUPLICATE KEY UPDATE `setting_value` = '{$setting[1]}', `description` = '{$setting[2]}'";
    runQuery($pdo, $sql, "Update setting: {$setting[0]}", $results);
}

// =====================================================
// 8. CREATE INDEXES
// =====================================================
echo "\n=== Creating Indexes ===\n";

$indexes = [
    "CREATE INDEX IF NOT EXISTS `idx_bookings_dates` ON `bookings` (`check_in_date`, `check_out_date`)",
    "CREATE INDEX IF NOT EXISTS `idx_bookings_status` ON `bookings` (`status`, `payment_status`)"
];

foreach ($indexes as $sql) {
    try {
        $pdo->exec($sql);
        $results['success'][] = "✅ Index created";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            $results['warnings'][] = "⚠️ Index already exists";
        }
    }
}

// =====================================================
// DISPLAY RESULTS
// =====================================================
echo "\n=== MIGRATION RESULTS ===\n";

echo "\n✅ SUCCESSES (" . count($results['success']) . "):\n";
foreach ($results['success'] as $msg) {
    echo "  " . $msg . "\n";
}

if (!empty($results['warnings'])) {
    echo "\n⚠️ WARNINGS (" . count($results['warnings']) . "):\n";
    foreach ($results['warnings'] as $msg) {
        echo "  " . $msg . "\n";
    }
}

if (!empty($results['errors'])) {
    echo "\n❌ ERRORS (" . count($results['errors']) . "):\n";
    foreach ($results['errors'] as $msg) {
        echo "  " . $msg . "\n";
    }
}

$totalSuccess = count($results['success']);
$totalWarnings = count($results['warnings']);
$totalErrors = count($results['errors']);

echo "\n=== SUMMARY ===\n";
echo "Total Success: $totalSuccess\n";
echo "Total Warnings: $totalWarnings\n";
echo "Total Errors: $totalErrors\n";

if ($totalErrors === 0) {
    echo "\n🎉 Migration completed successfully!\n";
} else {
    echo "\n⚠️ Migration completed with errors. Please review.\n";
}

echo $isCLI ? "" : "</pre>";

// Log the migration
try {
    $stmt = $pdo->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `entity_type`, `description`, `ip_address`, `created_at`) VALUES (?, 'pricing_migration', 'system', ?, ?, NOW())");
    $userId = $_SESSION['user_id'] ?? 0;
    $description = "Pricing migration completed: $totalSuccess success, $totalWarnings warnings, $totalErrors errors";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $stmt->execute([$userId, $description, $ip]);
} catch (Exception $e) {
    // Ignore logging errors
}
?>