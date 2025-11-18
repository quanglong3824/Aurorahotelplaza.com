<?php
session_start();
require_once '../config/database.php';

$page_title = 'Lịch đặt phòng';
$page_subtitle = 'Xem lịch đặt phòng theo tháng';

// Get current month/year or from query
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calculate first and last day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$month_name = date('F Y', $first_day);

// Get bookings for this month
try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, rt.type_name, r.room_number
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE (
            (YEAR(check_in_date) = :year AND MONTH(check_in_date) = :month)
            OR (YEAR(check_out_date) = :year AND MONTH(check_out_date) = :month)
            OR (check_in_date <= :month_end AND check_out_date >= :month_start)
        )
        AND status NOT IN ('cancelled', 'no_show')
        ORDER BY check_in_date ASC
    ");
    
    $month_start = date('Y-m-01', $first_day);
    $month_end = date('Y-m-t', $first_day);
    
    $stmt->execute([
        ':year' => $year,
        ':month' => $month,
        ':month_start' => $month_start,
        ':month_end' => $month_end
    ]);
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize bookings by date
    $bookings_by_date = [];
    foreach ($bookings as $booking) {
        $check_in = strtotime($booking['check_in_date']);
        $check_out = strtotime($booking['check_out_date']);
        
        for ($date = $check_in; $date <= $check_out; $date = strtotime('+1 day', $date)) {
            $date_key = date('Y-m-d', $date);
            if (!isset($bookings_by_date[$date_key])) {
                $bookings_by_date[$date_key] = [];
            }
            $bookings_by_date[$date_key][] = $booking;
        }
    }
    
} catch (Exception $e) {
    error_log("Calendar page error: " . $e->getMessage());
    $bookings = [];
    $bookings_by_date = [];
}

// Navigation
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
               class="btn btn-secondary">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
                Tháng trước
            </a>
            <h3 class="text-2xl font-bold"><?php echo $month_name; ?></h3>
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
               class="btn btn-secondary">
                Tháng sau
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="calendar-timeline.php" class="btn btn-secondary">
                <span class="material-symbols-outlined text-sm">view_timeline</span>
                Xem Timeline
            </a>
            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
               class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">today</span>
                Hôm nay
            </a>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="card">
    <div class="card-body p-0">
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
            <?php
            $days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
            foreach ($days as $day):
            ?>
                <div class="p-4 text-center font-bold text-sm bg-gray-50 dark:bg-slate-800">
                    <?php echo $day; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="grid grid-cols-7">
            <?php
            // Get day of week for first day (0 = Sunday)
            $first_day_of_week = date('w', $first_day);
            
            // Empty cells before first day
            for ($i = 0; $i < $first_day_of_week; $i++):
            ?>
                <div class="min-h-[120px] border-r border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/50"></div>
            <?php endfor; ?>
            
            <?php
            // Days of month
            for ($day = 1; $day <= $days_in_month; $day++):
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $is_today = ($date === date('Y-m-d'));
                $day_bookings = $bookings_by_date[$date] ?? [];
            ?>
                <div class="min-h-[120px] border-r border-b border-gray-200 dark:border-gray-700 p-2 <?php echo $is_today ? 'bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold <?php echo $is_today ? 'text-blue-600 dark:text-blue-400' : ''; ?>">
                            <?php echo $day; ?>
                        </span>
                        <?php if (!empty($day_bookings)): ?>
                            <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full">
                                <?php echo count($day_bookings); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-1">
                        <?php 
                        $shown = 0;
                        foreach ($day_bookings as $booking): 
                            if ($shown >= 3) break;
                            $is_checkin = (date('Y-m-d', strtotime($booking['check_in_date'])) === $date);
                            $is_checkout = (date('Y-m-d', strtotime($booking['check_out_date'])) === $date);
                        ?>
                            <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" 
                               class="block text-xs p-1.5 rounded bg-indigo-100 dark:bg-indigo-900/30 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">
                                <div class="flex items-center gap-1">
                                    <?php if ($is_checkin): ?>
                                        <span class="material-symbols-outlined text-xs text-green-600">login</span>
                                    <?php elseif ($is_checkout): ?>
                                        <span class="material-symbols-outlined text-xs text-red-600">logout</span>
                                    <?php endif; ?>
                                    <span class="truncate font-medium"><?php echo htmlspecialchars($booking['full_name']); ?></span>
                                </div>
                                <div class="text-gray-600 dark:text-gray-400 truncate">
                                    <?php echo htmlspecialchars($booking['room_number'] ?? $booking['type_name']); ?>
                                </div>
                            </a>
                        <?php 
                            $shown++;
                        endforeach; 
                        
                        if (count($day_bookings) > 3):
                        ?>
                            <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                +<?php echo count($day_bookings) - 3; ?> nữa
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
            
            <?php
            // Empty cells after last day
            $last_day_of_week = date('w', mktime(0, 0, 0, $month, $days_in_month, $year));
            $remaining_cells = 6 - $last_day_of_week;
            for ($i = 0; $i < $remaining_cells; $i++):
            ?>
                <div class="min-h-[120px] border-r border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/50"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="mt-6 flex items-center gap-6 text-sm">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-sm text-green-600">login</span>
        <span>Check-in</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-sm text-red-600">logout</span>
        <span>Check-out</span>
    </div>
    <div class="flex items-center gap-2">
        <div class="w-4 h-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200"></div>
        <span>Hôm nay</span>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
