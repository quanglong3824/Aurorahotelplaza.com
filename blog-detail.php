<?php
session_start();
require_once 'config/environment.php';
require_once 'config/database.php';
require_once 'config/performance.php';
require_once 'helpers/language.php';
require_once 'helpers/image-helper.php';
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

    $stmt = $db->prepare("
        SELECT p.*, u.full_name as author_name, u.avatar, bc.category_name, bc.category_name_en, bc.slug as category_slug
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

    $stmt = $db->prepare("UPDATE blog_posts SET views = views + 1 WHERE post_id = ?");
    $stmt->execute([$post['post_id']]);

    $stmt = $db->prepare("
        SELECT c.*, u.full_name as user_name, u.avatar
        FROM blog_comments c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = ? AND c.status = 'approved'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post['post_id']]);
    $blog_comments = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT * FROM blog_posts
        WHERE status = 'published' AND post_id != ? AND category_id = ?
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([$post['post_id'], $post['category_id']]);
    $related_posts = $stmt->fetchAll();

    // Fetch Recent Posts (Latest 5, excluding current)
    $stmt = $db->prepare("
        SELECT post_id, title, title_en, slug, featured_image, published_at
        FROM blog_posts
        WHERE status = 'published' AND post_id != ?
        ORDER BY RAND()
        LIMIT 5
    ");
    $stmt->execute([$post['post_id']]);
    $recent_posts = $stmt->fetchAll();

    // Fetch Featured Rooms for suggestion
    $stmt = $db->prepare("
        SELECT room_type_id, type_name, type_name_en, slug, base_price, thumbnail, category
        FROM room_types
        WHERE status = 'active' AND category = 'room'
        ORDER BY RAND()
        LIMIT 2
    ");
    $stmt->execute();
    $suggested_rooms = $stmt->fetchAll();

    // Fetch Featured Apartments for suggestion
    $stmt = $db->prepare("
        SELECT room_type_id, type_name, type_name_en, slug, base_price, thumbnail, category
        FROM room_types
        WHERE status = 'active' AND category = 'apartment'
        ORDER BY RAND()
        LIMIT 2
    ");
    $stmt->execute();
    $suggested_apartments = $stmt->fetchAll();

    if (!empty($post['featured_image']) && strpos($post['featured_image'], '../uploads/') === 0) {
        $post['featured_image'] = str_replace('../uploads/', 'uploads/', $post['featured_image']);
    }
    if (!empty($post['gallery_images'])) {
        $gallery = json_decode($post['gallery_images'], true);
        if (is_array($gallery)) {
            $post['gallery_images'] = json_encode(array_map(function ($img) {
                return strpos($img, '../uploads/') === 0 ? str_replace('../uploads/', 'uploads/', $img) : $img;
            }, $gallery));
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
        if (!isset($_SESSION['user_id'])) {
            $error = __('blog_page.login_required');
        } elseif (isset($post['allow_comments']) && (int) $post['allow_comments'] === 0) {
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

$layout = $post['layout'] ?? 'standard';
$gallery_images = !empty($post['gallery_images']) ? json_decode($post['gallery_images'], true) : [];
$video_url = $post['video_url'] ?? '';
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=5.0" name="viewport" />
    <title><?php echo htmlspecialchars(_f($post, 'title')); ?> - Aurora Hotel Plaza</title>
    <meta name="description" content="<?php echo htmlspecialchars(_f($post, 'excerpt') ?? ''); ?>">
    <link href="<?php echo assetVersion('css/tailwind-output.css'); ?>" rel="stylesheet" />
    <link href="<?php echo assetVersion('css/fonts.css'); ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/pages-glass.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/blog.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/blog-detail.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/blog-detail-glass.css'); ?>">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const API_URL = '<?php echo API_URL; ?>';
    </script>
</head>

<body class="blog-detail-page glass-page font-body text-white">
    <?php include 'includes/header.php'; ?>

    <main class="relative z-10 pt-24 pb-12 blog-detail-wrapper">
        <div class="mx-auto max-w-7xl px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                <!-- Left Column (Article + Comments) -->
                <div class="lg:col-span-2">
                    <nav class="blog-breadcrumb mb-6 flex items-center gap-2 text-sm">
                <a href="<?php echo route(''); ?>" class="text-[#d4af37] hover:underline"><?php _e('blog_page.home'); ?></a>
                <span class="material-symbols-outlined text-xs text-white/40">chevron_right</span>
                <a href="<?php echo route('tin-tuc'); ?>" class="text-[#d4af37] hover:underline"><?php _e('blog_page.posts'); ?></a>
                <span class="material-symbols-outlined text-xs text-white/40">chevron_right</span>
                <span class="text-white/60 truncate max-w-xs"><?php echo htmlspecialchars(_f($post, 'title')); ?></span>
            </nav>

            <article class="blog-article-card glass-card-solid p-6 md:p-8">
                <?php if ($post['category_name']): ?>
                    <span class="blog-category-badge"><?php echo htmlspecialchars(_f($post, 'category_name')); ?></span>
                <?php endif; ?>

                <h1 class="blog-detail-title mt-4"><?php echo htmlspecialchars(_f($post, 'title')); ?></h1>

                <div class="blog-meta-info flex-wrap">
                    <div class="blog-meta-item">
                        <?php if (!empty($post['avatar'])): ?>
                            <img src="<?php echo imgUrl($post['avatar']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="blog-author-avatar">
                        <?php else: ?>
                            <div class="blog-author-avatar-placeholder">
                                <span class="material-symbols-outlined text-accent text-lg">person</span>
                            </div>
                        <?php endif; ?>
                        <span class="font-semibold text-white"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                    </div>
                    <div class="blog-meta-item">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                    </div>
                    <div class="blog-meta-item">
                        <span class="material-symbols-outlined">visibility</span>
                        <span><?php echo number_format($post['views']); ?> <?php _e('blog_page.views'); ?></span>
                    </div>
                    <div class="blog-meta-item">
                        <span class="material-symbols-outlined">comment</span>
                        <span><?php echo count($blog_comments); ?> <?php _e('blog_page.comments'); ?></span>
                    </div>
                </div>

                <?php if ($layout === 'gallery' && !empty($gallery_images)): ?>
                    <?php if ($post['featured_image']): ?>
                        <div class="blog-featured-image">
                            <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                 onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';" 
                                 alt="<?php echo htmlspecialchars(_f($post, 'title')); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="mb-8">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <?php foreach ($gallery_images as $index => $img): ?>
                                <div class="aspect-square rounded-lg overflow-hidden group cursor-pointer" onclick="openLightbox(<?php echo $index + 1; ?>)">
                                    <img src="<?php echo imgUrl($img); ?>" 
                                         onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                         alt="Gallery image <?php echo $index + 1; ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($layout === 'slider' && !empty($gallery_images)): ?>
                    <div class="mb-8 relative" id="imageSlider">
                        <div class="overflow-hidden rounded-xl">
                            <div class="slider-track flex transition-transform duration-500" id="sliderTrack">
                                <?php if ($post['featured_image']): ?>
                                    <div class="slider-slide min-w-full">
                                        <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                             onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                                             class="w-full h-[400px] md:h-[500px] object-cover">
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($gallery_images as $index => $img): ?>
                                    <div class="slider-slide min-w-full">
                                        <img src="<?php echo imgUrl($img); ?>" 
                                             onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                             alt="Slide <?php echo $index + 1; ?>"
                                             class="w-full h-[400px] md:h-[500px] object-cover">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="slider-btn slider-prev absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white opacity-0 hover:opacity-100 transition-opacity" onclick="slideImage(-1)">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                        <button class="slider-btn slider-next absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white opacity-0 hover:opacity-100 transition-opacity" onclick="slideImage(1)">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                        <div class="flex justify-center gap-2 mt-4" id="sliderDots">
                            <?php
                            $totalSlides = ($post['featured_image'] ? 1 : 0) + count($gallery_images);
                            for ($i = 0; $i < $totalSlides; $i++):
                            ?>
                                <button class="w-3 h-3 rounded-full <?php echo $i === 0 ? 'bg-[#d4af37]' : 'bg-white/30 hover:bg-[#d4af37]'; ?> transition-colors" onclick="goToSlide(<?php echo $i; ?>)"></button>
                            <?php endfor; ?>
                        </div>
                    </div>

                <?php elseif ($layout === 'apartment' && $post['featured_image']): ?>
                    <div class="mb-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-2 rounded-xl overflow-hidden cursor-pointer" onclick="openLightbox(0)">
                                <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                     onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="w-full h-[300px] md:h-[400px] object-cover hover:scale-105 transition-transform duration-500">
                            </div>
                            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                                <?php
                                $sideImages = array_slice($gallery_images, 0, 2);
                                foreach ($sideImages as $index => $img):
                                ?>
                                    <div class="rounded-xl overflow-hidden cursor-pointer" onclick="openLightbox(<?php echo $index + 1; ?>)">
                                        <img src="<?php echo imgUrl($img); ?>" 
                                             onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                             alt="Gallery <?php echo $index + 1; ?>"
                                             class="w-full h-[140px] md:h-[190px] object-cover hover:scale-105 transition-transform duration-500">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($layout === 'video' && !empty($video_url)): ?>
                    <div class="mb-8">
                        <div class="aspect-video rounded-xl overflow-hidden bg-black">
                            <?php
                            $embed_url = '';
                            if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $video_url, $matches)) {
                                $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
                            } elseif (preg_match('/youtu\.be\/([^?]+)/', $video_url, $matches)) {
                                $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
                            } elseif (preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches)) {
                                $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
                            }
                            ?>
                            <?php if ($embed_url): ?>
                                <iframe src="<?php echo htmlspecialchars($embed_url); ?>" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-white">
                                    <p>Video không hợp lệ</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($post['featured_image']): ?>
                            <div class="mt-4 rounded-lg overflow-hidden">
                                <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                     onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($layout === 'hero' && $post['featured_image']): ?>
                    <div class="relative -mx-6 -mt-6 mb-8 rounded-t-xl overflow-hidden">
                        <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                             onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                             class="w-full h-[300px] md:h-[400px] object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                    </div>

                <?php elseif ($layout === 'magazine' && $post['featured_image']): ?>
                    <div class="mb-8 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                        <div class="rounded-xl overflow-hidden">
                            <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                 onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 class="w-full h-[350px] object-cover hover:scale-105 transition-transform duration-500 cursor-pointer"
                                 onclick="openLightbox(0)">
                        </div>
                        <div class="space-y-4">
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="text-xl leading-relaxed text-white/90 italic border-l-4 border-[#d4af37] pl-4">
                                    <?php echo htmlspecialchars(_f($post, 'excerpt')); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($gallery_images)): ?>
                                <div class="grid grid-cols-3 gap-2 mt-4">
                                    <?php foreach (array_slice($gallery_images, 0, 3) as $index => $img): ?>
                                        <div class="aspect-square rounded-lg overflow-hidden cursor-pointer" onclick="openLightbox(<?php echo $index + 1; ?>)">
                                            <img src="<?php echo imgUrl($img); ?>" 
                                                 onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                                 alt="Gallery <?php echo $index + 1; ?>"
                                                 class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <?php if ($post['featured_image']): ?>
                        <div class="blog-featured-image">
                            <img src="<?php echo imgUrl($post['featured_image']); ?>" 
                                 onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>';"
                                 alt="<?php echo htmlspecialchars(_f($post, 'title')); ?>">
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="prose blog-detail-prose max-w-none mb-8">
                    <?php echo _f($post, 'content'); ?>
                </div>

                <?php if ($post['tags']): ?>
                    <div class="blog-tags-container mb-6">
                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                            <span class="blog-tag">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="blog-interaction-section" id="interactionSection" data-post-id="<?php echo $post['post_id']; ?>">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-white/60"><?php _e('blog_page.rate_post'); ?></span>
                            <div class="flex items-center gap-3">
                                <div class="blog-star-rating" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="blog-star-btn" data-rating="<?php echo $i; ?>">
                                            <span class="material-symbols-outlined">star</span>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-sm text-white/60">
                                    <span id="ratingAvg"><?php echo number_format($post['rating_avg'] ?? 0, 1); ?></span>/5
                                    (<span id="ratingCount"><?php echo (int) ($post['rating_count'] ?? 0); ?></span> <?php _e('blog_page.ratings'); ?>)
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="button" id="likeBtn" class="blog-like-btn">
                                <span class="material-symbols-outlined like-icon">favorite</span>
                                <span id="likesCount"><?php echo (int) ($post['likes_count'] ?? 0); ?></span>
                            </button>

                            <div class="flex items-center gap-2">
                                <span class="text-sm text-white/60 mr-2"><?php _e('blog_page.share_post'); ?></span>
                                <button type="button" class="blog-share-btn facebook" data-platform="facebook" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                    </svg>
                                </button>
                                <button type="button" class="blog-share-btn twitter" data-platform="twitter" title="Twitter/X">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.727 8.79 7.292 11.71h-6.09l-4.64-6.17-5.277 6.17H2.25l8.09-9.39L2.25 4.5h6.19l4.24 5.56 5.764-5.56zm-1.08 16.5h1.72L8.7 7.21H6.92l10.244 11.54z"/>
                                    </svg>
                                </button>
                                <button type="button" class="blog-share-btn copy" data-platform="copy" title="Copy Link">
                                    <span class="material-symbols-outlined text-lg">link</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Comments Section (Now directly under article in Left Column) -->
            <section class="blog-comments-section mt-12 pt-8 border-t border-white/10">
                <h3 class="blog-comments-title"><?php _e('blog_page.comments_title'); ?> (<?php echo count($blog_comments); ?>)</h3>

                <?php if (isset($_SESSION['user_id']) && (!isset($post['allow_comments']) || (int) $post['allow_comments'] === 1)): ?>
                    <div class="blog-comment-form">
                        <?php if ($success): ?>
                            <div class="mb-4 p-4 bg-green-900/30 text-green-300 rounded-lg border border-green-500/30"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="mb-4 p-4 bg-red-900/30 text-red-300 rounded-lg border border-red-500/30"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <textarea name="content" rows="4" class="blog-comment-input" placeholder="<?php _e('blog_page.write_comment'); ?>" required></textarea>
                            <button type="submit" name="submit_comment" class="blog-btn-primary mt-4"><?php _e('blog_page.submit_comment'); ?></button>
                        </form>
                    </div>
                <?php elseif (isset($_SESSION['user_id']) && isset($post['allow_comments']) && (int) $post['allow_comments'] === 0): ?>
                    <div class="blog-login-prompt"><p class="text-white/70"><?php _e('blog_page.comments_disabled'); ?></p></div>
                <?php else: ?>
                    <div class="blog-login-prompt">
                        <p class="text-white/70 mb-4"><?php _e('blog_page.login_to_comment'); ?></p>
                        <a href="<?php echo route('dang-nhap', ['redirect' => $_SERVER['REQUEST_URI']]); ?>" class="blog-btn-primary"><?php _e('blog_page.login'); ?></a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($blog_comments)): ?>
                    <div class="space-y-4 mt-6">
                        <?php foreach ($blog_comments as $comment): ?>
                            <div class="blog-comment-item">
                                <div class="flex gap-4">
                                    <?php if (!empty($comment['avatar'])): ?>
                                        <img src="<?php echo imgUrl($comment['avatar']); ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>" class="blog-comment-avatar">
                                    <?php else: ?>
                                        <div class="blog-author-avatar-placeholder">
                                            <span class="material-symbols-outlined text-accent text-lg">person</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="blog-comment-author"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                            <span class="blog-comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                        <p class="blog-comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-white/50 py-8 mt-6"><?php _e('blog_page.no_comments'); ?></p>
                <?php endif; ?>
            </section>
        </div>
        <!-- End Left Column -->

        <!-- Right Column (Sidebar) -->
        <div class="space-y-8">
            <!-- Suggested Accommodations (Room/Apartment View) -->
            <?php if (!empty($suggested_rooms) || !empty($suggested_apartments)): ?>
                <section class="blog-suggestions-section bg-white/5 rounded-2xl p-6 border border-white/10 backdrop-blur-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-accent">hotel</span>
                        <h3 class="text-xl font-bold text-white"><?php _e('blog_page.suggested_rooms'); ?></h3>
                    </div>
                    
                    <div class="flex flex-col gap-6">
                        <?php foreach (array_merge($suggested_rooms, $suggested_apartments) as $sug): ?>
                            <div class="suggestion-card-glass group">
                                <div class="suggestion-img">
                                    <img src="<?php echo imgUrl($sug['thumbnail'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" alt="<?php echo htmlspecialchars(_f($sug, 'type_name')); ?>">
                                    <div class="suggestion-price">
                                        <?php echo number_format($sug['base_price'], 0, ',', '.'); ?> VND
                                    </div>
                                </div>
                                <div class="suggestion-content">
                                    <h4 class="suggestion-name"><?php echo htmlspecialchars(_f($sug, 'type_name')); ?></h4>
                                    <div class="flex gap-2 mt-3">
                                        <a href="<?php echo route('dat-phong', ['room_type' => $sug['slug']]); ?>" class="suggestion-btn-book">
                                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                                            <?php _e('home.book_now'); ?>
                                        </a>
                                        <a href="<?php echo route($sug['category'] === 'apartment' ? 'chi-tiet-can-ho' : 'chi-tiet-phong', ['slug' => $sug['slug']]); ?>" class="suggestion-btn-view">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Recent Posts Sidebar/List -->
            <?php if (!empty($recent_posts)): ?>
                <section class="blog-recent-list bg-white/5 rounded-2xl p-6 border border-white/10 backdrop-blur-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-accent">schedule</span>
                        <h3 class="text-xl font-bold text-white"><?php _e('blog_page.recent_posts'); ?></h3>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($recent_posts as $recent): ?>
                            <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $recent['slug']]); ?>" class="flex gap-4 group">
                                <div class="w-20 h-20 shrink-0 rounded-lg overflow-hidden border border-white/10 relative">
                                    <img src="<?php echo imgUrl($recent['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" alt="<?php echo htmlspecialchars(_f($recent, 'title')); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                </div>
                                <div class="flex-1 py-1">
                                    <h4 class="text-sm font-semibold text-white/90 group-hover:text-accent line-clamp-2 mb-1 transition-colors"><?php echo htmlspecialchars(_f($recent, 'title')); ?></h4>
                                    <span class="text-xs text-white/40 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                        <?php echo date('d/m/Y', strtotime($recent['published_at'])); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <!-- End Right Column -->
        </div>
        <!-- End Grid -->

        <?php if (!empty($related_posts)): ?>
            <section class="blog-related-section mt-12">
                <div class="mx-auto max-w-7xl px-4">
                    <h2 class="blog-related-title"><?php _e('blog_page.related_posts'); ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($related_posts as $related): ?>
                            <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $related['slug']]); ?>" class="blog-related-card">
                                <div class="blog-related-image" style="background-image: url('<?php echo imgUrl($related['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>')"></div>
                                <div class="blog-related-content">
                                    <h3 class="blog-related-title-text"><?php echo htmlspecialchars(_f($related, 'title')); ?></h3>
                                    <p class="blog-related-excerpt"><?php echo htmlspecialchars(_f($related, 'excerpt') ?? ''); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="<?php echo assetVersion('js/main.js'); ?>"></script>
    <script src="<?php echo assetVersion('js/blog-detail.js'); ?>"></script>

    <div class="lightbox-overlay" id="lightbox">
        <div class="lightbox-content">
            <span class="lightbox-close material-symbols-outlined" onclick="closeLightbox()">close</span>
            <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <img src="" alt="Lightbox image" id="lightboxImg">
            <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
            <div class="lightbox-counter"><span id="lightboxCurrent">1</span> / <span id="lightboxTotal">1</span></div>
        </div>
    </div>
</body>
</html>