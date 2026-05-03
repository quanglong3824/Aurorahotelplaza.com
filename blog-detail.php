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
$bgImage = !empty($post['featured_image']) ? imgUrl($post['featured_image']) : assetVersion('img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
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
    <link rel="stylesheet" href="<?php echo assetVersion('css/blog-detail-glass.css'); ?>">
    <!-- Google Fonts for Premium Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const API_URL = '<?php echo API_URL; ?>';
    </script>
</head>

<body class="blog-detail-page glass-page font-body text-white">
    <?php include 'includes/header.php'; ?>

    <style>
        /* CHỐNG CACHE TRÌNH DUYỆT & OVERRIDE BG */
        body.blog-detail-page {
            overflow-x: hidden !important;
        }
        body.blog-detail-page::before {
            background-image: url('<?php echo $bgImage; ?>') !important;
            filter: brightness(0.3) blur(15px) !important;
            transform: scale(1.1) !important;
        }
    </style>
    
    <!-- Hero Header for Blog Post -->
    <header class="blog-hero-header relative w-full h-[60vh] min-h-[450px] max-h-[700px] flex items-end justify-center pb-16 pt-32 overflow-hidden mt-0">
        <div class="absolute inset-0 z-0">
            <img src="<?php echo $bgImage; ?>" alt="Background" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-[#0f172a]/80 to-transparent"></div>
        </div>
        
        <div class="relative z-10 w-full max-w-5xl mx-auto px-4 text-center">
            <div class="animate-fade-up" style="animation-delay: 0.1s;">
                <?php if ($post['category_name']): ?>
                    <span class="blog-hero-category"><?php echo htmlspecialchars(_f($post, 'category_name')); ?></span>
                <?php endif; ?>
            </div>
            
            <h1 class="blog-hero-title animate-fade-up mt-6 mb-8 mx-auto" style="animation-delay: 0.2s;">
                <?php echo htmlspecialchars(_f($post, 'title')); ?>
            </h1>
            
            <div class="blog-hero-meta flex flex-wrap items-center justify-center gap-6 animate-fade-up" style="animation-delay: 0.3s;">
                <div class="flex items-center gap-3">
                    <?php if (!empty($post['avatar'])): ?>
                        <img src="<?php echo imgUrl($post['avatar']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="w-10 h-10 rounded-full border-2 border-[#d4af37]/50 object-cover shadow-lg">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-[#d4af37]/20 flex items-center justify-center border-2 border-[#d4af37]/50 shadow-lg">
                            <span class="material-symbols-outlined text-[#d4af37] text-xl">person</span>
                        </div>
                    <?php endif; ?>
                    <span class="font-medium text-white/90 text-sm tracking-wide"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                </div>
                
                <div class="w-1 h-1 rounded-full bg-white/30 hidden sm:block"></div>
                
                <div class="flex items-center gap-2 text-white/70 text-sm">
                    <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                    <span><?php echo date('d M, Y', strtotime($post['published_at'])); ?></span>
                </div>
                
                <div class="w-1 h-1 rounded-full bg-white/30 hidden sm:block"></div>
                
                <div class="flex items-center gap-2 text-white/70 text-sm">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                    <span><?php echo number_format($post['views']); ?> <?php _e('blog_page.views'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="relative z-20 blog-detail-wrapper -mt-8 pb-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Left Column (Article + Comments) -->
                <div class="lg:col-span-8 space-y-10">
                    <nav class="blog-breadcrumb-modern flex flex-wrap items-center gap-2 text-sm px-2 animate-fade-in" style="animation-delay: 0.4s;">
                        <a href="<?php echo route(''); ?>" class="hover:text-[#d4af37] transition-colors"><span class="material-symbols-outlined text-[18px] translate-y-[2px]">home</span></a>
                        <span class="material-symbols-outlined text-[16px] text-white/30">chevron_right</span>
                        <a href="<?php echo route('tin-tuc'); ?>" class="hover:text-[#d4af37] transition-colors"><?php _e('blog_page.posts'); ?></a>
                        <span class="material-symbols-outlined text-[16px] text-white/30">chevron_right</span>
                        <span class="text-[#d4af37] font-medium truncate max-w-[200px] sm:max-w-xs"><?php echo htmlspecialchars(_f($post, 'title')); ?></span>
                    </nav>

                    <article class="blog-article-premium glass-card-premium p-6 md:p-12 animate-fade-up" style="animation-delay: 0.5s;">
                        
                        <!-- Dynamic Layout Media -->
                        <?php if ($layout === 'gallery' && !empty($gallery_images)): ?>
                            <div class="mb-10">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <?php foreach ($gallery_images as $index => $img): ?>
                                        <div class="aspect-square rounded-xl overflow-hidden group cursor-pointer shadow-lg" onclick="openLightbox(<?php echo $index; ?>)">
                                            <img src="<?php echo imgUrl($img); ?>" 
                                                 alt="Gallery image <?php echo $index + 1; ?>"
                                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php elseif ($layout === 'slider' && !empty($gallery_images)): ?>
                            <div class="mb-10 relative group" id="imageSlider">
                                <div class="overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10">
                                    <div class="slider-track flex transition-transform duration-700 ease-in-out" id="sliderTrack">
                                        <?php if ($post['featured_image']): ?>
                                            <div class="slider-slide min-w-full">
                                                <img src="<?php echo imgUrl($post['featured_image']); ?>" class="w-full h-[300px] sm:h-[400px] md:h-[500px] object-cover">
                                            </div>
                                        <?php endif; ?>
                                        <?php foreach ($gallery_images as $index => $img): ?>
                                            <div class="slider-slide min-w-full">
                                                <img src="<?php echo imgUrl($img); ?>" class="w-full h-[300px] sm:h-[400px] md:h-[500px] object-cover">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="slider-btn slider-prev absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/40 hover:bg-[#d4af37] backdrop-blur-md rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300" onclick="slideImage(-1)">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <button class="slider-btn slider-next absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/40 hover:bg-[#d4af37] backdrop-blur-md rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300" onclick="slideImage(1)">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                                <div class="flex justify-center gap-3 mt-6" id="sliderDots">
                                    <?php
                                    $totalSlides = ($post['featured_image'] ? 1 : 0) + count($gallery_images);
                                    for ($i = 0; $i < $totalSlides; $i++):
                                    ?>
                                        <button class="w-2.5 h-2.5 rounded-full <?php echo $i === 0 ? 'bg-[#d4af37] scale-125' : 'bg-white/20 hover:bg-white/50'; ?> transition-all duration-300" onclick="goToSlide(<?php echo $i; ?>)"></button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php elseif ($layout === 'video' && !empty($video_url)): ?>
                            <div class="mb-10">
                                <div class="aspect-video rounded-2xl overflow-hidden bg-black shadow-2xl ring-1 ring-white/10">
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="prose blog-detail-prose-premium max-w-none mb-12">
                            <?php echo _f($post, 'content'); ?>
                        </div>

                        <?php if ($post['tags']): ?>
                            <div class="blog-tags-modern mb-10 pt-8 border-t border-white/5">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-[#d4af37]">sell</span>
                                    <span class="text-sm font-semibold text-white/80 uppercase tracking-wider">Tags</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                        <span class="blog-tag-pill">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Interactive Floating Action Bar -->
                        <div class="blog-interaction-premium bg-white/[0.02] border border-white/5 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6" id="interactionSection" data-post-id="<?php echo $post['post_id']; ?>">
                            <div class="flex flex-col items-center md:items-start gap-2">
                                <span class="text-xs font-bold text-white/50 uppercase tracking-widest"><?php _e('blog_page.rate_post'); ?></span>
                                <div class="flex items-center gap-4">
                                    <div class="blog-star-rating-modern" id="starRating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button type="button" class="star-btn-modern" data-rating="<?php echo $i; ?>">
                                                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="text-sm font-medium">
                                        <span class="text-[#d4af37] text-xl" id="ratingAvg"><?php echo number_format($post['rating_avg'] ?? 0, 1); ?></span>
                                        <span class="text-white/40">/ 5</span>
                                        <span class="text-white/30 text-xs ml-1">(<span id="ratingCount"><?php echo (int) ($post['rating_count'] ?? 0); ?></span>)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-px h-px md:h-12 bg-white/10"></div>

                            <div class="flex items-center gap-6">
                                <button type="button" id="likeBtn" class="blog-like-btn-modern flex items-center gap-2 group">
                                    <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-red-500/10 group-hover:border-red-500/30 group-[.liked]:bg-red-500 group-[.liked]:border-red-500 transition-all duration-300">
                                        <svg class="w-6 h-6 text-white/50 group-hover:text-red-500 group-[.liked]:text-white transition-colors like-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <span class="text-xs font-bold text-white/50 uppercase tracking-widest">Like</span>
                                        <span class="text-lg font-bold text-white" id="likesCount"><?php echo (int) ($post['likes_count'] ?? 0); ?></span>
                                    </div>
                                </button>

                                <div class="flex gap-2">
                                    <button type="button" class="blog-share-modern fb" data-platform="facebook" title="Facebook">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" /></svg>
                                    </button>
                                    <button type="button" class="blog-share-modern tw" data-platform="twitter" title="Twitter/X">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.727 8.79 7.292 11.71h-6.09l-4.64-6.17-5.277 6.17H2.25l8.09-9.39L2.25 4.5h6.19l4.24 5.56 5.764-5.56zm-1.08 16.5h1.72L8.7 7.21H6.92l10.244 11.54z"/></svg>
                                    </button>
                                    <button type="button" class="blog-share-modern link" data-platform="copy" title="Copy Link">
                                        <span class="material-symbols-outlined text-[20px]">link</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Comments Section -->
                    <section class="blog-comments-premium glass-card-premium p-6 md:p-10">
                        <h3 class="text-2xl font-playfair font-bold text-white mb-8 flex items-center gap-3">
                            <?php _e('blog_page.comments_title'); ?> <span class="bg-[#d4af37]/20 text-[#d4af37] text-sm py-1 px-3 rounded-full"><?php echo count($blog_comments); ?></span>
                        </h3>

                        <?php if (isset($_SESSION['user_id']) && (!isset($post['allow_comments']) || (int) $post['allow_comments'] === 1)): ?>
                            <div class="blog-comment-form-modern mb-10">
                                <?php if ($success): ?>
                                    <div class="mb-6 p-4 bg-green-500/10 text-green-400 rounded-xl border border-green-500/20 flex items-center gap-3"><span class="material-symbols-outlined">check_circle</span> <?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="mb-6 p-4 bg-red-500/10 text-red-400 rounded-xl border border-red-500/20 flex items-center gap-3"><span class="material-symbols-outlined">error</span> <?php echo $error; ?></div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <div class="flex gap-4">
                                        <div class="hidden sm:block shrink-0">
                                            <div class="w-12 h-12 rounded-full bg-[#d4af37]/20 flex items-center justify-center border border-[#d4af37]/30 text-[#d4af37] font-bold text-lg">
                                                <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <textarea name="content" rows="3" class="blog-input-premium w-full" placeholder="<?php _e('blog_page.write_comment'); ?>..." required></textarea>
                                            <div class="flex justify-end mt-3">
                                                <button type="submit" name="submit_comment" class="blog-btn-gold px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2">
                                                    <?php _e('blog_page.submit_comment'); ?> <span class="material-symbols-outlined text-[18px]">send</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php elseif (isset($_SESSION['user_id']) && isset($post['allow_comments']) && (int) $post['allow_comments'] === 0): ?>
                            <div class="p-6 text-center bg-white/5 rounded-2xl border border-white/5 mb-10 text-white/50">
                                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">comments_disabled</span>
                                <p><?php _e('blog_page.comments_disabled'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="p-8 text-center bg-white/5 rounded-2xl border border-white/5 mb-10">
                                <span class="material-symbols-outlined text-4xl mb-3 text-[#d4af37]/50">lock</span>
                                <p class="text-white/70 mb-5 font-medium"><?php _e('blog_page.login_to_comment'); ?></p>
                                <a href="<?php echo route('dang-nhap', ['redirect' => $_SERVER['REQUEST_URI']]); ?>" class="inline-flex items-center gap-2 bg-[#d4af37] hover:bg-[#c19b2e] text-white px-6 py-2.5 rounded-xl font-semibold transition-colors">
                                    <?php _e('blog_page.login'); ?> <span class="material-symbols-outlined text-[18px]">login</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($blog_comments)): ?>
                            <div class="space-y-6">
                                <?php foreach ($blog_comments as $comment): ?>
                                    <div class="comment-item-premium flex gap-4 p-5 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                                        <?php if (!empty($comment['avatar'])): ?>
                                            <img src="<?php echo imgUrl($comment['avatar']); ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>" class="w-12 h-12 rounded-full object-cover shrink-0 ring-2 ring-white/10">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center shrink-0 ring-2 ring-white/10 text-white/50 font-bold">
                                                <?php echo substr($comment['user_name'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-2">
                                                <span class="font-bold text-white tracking-wide"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                                <span class="text-xs text-white/40 font-medium tracking-wider uppercase"><?php echo date('d/m/Y - H:i', strtotime($comment['created_at'])); ?></span>
                                            </div>
                                            <p class="text-white/80 leading-relaxed text-[15px]"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
                <!-- End Left Column -->

                <!-- Right Column (Sidebar) -->
                <div class="lg:col-span-4 hidden lg:block">
                    <div class="sticky top-32 space-y-8 animate-fade-in" style="animation-delay: 0.6s;">
                        
                        <!-- Author Widget -->
                        <div class="sidebar-widget-premium p-6 flex flex-col items-center text-center">
                            <?php if (!empty($post['avatar'])): ?>
                                <img src="<?php echo imgUrl($post['avatar']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="w-24 h-24 rounded-full border-4 border-[#111827] shadow-[0_0_0_2px_rgba(212,175,55,0.5)] object-cover mb-4">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full border-4 border-[#111827] bg-[#d4af37]/20 flex items-center justify-center shadow-[0_0_0_2px_rgba(212,175,55,0.5)] mb-4">
                                    <span class="material-symbols-outlined text-[#d4af37] text-4xl">person</span>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-xl font-playfair font-bold text-white mb-1"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></h4>
                            <p class="text-xs text-[#d4af37] font-semibold uppercase tracking-widest mb-4">Content Creator</p>
                            <p class="text-sm text-white/60 leading-relaxed mb-6">Bringing you the latest insights, news, and exclusive updates from Aurora Hotel Plaza.</p>
                            <a href="<?php echo route('tin-tuc'); ?>" class="w-full py-2.5 rounded-xl border border-white/10 hover:bg-white/5 text-white text-sm font-semibold transition-all inline-block">View All Posts</a>
                        </div>

                        <!-- Recent Posts Sidebar -->
                        <?php if (!empty($recent_posts)): ?>
                            <div class="sidebar-widget-premium p-6">
                                <h3 class="text-lg font-playfair font-bold text-white mb-5 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#d4af37]">history</span> Latest Articles
                                </h3>
                                <div class="space-y-5">
                                    <?php foreach ($recent_posts as $recent): ?>
                                        <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $recent['slug']]); ?>" class="flex gap-4 group items-center">
                                            <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden relative">
                                                <img src="<?php echo imgUrl($recent['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-[15px] font-semibold text-white/90 group-hover:text-[#d4af37] leading-snug line-clamp-2 mb-2 transition-colors"><?php echo htmlspecialchars(_f($recent, 'title')); ?></h4>
                                                <span class="text-xs text-white/40 uppercase tracking-wider font-medium"><?php echo date('M d, Y', strtotime($recent['published_at'])); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Suggested Accommodations -->
                        <?php if (!empty($suggested_rooms) || !empty($suggested_apartments)): ?>
                            <div class="sidebar-widget-premium p-6">
                                <h3 class="text-lg font-playfair font-bold text-white mb-5 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#d4af37]">hotel</span> Luxury Stay
                                </h3>
                                <div class="space-y-4">
                                    <?php foreach (array_merge($suggested_rooms, $suggested_apartments) as $sug): ?>
                                        <div class="relative group rounded-xl overflow-hidden aspect-[4/3] block">
                                            <img src="<?php echo imgUrl($sug['thumbnail'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                                            <div class="absolute bottom-0 left-0 right-0 p-4">
                                                <h4 class="text-white font-bold text-lg mb-1 drop-shadow-lg"><?php echo htmlspecialchars(_f($sug, 'type_name')); ?></h4>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[#d4af37] font-semibold"><?php echo number_format($sug['base_price'], 0, ',', '.'); ?> ₫</span>
                                                    <a href="<?php echo route($sug['category'] === 'apartment' ? 'chi-tiet-can-ho' : 'chi-tiet-phong', ['slug' => $sug['slug']]); ?>" class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center hover:bg-[#d4af37] transition-colors text-white">
                                                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <!-- End Right Column -->
            </div>
        </div>

        <!-- Related Posts Section (Full Width Bottom) -->
        <?php if (!empty($related_posts)): ?>
            <section class="mt-20 pt-16 border-t border-white/10 relative overflow-hidden">
                <!-- Background decorative glow -->
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] bg-[#d4af37]/5 blur-[120px] rounded-full pointer-events-none"></div>
                
                <div class="mx-auto max-w-7xl px-4 lg:px-8 relative z-10">
                    <div class="flex items-end justify-between mb-10">
                        <div>
                            <span class="text-[#d4af37] font-semibold tracking-widest uppercase text-sm mb-2 block">Read More</span>
                            <h2 class="text-3xl md:text-4xl font-playfair font-bold text-white"><?php _e('blog_page.related_posts'); ?></h2>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($related_posts as $related): ?>
                            <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $related['slug']]); ?>" class="group block">
                                <div class="rounded-2xl overflow-hidden relative aspect-[4/3] mb-5">
                                    <img src="<?php echo imgUrl($related['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors duration-500"></div>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white/90 group-hover:text-[#d4af37] transition-colors mb-2 line-clamp-2 leading-snug"><?php echo htmlspecialchars(_f($related, 'title')); ?></h3>
                                    <p class="text-white/50 text-sm line-clamp-2 leading-relaxed"><?php echo htmlspecialchars(_f($related, 'excerpt') ?? ''); ?></p>
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