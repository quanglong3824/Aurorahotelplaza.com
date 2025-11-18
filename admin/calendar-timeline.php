<?php
session_start();
require_once '../config/database.php';

$page_title = 'Lịch đặt phòng - Timeline';
$page_subtitle = 'Xem lịch đặt phòng theo timeline';

// Get date range
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$days_to_show = 14; // Show 2 weeks

try {
    $db = getDB();
    
    // Get all rooms with their types
    $stmt = $db->query("
        SELECT r.*, rt.type_name, rt.category
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        ORDER BY rt.category, r.room_number
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bookings for the date range
    $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $days_to_show . ' days'));
    
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, r.room_number, rt.type_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.status NOT IN ('cancelled', 'no_show')
        AND (
            (b.check_in_date <= :end_date AND b.check_out_date >= :start_date)
        )
        ORDER BY b.check_in_date ASC
    ");
    
    $stmt->execute([
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize bookings by room
    $bookings_by_room = [];
    foreach ($bookings as $booking) {
        if ($booking['room_id']) {
            if (!isset($bookings_by_room[$booking['room_id']])) {
                $bookings_by_room[$booking['room_id']] = [];
            }
            $bookings_by_room[$booking['room_id']][] = $booking;
        }
    }
    
} catch (Exception $e) {
    error_log("Calendar timeline error: " . $e->getMessage());
    $rooms = [];
    $bookings = [];
    $bookings_by_room = [];
}

// Status colors
$status_colors = [
    'pending' => ['bg' => '#fbbf24', 'text' => '#92400e', 'label' => 'Chờ duyệt'],
    'confirmed' => ['bg' => '#3b82f6', 'text' => '#1e3a8a', 'label' => 'Đã xác nhận'],
    'checked_in' => ['bg' => '#10b981', 'text' => '#065f46', 'label' => 'Đang ở'],
    'checked_out' => ['bg' => '#6b7280', 'text' => '#1f2937', 'label' => 'Đã trả'],
    'completed' => ['bg' => '#8b5cf6', 'text' => '#4c1d95', 'label' => 'Hoàn thành']
];

include 'includes/admin-header.php';
?>

<style>
.timeline-container {
    overflow-x: auto;
    position: relative;
}

.timeline-grid {
    display: grid;
    grid-template-columns: 200px repeat(<?php echo $days_to_show; ?>, 100px);
    min-width: fit-content;
}

.timeline-header {
    position: sticky;
    top: 0;
    z-index: 20;
    background: white;
    border-bottom: 2px solid #d4af37;
}

.dark .timeline-header {
    background: #0f172a;
}

.room-row {
    border-bottom: 1px solid #e5e7eb;
    min-height: 60px;
    position: relative;
}

.dark .room-row {
    border-color: #334155;
}

.room-label {
    position: sticky;
    left: 0;
    z-index: 10;
    background: white;
    border-right: 2px solid #e5e7eb;
    padding: 12px;
    font-weight: 600;
}

.dark .room-label {
    background: #1e293b;
    border-color: #334155;
}

.date-cell {
    border-right: 1px solid #e5e7eb;
    position: relative;
}

.dark .date-cell {
    border-color: #334155;
}

.date-cell.today {
    background: rgba(212, 175, 55, 0.1);
}

.booking-bar {
    position: absolute;
    top: 8px;
    height: 44px;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 5;
}

.booking-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    z-index: 15;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.dark .legend-item {
    background: #1e293b;
}
</style>

<!-- Controls -->
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="?start=<?php echo date('Y-m-d', strtotime($start_date . ' -7 days')); ?>" 
           class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">chevron_left</span>
            7 ngày trước
        </a>
        <a href="?start=<?php echo date('Y-m-d'); ?>" 
           class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">today</span>
            Hôm nay
        </a>
        <a href="?start=<?php echo date('Y-m-d', strtotime($start_date . ' +7 days')); ?>" 
           class="btn btn-secondary">
            7 ngày sau
            <span class="material-symbols-outlined text-sm">chevron_right</span>
        </a>
    </div>
    
    <div class="flex items-center gap-3">
        <a href="calendar.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">calendar_month</span>
            Xem dạng lịch
        </a>
        <a href="bookings.php" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">add</span>
            Tạo đặt phòng
        </a>
    </div>
</div>

<!-- Legend -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-center gap-4 flex-wrap">
            <span class="font-semibold text-sm">Trạng thái:</span>
            <?php foreach ($status_colors as $status => $config): ?>
                <div class="legend-item">
                    <div class="w-4 h-4 rounded" style="background-color: <?php echo $config['bg']; ?>;"></div>
                    <span class="text-sm"><?php echo $config['label']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Timeline -->
<div class="card">
    <div class="card-body p-0">
        <div class="timeline-container">
            <div class="timeline-grid">
                <!-- Header Row -->
                <div class="timeline-header p-4 font-bold">Phòng</div>
                <?php for ($i = 0; $i < $days_to_show; $i++): 
                    $date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' days'));
                    $is_today = ($date === date('Y-m-d'));
                    $day_name = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][date('w', strtotime($date))];
                ?>
                    <div class="timeline-header p-2 text-center <?php echo $is_today ? 'bg-[#d4af37]/20' : ''; ?>">
                        <div class="text-xs text-gray-500"><?php echo $day_name; ?></div>
                        <div class="font-bold <?php echo $is_today ? 'text-[#d4af37]' : ''; ?>">
                            <?php echo date('d/m', strtotime($date)); ?>
                        </div>
                    </div>
                <?php endfor; ?>
                
                <!-- Room Rows -->
                <?php 
                $current_category = '';
                foreach ($rooms as $room): 
                    // Category header
                    if ($current_category !== $room['category']):
                        $current_category = $room['category'];
                        $category_name = $room['category'] === 'room' ? 'PHÒNG' : 'CĂN HỘ';
                ?>
                    <div class="col-span-full bg-gray-100 dark:bg-slate-800 p-2 font-bold text-sm" style="color: #d4af37;">
                        <?php echo $category_name; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Room Label -->
                <div class="room-label">
                    <div class="font-semibold"><?php echo htmlspecialchars($room['room_number']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($room['type_name']); ?></div>
                </div>
                
                <!-- Date Cells -->
                <?php for ($i = 0; $i < $days_to_show; $i++): 
                    $date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' days'));
                    $is_today = ($date === date('Y-m-d'));
                ?>
                    <div class="room-row date-cell <?php echo $is_today ? 'today' : ''; ?>" 
                         data-room="<?php echo $room['room_id']; ?>" 
                         data-date="<?php echo $date; ?>">
                        
                        <?php 
                        // Check if this is the first day of a booking
                        if (isset($bookings_by_room[$room['room_id']])):
                            foreach ($bookings_by_room[$room['room_id']] as $booking):
                                if ($booking['check_in_date'] === $date):
                                    // Calculate width (number of days)
                                    $check_in = strtotime($booking['check_in_date']);
                                    $check_out = strtotime($booking['check_out_date']);
                                    $duration = ceil(($check_out - $check_in) / 86400);
                                    
                                    // Calculate width in pixels (100px per day)
                                    $width = ($duration * 100) - 4;
                                    
                                    $status = $booking['status'];
                                    $color = $status_colors[$status] ?? $status_colors['confirmed'];
                        ?>
                            <div class="booking-bar" 
                                 style="width: <?php echo $width; ?>px; 
                                        background-color: <?php echo $color['bg']; ?>; 
                                        color: <?php echo $color['text']; ?>;"
                                 onclick="window.location.href='booking-detail.php?id=<?php echo $booking['booking_id']; ?>'"
                                 title="<?php echo htmlspecialchars($booking['full_name']); ?> - <?php echo $color['label']; ?>">
                                <div class="font-semibold"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                <div class="text-xs opacity-75"><?php echo $duration; ?> đêm</div>
                            </div>
                        <?php 
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                <?php endfor; ?>
                
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold mb-2" style="color: #d4af37;"><?php echo count($rooms); ?></div>
            <div class="text-sm text-gray-600">Tổng số phòng</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo count($bookings); ?></div>
            <div class="text-sm text-gray-600">Đơn đặt trong kỳ</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <?php 
            $occupied = 0;
            foreach ($rooms as $room) {
                if (isset($bookings_by_room[$room['room_id']])) {
                    foreach ($bookings_by_room[$room['room_id']] as $booking) {
                        if ($booking['check_in_date'] <= date('Y-m-d') && $booking['check_out_date'] >= date('Y-m-d')) {
                            $occupied++;
                            break;
                        }
                    }
                }
            }
            $occupancy_rate = count($rooms) > 0 ? ($occupied / count($rooms)) * 100 : 0;
            ?>
            <div class="text-3xl font-bold text-green-600 mb-2"><?php echo number_format($occupancy_rate, 0); ?>%</div>
            <div class="text-sm text-gray-600">Tỷ lệ lấp đầy</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2"><?php echo count($rooms) - $occupied; ?></div>
            <div class="text-sm text-gray-600">Phòng trống hôm nay</div>
        </div>
    </div>
</div>

<script>
// Add click handler for empty cells to create booking
document.querySelectorAll('.date-cell').forEach(cell => {
    cell.addEventListener('click', function(e) {
        if (e.target === this) {
            const roomId = this.dataset.room;
            const date = this.dataset.date;
            window.location.href = `bookings.php?room_id=${roomId}&check_in=${date}`;
        }
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
