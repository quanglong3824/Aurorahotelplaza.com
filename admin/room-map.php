<?php
session_start();
require_once '../config/database.php';

$page_title = 'Sơ đồ phòng';
$page_subtitle = 'Xem trạng thái phòng theo tầng';

// Get selected floor
$selected_floor = $_GET['floor'] ?? 'all';
$check_date = $_GET['date'] ?? date('Y-m-d');

try {
    $db = getDB();
    
    // Get all rooms with their current status
    $stmt = $db->prepare("
        SELECT r.*, rt.type_name, rt.category,
               (SELECT b.booking_id 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= :check_date 
                AND b.check_out_date > :check_date
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as current_booking_id,
               (SELECT b.status 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= :check_date 
                AND b.check_out_date > :check_date
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as booking_status,
               (SELECT u.full_name 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.user_id
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= :check_date 
                AND b.check_out_date > :check_date
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as guest_name
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        ORDER BY r.floor ASC, r.room_number ASC
    ");
    
    $stmt->execute([':check_date' => $check_date]);
    $all_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize rooms by floor
    $rooms_by_floor = [];
    foreach ($all_rooms as $room) {
        $floor = $room['floor'];
        if (!isset($rooms_by_floor[$floor])) {
            $rooms_by_floor[$floor] = [];
        }
        $rooms_by_floor[$floor][] = $room;
    }
    
    // Get stats
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_rooms,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning
        FROM rooms
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get floor maintenance status
    $stmt = $db->query("SELECT * FROM floor_maintenance ORDER BY floor ASC");
    $floor_maintenance_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $floor_maintenance = [];
    foreach ($floor_maintenance_list as $fm) {
        $floor_maintenance[$fm['floor']] = $fm;
    }
    
} catch (Exception $e) {
    error_log("Room map error: " . $e->getMessage());
    $rooms_by_floor = [];
    $stats = ['total_rooms' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0];
    $floor_maintenance = [];
}

// Room status colors
$status_colors = [
    'available' => ['bg' => '#10b981', 'text' => '#ffffff', 'label' => 'Trống'],
    'occupied' => ['bg' => '#ef4444', 'text' => '#ffffff', 'label' => 'Đang ở'],
    'maintenance' => ['bg' => '#f59e0b', 'text' => '#ffffff', 'label' => 'Bảo trì'],
    'cleaning' => ['bg' => '#3b82f6', 'text' => '#ffffff', 'label' => 'Dọn dẹp'],
    'reserved' => ['bg' => '#8b5cf6', 'text' => '#ffffff', 'label' => 'Đã đặt']
];

include 'includes/admin-header.php';
?>

<!-- Quick Jump Box -->
<div class="mb-6 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
    <div class="flex items-center gap-4">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">search</span>
            </div>
        </div>
        <div class="flex-1">
            <h3 class="text-xl font-bold mb-2">Tìm phòng nhanh</h3>
            <div class="flex gap-2">
                <input type="number" 
                       id="quickJumpInput" 
                       placeholder="Nhập số phòng (VD: 701, 712)..." 
                       class="flex-1 px-4 py-3 rounded-lg text-gray-900 font-semibold text-lg"
                       onkeypress="if(event.key==='Enter') quickJumpToRoom()">
                <button onclick="quickJumpToRoom()" class="px-6 py-3 bg-white text-blue-600 rounded-lg font-bold hover:bg-blue-50 transition-colors">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
            <p class="text-sm text-blue-100 mt-2">💡 Nhấn Enter hoặc click → để xem chi tiết phòng</p>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/room-map.css?v=<?php echo time(); ?>">

<!-- Controls -->
<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="form-group mb-0">
            <label class="form-label text-sm mb-1">Ngày xem</label>
            <input type="date" id="checkDate" value="<?php echo $check_date; ?>" 
                   class="form-input w-48" onchange="changeDate()">
        </div>
        
        <div class="flex items-center gap-3">
            <a href="rooms.php" class="btn btn-secondary">
                <span class="material-symbols-outlined text-sm">list</span>
                Danh sách phòng
            </a>
        </div>
    </div>
    
    <!-- Floor Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <a href="?date=<?php echo $check_date; ?>&floor=all" 
           class="px-6 py-3 rounded-xl font-semibold transition-all <?php echo $selected_floor === 'all' ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
            Tất cả tầng
        </a>
        <?php for ($i = 7; $i <= 12; $i++): ?>
            <a href="?date=<?php echo $check_date; ?>&floor=<?php echo $i; ?>" 
               class="px-6 py-3 rounded-xl font-semibold transition-all whitespace-nowrap <?php echo $selected_floor == $i ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
                Tầng <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold mb-1" style="color: #d4af37;"><?php echo $stats['total_rooms']; ?></div>
            <div class="text-xs text-gray-600">Tổng phòng</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $stats['available']; ?></div>
            <div class="text-xs text-gray-600">Trống</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-red-600 mb-1"><?php echo $stats['occupied']; ?></div>
            <div class="text-xs text-gray-600">Đang ở</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $stats['cleaning']; ?></div>
            <div class="text-xs text-gray-600">Dọn dẹp</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-orange-600 mb-1"><?php echo $stats['maintenance']; ?></div>
            <div class="text-xs text-gray-600">Bảo trì</div>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-center gap-6 flex-wrap">
            <span class="font-semibold text-sm">Chú thích:</span>
            <?php foreach ($status_colors as $status => $config): ?>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded" style="background: <?php echo $config['bg']; ?>;"></div>
                    <span class="text-sm"><?php echo $config['label']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Room Map -->
<?php
// Define floor configurations
$floor_configs = [
    7 => ['row1' => range(1, 10), 'row2' => array_merge(range(11, 12), range(14, 20))],
    8 => ['row1' => range(1, 10), 'row2' => array_merge(range(11, 12), range(14, 19))],
    9 => ['row1' => range(1, 11), 'row2' => array_merge(range(12, 12), range(14, 23))],
    10 => ['row1' => range(1, 11), 'row2' => array_merge(range(12, 12), range(14, 23))],
    11 => ['row1' => range(1, 11), 'row2' => array_merge(range(12, 12), range(14, 23))],
    12 => ['row1' => range(1, 10), 'row2' => array_merge(range(11, 12), range(14, 20))]
];

foreach ($floor_configs as $floor => $config):
    if ($selected_floor !== 'all' && $selected_floor != $floor) continue;
    
    // Get rooms for this floor
    $floor_rooms = $rooms_by_floor[$floor] ?? [];
    $rooms_map = [];
    foreach ($floor_rooms as $room) {
        $room_num = (int)substr($room['room_number'], -2);
        $rooms_map[$room_num] = $room;
    }

    $is_floor_maintenance = isset($floor_maintenance[$floor]) && $floor_maintenance[$floor]['is_maintenance'];
    $floor_note = $floor_maintenance[$floor]['maintenance_note'] ?? '';
?>
    <div class="floor-section <?php echo $is_floor_maintenance ? 'floor-maintenance-active' : ''; ?>">
        <div class="floor-header">
            <span class="floor-badge <?php echo $is_floor_maintenance ? 'bg-orange-500' : ''; ?>">
                Tầng <?php echo $floor; ?>
                <?php if ($is_floor_maintenance): ?>
                    <span class="material-symbols-outlined text-sm ml-1">construction</span>
                <?php endif; ?>
            </span>
            <div class="flex-1">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <?php 
                    $floor_room_count = count($floor_rooms);
                    $floor_available = count(array_filter($floor_rooms, fn($r) => $r['status'] === 'available'));
                    echo "$floor_available/$floor_room_count phòng trống";
                    ?>
                </div>
                <?php if ($is_floor_maintenance && $floor_note): ?>
                    <div class="text-xs text-orange-600 dark:text-orange-400 mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">info</span>
                        <?php echo htmlspecialchars($floor_note); ?>
                    </div>
                <?php endif; ?>
            </div>
            <button onclick="toggleFloorMaintenance(<?php echo $floor; ?>, <?php echo $is_floor_maintenance ? 'true' : 'false'; ?>)" 
                    class="btn btn-sm <?php echo $is_floor_maintenance ? 'btn-warning' : 'btn-secondary'; ?> flex items-center gap-1">
                <span class="material-symbols-outlined text-sm"><?php echo $is_floor_maintenance ? 'build_circle' : 'construction'; ?></span>
                <?php echo $is_floor_maintenance ? 'Đang bảo trì' : 'Bảo trì tầng'; ?>
            </button>
        </div>
        
        <!-- Row 1 -->
        <div class="mb-6">
            <div class="row-label">Dãy 1</div>
            <div class="room-grid">
                <?php foreach ($config['row1'] as $room_num): 
                    $room_number = $floor . str_pad($room_num, 2, '0', STR_PAD_LEFT);
                    $room = $rooms_map[$room_num] ?? null;
                    
                    if ($room):
                        $status = $room['status'];
                        if ($room['current_booking_id']) {
                            $status = $room['booking_status'] === 'checked_in' ? 'occupied' : 'reserved';
                        }
                        $color = $status_colors[$status] ?? $status_colors['available'];
                ?>
                    <div class="room-card <?php echo $status; ?>" 
                         onclick="viewRoom(<?php echo $room['room_id']; ?>)"
                         title="<?php echo htmlspecialchars($room['type_name']); ?>">
                        <div class="room-number"><?php echo $room_number; ?></div>
                        <div class="room-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                        <?php if ($room['guest_name']): ?>
                            <div class="room-guest"><?php echo htmlspecialchars($room['guest_name']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="room-card" style="background: #e5e7eb; opacity: 0.5; cursor: not-allowed;">
                        <div class="room-number" style="color: #9ca3af;"><?php echo $room_number; ?></div>
                        <div class="room-type" style="color: #9ca3af;">Chưa có</div>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Row 2 -->
        <div>
            <div class="row-label">Dãy 2</div>
            <div class="room-grid">
                <?php foreach ($config['row2'] as $room_num): 
                    $room_number = $floor . str_pad($room_num, 2, '0', STR_PAD_LEFT);
                    $room = $rooms_map[$room_num] ?? null;
                    
                    if ($room):
                        $status = $room['status'];
                        if ($room['current_booking_id']) {
                            $status = $room['booking_status'] === 'checked_in' ? 'occupied' : 'reserved';
                        }
                        $color = $status_colors[$status] ?? $status_colors['available'];
                ?>
                    <div class="room-card <?php echo $status; ?>" 
                         onclick="viewRoom(<?php echo $room['room_id']; ?>)"
                         title="<?php echo htmlspecialchars($room['type_name']); ?>">
                        <div class="room-number"><?php echo $room_number; ?></div>
                        <div class="room-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                        <?php if ($room['guest_name']): ?>
                            <div class="room-guest"><?php echo htmlspecialchars($room['guest_name']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="room-card" style="background: #e5e7eb; opacity: 0.5; cursor: not-allowed;">
                        <div class="room-number" style="color: #9ca3af;"><?php echo $room_number; ?></div>
                        <div class="room-type" style="color: #9ca3af;">Chưa có</div>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Room Detail Modal -->
<div id="roomModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden" style="background: rgba(0,0,0,0.5);">
    <div class="absolute inset-0" onclick="closeRoomModal()"></div>
    <div class="modal-content max-w-4xl relative z-10 bg-white dark:bg-slate-800 rounded-xl shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="modal-header px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-800 z-20">
            <h3 class="font-bold text-lg">Chi tiết phòng</h3>
            <button onclick="closeRoomModal()" class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="roomModalContent" class="modal-body p-6">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#d4af37]"></div>
            </div>
        </div>
    </div>
</div>

<!-- Status Selection Modal -->
<div id="statusModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden" style="background: rgba(0,0,0,0.6);">
    <div class="absolute inset-0" onclick="closeStatusModal()"></div>
    <div class="relative z-10 bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-lg">Đổi trạng thái phòng</h3>
            <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Chọn trạng thái mới cho phòng <strong id="statusRoomNumber"></strong>:</p>
            <input type="hidden" id="statusRoomId">
            <div class="grid grid-cols-2 gap-3">
                <button onclick="selectStatus('available')" class="flex items-center gap-3 p-4 rounded-xl border-2 border-transparent hover:border-green-500 bg-green-50 dark:bg-green-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">check_circle</span>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-green-700 dark:text-green-400">Trống</div>
                        <div class="text-xs text-gray-500">Available</div>
                    </div>
                </button>
                <button onclick="selectStatus('occupied')" class="flex items-center gap-3 p-4 rounded-xl border-2 border-transparent hover:border-red-500 bg-red-50 dark:bg-red-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">person</span>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-red-700 dark:text-red-400">Đang ở</div>
                        <div class="text-xs text-gray-500">Occupied</div>
                    </div>
                </button>
                <button onclick="selectStatus('maintenance')" class="flex items-center gap-3 p-4 rounded-xl border-2 border-transparent hover:border-orange-500 bg-orange-50 dark:bg-orange-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">build</span>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-orange-700 dark:text-orange-400">Bảo trì</div>
                        <div class="text-xs text-gray-500">Maintenance</div>
                    </div>
                </button>
                <button onclick="selectStatus('cleaning')" class="flex items-center gap-3 p-4 rounded-xl border-2 border-transparent hover:border-blue-500 bg-blue-50 dark:bg-blue-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">cleaning_services</span>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-blue-700 dark:text-blue-400">Dọn dẹp</div>
                        <div class="text-xs text-gray-500">Cleaning</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Room Type Selection Modal -->
<div id="roomTypeModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden" style="background: rgba(0,0,0,0.6);">
    <div class="absolute inset-0" onclick="closeRoomTypeModal()"></div>
    <div class="relative z-10 bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-lg max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-lg">Đổi loại phòng</h3>
            <button onclick="closeRoomTypeModal()" class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Chọn loại phòng mới cho phòng <strong id="typeRoomNumber"></strong>:</p>
            <input type="hidden" id="typeRoomId">
            <div id="roomTypeList" class="space-y-2 max-h-[50vh] overflow-y-auto">
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#d4af37]"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floor Maintenance Modal -->
<div id="floorMaintenanceModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden" style="background: rgba(0,0,0,0.6);">
    <div class="absolute inset-0" onclick="closeFloorMaintenanceModal()"></div>
    <div class="relative z-10 bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-orange-500 to-orange-600">
            <h3 class="font-bold text-lg text-white flex items-center gap-2">
                <span class="material-symbols-outlined">construction</span>
                Bảo trì tầng <span id="maintenanceFloorNumber"></span>
            </h3>
            <button onclick="closeFloorMaintenanceModal()" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="maintenanceFloor">
            <input type="hidden" id="maintenanceCurrentStatus">
            
            <div class="mb-4">
                <label class="form-label">Ghi chú bảo trì</label>
                <textarea id="maintenanceNote" class="form-input w-full" rows="3" placeholder="VD: Sửa chữa hệ thống điện, nước..."></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="form-label">Ngày bắt đầu</label>
                    <input type="date" id="maintenanceStartDate" class="form-input w-full">
                </div>
                <div>
                    <label class="form-label">Ngày kết thúc (dự kiến)</label>
                    <input type="date" id="maintenanceEndDate" class="form-input w-full">
                </div>
            </div>
            
            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-6">
                <p class="text-sm text-orange-800 dark:text-orange-200 flex items-start gap-2">
                    <span class="material-symbols-outlined text-sm mt-0.5">warning</span>
                    <span>Khi bật bảo trì tầng, tất cả phòng trống trên tầng sẽ chuyển sang trạng thái "Bảo trì" và không thể đặt phòng.</span>
                </p>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeFloorMaintenanceModal()" class="btn btn-secondary flex-1">Hủy</button>
                <button onclick="saveFloorMaintenance()" id="btnSaveMaintenance" class="btn btn-primary flex-1 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Bật bảo trì
                </button>
                <button onclick="disableFloorMaintenance()" id="btnDisableMaintenance" class="btn btn-success flex-1 hidden flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Tắt bảo trì
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/room-map.js?v=<?php echo time(); ?>"></script>

<?php include 'includes/admin-footer.php'; ?>
