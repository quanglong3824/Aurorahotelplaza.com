<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';

// Kiểm tra quyền truy cập
AuthMiddleware::requireStaff();

$current_page = 'apartment-inquiries';
$page_title = 'Quản lý yêu cầu căn hộ';
$page_subtitle = 'Danh sách yêu cầu tư vấn căn hộ từ khách hàng';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$apartment_filter = $_GET['apartment'] ?? 'all';
$duration_filter = $_GET['duration'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = ["b.booking_type = 'inquiry'"];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "b.status = :status";
    $params[':status'] = $status_filter;
}

if ($apartment_filter !== 'all') {
    $where_clauses[] = "b.room_type_id = :room_type_id";
    $params[':room_type_id'] = $apartment_filter;
}

if ($duration_filter !== 'all') {
    $where_clauses[] = "b.duration_type = :duration_type";
    $params[':duration_type'] = $duration_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(b.booking_code LIKE :search OR b.guest_name LIKE :search OR b.guest_email LIKE :search OR b.guest_phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch inquiries
$inquiries = [];
$total_inquiries = 0;
$stats = ['pending' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0];
$apartments = [];

try {
    $db = getDB();

    // Get total count
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM bookings b WHERE {$where_sql}");
    $count_stmt->execute($params);
    $total_inquiries = $count_stmt->fetchColumn();

    // Get inquiries with pagination
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, rt.thumbnail,
               u.full_name as user_name, u.phone as user_phone
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE {$where_sql}
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats
    $stats_stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM bookings 
        WHERE booking_type = 'inquiry'
        GROUP BY status
    ");
    while ($row = $stats_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }

    // Get apartment types for filter
    $apartments_stmt = $db->query("
        SELECT room_type_id, type_name 
        FROM room_types 
        WHERE category = 'apartment' 
        ORDER BY type_name
    ");
    $apartments = $apartments_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Apartment inquiries page error: " . $e->getMessage());
}

$total_pages = ceil($total_inquiries / $per_page);

// Duration labels
$duration_labels = [
    '1_month' => '1 tháng',
    '3_months' => '3 tháng',
    '6_months' => '6 tháng',
    '12_months' => '12 tháng',
    'custom' => 'Khác'
];

// Status labels
$status_labels = [
    'pending' => ['label' => 'Chờ liên hệ', 'class' => 'badge-warning'],
    'contacted' => ['label' => 'Đã liên hệ', 'class' => 'badge-info'],
    'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'badge-success'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'badge-danger'],
    'checked_out' => ['label' => 'Hoàn tất', 'class' => 'badge-secondary']
];

include 'includes/admin-header.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Chờ liên hệ</p>
        <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?? 0 ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã liên hệ</p>
        <p class="text-2xl font-bold text-blue-600"><?= $stats['contacted'] ?? 0 ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã xác nhận</p>
        <p class="text-2xl font-bold text-green-600"><?= $stats['confirmed'] ?? 0 ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã hủy</p>
        <p class="text-2xl font-bold text-red-600"><?= $stats['cancelled'] ?? 0 ?></p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium mb-1">Tìm kiếm</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Mã, tên, email, SĐT..."
                class="form-input w-full rounded-lg border border-gray-300 px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select name="status" class="form-select rounded-lg border border-gray-300 px-4 py-2">
                <option value="all">Tất cả</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Chờ liên hệ</option>
                <option value="contacted" <?= $status_filter === 'contacted' ? 'selected' : '' ?>>Đã liên hệ</option>
                <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Căn hộ</label>
            <select name="apartment" class="form-select rounded-lg border border-gray-300 px-4 py-2">
                <option value="all">Tất cả</option>
                <?php foreach ($apartments as $apt): ?>
                    <option value="<?= $apt['room_type_id'] ?>" <?= $apartment_filter == $apt['room_type_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($apt['type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Thời gian</label>
            <select name="duration" class="form-select rounded-lg border border-gray-300 px-4 py-2">
                <option value="all">Tất cả</option>
                <?php foreach ($duration_labels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $duration_filter === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-accent text-white rounded-lg px-6 py-2 font-medium hover:opacity-90">
            <span class="material-symbols-outlined text-sm align-middle">filter_alt</span> Lọc
        </button>
        <?php if ($search || $status_filter !== 'all' || $apartment_filter !== 'all' || $duration_filter !== 'all'): ?>
            <a href="apartment-inquiries.php" class="text-gray-600 hover:text-gray-800 px-4 py-2">Xóa bộ lọc</a>
        <?php endif; ?>
    </form>
</div>

<!-- Inquiries Table -->
<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="font-semibold">Danh sách yêu cầu (<?= $total_inquiries ?>)</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>Căn hộ</th>
                    <th>Ngày dự kiến</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">Không có yêu cầu nào</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                        <tr class="<?= $inquiry['status'] === 'pending' ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' ?>">
                            <td class="font-medium font-mono">
                                <a href="booking-detail.php?code=<?= htmlspecialchars($inquiry['booking_code']) ?>"
                                    class="text-accent hover:underline">
                                    <?= htmlspecialchars($inquiry['booking_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($inquiry['guest_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($inquiry['guest_email']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($inquiry['guest_phone']) ?></div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-purple-500">apartment</span>
                                    <?= htmlspecialchars($inquiry['type_name'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <?= date('d/m/Y', strtotime($inquiry['check_in_date'])) ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= $duration_labels[$inquiry['duration_type']] ?? $inquiry['duration_type'] ?? 'N/A' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusInfo = $status_labels[$inquiry['status']] ?? ['label' => $inquiry['status'], 'class' => 'badge-secondary'];
                                ?>
                                <span class="badge <?= $statusInfo['class'] ?>">
                                    <?= $statusInfo['label'] ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <?= date('d/m/Y H:i', strtotime($inquiry['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn" onclick="viewInquiry(<?= $inquiry['booking_id'] ?>)"
                                        title="Xem chi tiết">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </button>
                                    <button class="action-btn"
                                        onclick="updateInquiryStatus(<?= $inquiry['booking_id'] ?>, '<?= $inquiry['status'] ?>')"
                                        title="Cập nhật trạng thái">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <a href="tel:<?= htmlspecialchars($inquiry['guest_phone']) ?>"
                                        class="action-btn text-green-600" title="Gọi điện">
                                        <span class="material-symbols-outlined text-sm">call</span>
                                    </a>
                                    <a href="mailto:<?= htmlspecialchars($inquiry['guest_email']) ?>"
                                        class="action-btn text-blue-600" title="Gửi email">
                                        <span class="material-symbols-outlined text-sm">mail</span>
                                    </a>
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
        <div class="p-4 border-t flex justify-center gap-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&apartment=<?= $apartment_filter ?>&duration=<?= $duration_filter ?>&search=<?= urlencode($search) ?>"
                    class="px-3 py-1 rounded border hover:bg-gray-100">Trước</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&apartment=<?= $apartment_filter ?>&duration=<?= $duration_filter ?>&search=<?= urlencode($search) ?>"
                    class="px-3 py-1 rounded border <?= $i === $page ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&apartment=<?= $apartment_filter ?>&duration=<?= $duration_filter ?>&search=<?= urlencode($search) ?>"
                    class="px-3 py-1 rounded border hover:bg-gray-100">Sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View Inquiry Modal -->
<div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold">Chi tiết yêu cầu căn hộ</h3>
            <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="viewModalContent" class="p-6">
            <!-- Content loaded via JS -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full mx-4">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold">Cập nhật trạng thái</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="statusForm" class="p-6">
            <input type="hidden" name="booking_id" id="statusBookingId">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Trạng thái</label>
                <select name="status" id="statusSelect"
                    class="form-select w-full rounded-lg border border-gray-300 px-4 py-2">
                    <option value="pending">Chờ liên hệ</option>
                    <option value="contacted">Đã liên hệ</option>
                    <option value="confirmed">Đã xác nhận</option>
                    <option value="cancelled">Đã hủy</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Ghi chú (tùy chọn)</label>
                <textarea name="notes" id="statusNotes" rows="3"
                    class="form-textarea w-full rounded-lg border border-gray-300 px-4 py-2"
                    placeholder="Ghi chú về cuộc gọi/email..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal('statusModal')"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-100">Hủy</button>
                <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg hover:opacity-90">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function viewInquiry(id) {
        fetch('api/apartment-inquiries.php?action=get&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const i = data.inquiry;
                    const durationLabels = {
                        '1_month': '1 tháng',
                        '3_months': '3 tháng',
                        '6_months': '6 tháng',
                        '12_months': '12 tháng (1 năm)',
                        'custom': 'Khác'
                    };

                    document.getElementById('viewModalContent').innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <span class="material-symbols-outlined text-2xl text-purple-500">apartment</span>
                            <div>
                                <p class="font-bold">${escapeHtml(i.type_name || 'N/A')}</p>
                                <p class="text-sm text-gray-500">Mã: ${escapeHtml(i.booking_code)}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-500">Họ tên</label>
                                <p class="font-medium">${escapeHtml(i.guest_name)}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Số điện thoại</label>
                                <p class="font-medium">
                                    <a href="tel:${escapeHtml(i.guest_phone)}" class="text-accent hover:underline">
                                        ${escapeHtml(i.guest_phone)}
                                    </a>
                                </p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-sm text-gray-500">Email</label>
                                <p class="font-medium">
                                    <a href="mailto:${escapeHtml(i.guest_email)}" class="text-accent hover:underline">
                                        ${escapeHtml(i.guest_email)}
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <hr class="border-gray-200 dark:border-gray-700">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-500">Ngày dự kiến nhận</label>
                                <p class="font-medium">${i.check_in_date}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Thời gian cư trú</label>
                                <p class="font-medium">${durationLabels[i.duration_type] || i.duration_type || 'N/A'}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Số người lớn</label>
                                <p class="font-medium">${i.num_adults || 1}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Số trẻ em</label>
                                <p class="font-medium">${i.num_children || 0}</p>
                            </div>
                        </div>
                        
                        ${i.inquiry_message ? `
                        <div>
                            <label class="text-sm text-gray-500">Tin nhắn / Yêu cầu</label>
                            <p class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg whitespace-pre-wrap">${escapeHtml(i.inquiry_message)}</p>
                        </div>
                        ` : ''}
                        
                        <div class="flex gap-3 pt-4">
                            <a href="tel:${escapeHtml(i.guest_phone)}" 
                               class="flex-1 bg-green-600 text-white text-center px-4 py-2 rounded-lg hover:opacity-90">
                                <span class="material-symbols-outlined text-sm align-middle mr-1">call</span>
                                Gọi điện
                            </a>
                            <a href="mailto:${escapeHtml(i.guest_email)}" 
                               class="flex-1 bg-blue-600 text-white text-center px-4 py-2 rounded-lg hover:opacity-90">
                                <span class="material-symbols-outlined text-sm align-middle mr-1">mail</span>
                                Gửi email
                            </a>
                        </div>
                    </div>
                `;
                    openModal('viewModal');
                } else {
                    showToast(data.message || 'Không thể tải thông tin', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    }

    function updateInquiryStatus(id, currentStatus) {
        document.getElementById('statusBookingId').value = id;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('statusNotes').value = '';
        openModal('statusModal');
    }

    document.getElementById('statusForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_status');

        fetch('api/apartment-inquiries.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Cập nhật thành công', 'success');
                    closeModal('statusModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    });

    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include 'includes/admin-footer.php'; ?>