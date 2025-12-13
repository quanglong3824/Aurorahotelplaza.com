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
    $stats = ['available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0];
    
    foreach ($all_rooms as $room) {
        $floor = $room['floor'];
        if (!isset($rooms_by_floor[$floor])) {
            $rooms_by_floor[$floor] = [];
        }
        $rooms_by_floor[$floor][] = $room;
        
        // Count stats
        if (isset($stats[$room['display_status']])) {
            $stats[$room['display_status']]++;
        }
    }
    
} catch (Exception $e) {
    error_log("Room map user error: " . $e->getMessage());
    $rooms_by_floor = [];
    $stats = ['available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0];
}

$total_rooms = array_sum($stats);
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
<link rel="stylesheet" href="assets/css/liquid-glass.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section with Liquid Glass -->
    <section class="roommap-hero">
        <div class="roommap-hero-bg"></div>
        <div class="roommap-hero-overlay"></div>
        <div class="roommap-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">map</span>
                Xem tình trạng phòng
            </span>
            <h1 class="roommap-hero-title">Sơ đồ phòng</h1>
            <p class="roommap-hero-subtitle">Xem tình trạng phòng real-time tại Aurora Hotel Plaza</p>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 max-w-3xl mx-auto">
                <div class="glass-stat-card">
                    <span class="stat-value text-green-400"><?php echo $stats['available']; ?></span>
                    <span class="stat-label">Phòng trống</span>
                </div>
                <div class="glass-stat-card">
                    <span class="stat-value text-red-400"><?php echo $stats['occupied']; ?></span>
                    <span class="stat-label">Đang ở</span>
                </div>
                <div class="glass-stat-card">
                    <span class="stat-value text-orange-400"><?php echo $stats['maintenance']; ?></span>
                    <span class="stat-label">Bảo trì</span>
                </div>
                <div class="glass-stat-card">
                    <span class="stat-value text-blue-400"><?php echo $stats['cleaning']; ?></span>
                    <span class="stat-label">Dọn dẹp</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Room Map Section -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Legend & Floor Tabs - Liquid Glass -->
            <div class="glass-card-solid p-6 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <!-- Legend -->
                    <div class="flex flex-wrap gap-4">
                        <h3 class="font-bold text-lg w-full lg:w-auto mb-2 lg:mb-0">Chú thích:</h3>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-green-400 to-green-600 shadow-lg"></div>
                            <span class="text-sm font-medium">Trống</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-red-400 to-red-600 shadow-lg"></div>
                            <span class="text-sm font-medium">Đang ở</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 shadow-lg"></div>
                            <span class="text-sm font-medium">Bảo trì</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg"></div>
                            <span class="text-sm font-medium">Dọn dẹp</span>
                        </div>
                    </div>
                    
                    <!-- Floor Tabs -->
                    <div class="flex flex-wrap gap-2">
                        <button class="floor-tab-glass active" onclick="showAllFloors(this)">
                            <span class="material-symbols-outlined text-sm">layers</span>
                            Tất cả
                        </button>
                        <?php foreach (array_keys($rooms_by_floor) as $floor): ?>
                            <button class="floor-tab-glass" onclick="showFloor(<?php echo $floor; ?>, this)">
                                Tầng <?php echo $floor; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Floors Grid -->
            <div class="space-y-8">
                <?php foreach ($rooms_by_floor as $floor => $rooms): ?>
                    <div class="floor-section glass-card-solid p-6" data-floor="<?php echo $floor; ?>">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-accent to-accent/80 flex items-center justify-center text-white font-bold">
                                <?php echo $floor; ?>
                            </div>
                            <div>
                                <h3 class="font-display text-xl font-bold">Tầng <?php echo $floor; ?></h3>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo count($rooms); ?> phòng
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 xl:grid-cols-12 gap-3">
                            <?php foreach ($rooms as $room): ?>
                                <div class="room-box-glass <?php echo $room['display_status']; ?>" 
                                     onclick="showRoomModal(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                     title="<?php echo $room['room_number']; ?> - <?php echo $room['type_name']; ?>">
                                    <span class="room-number"><?php echo $room['room_number']; ?></span>
                                    <span class="room-status-icon">
                                        <?php 
                                        $icons = [
                                            'available' => 'check_circle',
                                            'occupied' => 'person',
                                            'maintenance' => 'build',
                                            'cleaning' => 'cleaning_services'
                                        ];
                                        echo '<span class="material-symbols-outlined text-xs">' . ($icons[$room['display_status']] ?? 'help') . '</span>';
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Map Section -->
            <div class="glass-card-solid p-6 mt-8">
                <h3 class="font-display text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent">location_on</span>
                    Vị trí khách sạn
                </h3>
                <div class="rounded-xl overflow-hidden shadow-lg">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <div class="mt-4 flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">location_on</span>
                        <span>253 Phạm Văn Thuận, Tam Hiệp, Biên Hòa, Đồng Nai</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">phone</span>
                        <a href="tel:+842513918888" class="hover:text-accent">(+84-251) 391.8888</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<!-- Room Detail Modal -->
<div id="roomModal" class="fixed inset-0 z-50 hidden">
    <div class="glass-modal-backdrop" onclick="closeRoomModal()"></div>
    <div class="glass-modal p-0 max-w-md">
        <div class="modal-header p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-xl font-bold" id="modalRoomNumber">Phòng 101</h3>
                <button onclick="closeRoomModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div class="modal-body p-6">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-accent">hotel</span>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Loại phòng</p>
                        <p class="font-semibold" id="modalRoomType">-</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-accent">payments</span>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giá phòng</p>
                        <p class="font-semibold text-accent" id="modalRoomPrice">-</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-accent" id="modalStatusIcon">info</span>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Trạng thái</p>
                        <p class="font-semibold" id="modalRoomStatus">-</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer p-6 border-t border-gray-200 dark:border-gray-700">
            <div id="modalActions"></div>
        </div>
    </div>
</div>

<style>
/* Hero Section */
.roommap-hero {
    position: relative;
    min-height: 450px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 140px 20px 80px;
    overflow: hidden;
}

.roommap-hero-bg {
    position: absolute;
    inset: 0;
    background: url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
    background-size: cover;
    background-position: center;
    z-index: 0;
}

.roommap-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.7));
    z-index: 1;
}

.roommap-hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
    max-width: 900px;
}

.roommap-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 16px;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.roommap-hero-subtitle {
    font-size: 18px;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Floor Tabs - Liquid Glass */
.floor-tab-glass {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary-light);
    cursor: pointer;
    transition: all 0.3s ease;
}

.dark .floor-tab-glass {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-primary-dark);
}

.floor-tab-glass:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: rgba(212, 175, 55, 0.3);
}

.floor-tab-glass.active {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
}

/* Room Box - Liquid Glass */
.room-box-glass {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
}

.room-box-glass::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.3), transparent);
    transition: opacity 0.3s ease;
}

.room-box-glass:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    z-index: 10;
}

.room-box-glass:hover::before {
    opacity: 1;
}

.room-box-glass .room-number {
    font-size: 14px;
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.room-box-glass .room-status-icon {
    position: absolute;
    bottom: 4px;
    right: 4px;
    opacity: 0.8;
    color: white;
}

.room-box-glass.available {
    background: linear-gradient(135deg, #10b981, #059669);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.room-box-glass.occupied {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.room-box-glass.maintenance {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.room-box-glass.cleaning {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

/* Modal Styles */
.glass-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 450px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 1.5rem;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    overflow: hidden;
}

.dark .glass-modal {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .roommap-hero-title {
        font-size: 32px;
    }
    
    .room-box-glass .room-number {
        font-size: 12px;
    }
}
</style>

<script>
function showFloor(floor, btn) {
    // Update tabs
    document.querySelectorAll('.floor-tab-glass').forEach(tab => tab.classList.remove('active'));
    btn.classList.add('active');
    
    // Show/hide floors
    document.querySelectorAll('.floor-section').forEach(section => {
        section.style.display = parseInt(section.dataset.floor) === floor ? 'block' : 'none';
    });
}

function showAllFloors(btn) {
    // Update tabs
    document.querySelectorAll('.floor-tab-glass').forEach(tab => tab.classList.remove('active'));
    btn.classList.add('active');
    
    // Show all floors
    document.querySelectorAll('.floor-section').forEach(section => {
        section.style.display = 'block';
    });
}

function showRoomModal(room) {
    const statusText = {
        'available': 'Phòng trống',
        'occupied': 'Đang có khách',
        'maintenance': 'Đang bảo trì',
        'cleaning': 'Đang dọn dẹp'
    };
    
    const statusIcons = {
        'available': 'check_circle',
        'occupied': 'person',
        'maintenance': 'build',
        'cleaning': 'cleaning_services'
    };
    
    document.getElementById('modalRoomNumber').textContent = 'Phòng ' + room.room_number;
    document.getElementById('modalRoomType').textContent = room.type_name || 'Chưa phân loại';
    document.getElementById('modalRoomPrice').textContent = parseInt(room.base_price).toLocaleString('vi-VN') + 'đ/đêm';
    document.getElementById('modalRoomStatus').textContent = statusText[room.display_status] || 'Không xác định';
    document.getElementById('modalStatusIcon').textContent = statusIcons[room.display_status] || 'info';
    
    // Actions
    let actionsHtml = '';
    if (room.display_status === 'available') {
        actionsHtml = `
            <a href="booking/index.php?room=${room.room_number}" class="btn-glass-primary w-full justify-center">
                <span class="material-symbols-outlined">calendar_month</span>
                Đặt phòng ngay
            </a>
        `;
    } else {
        actionsHtml = `
            <button onclick="closeRoomModal()" class="btn-glass-outline w-full justify-center">
                <span class="material-symbols-outlined">close</span>
                Đóng
            </button>
        `;
    }
    document.getElementById('modalActions').innerHTML = actionsHtml;
    
    document.getElementById('roomModal').classList.remove('hidden');
}

function closeRoomModal() {
    document.getElementById('roomModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRoomModal();
    }
});
</script>

</body>
</html>
