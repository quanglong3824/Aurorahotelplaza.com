<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Sơ đồ phòng';

try {
    $db = getDB();
    
    // Get all rooms with their types and current booking status
    $stmt = $db->query("
        SELECT 
            r.*,
            rt.type_name,
            rt.category,
            rt.base_price,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.room_id = r.room_id 
                    AND b.status IN ('confirmed', 'checked_in')
                    AND CURDATE() BETWEEN b.check_in_date AND b.check_out_date
                ) THEN 'occupied'
                ELSE r.status
            END as display_status
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        ORDER BY r.floor ASC, r.room_number ASC
    ");
    
    $all_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group rooms by floor
    $rooms_by_floor = [];
    foreach ($all_rooms as $room) {
        $floor = $room['floor'];
        if (!isset($rooms_by_floor[$floor])) {
            $rooms_by_floor[$floor] = [];
        }
        $rooms_by_floor[$floor][] = $room;
    }
    
} catch (Exception $e) {
    error_log("Room map user error: " . $e->getMessage());
    $rooms_by_floor = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo $page_title; ?> - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.room-box {
    width: 50px;
    height: 50px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 11px;
    font-weight: 600;
}

.room-box:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10;
}

.room-box.available {
    background: #10b981;
    color: white;
}

.room-box.occupied {
    background: #ef4444;
    color: white;
}

.room-box.maintenance {
    background: #f59e0b;
    color: white;
}

.room-box.cleaning {
    background: #3b82f6;
    color: white;
}

.floor-tab {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.floor-tab.active {
    background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
    color: white;
}

.floor-tab:not(.active) {
    background: #f3f4f6;
    color: #6b7280;
}

.floor-tab:not(.active):hover {
    background: #e5e7eb;
}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20">
    <!-- Page Header -->
    <section class="bg-gradient-to-br from-[#d4af37] to-[#b8941f] py-8">
        <div class="container-custom">
            <h1 class="text-3xl font-bold text-white mb-1">Sơ đồ phòng</h1>
            <p class="text-white/90 text-sm">Xem tình trạng phòng real-time</p>
        </div>
    </section>

    <!-- Room Map -->
    <section class="py-6">
        <div class="container-custom">
            <!-- Legend & Tabs -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6 p-4 bg-white dark:bg-slate-800 rounded-xl shadow">
                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-4 rounded bg-green-500"></div>
                        <span class="text-xs font-medium">Trống</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-4 rounded bg-red-500"></div>
                        <span class="text-xs font-medium">Đang ở</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-4 rounded bg-orange-500"></div>
                        <span class="text-xs font-medium">Bảo trì</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-4 rounded bg-blue-500"></div>
                        <span class="text-xs font-medium">Dọn dẹp</span>
                    </div>
                </div>
                
                <!-- Floor Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button class="floor-tab active" onclick="showAllFloors()">Tất cả</button>
                    <?php foreach (array_keys($rooms_by_floor) as $floor): ?>
                        <button class="floor-tab" onclick="showFloor(<?php echo $floor; ?>)">T<?php echo $floor; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Floors -->
            <?php foreach ($rooms_by_floor as $floor => $rooms): ?>
                <div class="floor-section mb-8" data-floor="<?php echo $floor; ?>">
                    <h3 class="text-lg font-bold mb-3" style="color: #d4af37;">Tầng <?php echo $floor; ?></h3>
                    <div class="grid grid-cols-6 sm:grid-cols-10 md:grid-cols-12 lg:grid-cols-16 xl:grid-cols-20 gap-2">
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-box <?php echo $room['display_status']; ?>" 
                                 onclick="showRoomInfo(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                 title="<?php echo $room['room_number']; ?> - <?php echo number_format($room['base_price'], 0); ?>đ">
                                <div><?php echo $room['room_number']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<script>
function showFloor(floor) {
    // Update tabs
    document.querySelectorAll('.floor-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide floors
    document.querySelectorAll('.floor-section').forEach(section => {
        section.style.display = parseInt(section.dataset.floor) === floor ? 'block' : 'none';
    });
}

function showAllFloors() {
    // Update tabs
    document.querySelectorAll('.floor-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show all floors
    document.querySelectorAll('.floor-section').forEach(section => {
        section.style.display = 'block';
    });
}

function showRoomInfo(room) {
    const statusText = {
        'available': 'Trống',
        'occupied': 'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning': 'Dọn dẹp'
    };
    
    alert(`Phòng ${room.room_number}\n` +
          `Loại: ${room.type_name}\n` +
          `Giá: ${parseInt(room.base_price).toLocaleString()}đ/đêm\n` +
          `Trạng thái: ${statusText[room.display_status]}\n\n` +
          (room.display_status === 'available' ? 'Bạn có thể đặt phòng này!' : 'Phòng hiện không khả dụng'));
}
</script>

</body>
</html>
