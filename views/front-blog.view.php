<!DOCTYPE html>
<html translate="no" class="dark" lang="<?php echo getLang(); ?>">

<head>
    <?php
    require_once __DIR__ . '/../helpers/seo.php';
    require_once __DIR__ . '/../config/performance.php';
    echo SEO::generateMetaTags([
        'title' => __('blog_page.title'),
        'description' => __('blog_page.page_subtitle'),
    ]);
    ?>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />

    <!-- DNS Prefetch & Preconnect -->
    <?php echo preconnect('https://fonts.googleapis.com', true); ?>
    <?php echo preconnect('https://fonts.gstatic.com', true); ?>

    <!-- Scripts & Styles -->
    <link href="<?php echo assetVersion('assets/css/tailwind-output.css'); ?>" rel="stylesheet" />
    <link href="<?php echo assetVersion('assets/css/fonts.css'); ?>" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="<?php echo assetVersion('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('assets/css/liquid-glass.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('assets/css/blog-glass.css'); ?>">
    <style>
        .page-header-blog,
        .blog-content-wrapper {
            background-image: url('<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-4.jpg'); ?>') !important;
        }
    </style>
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
                            <a href="<?php echo prettyUrl('blog.php'); ?>" class="category-tag <?php echo empty($category_slug) ? 'active' : ''; ?>">
                                <?php _e('blog_page.all'); ?>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="<?php echo prettyUrl('blog.php'); ?>?category=<?php echo urlencode($cat['slug']); ?>"
                                    class="category-tag <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars(_f($cat, 'category_name')); ?>
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
                                    <a href="<?php echo prettyUrl('blog-detail.php', $post['slug']); ?>"
                                        class="blog-glass-card group block h-full">

                                        <div class="blog-card-image-wrapper">
                                            <div class="blog-card-image"
                                                style="background-image: url('<?php echo htmlspecialchars($featured_img); ?>')">
                                            </div>
                                            <?php if (!empty($post['category_name'])): ?>
                                                <span class="blog-category-badge">
                                                    <?php echo htmlspecialchars(_f($post, 'category_name')); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="blog-card-content">
                                            <div class="blog-meta">
                                                <div class="blog-meta-item">
                                                    <span class="material-symbols-outlined">calendar_month</span>
                                                    <span><?php echo date('m/d/Y', strtotime($post['published_at'])); ?></span>
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

                                            <h3 class="blog-title"><?php echo htmlspecialchars(_f($post, 'title')); ?></h3>

                                            <?php if ($post['excerpt']): ?>
                                                <p class="blog-excerpt"><?php echo htmlspecialchars(_f($post, 'excerpt')); ?></p>
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
