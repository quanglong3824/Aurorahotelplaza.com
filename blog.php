<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Get category filter
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $db = getDB();
    
    // Build query
    $select_from = "
        SELECT p.*, u.full_name as author_name, bc.category_name, bc.slug as category_slug,
               (SELECT COUNT(*) FROM blog_comments WHERE post_id = p.post_id AND status = 'approved') as comment_count
        FROM blog_posts p
        LEFT JOIN users u ON p.author_id = u.user_id
        LEFT JOIN blog_categories bc ON p.category_id = bc.category_id
    ";
    
    $where = "p.status = 'published'";
    $params = [];
    
    if ($category_slug) {
        $where .= " AND bc.slug = ?";
        $params[] = $category_slug;
    }
    
    // Get total posts
    // To properly count with the join, we need a slightly different query
    $count_query = "
        SELECT COUNT(p.post_id) as total 
        FROM blog_posts p 
        LEFT JOIN blog_categories bc ON p.category_id = bc.category_id 
        WHERE $where
    ";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_posts = $stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    // Get posts
    $query = $select_from . " WHERE $where ORDER BY p.published_at DESC, p.created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Get categories from the blog_categories table
    $stmt = $db->query("SELECT category_name, slug FROM blog_categories ORDER BY sort_order ASC, category_name ASC");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Basic error logging
    error_log("Blog page error: " . $e->getMessage());
    $posts = [];
    $categories = [];
    $total_pages = 0;
    $error_message = "Could not load posts: " . $e->getMessage();
}

// Debug: uncomment to see query results
// echo "<!-- Debug: total_posts=$total_posts, posts_count=" . count($posts) . " -->";

?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('blog_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<link rel="stylesheet" href="assets/css/blog.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    
    <!-- Hero Section -->
    <section class="page-header-blog">
        <div class="page-header-content">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">article</span>
                <?php _e('blog_page.news_articles'); ?>
            </span>
            <h1 class="page-title"><?php _e('blog_page.page_title'); ?></h1>
            <p class="page-subtitle"><?php _e('blog_page.page_subtitle'); ?></p>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('blog_page.book_now'); ?>
                </a>
                <a href="#blog-posts" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('blog_page.view_articles'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Blog Content -->
    <section id="blog-posts" class="py-16">
        <div class="mx-auto max-w-7xl px-4">
            
            <!-- Category Filter -->
            <?php if (!empty($categories)): ?>
            <div class="mb-8 flex flex-wrap gap-3 justify-center">
                <a href="blog.php" class="category-tag <?php echo empty($category_slug) ? 'active' : ''; ?>">
                    <?php _e('blog_page.all'); ?>
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="blog.php?category=<?php echo urlencode($cat['slug']); ?>"
                   class="category-tag <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Posts Grid -->
            <?php if (!empty($posts)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $post): 
                    // Fix image path - convert ../uploads/ to uploads/
                    $featured_img = $post['featured_image'] ?? '';
                    if ($featured_img && strpos($featured_img, '../uploads/') === 0) {
                        $featured_img = str_replace('../uploads/', 'uploads/', $featured_img);
                    }
                ?>
                <article class="blog-card">
                    <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>" class="block">
                        <?php if ($featured_img): ?>
                        <div class="blog-card-image" style="background-image: url('<?php echo htmlspecialchars($featured_img); ?>')"></div>
                        <?php else: ?>
                        <div class="blog-card-image" style="background-image: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg')"></div>
                        <?php endif; ?>
                        
                        <div class="blog-card-content">
                            <?php if (!empty($post['category_name'])): ?>
                            <span class="blog-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
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
                <a href="?page=<?php echo $page - 1; ?><?php echo $category_slug ? '&category=' . urlencode($category_slug) : ''; ?>"
                   class="pagination-btn">
                    <span class="material-symbols-outlined">chevron_left</span>
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="pagination-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $category_slug ? '&category=' . urlencode($category_slug) : ''; ?>"
                       class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $category_slug ? '&category=' . urlencode($category_slug) : ''; ?>"
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
                    <?php _e('blog_page.no_posts'); ?>
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
