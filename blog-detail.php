<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$slug = $_GET['slug'] ?? '';
$success = '';
$error = '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

try {
    $db = getDB();
    
    // Get post
    $stmt = $db->prepare("
        SELECT p.*, u.full_name as author_name, u.avatar, bc.category_name, bc.slug as category_slug
        FROM blog_posts p
        LEFT JOIN users u ON p.author_id = u.user_id
        LEFT JOIN blog_categories bc ON p.category_id = bc.category_id
        WHERE p.slug = ? AND p.status = 'published'
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: blog.php');
        exit;
    }
    
    // Update views
    $stmt = $db->prepare("UPDATE blog_posts SET views = views + 1 WHERE post_id = ?");
    $stmt->execute([$post['post_id']]);
    
    // Get blog_comments
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as user_name, u.avatar
        FROM blog_comments c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = ? AND c.status = 'approved'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post['post_id']]);
    $blog_comments = $stmt->fetchAll();
    
    // Get related posts
    $stmt = $db->prepare("
        SELECT * FROM blog_posts
        WHERE status = 'published' AND post_id != ? AND category_id = ?
        ORDER BY published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$post['post_id'], $post['category_id']]);
    $related_posts = $stmt->fetchAll();
    
    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
        if (!isset($_SESSION['user_id'])) {
            $error = __('blog_page.login_required');
        } elseif (isset($post['allow_comments']) && (int)$post['allow_comments'] === 0) {
            $error = __('blog_page.comments_disabled');
        } else {
            $content = trim($_POST['content'] ?? '');
            
            if (empty($content)) {
                $error = __('blog_page.comment_empty');
            } else {
                $author_name = $_SESSION['user_name'] ?? 'User';
                $author_email = $_SESSION['user_email'] ?? '';
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);

                $stmt = $db->prepare("
                    INSERT INTO blog_comments (post_id, user_id, author_name, author_email, content, status, ip_address)
                    VALUES (?, ?, ?, ?, ?, 'pending', ?)
                ");
                $stmt->execute([$post['post_id'], $_SESSION['user_id'], $author_name, $author_email, $content, $ip_address]);
                $success = __('blog_page.comment_pending');
            }
        }
    }
    
} catch (Exception $e) {
    header('Location: blog.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo htmlspecialchars($post['title']); ?> - Aurora Hotel Plaza</title>
<meta name="description" content="<?php echo htmlspecialchars($post['excerpt'] ?? ''); ?>">
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/blog.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20">
    
    <!-- Article Header -->
    <article class="py-16">
        <div class="mx-auto max-w-4xl px-4">
            
            <!-- Breadcrumb -->
            <nav class="mb-8 flex items-center gap-2 text-sm">
                <a href="index.php" class="text-accent hover:underline"><?php _e('blog_page.home'); ?></a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <a href="blog.php" class="text-accent hover:underline"><?php _e('blog_page.posts'); ?></a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-text-secondary-light dark:text-text-secondary-dark">
                    <?php echo htmlspecialchars($post['title']); ?>
                </span>
            </nav>

            <!-- Category -->
            <?php if ($post['category_name']): ?>
            <span class="blog-category inline-block mb-4">
                <?php echo htmlspecialchars($post['category_name']); ?>
            </span>
            <?php endif; ?>

            <!-- Title -->
            <h1 class="font-display text-4xl md:text-5xl font-bold mb-6">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-6 mb-8 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                <div class="flex items-center gap-2">
                    <?php if (!empty($post['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($post['avatar']); ?>" 
                         alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                         class="w-10 h-10 rounded-full object-cover">
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-accent">person</span>
                    </div>
                    <?php endif; ?>
                    <span class="font-semibold"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                    <span><?php echo number_format($post['views']); ?> <?php _e('blog_page.views'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">comment</span>
                    <span><?php echo count($blog_comments); ?> <?php _e('blog_page.comments'); ?></span>
                </div>
            </div>

            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
            <div class="mb-8 rounded-xl overflow-hidden">
                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                     class="w-full h-auto">
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="prose prose-lg dark:prose-invert max-w-none mb-12">
                <?php echo $post['content']; ?>
            </div>

            <!-- Tags -->
            <?php if ($post['tags']): ?>
            <div class="flex flex-wrap gap-2 mb-8">
                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                <span class="px-3 py-1 bg-primary-light/20 dark:bg-gray-700 rounded-full text-sm">
                    #<?php echo htmlspecialchars(trim($tag)); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Share -->
            <div class="border-t border-b border-gray-200 dark:border-gray-700 py-6 mb-12">
                <div class="flex items-center justify-between">
                    <span class="font-semibold"><?php _e('blog_page.share_post'); ?></span>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="share-btn">
                            <span class="material-symbols-outlined">share</span>
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($post['title']); ?>" 
                           target="_blank" class="share-btn">
                            <span class="material-symbols-outlined">share</span>
                            Twitter
                        </a>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="mb-12">
                <h3 class="text-2xl font-bold mb-6">
                    <?php _e('blog_page.comments_title'); ?> (<?php echo count($blog_comments); ?>)
                </h3>

                <!-- Comment Form -->
                <?php if (isset($_SESSION['user_id']) && (!isset($post['allow_comments']) || (int)$post['allow_comments'] === 1)): ?>
                <div class="mb-8 p-6 bg-surface-light dark:bg-surface-dark rounded-xl">
                    <?php if ($success): ?>
                    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <textarea name="content" rows="4" 
                                  class="w-full p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 resize-none"
                                  placeholder="<?php _e('blog_page.write_comment'); ?>" required></textarea>
                        <button type="submit" name="submit_comment" class="mt-4 btn-primary">
                            <?php _e('blog_page.submit_comment'); ?>
                        </button>
                    </form>
                </div>
                <?php elseif (isset($_SESSION['user_id']) && isset($post['allow_comments']) && (int)$post['allow_comments'] === 0): ?>
                <div class="mb-8 p-6 bg-surface-light dark:bg-surface-dark rounded-xl text-center">
                    <p class="mb-0"><?php _e('blog_page.comments_disabled'); ?></p>
                </div>
                <?php else: ?>
                <div class="mb-8 p-6 bg-surface-light dark:bg-surface-dark rounded-xl text-center">
                    <p class="mb-4"><?php _e('blog_page.login_to_comment'); ?></p>
                    <a href="auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                       class="btn-primary inline-block">
                        <?php _e('blog_page.login'); ?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Comments List -->
                <?php if (!empty($blog_comments)): ?>
                <div class="space-y-6">
                    <?php foreach ($blog_comments as $comment): ?>
                    <div class="comment-item">
                        <div class="flex gap-4">
                            <?php if (!empty($comment['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($comment['user_name']); ?>"
                                 class="w-12 h-12 rounded-full object-cover">
                            <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-accent">person</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="font-semibold"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                    <span class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-center text-text-secondary-light dark:text-text-secondary-dark py-8">
                    <?php _e('blog_page.no_comments'); ?>
                </p>
                <?php endif; ?>
            </div>

        </div>
    </article>

    <!-- Related Posts -->
    <?php if (!empty($related_posts)): ?>
    <section class="py-16 bg-primary-light/30 dark:bg-surface-dark">
        <div class="mx-auto max-w-7xl px-4">
            <h2 class="text-3xl font-bold mb-8 text-center"><?php _e('blog_page.related_posts'); ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($related_posts as $related): ?>
                <article class="blog-card">
                    <a href="blog-detail.php?slug=<?php echo urlencode($related['slug']); ?>">
                        <div class="blog-card-image" 
                             style="background-image: url('<?php echo htmlspecialchars($related['featured_image'] ?? 'assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg'); ?>')">
                        </div>
                        <div class="blog-card-content">
                            <h3 class="blog-card-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                            <p class="blog-card-excerpt"><?php echo htmlspecialchars($related['excerpt'] ?? ''); ?></p>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
