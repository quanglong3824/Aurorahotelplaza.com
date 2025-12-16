<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
require_once 'helpers/image-helper.php';
initLanguage();

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Category filter
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $db = getDB();

    // Core Query Parts
    $select = "SELECT p.*, u.full_name as author_name, bc.category_name, bc.slug as category_slug,
               (SELECT COUNT(*) FROM blog_comments WHERE post_id = p.post_id AND status = 'approved') as comment_count";
    $from = "FROM blog_posts p 
             LEFT JOIN users u ON p.author_id = u.user_id 
             LEFT JOIN blog_categories bc ON p.category_id = bc.category_id";
    $where = "WHERE p.status = 'published'";
    $params = [];

    if ($category_slug) {
        $where .= " AND bc.slug = ?";
        $params[] = $category_slug;
    }

    // Total Count for Pagination
    $stmt = $db->prepare("SELECT COUNT(p.post_id) as total $from $where");
    $stmt->execute($params);
    $total_posts = $stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);

    // Fetch Posts
    // Add secondary sort by ID to ensure stable order
    $order = "ORDER BY p.published_at DESC, p.post_id DESC";
    $limit = "LIMIT $per_page OFFSET $offset";

    $stmt = $db->prepare("$select $from $where $order $limit");
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Fetch Categories
    $stmt = $db->query("SELECT category_name, slug FROM blog_categories ORDER BY sort_order ASC, category_name ASC");
    $categories = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Blog page error: " . $e->getMessage());
    $posts = [];
    $categories = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html class="dark" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('blog_page.title'); ?></title>

    <!-- Scripts & Styles -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <script src="assets/js/tailwind-config.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/blog-glass.css?v=<?php echo time(); ?>">
</head>

<body class="bg-gray-900 font-body text-gray-100">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">

            <!-- Hero Section -->
            <section class="page-header-blog">
                <div class="page-header-content">
                    <span
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-white text-sm font-bold uppercase tracking-wider mb-6 backdrop-blur-md">
                        <span class="material-symbols-outlined text-accent text-base">article</span>
                        <?php _e('blog_page.news_articles'); ?>
                    </span>
                    <h1 class="page-title"><?php _e('blog_page.page_title'); ?></h1>
                    <p class="page-subtitle"><?php _e('blog_page.page_subtitle'); ?></p>
                </div>
            </section>

            <!-- Blog Main Content Wrapper (Dark Glass Theme) -->
            <div class="blog-content-wrapper">
                <div class="mx-auto max-w-7xl px-4">

                    <!-- Category Filters -->
                    <?php if (!empty($categories)): ?>
                        <div class="blog-categories">
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
                                $featured_img = imgUrl($post['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
                                ?>
                                <article class="h-full">
                                    <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>"
                                        class="blog-glass-card group block h-full">

                                        <div class="blog-card-image-wrapper">
                                            <div class="blog-card-image"
                                                style="background-image: url('<?php echo htmlspecialchars($featured_img); ?>')">
                                            </div>
                                            <?php if (!empty($post['category_name'])): ?>
                                                <span class="blog-category-badge">
                                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="blog-card-content">
                                            <div class="blog-meta">
                                                <div class="blog-meta-item">
                                                    <span class="material-symbols-outlined">calendar_month</span>
                                                    <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                                                </div>
                                                <div class="blog-meta-item">
                                                    <span class="material-symbols-outlined">person</span>
                                                    <span><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                                </div>
                                                <?php if ($post['comment_count'] > 0): ?>
                                                    <div class="blog-meta-item">
                                                        <span class="material-symbols-outlined">chat</span>
                                                        <span><?php echo $post['comment_count']; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <h3 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h3>

                                            <?php if ($post['excerpt']): ?>
                                                <p class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                            <?php endif; ?>

                                            <span class="blog-read-more">
                                                <?php _e('common.view_detail'); ?>
                                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                            </span>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container">
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
                        <!-- Empty State (Glass Theme) -->
                        <div class="glass-empty-state max-w-2xl mx-auto">
                            <span
                                class="material-symbols-outlined text-6xl text-white/30 mb-4 block mx-auto">auto_stories</span>
                            <h3 class="text-2xl font-bold mb-2 text-white">
                                <?php _e('blog_page.no_posts'); ?>
                            </h3>
                            <p class="text-white/60">
                                <?php echo __('blog_page.no_posts_desc') !== 'blog_page.no_posts_desc' ? __('blog_page.no_posts_desc') : 'Hiện chưa có bài viết nào trong danh mục này.'; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>