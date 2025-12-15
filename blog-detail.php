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
    
    // Fix image path - convert ../uploads/ to uploads/
    if (!empty($post['featured_image']) && strpos($post['featured_image'], '../uploads/') === 0) {
        $post['featured_image'] = str_replace('../uploads/', 'uploads/', $post['featured_image']);
    }
    if (!empty($post['gallery_images'])) {
        $gallery = json_decode($post['gallery_images'], true);
        if (is_array($gallery)) {
            $post['gallery_images'] = json_encode(array_map(function($img) {
                return strpos($img, '../uploads/') === 0 ? str_replace('../uploads/', 'uploads/', $img) : $img;
            }, $gallery));
        }
    }
    
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

            <!-- Rating, Like, Share Section -->
            <div class="border-t border-b border-gray-200 dark:border-gray-700 py-6 mb-12" id="interactionSection" data-post-id="<?php echo $post['post_id']; ?>">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    
                    <!-- Star Rating -->
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400"><?php _e('blog_page.rate_post'); ?></span>
                        <div class="flex items-center gap-3">
                            <div class="star-rating flex gap-1" id="starRating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="<?php echo $i; ?>">
                                    <span class="material-symbols-outlined">star</span>
                                </button>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-500">
                                <span id="ratingAvg"><?php echo number_format($post['rating_avg'] ?? 0, 1); ?></span>/5
                                (<span id="ratingCount"><?php echo (int)($post['rating_count'] ?? 0); ?></span> <?php _e('blog_page.ratings'); ?>)
                            </span>
                        </div>
                    </div>
                    
                    <!-- Like Button -->
                    <div class="flex items-center gap-4">
                        <button type="button" id="likeBtn" class="flex items-center gap-2 px-4 py-2 rounded-full border-2 border-red-400 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                            <span class="material-symbols-outlined like-icon">favorite</span>
                            <span id="likesCount"><?php echo (int)($post['likes_count'] ?? 0); ?></span>
                        </button>
                        
                        <!-- Share Buttons -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 mr-2"><?php _e('blog_page.share_post'); ?></span>
                            <button type="button" class="share-btn-icon facebook" data-platform="facebook" title="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </button>
                            <button type="button" class="share-btn-icon twitter" data-platform="twitter" title="Twitter/X">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </button>
                            <button type="button" class="share-btn-icon linkedin" data-platform="linkedin" title="LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </button>
                            <button type="button" class="share-btn-icon copy-link" data-platform="copy_link" title="Copy Link">
                                <span class="material-symbols-outlined text-xl">link</span>
                            </button>
                        </div>
                        <span class="text-sm text-gray-500">
                            <span id="sharesCount"><?php echo (int)($post['shares_count'] ?? 0); ?></span> <?php _e('blog_page.shares'); ?>
                        </span>
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

<!-- Blog Interaction Script -->
<style>
.share-btn-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    color: white;
}
.share-btn-icon.facebook { background: #1877f2; }
.share-btn-icon.facebook:hover { background: #0d65d9; }
.share-btn-icon.twitter { background: #000; }
.share-btn-icon.twitter:hover { background: #333; }
.share-btn-icon.linkedin { background: #0a66c2; }
.share-btn-icon.linkedin:hover { background: #004182; }
.share-btn-icon.copy-link { background: #6b7280; }
.share-btn-icon.copy-link:hover { background: #4b5563; }

.star-btn.active span,
.star-btn.hover span { color: #facc15; }
.star-btn span { transition: color 0.15s; }

#likeBtn.liked {
    background: #fef2f2;
    border-color: #ef4444;
}
#likeBtn.liked .like-icon {
    font-variation-settings: 'FILL' 1;
    color: #ef4444;
}
.dark #likeBtn.liked {
    background: rgba(239, 68, 68, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('interactionSection');
    if (!section) return;
    
    const postId = section.dataset.postId;
    const starRating = document.getElementById('starRating');
    const likeBtn = document.getElementById('likeBtn');
    const likesCount = document.getElementById('likesCount');
    const ratingAvg = document.getElementById('ratingAvg');
    const ratingCount = document.getElementById('ratingCount');
    const sharesCount = document.getElementById('sharesCount');
    const starBtns = starRating.querySelectorAll('.star-btn');
    
    // Load initial status
    fetch(`api/blog-interaction.php?action=get_status&post_id=${postId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.is_liked) likeBtn.classList.add('liked');
                if (data.user_rating > 0) highlightStars(data.user_rating);
                likesCount.textContent = data.likes_count;
                ratingAvg.textContent = data.rating_avg.toFixed(1);
                ratingCount.textContent = data.rating_count;
                sharesCount.textContent = data.shares_count;
            }
        }).catch(() => {});
    
    // Star rating hover effect
    starBtns.forEach((btn, index) => {
        btn.addEventListener('mouseenter', () => {
            starBtns.forEach((b, i) => {
                b.classList.toggle('hover', i <= index);
            });
        });
        btn.addEventListener('mouseleave', () => {
            starBtns.forEach(b => b.classList.remove('hover'));
        });
        btn.addEventListener('click', () => {
            const rating = parseInt(btn.dataset.rating);
            submitRating(rating);
        });
    });
    
    function highlightStars(rating) {
        starBtns.forEach((btn, i) => {
            btn.classList.toggle('active', i < rating);
        });
    }
    
    function submitRating(rating) {
        const formData = new FormData();
        formData.append('action', 'rate');
        formData.append('post_id', postId);
        formData.append('rating', rating);
        
        fetch('api/blog-interaction.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    highlightStars(data.user_rating);
                    ratingAvg.textContent = data.rating_avg.toFixed(1);
                    ratingCount.textContent = data.rating_count;
                }
            });
    }
    
    // Like button
    likeBtn.addEventListener('click', function() {
        const isLiked = this.classList.contains('liked');
        const action = isLiked ? 'unlike' : 'like';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('post_id', postId);
        
        fetch('api/blog-interaction.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    likeBtn.classList.toggle('liked', data.liked);
                    likesCount.textContent = data.likes_count;
                }
            });
    });
    
    // Share buttons
    const shareUrl = encodeURIComponent(window.location.href);
    const shareTitle = encodeURIComponent(document.title);
    
    document.querySelectorAll('.share-btn-icon').forEach(btn => {
        btn.addEventListener('click', function() {
            const platform = this.dataset.platform;
            let url = '';
            
            switch(platform) {
                case 'facebook':
                    url = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
                    break;
                case 'twitter':
                    url = `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`;
                    break;
                case 'linkedin':
                    url = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;
                    break;
                case 'copy_link':
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        alert('Link đã được sao chép!');
                    });
                    break;
            }
            
            if (url) {
                window.open(url, '_blank', 'width=600,height=400');
            }
            
            // Track share
            const formData = new FormData();
            formData.append('action', 'share');
            formData.append('post_id', postId);
            formData.append('platform', platform);
            
            fetch('api/blog-interaction.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        sharesCount.textContent = data.shares_count;
                    }
                });
        });
    });
});
</script>
</body>
</html>
