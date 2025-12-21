<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/booking-helper.php';

$page_title = 'Quản lý đặt phòng';
$page_subtitle = 'Danh sách và quản lý các đơn đặt phòng';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "b.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    // Smart search - hỗ trợ mã ngắn
    $possible_codes = BookingHelper::parseSmartCode($search);

    $search_conditions = [];
    foreach ($possible_codes as $index => $code) {
        if (strpos($code, '%') !== false) {
            $search_conditions[] = "b.booking_code LIKE :code{$index}";
            $params[":code{$index}"] = $code;
        } else {
            $search_conditions[] = "b.booking_code = :code{$index}";
            $params[":code{$index}"] = $code;
        }
    }

    // Thêm tìm kiếm theo tên, email, SĐT
    $search_conditions[] = "b.guest_name LIKE :search_text";
    $search_conditions[] = "b.guest_email LIKE :search_text";
    $search_conditions[] = "b.guest_phone LIKE :search_text";
    $params[':search_text'] = "%$search%";

    $where_clauses[] = "(" . implode(' OR ', $search_conditions) . ")";
}

if (!empty($date_from)) {
    $where_clauses[] = "b.check_in_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "b.check_in_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM bookings b $where_sql";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);

    // Get bookings
    $sql = "
        SELECT b.*, u.full_name as user_name, rt.type_name, r.room_number
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        $where_sql
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get status counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
            SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings
    ");
    $status_counts = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Bookings page error: " . $e->getMessage());
    $bookings = [];
    $total_records = 0;
    $total_pages = 0;
    $status_counts = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'checked_in' => 0, 'checked_out' => 0, 'cancelled' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
        <!-- Search -->
        <div class="search-box flex-1 min-w-[200px] relative group">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="VD: 6C320B hoặc BK20251119..." class="form-input"
                title="Tìm kiếm thông minh: Nhập 6 ký tự cuối hoặc mã đầy đủ">

            <!-- Tooltip -->
            <div
                class="hidden group-hover:block absolute top-full left-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 z-50">
                <p class="font-semibold mb-2 text-sm">🔍 Tìm kiếm thông minh:</p>
                <ul class="text-xs space-y-1 text-gray-600 dark:text-gray-400">
                    <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">6C320B</span> - Chỉ 6 ký tự
                        cuối (tự động thêm ngày hôm nay)</li>
                    <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">BK20251119</span> - Tìm tất
                        cả đơn trong ngày</li>
                    <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">BK202511196C320B</span> - Mã
                        đầy đủ</li>
                    <li>✅ Tên khách, email, số điện thoại</li>
                </ul>
            </div>
        </div>

        <!-- Status Filter -->
        <select name="status" class="form-select w-auto">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
            <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Đã nhận phòng
            </option>
            <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Đã trả phòng
            </option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
        </select>

        <!-- Date From -->
        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="Từ ngày"
            class="form-input w-auto">

        <!-- Date To -->
        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="Đến ngày"
            class="form-input w-auto">

        <!-- Buttons -->
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>

        <a href="bookings.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Reset
        </a>
    </form>

    <!-- Create Booking Button -->
    <a href="create-booking.php" class="btn btn-success">
        <span class="material-symbols-outlined text-sm">add</span>
        Tạo đặt phòng
    </a>
</div>

<!-- Status Tabs -->
<div class="tabs mb-6">
    <a href="?status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
        Tất cả (<?php echo $status_counts['total']; ?>)
    </a>
    <a href="?status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
        Chờ xác nhận (<?php echo $status_counts['pending']; ?>)
    </a>
    <a href="?status=confirmed" class="tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
        Đã xác nhận (<?php echo $status_counts['confirmed']; ?>)
    </a>
    <a href="?status=checked_in" class="tab <?php echo $status_filter === 'checked_in' ? 'active' : ''; ?>">
        Đã nhận phòng (<?php echo $status_counts['checked_in']; ?>)
    </a>
    <a href="?status=checked_out" class="tab <?php echo $status_filter === 'checked_out' ? 'active' : ''; ?>">
        Đã trả phòng (<?php echo $status_counts['checked_out']; ?>)
    </a>
    <a href="?status=cancelled" class="tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
        Đã hủy (<?php echo $status_counts['cancelled']; ?>)
    </a>
</div>

<!-- Bookings Table -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-semibold">Danh sách đặt phòng</h3>
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                Hiển thị <?php echo count($bookings); ?> / <?php echo $total_records; ?> đơn
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="btn btn-secondary btn-sm no-print">
                <span class="material-symbols-outlined text-sm">print</span>
                In
            </button>
            <a href="api/export-bookings.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
                <span class="material-symbols-outlined text-sm">download</span>
                Xuất Excel
            </a>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Loại phòng</th>
                    <th>Phòng</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Số đêm</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th class="no-print">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">inbox</span>
                                <p class="empty-state-title">Không tìm thấy đặt phòng</p>
                                <p class="empty-state-description">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>"
                                            class="text-accent hover:underline">
                                            <?php echo BookingHelper::formatBookingCode($booking['booking_code'], true); ?>
                                        </a>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Mã ngắn: <span
                                                class="font-mono font-bold"><?php echo BookingHelper::getShortCode($booking['booking_code']); ?></span>
                                        </div>
                                    </div>
                                    <button onclick="quickView(<?php echo $booking['booking_id']; ?>)"
                                        class="p-1.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                        title="Xem nhanh">
                                        <span class="material-symbols-outlined text-sm text-blue-600">visibility</span>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <?php echo htmlspecialchars($booking['guest_phone']); ?>
                                    </p>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                            <td>
                                <?php if ($booking['room_number']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                                <?php else: ?>
                                    <span class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Chưa phân</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                            <td class="text-center"><?php echo $booking['total_nights']; ?></td>
                            <td class="font-medium"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'checked_in' => 'badge-success',
                                    'checked_out' => 'badge-secondary',
                                    'cancelled' => 'badge-danger',
                                    'no_show' => 'badge-danger'
                                ];
                                $status_labels = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'checked_in' => 'Đã nhận phòng',
                                    'checked_out' => 'Đã trả phòng',
                                    'cancelled' => 'Đã hủy',
                                    'no_show' => 'Không đến'
                                ];
                                ?>
                                <span class="badge <?php echo $status_classes[$booking['status']] ?? 'badge-secondary'; ?>">
                                    <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $payment_classes = [
                                    'unpaid' => 'badge-danger',
                                    'partial' => 'badge-warning',
                                    'paid' => 'badge-success',
                                    'refunded' => 'badge-secondary'
                                ];
                                $payment_labels = [
                                    'unpaid' => 'Chưa thanh toán',
                                    'partial' => 'Thanh toán 1 phần',
                                    'paid' => 'Đã thanh toán',
                                    'refunded' => 'Đã hoàn tiền'
                                ];
                                ?>
                                <span
                                    class="badge <?php echo $payment_classes[$booking['payment_status']] ?? 'badge-secondary'; ?>">
                                    <?php echo $payment_labels[$booking['payment_status']] ?? $booking['payment_status']; ?>
                                </span>
                            </td>
                            <td class="no-print">
                                <div class="action-buttons">
                                    <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn"
                                        title="Xem chi tiết">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </a>

                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <button onclick="confirmBooking(<?php echo $booking['booking_id']; ?>)"
                                            class="action-btn text-green-600" title="Xác nhận">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <button onclick="checkinBooking(<?php echo $booking['booking_id']; ?>)"
                                            class="action-btn text-blue-600" title="Check-in">
                                            <span class="material-symbols-outlined text-sm">login</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($booking['status'] === 'checked_in'): ?>
                                        <button onclick="checkoutBooking(<?php echo $booking['booking_id']; ?>)"
                                            class="action-btn text-orange-600" title="Check-out">
                                            <span class="material-symbols-outlined text-sm">logout</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)"
                                            class="action-btn text-red-600" title="Hủy đơn">
                                            <span class="material-symbols-outlined text-sm">cancel</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer flex items-center justify-between">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
            </p>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="pagination-item">
                        <span class="material-symbols-outlined text-sm">first_page</span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                        class="pagination-item">
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                        class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                        class="pagination-item">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                        class="pagination-item">
                        <span class="material-symbols-outlined text-sm">last_page</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Xem nhanh</h3>
            <button onclick="closeQuickView()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Content -->
        <div id="quickViewContent" class="flex-1 overflow-y-auto p-6">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Quick View Function
    function quickView(bookingId) {
        const modal = document.getElementById('quickViewModal');
        const content = document.getElementById('quickViewContent');

        modal.classList.remove('hidden');
        content.innerHTML = '<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div></div>';

        fetch(`api/quick-view-booking.php?booking_id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderQuickView(data);
                } else {
                    content.innerHTML = `<div class="text-center text-red-600 py-12">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<div class="text-center text-red-600 py-12">Có lỗi xảy ra</div>';
            });
    }

    function closeQuickView() {
        document.getElementById('quickViewModal').classList.add('hidden');
    }

    function renderQuickView(data) {
        const { booking, customer, customer_stats, recent_bookings, payments } = data;

        const statusLabels = {
            'pending': { label: 'Chờ xác nhận', class: 'bg-yellow-100 text-yellow-800' },
            'confirmed': { label: 'Đã xác nhận', class: 'bg-blue-100 text-blue-800' },
            'checked_in': { label: 'Đã nhận phòng', class: 'bg-green-100 text-green-800' },
            'checked_out': { label: 'Đã trả phòng', class: 'bg-gray-100 text-gray-800' },
            'cancelled': { label: 'Đã hủy', class: 'bg-red-100 text-red-800' }
        };

        const priceTypeLabels = {
            'single': 'Giá 1 người',
            'double': 'Giá 2 người',
            'short_stay': 'Nghỉ ngắn hạn',
            'weekly': 'Giá tuần',
            'daily': 'Giá ngày'
        };

        const isShortStay = booking.booking_type === 'short_stay';
        const isInquiry = booking.booking_type === 'inquiry';
        const isGuest = customer.is_guest;

        const html = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Booking Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Booking Card -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="text-2xl font-bold" style="color: #d4af37;">${booking.booking_code}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Mã ngắn: <span class="font-mono font-bold">${booking.short_code}</span></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            ${isShortStay ? '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">Nghỉ ngắn hạn</span>' : ''}
                            ${isInquiry ? '<span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">Yêu cầu căn hộ</span>' : ''}
                            <span class="px-3 py-1 rounded-full text-sm font-semibold ${statusLabels[booking.status]?.class || 'bg-gray-100 text-gray-800'}">
                                ${statusLabels[booking.status]?.label || booking.status}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Loại phòng</p>
                            <p class="font-semibold">${booking.type_name}</p>
                            <p class="text-xs text-gray-500">${booking.category === 'apartment' ? 'Căn hộ' : 'Khách sạn'}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Phòng số</p>
                            <p class="font-semibold">${booking.room_number || '<span class="text-yellow-600">Chưa phân</span>'}</p>
                            ${booking.floor ? `<p class="text-xs text-gray-500">Tầng ${booking.floor}${booking.building ? ' - ' + booking.building : ''}</p>` : ''}
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Số khách</p>
                            <p class="font-semibold">${booking.num_adults} người lớn${booking.num_children > 0 ? `, ${booking.num_children} trẻ em` : ''}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Check-in</p>
                            <p class="font-semibold">${new Date(booking.check_in_date).toLocaleDateString('vi-VN')}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Check-out</p>
                            <p class="font-semibold">${new Date(booking.check_out_date).toLocaleDateString('vi-VN')}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Số đêm</p>
                            <p class="font-semibold">${booking.total_nights} đêm</p>
                        </div>
                    </div>

                    <!-- Price Details -->
                    <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-amber-600 text-lg">receipt_long</span>
                            <span class="font-bold text-gray-900 dark:text-white">Chi tiết giá</span>
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-xs font-semibold">${priceTypeLabels[booking.price_type_used] || booking.price_type_used || 'Giá 2 người'}</span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tiền phòng (${booking.total_nights} đêm)</span>
                                <span class="font-medium">${new Intl.NumberFormat('vi-VN').format(booking.room_price * booking.total_nights)}đ</span>
                            </div>
                            ${booking.extra_guest_fee > 0 ? `
                            <div class="flex justify-between text-blue-600">
                                <span>Phụ thu khách thêm</span>
                                <span class="font-medium">${new Intl.NumberFormat('vi-VN').format(booking.extra_guest_fee)}đ</span>
                            </div>
                            ` : ''}
                            ${booking.extra_bed_fee > 0 ? `
                            <div class="flex justify-between text-orange-600">
                                <span>Phí giường phụ (${booking.extra_beds} giường)</span>
                                <span class="font-medium">${new Intl.NumberFormat('vi-VN').format(booking.extra_bed_fee)}đ</span>
                            </div>
                            ` : ''}
                            ${booking.discount_amount > 0 ? `
                            <div class="flex justify-between text-green-600">
                                <span>Giảm giá</span>
                                <span class="font-medium">-${new Intl.NumberFormat('vi-VN').format(booking.discount_amount)}đ</span>
                            </div>
                            ` : ''}
                            <div class="flex justify-between pt-2 border-t border-blue-200 dark:border-blue-700 font-bold text-lg">
                                <span>Tổng cộng</span>
                                <span style="color: #d4af37;">${new Intl.NumberFormat('vi-VN').format(booking.total_amount)}đ</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex gap-2">
                        <a href="booking-detail.php?id=${booking.booking_id}" class="btn btn-primary btn-sm flex-1">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            Xem chi tiết đầy đủ
                        </a>
                        <a href="view-qrcode.php?id=${booking.booking_id}" class="btn btn-secondary btn-sm">
                            <span class="material-symbols-outlined text-sm">qr_code</span>
                            QR
                        </a>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                ${recent_bookings && recent_bookings.length > 0 ? `
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h5 class="font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">history</span>
                        Lịch sử đặt phòng gần đây
                    </h5>
                    <div class="space-y-2">
                        ${recent_bookings.map(rb => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex-1">
                                    <p class="font-semibold text-sm">${rb.booking_code}</p>
                                    <p class="text-xs text-gray-500">${rb.type_name} • ${new Date(rb.check_in_date).toLocaleDateString('vi-VN')}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-sm">${new Intl.NumberFormat('vi-VN').format(rb.total_amount)}đ</p>
                                    <span class="text-xs px-2 py-0.5 rounded ${statusLabels[rb.status]?.class || 'bg-gray-100'}">${statusLabels[rb.status]?.label || rb.status}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
            
            <!-- Customer Info -->
            <div class="space-y-6">
                <!-- Customer Card -->
                <div class="bg-gradient-to-br ${isGuest ? 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20' : 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20'} rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 ${isGuest ? 'bg-gradient-to-br from-gray-500 to-gray-700' : 'bg-gradient-to-br from-purple-500 to-purple-700'} rounded-full flex items-center justify-center text-white font-bold text-xl">
                            ${customer.full_name?.charAt(0).toUpperCase() || '?'}
                        </div>
                        <div class="flex-1">
                            <h5 class="font-bold text-lg">${customer.full_name}</h5>
                            ${isGuest ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-700">Khách vãng lai</span>' : ''}
                            ${!isGuest && customer.tier_name ? `
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold" style="background-color: ${customer.tier_color}20; color: ${customer.tier_color};">
                                    <span class="material-symbols-outlined text-xs">workspace_premium</span>
                                    ${customer.tier_name}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-gray-600">email</span>
                            <span>${customer.email}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-gray-600">phone</span>
                            <a href="tel:${customer.phone}" class="text-blue-600 hover:underline">${customer.phone}</a>
                        </div>
                        ${!isGuest && customer.current_points ? `
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-gray-600">stars</span>
                            <span>${new Intl.NumberFormat('vi-VN').format(customer.current_points)} điểm</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    ${!isGuest ? `
                    <a href="customer-detail.php?id=${customer.user_id}" class="btn btn-secondary btn-sm w-full mt-4">
                        <span class="material-symbols-outlined text-sm">person</span>
                        Xem profile đầy đủ
                    </a>
                    ` : ''}
                </div>
                
                <!-- Stats Card (only for registered users) -->
                ${!isGuest && customer_stats ? `
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h5 class="font-bold mb-4">Thống kê khách hàng</h5>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Tổng đơn</span>
                            <span class="font-bold">${customer_stats.total_bookings}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Hoàn thành</span>
                            <span class="font-bold text-green-600">${customer_stats.completed_bookings}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Đã hủy</span>
                            <span class="font-bold text-red-600">${customer_stats.cancelled_bookings}</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Tổng chi tiêu</span>
                            <span class="font-bold" style="color: #d4af37;">${new Intl.NumberFormat('vi-VN').format(customer_stats.total_spent)}đ</span>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;

        document.getElementById('quickViewContent').innerHTML = html;
    }

    function confirmBooking(id) {
        if (confirm('Xác nhận đơn đặt phòng này?')) {
            updateBookingStatus(id, 'confirmed');
        }
    }

    function checkinBooking(id) {
        if (confirm('Xác nhận khách đã check-in?')) {
            updateBookingStatus(id, 'checked_in');
        }
    }

    function checkoutBooking(id) {
        if (confirm('Xác nhận khách đã check-out?')) {
            updateBookingStatus(id, 'checked_out');
        }
    }

    function cancelBooking(id) {
        const reason = prompt('Lý do hủy đơn:');
        if (reason !== null) {
            updateBookingStatus(id, 'cancelled', reason);
        }
    }

    function updateBookingStatus(id, status, reason = '') {
        const formData = new FormData();
        formData.append('booking_id', id);
        formData.append('status', status);
        if (reason) formData.append('reason', reason);

        fetch('api/update-booking-status.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Cập nhật thành công!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    }
</script>

<?php include 'includes/admin-footer.php'; ?>