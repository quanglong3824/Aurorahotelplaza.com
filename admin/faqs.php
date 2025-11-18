<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý FAQs';
$page_subtitle = 'Câu hỏi thường gặp';

// Get filter parameters
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build query
$where_clauses = [];
$params = [];

if ($category_filter !== 'all') {
    $where_clauses[] = "category = :category";
    $params[':category'] = $category_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get FAQs
    $sql = "
        SELECT *
        FROM faqs
        $where_sql
        ORDER BY sort_order ASC, created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $stmt = $db->query("SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(views) as total_views
        FROM faqs
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("FAQs page error: " . $e->getMessage());
    $faqs = [];
    $categories = [];
    $counts = ['total' => 0, 'active' => 0, 'total_views' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng câu hỏi</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hiển thị</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['active']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng lượt xem</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($counts['total_views']); ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2">
        <select name="category" class="form-select">
            <option value="all">Tất cả danh mục</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" 
                        <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="status" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Đang hiển thị</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Đã ẩn</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
    </form>
    
    <button onclick="openFaqModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm câu hỏi
    </button>
</div>

<!-- FAQs List -->
<?php if (empty($faqs)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">help</span>
                <p class="empty-state-title">Chưa có câu hỏi nào</p>
                <p class="empty-state-description">Thêm câu hỏi thường gặp đầu tiên</p>
                <button onclick="openFaqModal()" class="btn btn-primary mt-4">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Thêm câu hỏi
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($faqs as $faq): ?>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <span class="badge badge-<?php echo $faq['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $faq['status'] === 'active' ? 'Hiển thị' : 'Ẩn'; ?>
                                </span>
                                <?php if ($faq['category']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($faq['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-3">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </p>
                            
                            <div class="flex items-center gap-4 text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                <span>
                                    <span class="material-symbols-outlined text-xs align-middle">visibility</span>
                                    <?php echo number_format($faq['views']); ?> lượt xem
                                </span>
                                <span>Thứ tự: <?php echo $faq['sort_order']; ?></span>
                                <span>Cập nhật: <?php echo date('d/m/Y', strtotime($faq['updated_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-4">
                            <button onclick='editFaq(<?php echo json_encode($faq); ?>)' 
                                    class="btn btn-sm btn-primary">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            <button onclick="deleteFaq(<?php echo $faq['faq_id']; ?>)" 
                                    class="btn btn-sm btn-danger">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- FAQ Modal -->
<div id="faqModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-semibold" id="modalTitle">Thêm câu hỏi</h3>
            <button onclick="closeFaqModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="faqForm">
                <input type="hidden" id="faq_id" name="faq_id">
                
                <div class="form-group">
                    <label class="form-label">Câu hỏi *</label>
                    <input type="text" id="question" name="question" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Câu trả lời *</label>
                    <textarea id="answer" name="answer" class="form-textarea" rows="5" required></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group mb-0">
                        <label class="form-label">Danh mục</label>
                        <input type="text" id="category" name="category" class="form-input" 
                               list="categoryList" placeholder="VD: Đặt phòng, Thanh toán...">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="form-label">Trạng thái</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active">Hiển thị</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeFaqModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitFaq()" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<script>
function openFaqModal() {
    document.getElementById('modalTitle').textContent = 'Thêm câu hỏi';
    document.getElementById('faqForm').reset();
    document.getElementById('faq_id').value = '';
    document.getElementById('faqModal').classList.add('active');
}

function closeFaqModal() {
    document.getElementById('faqModal').classList.remove('active');
}

function editFaq(faq) {
    document.getElementById('modalTitle').textContent = 'Sửa câu hỏi';
    document.getElementById('faq_id').value = faq.faq_id;
    document.getElementById('question').value = faq.question;
    document.getElementById('answer').value = faq.answer;
    document.getElementById('category').value = faq.category || '';
    document.getElementById('sort_order').value = faq.sort_order;
    document.getElementById('status').value = faq.status;
    document.getElementById('faqModal').classList.add('active');
}

function submitFaq() {
    const form = document.getElementById('faqForm');
    const formData = new FormData(form);
    
    fetch('api/save-faq.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lưu thành công!', 'success');
            closeFaqModal();
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

function deleteFaq(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa câu hỏi này?')) return;
    
    fetch('api/delete-faq.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'faq_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa thành công!', 'success');
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

// Close modal when clicking outside
document.getElementById('faqModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeFaqModal();
});
</script>

<?php include 'includes/admin-footer.php'; ?>
