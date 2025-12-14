<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';

// Kiểm tra quyền truy cập
AuthMiddleware::requireStaff();

$page_title = 'Quản lý liên hệ';
$page_subtitle = 'Danh sách liên hệ từ khách hàng';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$subject_filter = $_GET['subject'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = ["1=1"];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "c.status = :status";
    $params[':status'] = $status_filter;
}

if ($subject_filter !== 'all') {
    $where_clauses[] = "c.subject = :subject";
    $params[':subject'] = $subject_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR c.message LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch contacts
$contacts = [];
$total_contacts = 0;
$stats = ['new' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
$subjects = [];

try {
    $db = getDB();
    
    // Get total count
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM contact_submissions c WHERE {$where_sql}");
    $count_stmt->execute($params);
    $total_contacts = $count_stmt->fetchColumn();
    
    // Get contacts with pagination
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as assigned_name
        FROM contact_submissions c
        LEFT JOIN users u ON c.assigned_to = u.user_id
        WHERE {$where_sql}
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $stats_stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM contact_submissions 
        GROUP BY status
    ");
    while ($row = $stats_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }
    
    // Get unique subjects for filter
    $subjects_stmt = $db->query("SELECT DISTINCT subject FROM contact_submissions WHERE subject IS NOT NULL ORDER BY subject");
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    error_log("Contacts page error: " . $e->getMessage());
}

$total_pages = ceil($total_contacts / $per_page);

include 'includes/admin-header.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Mới</p>
        <p class="text-2xl font-bold text-blue-600"><?= $stats['new'] ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang xử lý</p>
        <p class="text-2xl font-bold text-yellow-600"><?= $stats['in_progress'] ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã giải quyết</p>
        <p class="text-2xl font-bold text-green-600"><?= $stats['resolved'] ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã đóng</p>
        <p class="text-2xl font-bold text-gray-600"><?= $stats['closed'] ?></p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium mb-1">Tìm kiếm</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Tên, email, SĐT, nội dung..."
                   class="form-input w-full rounded-lg border border-gray-300 px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select name="status" class="form-select rounded-lg border border-gray-300 px-4 py-2">
                <option value="all">Tất cả</option>
                <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>Mới</option>
                <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>Đang xử lý</option>
                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Đã giải quyết</option>
                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Chủ đề</label>
            <select name="subject" class="form-select rounded-lg border border-gray-300 px-4 py-2">
                <option value="all">Tất cả</option>
                <?php foreach ($subjects as $subj): ?>
                <option value="<?= htmlspecialchars($subj) ?>" <?= $subject_filter === $subj ? 'selected' : '' ?>>
                    <?= htmlspecialchars($subj) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-accent text-white rounded-lg px-6 py-2 font-medium hover:opacity-90">
            <span class="material-symbols-outlined text-sm align-middle">filter_alt</span> Lọc
        </button>
        <?php if ($search || $status_filter !== 'all' || $subject_filter !== 'all'): ?>
        <a href="contacts.php" class="text-gray-600 hover:text-gray-800 px-4 py-2">Xóa bộ lọc</a>
        <?php endif; ?>
    </form>
</div>

<!-- Contacts Table -->
<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="font-semibold">Danh sách liên hệ (<?= $total_contacts ?>)</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Chủ đề</th>
                    <th>Nội dung</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">Không có liên hệ nào</td>
                </tr>
                <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                <tr class="<?= $contact['status'] === 'new' ? 'bg-blue-50 dark:bg-blue-900/20' : '' ?>">
                    <td class="font-medium">#<?= $contact['submission_id'] ?></td>
                    <td>
                        <div class="font-medium"><?= htmlspecialchars($contact['name']) ?></div>
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($contact['email']) ?></div>
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($contact['phone']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($contact['subject'] ?? 'Liên hệ chung') ?></td>
                    <td class="max-w-xs">
                        <div class="truncate" title="<?= htmlspecialchars($contact['message']) ?>">
                            <?= htmlspecialchars(mb_substr($contact['message'], 0, 80)) ?>...
                        </div>
                    </td>
                    <td>
                        <?php
                        $status_badges = [
                            'new' => 'badge-info',
                            'in_progress' => 'badge-warning',
                            'resolved' => 'badge-success',
                            'closed' => 'badge-secondary'
                        ];
                        $status_labels = [
                            'new' => 'Mới',
                            'in_progress' => 'Đang xử lý',
                            'resolved' => 'Đã giải quyết',
                            'closed' => 'Đã đóng'
                        ];
                        ?>
                        <span class="badge <?= $status_badges[$contact['status']] ?? 'badge-secondary' ?>">
                            <?= $status_labels[$contact['status']] ?? $contact['status'] ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap">
                        <?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="viewContact(<?= $contact['submission_id'] ?>)" title="Xem chi tiết">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                            <button class="action-btn" onclick="updateStatus(<?= $contact['submission_id'] ?>)" title="Cập nhật trạng thái">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            <button class="action-btn text-red-600" onclick="deleteContact(<?= $contact['submission_id'] ?>)" title="Xóa">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
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
        <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&subject=<?= urlencode($subject_filter) ?>&search=<?= urlencode($search) ?>" 
           class="px-3 py-1 rounded border hover:bg-gray-100">Trước</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&subject=<?= urlencode($subject_filter) ?>&search=<?= urlencode($search) ?>" 
           class="px-3 py-1 rounded border <?= $i === $page ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&subject=<?= urlencode($subject_filter) ?>&search=<?= urlencode($search) ?>" 
           class="px-3 py-1 rounded border hover:bg-gray-100">Sau</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- View Contact Modal -->
<div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold">Chi tiết liên hệ</h3>
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
            <input type="hidden" name="submission_id" id="statusSubmissionId">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Trạng thái</label>
                <select name="status" id="statusSelect" class="form-select w-full rounded-lg border border-gray-300 px-4 py-2">
                    <option value="new">Mới</option>
                    <option value="in_progress">Đang xử lý</option>
                    <option value="resolved">Đã giải quyết</option>
                    <option value="closed">Đã đóng</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Ghi chú (tùy chọn)</label>
                <textarea name="note" id="statusNote" rows="3" 
                          class="form-textarea w-full rounded-lg border border-gray-300 px-4 py-2"
                          placeholder="Ghi chú nội bộ..."></textarea>
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
function viewContact(id) {
    fetch('api/contacts.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const c = data.contact;
                document.getElementById('viewModalContent').innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-500">Họ tên</label>
                                <p class="font-medium">${escapeHtml(c.name)}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Email</label>
                                <p class="font-medium">${escapeHtml(c.email)}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Số điện thoại</label>
                                <p class="font-medium">${escapeHtml(c.phone || 'N/A')}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Chủ đề</label>
                                <p class="font-medium">${escapeHtml(c.subject || 'Liên hệ chung')}</p>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Nội dung</label>
                            <p class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg whitespace-pre-wrap">${escapeHtml(c.message)}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <label class="text-gray-500">Ngày gửi:</label>
                                <span class="ml-2">${c.created_at}</span>
                            </div>
                            <div>
                                <label class="text-gray-500">IP:</label>
                                <span class="ml-2">${c.ip_address || 'N/A'}</span>
                            </div>
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

function updateStatus(id) {
    document.getElementById('statusSubmissionId').value = id;
    openModal('statusModal');
}

document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_status');
    
    fetch('api/contacts.php', {
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

function deleteContact(id) {
    if (!confirm('Bạn có chắc muốn xóa liên hệ này?')) return;
    
    fetch('api/contacts.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đã xóa liên hệ', 'success');
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
