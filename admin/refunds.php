<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';

AuthMiddleware::requireStaff();

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

try {
    $db = getDB();
    
    // Build query
    $where_conditions = ['1=1'];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = 'r.refund_status = ?';
        $params[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = '(b.booking_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM refunds r
        JOIN bookings b ON r.booking_id = b.booking_id
        LEFT JOIN users u ON r.requested_by = u.user_id
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    // Get refunds
    $offset = ($page - 1) * $per_page;
    $sql = "
        SELECT r.*, b.booking_code, b.total_amount as booking_amount,
               u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
               req_user.full_name as requested_by_name,
               app_user.full_name as approved_by_name,
               proc_user.full_name as processed_by_name
        FROM refunds r
        JOIN bookings b ON r.booking_id = b.booking_id
        LEFT JOIN users u ON r.requested_by = u.user_id
        LEFT JOIN users req_user ON r.requested_by = req_user.user_id
        LEFT JOIN users app_user ON r.approved_by = app_user.user_id
        LEFT JOIN users proc_user ON r.processed_by = proc_user.user_id
        $where_clause
        ORDER BY r.requested_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_refunds,
            SUM(CASE WHEN refund_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN refund_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN refund_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN refund_status = 'pending' THEN refund_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN refund_status = 'completed' THEN refund_amount ELSE 0 END) as completed_amount
        FROM refunds
    ";
    $stmt = $db->query($stats_sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Refunds page error: " . $e->getMessage());
    $error = "Có lỗi xảy ra khi tải danh sách hoàn tiền";
    $refunds = [];
    $stats = [
        'total_refunds' => 0,
        'pending_count' => 0,
        'approved_count' => 0,
        'completed_count' => 0,
        'pending_amount' => 0,
        'completed_amount' => 0
    ];
}

$status_labels = [
    'pending' => ['label' => 'Chờ duyệt', 'color' => 'bg-yellow-100 text-yellow-800'],
    'approved' => ['label' => 'Đã duyệt', 'color' => 'bg-blue-100 text-blue-800'],
    'processing' => ['label' => 'Đang xử lý', 'color' => 'bg-purple-100 text-purple-800'],
    'completed' => ['label' => 'Hoàn thành', 'color' => 'bg-green-100 text-green-800'],
    'rejected' => ['label' => 'Từ chối', 'color' => 'bg-red-100 text-red-800']
];

include 'includes/admin-header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Quản lý hoàn tiền</h1>
        <p class="text-gray-600">Xử lý yêu cầu hoàn tiền khi hủy đặt phòng</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Chờ duyệt</p>
                    <p class="text-2xl font-bold"><?php echo $stats['pending_count']; ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo number_format($stats['pending_amount']); ?> VNĐ</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <span class="material-symbols-outlined text-yellow-600">pending</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Đã duyệt</p>
                    <p class="text-2xl font-bold"><?php echo $stats['approved_count']; ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600">check_circle</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Hoàn thành</p>
                    <p class="text-2xl font-bold"><?php echo $stats['completed_count']; ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo number_format($stats['completed_amount']); ?> VNĐ</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <span class="material-symbols-outlined text-green-600">task_alt</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tổng yêu cầu</p>
                    <p class="text-2xl font-bold"><?php echo $stats['total_refunds']; ?></p>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <span class="material-symbols-outlined text-gray-600">receipt_long</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Tìm mã booking, tên khách, email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($status_labels as $status => $info): ?>
                <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                    <?php echo $info['label']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <span class="material-symbols-outlined text-sm mr-1">search</span>
                Tìm kiếm
            </button>
            
            <a href="refunds.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <span class="material-symbols-outlined">refresh</span>
            </a>
        </form>
    </div>

    <!-- Refunds Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã booking</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày yêu cầu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($refunds)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Không có yêu cầu hoàn tiền nào
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($refunds as $refund): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="booking-detail.php?id=<?php echo $refund['booking_id']; ?>" 
                               class="font-medium text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($refund['booking_code']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($refund['customer_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($refund['customer_email']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-bold text-green-600"><?php echo number_format($refund['refund_amount']); ?> VNĐ</p>
                                <p class="text-xs text-gray-500"><?php echo $refund['refund_percentage']; ?>% của <?php echo number_format($refund['booking_amount']); ?> VNĐ</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_labels[$refund['refund_status']]['color']; ?>">
                                <?php echo $status_labels[$refund['refund_status']]['label']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($refund['requested_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="viewRefund(<?php echo $refund['refund_id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-800">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-600">
                Hiển thị <?php echo (($page - 1) * $per_page) + 1; ?> - <?php echo min($page * $per_page, $total); ?> 
                trong tổng số <?php echo $total; ?> yêu cầu
            </p>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Trước
                </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Sau
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewRefund(refundId) {
    window.location.href = 'refund-detail.php?id=' + refundId;
}
</script>

<?php include 'includes/admin-footer.php'; ?>
