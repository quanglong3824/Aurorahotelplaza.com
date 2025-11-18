<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý đánh giá';
$page_subtitle = 'Quản lý đánh giá từ khách hàng';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($rating_filter !== 'all') {
    $where_clauses[] = "r.rating >= :rating";
    $params[':rating'] = $rating_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(r.title LIKE :search OR r.comment LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get reviews
    $sql = "
        SELECT r.*, u.full_name, u.email, rt.type_name,
               (SELECT COUNT(*) FROM review_responses WHERE review_id = r.review_id) as response_count
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        $where_sql
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            AVG(rating) as avg_rating
        FROM reviews
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Reviews page error: " . $e->getMessage());
    $reviews = [];
    $counts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'avg_rating' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng đánh giá</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Chờ duyệt</p>
        <p class="text-2xl font-bold text-yellow-600"><?php echo $counts['pending']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã duyệt</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['approved']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Từ chối</p>
        <p class="text-2xl font-bold text-red-600"><?php echo $counts['rejected']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Điểm TB</p>
        <p class="text-2xl font-bold text-accent"><?php echo number_format($counts['avg_rating'], 1); ?> ⭐</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
        <div class="search-box flex-1 min-w-[200px]">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm đánh giá..." class="form-input">
        </div>
        
        <select name="status" class="form-select w-auto">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
        </select>
        
        <select name="rating" class="form-select w-auto">
            <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>Tất cả đánh giá</option>
            <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 sao</option>
            <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 sao trở lên</option>
            <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 sao trở lên</option>
            <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 sao trở lên</option>
            <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 sao trở lên</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
        
        <a href="reviews.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Reset
        </a>
    </form>
</div>

<!-- Reviews List -->
<div class="space-y-4">
    <?php if (empty($reviews)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <span class="empty-state-icon material-symbols-outlined">rate_review</span>
                    <p class="empty-state-title">Chưa có đánh giá nào</p>
                    <p class="empty-state-description">Đánh giá từ khách hàng sẽ hiển thị ở đây</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 bg-accent/20 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-accent">person</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-semibold"><?php echo htmlspecialchars($review['full_name']); ?></h4>
                                    <div class="flex items-center gap-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="material-symbols-outlined text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-500' : 'text-gray-300'; ?>">
                                                star
                                            </span>
                                        <?php endfor; ?>
                                        <span class="text-sm font-medium ml-1"><?php echo number_format($review['rating'], 1); ?></span>
                                    </div>
                                    <span class="badge badge-<?php echo $review['status'] === 'approved' ? 'success' : ($review['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php 
                                        $status_labels = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
                                        echo $status_labels[$review['status']] ?? $review['status'];
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if ($review['type_name']): ?>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-2">
                                        <span class="material-symbols-outlined text-xs align-middle">meeting_room</span>
                                        <?php echo htmlspecialchars($review['type_name']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($review['title']): ?>
                                    <h5 class="font-medium mb-2"><?php echo htmlspecialchars($review['title']); ?></h5>
                                <?php endif; ?>
                                
                                <?php if ($review['comment']): ?>
                                    <p class="text-sm mb-3"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                <?php endif; ?>
                                
                                <!-- Rating Details -->
                                <?php if ($review['cleanliness_rating'] || $review['service_rating'] || $review['location_rating'] || $review['value_rating']): ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3 text-sm">
                                        <?php if ($review['cleanliness_rating']): ?>
                                            <div>
                                                <span class="text-text-secondary-light dark:text-text-secondary-dark">Sạch sẽ:</span>
                                                <span class="font-medium"><?php echo $review['cleanliness_rating']; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($review['service_rating']): ?>
                                            <div>
                                                <span class="text-text-secondary-light dark:text-text-secondary-dark">Dịch vụ:</span>
                                                <span class="font-medium"><?php echo $review['service_rating']; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($review['location_rating']): ?>
                                            <div>
                                                <span class="text-text-secondary-light dark:text-text-secondary-dark">Vị trí:</span>
                                                <span class="font-medium"><?php echo $review['location_rating']; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($review['value_rating']): ?>
                                            <div>
                                                <span class="text-text-secondary-light dark:text-text-secondary-dark">Giá trị:</span>
                                                <span class="font-medium"><?php echo $review['value_rating']; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center gap-4 text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                    <span><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                    <?php if ($review['helpful_count'] > 0): ?>
                                        <span><?php echo $review['helpful_count']; ?> người thấy hữu ích</span>
                                    <?php endif; ?>
                                    <?php if ($review['response_count'] > 0): ?>
                                        <span class="text-accent"><?php echo $review['response_count']; ?> phản hồi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <?php if ($review['status'] === 'pending'): ?>
                                <button onclick="approveReview(<?php echo $review['review_id']; ?>)" 
                                        class="btn btn-sm btn-success">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    Duyệt
                                </button>
                                <button onclick="rejectReview(<?php echo $review['review_id']; ?>)" 
                                        class="btn btn-sm btn-danger">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    Từ chối
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="respondReview(<?php echo $review['review_id']; ?>)" 
                                    class="btn btn-sm btn-primary">
                                <span class="material-symbols-outlined text-sm">reply</span>
                                Phản hồi
                            </button>
                            
                            <button onclick="deleteReview(<?php echo $review['review_id']; ?>)" 
                                    class="btn btn-sm btn-danger">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function approveReview(id) {
    if (confirm('Duyệt đánh giá này?')) {
        updateReviewStatus(id, 'approved');
    }
}

function rejectReview(id) {
    if (confirm('Từ chối đánh giá này?')) {
        updateReviewStatus(id, 'rejected');
    }
}

function updateReviewStatus(id, status) {
    const formData = new FormData();
    formData.append('review_id', id);
    formData.append('status', status);
    
    fetch('api/update-review-status.php', {
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

function respondReview(id) {
    const response = prompt('Nhập phản hồi của bạn:');
    if (response === null || response.trim() === '') return;
    
    const formData = new FormData();
    formData.append('review_id', id);
    formData.append('response', response);
    
    fetch('api/respond-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Phản hồi thành công!', 'success');
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

function deleteReview(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) return;
    
    fetch('api/delete-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'review_id=' + id
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
</script>

<?php include 'includes/admin-footer.php'; ?>
