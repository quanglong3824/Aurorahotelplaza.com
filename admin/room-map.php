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
    
} catch (Exception $e) {
    error_log("Room map error: " . $e->getMessage());
    $rooms_by_floor = [];
    $stats = ['total_rooms' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0];
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

<style>
.room-card {
    position: relative;
    padding: 16px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.room-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    border-color: #d4af37;
}

.room-card.occupied {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.room-card.available {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.room-card.maintenance {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.room-card.cleaning {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.room-card.reserved {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.room-number {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.room-type {
    font-size: 11px;
    color: rgba(255,255,255,0.9);
    font-weight: 600;
}

.room-guest {
    font-size: 10px;
    color: rgba(255,255,255,0.8);
    margin-top: 4px;
}

.floor-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dark .floor-section {
    background: #1e293b;
}

.floor-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #d4af37;
}

.floor-badge {
    background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 18px;
}

.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 16px;
}

.row-label {
    font-weight: 700;
    color: #d4af37;
    margin-bottom: 12px;
    font-size: 14px;
}
</style>

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
?>
    <div class="floor-section">
        <div class="floor-header">
            <span class="floor-badge">Tầng <?php echo $floor; ?></span>
            <div class="flex-1">
                <div class="text-sm text-gray-600">
                    <?php 
                    $floor_room_count = count($floor_rooms);
                    $floor_available = count(array_filter($floor_rooms, fn($r) => $r['status'] === 'available'));
                    echo "$floor_available/$floor_room_count phòng trống";
                    ?>
                </div>
            </div>
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
<div id="roomModal" class="modal">
    <div class="modal-content max-w-4xl">
        <div class="modal-header">
            <h3 class="font-bold text-lg">Chi tiết phòng</h3>
            <button onclick="closeRoomModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="roomModalContent" class="modal-body">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#d4af37]"></div>
            </div>
        </div>
    </div>
</div>

<script>
function changeDate() {
    const date = document.getElementById('checkDate').value;
    const urlParams = new URLSearchParams(window.location.search);
    const floor = urlParams.get('floor') || 'all';
    window.location.href = `room-map.php?date=${date}&floor=${floor}`;
}

function viewRoom(roomId) {
    document.getElementById('roomModal').classList.add('active');
    
    fetch(`api/get-room-detail.php?room_id=${roomId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayRoomDetail(data.room, data.current_booking, data.booking_history);
            } else {
                document.getElementById('roomModalContent').innerHTML = 
                    '<div class="text-center py-8 text-red-600">Không thể tải thông tin phòng</div>';
            }
        })
        .catch(err => {
            document.getElementById('roomModalContent').innerHTML = 
                '<div class="text-center py-8 text-red-600">Có lỗi xảy ra</div>';
        });
}

function displayRoomDetail(room, currentBooking, history) {
    const statusColors = {
        'available': 'bg-green-100 text-green-800',
        'occupied': 'bg-red-100 text-red-800',
        'maintenance': 'bg-orange-100 text-orange-800',
        'cleaning': 'bg-blue-100 text-blue-800'
    };
    
    const statusLabels = {
        'available': 'Trống',
        'occupied': 'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning': 'Dọn dẹp'
    };
    
    let html = `
        <div class="space-y-6">
            <!-- Room Info -->
            <div class="bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="text-3xl font-bold mb-2" style="color: #d4af37;">Phòng ${room.room_number}</h4>
                        <p class="text-lg font-semibold">${room.type_name}</p>
                        <p class="text-sm text-gray-600">Tầng ${room.floor} - ${room.category === 'room' ? 'Phòng' : 'Căn hộ'}</p>
                    </div>
                    <span class="badge ${statusColors[room.status]} text-sm px-4 py-2">
                        ${statusLabels[room.status]}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Giá cơ bản</div>
                        <div class="text-xl font-bold" style="color: #d4af37;">${parseInt(room.base_price).toLocaleString()}đ</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Sức chứa</div>
                        <div class="text-xl font-bold">${room.max_occupancy} người</div>
                    </div>
                </div>
            </div>
            
            <!-- Current Booking -->
            ${currentBooking ? `
                <div class="card">
                    <div class="card-header">
                        <h5 class="font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined">person</span>
                            Khách đang ở
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-lg">${currentBooking.guest_name}</p>
                                <p class="text-sm text-gray-600">${currentBooking.email || ''}</p>
                                <p class="text-sm text-gray-600">${currentBooking.phone || ''}</p>
                            </div>
                            <a href="booking-detail.php?id=${currentBooking.booking_id}" class="btn btn-primary btn-sm">
                                Xem chi tiết
                            </a>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Check-in:</span>
                                <p class="font-semibold">${new Date(currentBooking.check_in_date).toLocaleDateString('vi-VN')}</p>
                            </div>
                            <div>
                                <span class="text-gray-600">Check-out:</span>
                                <p class="font-semibold">${new Date(currentBooking.check_out_date).toLocaleDateString('vi-VN')}</p>
                            </div>
                            <div>
                                <span class="text-gray-600">Tổng tiền:</span>
                                <p class="font-semibold" style="color: #d4af37;">${parseInt(currentBooking.total_amount).toLocaleString()}đ</p>
                            </div>
                        </div>
                    </div>
                </div>
            ` : '<div class="text-center py-4 text-gray-500">Phòng hiện đang trống</div>'}
            
            <!-- Booking History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">history</span>
                        Lịch sử đặt phòng
                    </h5>
                </div>
                <div class="card-body">
                    ${history && history.length > 0 ? `
                        <div class="space-y-3">
                            ${history.map(booking => `
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                    <div class="flex-1">
                                        <p class="font-semibold">${booking.guest_name}</p>
                                        <p class="text-sm text-gray-600">
                                            ${new Date(booking.check_in_date).toLocaleDateString('vi-VN')} - 
                                            ${new Date(booking.check_out_date).toLocaleDateString('vi-VN')}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold" style="color: #d4af37;">${parseInt(booking.total_amount).toLocaleString()}đ</p>
                                        <span class="badge badge-${booking.status === 'completed' ? 'success' : 'secondary'} text-xs">
                                            ${booking.status === 'completed' ? 'Hoàn thành' : booking.status}
                                        </span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-center text-gray-500 py-4">Chưa có lịch sử đặt phòng</p>'}
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">tune</span>
                        Thao tác nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="changeRoomType(${room.room_id})" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">swap_horiz</span>
                            Đổi loại phòng
                        </button>
                        <button onclick="changeRoomStatus(${room.room_id}, '${room.status}')" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">toggle_on</span>
                            Đổi trạng thái
                        </button>
                        <a href="room-form.php?id=${room.room_id}" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Sửa chi tiết
                        </a>
                        ${room.status === 'available' ? `
                            <a href="bookings.php?room_id=${room.room_id}" class="btn btn-primary w-full">
                                <span class="material-symbols-outlined text-sm">add</span>
                                Tạo booking
                            </a>
                        ` : `
                            <button class="btn btn-secondary w-full" disabled>
                                <span class="material-symbols-outlined text-sm">block</span>
                                Đang có khách
                            </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('roomModalContent').innerHTML = html;
}

function closeRoomModal() {
    document.getElementById('roomModal').classList.remove('active');
}

// Đổi loại phòng
async function changeRoomType(roomId) {
    try {
        // Lấy danh sách loại phòng
        const response = await fetch('api/get-room-types.php');
        const data = await response.json();
        
        if (!data.success) {
            alert('Không thể tải danh sách loại phòng');
            return;
        }
        
        const roomTypes = data.room_types;
        
        // Tạo options cho select
        const options = roomTypes.map(type => 
            `<option value="${type.room_type_id}">
                ${type.type_name} - ${type.category === 'room' ? 'Phòng' : 'Căn hộ'} (${parseInt(type.base_price).toLocaleString()}đ)
            </option>`
        ).join('');
        
        // Hiển thị dialog
        const newTypeId = prompt(
            'Nhập ID loại phòng mới:\n\n' + 
            roomTypes.map(t => `${t.room_type_id}: ${t.type_name}`).join('\n')
        );
        
        if (!newTypeId) return;
        
        // Cập nhật
        const updateResponse = await fetch('api/update-room-type.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `room_id=${roomId}&room_type_id=${newTypeId}`
        });
        
        const updateData = await updateResponse.json();
        
        if (updateData.success) {
            alert('Đã đổi loại phòng thành công!');
            location.reload();
        } else {
            alert(updateData.message || 'Có lỗi xảy ra');
        }
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}

// Đổi trạng thái phòng
async function changeRoomStatus(roomId, currentStatus) {
    const statuses = {
        'available': 'Trống',
        'occupied': 'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning': 'Dọn dẹp'
    };
    
    const statusOptions = Object.keys(statuses)
        .filter(s => s !== currentStatus)
        .map((s, i) => `${i + 1}. ${statuses[s]} (${s})`)
        .join('\n');
    
    const newStatus = prompt(
        `Trạng thái hiện tại: ${statuses[currentStatus]}\n\nChọn trạng thái mới:\n${statusOptions}\n\nNhập tên trạng thái (available/occupied/maintenance/cleaning):`
    );
    
    if (!newStatus || !statuses[newStatus]) {
        if (newStatus) alert('Trạng thái không hợp lệ!');
        return;
    }
    
    try {
        const response = await fetch('api/update-room-status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `room_id=${roomId}&status=${newStatus}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Đã đổi trạng thái thành công!');
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}
</script>

<?php include 'includes/admin-footer.php'; ?>
