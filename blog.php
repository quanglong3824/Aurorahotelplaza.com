<?php
require_once 'config/database.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Get category filter
$category = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $db = getDB();
    
    // Build query
    $where = "status = 'published'";
    $params = [];
    
    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
    }
    
    // Get total posts
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM posts WHERE $where");
    $stmt->execute($params);
    $total_posts = $stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    // Get posts
    $stmt = $db->prepare("
        SELECT p.*, u.full_name as author_name,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'approved') as comment_count
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        WHERE $where
        ORDER BY p.published_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Get categories
    $stmt = $db->query("SELECT DISTINCT category FROM posts WHERE status = 'published' AND category IS NOT NULL");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $posts = [];
    $categories = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Bài viết - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/blog.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20">
    
    <!-- Hero Section -->
    <section class="relative bg-primary-light/30 dark:bg-surface-dark py-16">
        <div class="mx-auto max-w-7xl px-4">
            <div class="text-center">
                <h1 class="font-display text-4xl font-bold md:text-5xl mb-4">Tin tức & Bài viết</h1>
                <p class="text-lg text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                    Khám phá những câu chuyện, mẹo du lịch và tin tức mới nhất từ Aurora Hotel Plaza
                </p>
            </div>
        </div>
    </section>

    <!-- Blog Content -->
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4">
            
            <!-- Category Filter -->
            <?php if (!empty($categories)): ?>
            <div class="mb-8 flex flex-wrap gap-3 justify-center">
                <a href="blog.php" class="category-tag <?php echo empty($category) ? 'active' : ''; ?>">
                    Tất cả
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="blog.php?category=<?php echo urlencode($cat); ?>" 
                   class="category-tag <?php echo $category === $cat ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Posts Grid -->
            <?php if (!empty($posts)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $post): ?>
                <article class="blog-card">
                    <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>" class="block">
                        <?php if ($post['featured_image']): ?>
                        <div class="blog-card-image" style="background-image: url('<?php echo htmlspecialchars($post['featured_image']); ?>')"></div>
                        <?php else: ?>
                        <div class="blog-card-image" style="background-image: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg')"></div>
                        <?php endif; ?>
                        
                        <div class="blog-card-content">
                            <?php if ($post['category']): ?>
                            <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                            <?php endif; ?>
                            
                            <h3 class="blog-card-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            
                            <?php if ($post['excerpt']): ?>
                            <p class="blog-card-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <?php endif; ?>
                            
                            <div class="blog-card-meta">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                    <span><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                                    <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">comment</span>
                                    <span><?php echo $post['comment_count']; ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                   class="pagination-btn">
                    <span class="material-symbols-outlined">chevron_left</span>
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="pagination-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                       class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                   class="pagination-btn">
                    <span class="material-symbols-outlined">chevron_right</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-16">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">article</span>
                <p class="text-xl text-text-secondary-light dark:text-text-secondary-dark">
                    Chưa có bài viết nào
                </p>
            </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
