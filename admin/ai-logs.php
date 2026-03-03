<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Bộ lọc
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = [];
$params = [];
if ($type_filter) {
    $where[] = "ai_type = ?";
    $params[] = $type_filter;
}
if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Lấy dữ liệu
$stmt = $db->prepare("SELECT * FROM ai_logs $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng số để phân trang
$total_stmt = $db->prepare("SELECT COUNT(*) FROM ai_logs $where_sql");
$total_stmt->execute($params);
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

require_once 'includes/admin-header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700 mt-14">
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nhật ký Hoạt động AI</h2>
                <p class="text-sm text-gray-500">Theo dõi hiệu năng và lỗi của hệ thống Trợ lý ảo</p>
            </div>
            <div class="flex gap-2">
                <a href="ai-stats.php" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    Xem Thống kê Key
                </a>
            </div>
        </div>

        <!-- Filters -->
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-xl">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Loại AI</label>
                <select name="type" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">Tất cả</option>
                    <option value="client" <?php echo $type_filter == 'client' ? 'selected' : ''; ?>>Khách hàng (Client)</option>
                    <option value="admin" <?php echo $type_filter == 'admin' ? 'selected' : ''; ?>>Quản trị (Admin)</option>
                </select>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Trạng thái</label>
                <select name="status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">Tất cả</option>
                    <option value="success" <?php echo $status_filter == 'success' ? 'selected' : ''; ?>>Thành công</option>
                    <option value="error" <?php echo $status_filter == 'error' ? 'selected' : ''; ?>>Lỗi (Error)</option>
                    <option value="rate_limit" <?php echo $status_filter == 'rate_limit' ? 'selected' : ''; ?>>Giới hạn (429)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2">
                    Lọc dữ liệu
                </button>
                <a href="ai-logs.php" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">Xóa bộ lọc</a>
            </div>
        </form>

        <!-- Table -->
        <div class="relative overflow-x_auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Thời gian</th>
                        <th scope="col" class="px-6 py-3">Hệ thống</th>
                        <th scope="col" class="px-6 py-3">Prompt / Reply</th>
                        <th scope="col" class="px-6 py-3">Chi tiết</th>
                        <th scope="col" class="px-6 py-3">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="5" class="px-6 py-4 text-center">Chưa có dữ liệu nhật ký.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                                    <div class="text-xs"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($log['ai_type'] == 'admin'): ?>
                                        <span class="bg-purple-100 text-purple-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">ADMIN AI</span>
                                    <?php else: ?>
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">GUEST AI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 max-w-md">
                                    <div class="mb-1">
                                        <span class="text-xs font-bold text-gray-400">HỎI:</span>
                                        <div class="text-xs text-gray-600 line-clamp-1 italic"><?php echo htmlspecialchars($log['prompt_text']); ?></div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-gray-400">ĐÁP:</span>
                                        <div class="text-xs text-blue-600 dark:text-blue-400 line-clamp-2"><?php echo htmlspecialchars($log['reply_text'] ?: '(Trống)'); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div>Model: <?php echo $log['model_name']; ?></div>
                                    <div>Tokens: <?php echo number_format($log['tokens_used']); ?></div>
                                    <div class="text-gray-400">Time: <?php echo $log['execution_time']; ?>s</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($log['status'] == 'success'): ?>
                                        <span class="flex items-center text-green-600 font-bold">
                                            <div class="h-2.5 w-2.5 rounded-full bg-green-500 mr-2"></div> Success
                                        </span>
                                    <?php elseif ($log['status'] == 'rate_limit'): ?>
                                        <span class="flex items-center text-orange-500 font-bold">
                                            <div class="h-2.5 w-2.5 rounded-full bg-orange-500 mr-2"></div> Limit (429)
                                        </span>
                                    <?php else: ?>
                                        <div class="flex flex-col">
                                            <span class="flex items-center text-red-600 font-bold">
                                                <div class="h-2.5 w-2.5 rounded-full bg-red-500 mr-2"></div> Error
                                            </span>
                                            <span class="text-[10px] text-red-400 truncate max-w-[100px]" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                <?php echo htmlspecialchars($log['error_message']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                Hiển thị <span class="font-semibold text-gray-900 dark:text-white"><?php echo count($logs); ?></span> trên <span class="font-semibold text-gray-900 dark:text-white"><?php echo $total_rows; ?></span> kết quả
            </span>
            <ul class="inline-flex items-stretch -space-x-px">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li>
                        <a href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>" 
                           class="flex items-center justify-center text-sm py-2 px-3 leading-tight <?php echo $i == $page ? 'text-blue-600 bg-blue-50 border border-blue-300' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
