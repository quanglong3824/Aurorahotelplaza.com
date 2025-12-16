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
            $post['gallery_images'] = json_encode(array_map(function ($img) {
                return strpos($img, '../uploads/') === 0 ? str_replace('../uploads/', 'uploads/', $img) : $img;
            }, $gallery));
        }
    }

    // Handle comment submission
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
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php echo htmlspecialchars($post['title']); ?> - Aurora Hotel Plaza</title>
    <meta name="description" content="<?php echo htmlspecialchars($post['excerpt'] ?? ''); ?>">
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/blog.css">
    <link rel="stylesheet" href="assets/css/blog-detail.css">
</head>

<body class="bg-gray-900 font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Glass Page Wrapper for Dark Theme Consistency -->
            <div class="glass-page-wrapper blog-detail-wrapper"
                style="background-image: url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); padding-top: 180px;">

                <!-- Article Header -->
                <article class="py-0 relative z-10">
                    <div class="mx-auto max-w-4xl px-4">

                        <!-- Breadcrumb -->
                        <nav class="mb-8 flex items-center gap-2 text-sm">
                            <a href="index.php" class="text-accent hover:underline"><?php _e('blog_page.home'); ?></a>
                            <span class="material-symbols-outlined text-sm text-white/50">chevron_right</span>
                            <a href="blog.php" class="text-accent hover:underline"><?php _e('blog_page.posts'); ?></a>
                            <span class="material-symbols-outlined text-sm text-white/50">chevron_right</span>
                            <span class="text-white/70">
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
                        <h1 class="font-display text-4xl md:text-5xl font-bold mb-6 text-white leading-tight">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h1>

                        <!-- Meta -->
                        <div class="flex flex-wrap items-center gap-6 mb-8 text-sm text-white/70">
                            <div class="flex items-center gap-2">
                                <?php if (!empty($post['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['avatar']); ?>"
                                        alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                        class="w-10 h-10 rounded-full object-cover border border-white/20">
                                <?php else: ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center border border-white/20">
                                        <span class="material-symbols-outlined text-accent">person</span>
                                    </div>
                                <?php endif; ?>
                                <span
                                    class="font-semibold text-white"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">calendar_today</span>
                                <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                <span><?php echo number_format($post['views']); ?>
                                    <?php _e('blog_page.views'); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">comment</span>
                                <span><?php echo count($blog_comments); ?> <?php _e('blog_page.comments'); ?></span>
                            </div>
                        </div>

                        <!-- Content Container with Glass Effect -->
                        <div class="glass-card-solid p-6 md:p-10 mb-12">

                            <!-- Dynamic Layout Based on Post Layout Type -->
                            <?php
                            $layout = $post['layout'] ?? 'standard';
                            $gallery_images = !empty($post['gallery_images']) ? json_decode($post['gallery_images'], true) : [];
                            $video_url = $post['video_url'] ?? '';
                            ?>

                            <?php if ($layout === 'hero' && $post['featured_image']): ?>
                                <!-- HERO LAYOUT - Full width hero image with overlay -->
                                <div class="relative -mx-6 md:-mx-10 mt-[-2.5rem] mb-10 rounded-t-xl overflow-hidden">
                                    <div class="aspect-[21/9] overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>"
                                            class="w-full h-full object-cover">
                                    </div>
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent">
                                    </div>
                                </div>

                            <?php elseif ($layout === 'fullwidth' && $post['featured_image']): ?>
                                <!-- FULLWIDTH LAYOUT -->
                                <div class="-mx-6 md:-mx-10 mb-12">
                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                        alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
                                    <div class="h-1 bg-gradient-to-r from-transparent via-[#d4af37] to-transparent"></div>
                                </div>

                            <?php elseif ($layout === 'gallery' && !empty($gallery_images)): ?>
                                <!-- GALLERY LAYOUT -->
                                <div class="mb-12">
                                    <?php if ($post['featured_image']): ?>
                                        <div class="mb-4 rounded-xl overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                class="w-full h-auto cursor-pointer hover:scale-105 transition-transform duration-500"
                                                onclick="openLightbox(0)">
                                        </div>
                                    <?php endif; ?>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                        <?php foreach ($gallery_images as $index => $img): ?>
                                            <div class="aspect-square rounded-lg overflow-hidden group cursor-pointer"
                                                onclick="openLightbox(<?php echo $index + 1; ?>)">
                                                <img src="<?php echo htmlspecialchars($img); ?>"
                                                    alt="Gallery image <?php echo $index + 1; ?>"
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php elseif ($layout === 'slider' && !empty($gallery_images)): ?>
                                <!-- SLIDER LAYOUT -->
                                <div class="mb-12 relative" id="imageSlider">
                                    <div class="overflow-hidden rounded-xl">
                                        <div class="slider-track flex transition-transform duration-500" id="sliderTrack">
                                            <?php if ($post['featured_image']): ?>
                                                <div class="slider-slide min-w-full">
                                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                        alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                        class="w-full h-[400px] md:h-[500px] object-cover">
                                                </div>
                                            <?php endif; ?>
                                            <?php foreach ($gallery_images as $index => $img): ?>
                                                <div class="slider-slide min-w-full">
                                                    <img src="<?php echo htmlspecialchars($img); ?>"
                                                        alt="Slide <?php echo $index + 1; ?>"
                                                        class="w-full h-[400px] md:h-[500px] object-cover">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <!-- Slider Controls -->
                                    <button
                                        class="slider-btn slider-prev absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white"
                                        onclick="slideImage(-1)">
                                        <span class="material-symbols-outlined">chevron_left</span>
                                    </button>
                                    <button
                                        class="slider-btn slider-next absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white"
                                        onclick="slideImage(1)">
                                        <span class="material-symbols-outlined">chevron_right</span>
                                    </button>
                                    <!-- Slider Dots -->
                                    <div class="flex justify-center gap-2 mt-4" id="sliderDots">
                                        <?php
                                        $totalSlides = ($post['featured_image'] ? 1 : 0) + count($gallery_images);
                                        for ($i = 0; $i < $totalSlides; $i++):
                                            ?>
                                            <button
                                                class="w-3 h-3 rounded-full bg-white/30 hover:bg-[#d4af37] transition-colors <?php echo $i === 0 ? 'bg-[#d4af37]' : ''; ?>"
                                                onclick="goToSlide(<?php echo $i; ?>)"></button>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                            <?php elseif ($layout === 'apartment' && $post['featured_image']): ?>
                                <!-- APARTMENT LAYOUT -->
                                <div class="mb-12">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <!-- Main Image -->
                                        <div class="lg:col-span-2 rounded-xl overflow-hidden cursor-pointer"
                                            onclick="openLightbox(0)">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                class="w-full h-[300px] md:h-[400px] object-cover hover:scale-105 transition-transform duration-500">
                                        </div>
                                        <!-- Side Thumbnails -->
                                        <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                                            <?php
                                            $sideImages = array_slice($gallery_images, 0, 2);
                                            foreach ($sideImages as $index => $img):
                                                ?>
                                                <div class="rounded-xl overflow-hidden cursor-pointer"
                                                    onclick="openLightbox(<?php echo $index + 1; ?>)">
                                                    <img src="<?php echo htmlspecialchars($img); ?>"
                                                        alt="Gallery <?php echo $index + 1; ?>"
                                                        class="w-full h-[140px] md:h-[190px] object-cover hover:scale-105 transition-transform duration-500">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php if (count($gallery_images) > 2): ?>
                                        <div class="grid grid-cols-4 gap-3 mt-4">
                                            <?php
                                            $moreImages = array_slice($gallery_images, 2, 4);
                                            foreach ($moreImages as $index => $img):
                                                ?>
                                                <div class="aspect-video rounded-lg overflow-hidden cursor-pointer relative group"
                                                    onclick="openLightbox(<?php echo $index + 3; ?>)">
                                                    <img src="<?php echo htmlspecialchars($img); ?>"
                                                        alt="Gallery <?php echo $index + 3; ?>"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                    <?php if ($index === 3 && count($gallery_images) > 6): ?>
                                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                                            <span
                                                                class="text-white text-xl font-bold">+<?php echo count($gallery_images) - 6; ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($layout === 'masonry' && !empty($gallery_images)): ?>
                                <!-- MASONRY LAYOUT -->
                                <div class="mb-12">
                                    <?php if ($post['featured_image']): ?>
                                        <div class="mb-4 rounded-xl overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                class="w-full h-auto cursor-pointer hover:brightness-110 transition-all"
                                                onclick="openLightbox(0)">
                                        </div>
                                    <?php endif; ?>
                                    <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
                                        <?php foreach ($gallery_images as $index => $img): ?>
                                            <div class="break-inside-avoid rounded-lg overflow-hidden cursor-pointer group"
                                                onclick="openLightbox(<?php echo $index + 1; ?>)">
                                                <img src="<?php echo htmlspecialchars($img); ?>"
                                                    alt="Gallery <?php echo $index + 1; ?>"
                                                    class="w-full h-auto group-hover:scale-105 transition-transform duration-500">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php elseif ($layout === 'magazine' && $post['featured_image']): ?>
                                <!-- MAGAZINE LAYOUT -->
                                <div class="mb-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                                    <div class="rounded-xl overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>"
                                            class="w-full h-[350px] object-cover hover:scale-105 transition-transform duration-500 cursor-pointer"
                                            onclick="openLightbox(0)">
                                    </div>
                                    <div class="space-y-4">
                                        <?php if (!empty($post['excerpt'])): ?>
                                            <p
                                                class="text-xl leading-relaxed text-white/90 italic border-l-4 border-[#d4af37] pl-4">
                                                <?php echo htmlspecialchars($post['excerpt']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($gallery_images)): ?>
                                            <div class="grid grid-cols-3 gap-2 mt-4">
                                                <?php foreach (array_slice($gallery_images, 0, 3) as $index => $img): ?>
                                                    <div class="aspect-square rounded-lg overflow-hidden cursor-pointer"
                                                        onclick="openLightbox(<?php echo $index + 1; ?>)">
                                                        <img src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="Gallery <?php echo $index + 1; ?>"
                                                            class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php elseif ($layout === 'video' && !empty($video_url)): ?>
                                <!-- VIDEO LAYOUT -->
                                <div class="mb-12">
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
                                            <iframe src="<?php echo htmlspecialchars($embed_url); ?>" class="w-full h-full"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-white">
                                                <p>Video không hợp lệ</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($post['featured_image']): ?>
                                        <div class="mt-4 rounded-lg overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($layout === 'timeline'): ?>
                                <!-- TIMELINE LAYOUT -->
                                <div class="mb-12">
                                    <?php if ($post['featured_image']): ?>
                                        <div class="mb-6 rounded-xl overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($gallery_images)): ?>
                                        <div class="relative pl-8 border-l-2 border-[#d4af37] space-y-8">
                                            <?php foreach ($gallery_images as $index => $img): ?>
                                                <div class="relative">
                                                    <div
                                                        class="absolute -left-[41px] w-4 h-4 bg-[#d4af37] rounded-full border-4 border-gray-900">
                                                    </div>
                                                    <div class="rounded-xl overflow-hidden cursor-pointer"
                                                        onclick="openLightbox(<?php echo $index + 1; ?>)">
                                                        <img src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="Timeline <?php echo $index + 1; ?>"
                                                            class="w-full h-auto hover:brightness-110 transition-all">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <!-- STANDARD LAYOUT -->
                                <?php if ($post['featured_image']): ?>
                                    <div class="mb-8 rounded-xl overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Content -->
                            <div class="prose prose-lg dark:prose-invert max-w-none mb-12 text-white/90">
                                <?php echo $post['content']; ?>
                            </div>

                            <!-- Tags -->
                            <?php if ($post['tags']): ?>
                                <div class="flex flex-wrap gap-2 mb-8">
                                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                        <span class="px-3 py-1 bg-white/10 rounded-full text-sm text-white/70">
                                            #<?php echo htmlspecialchars(trim($tag)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Rating, Like, Share Section -->
                            <div class="border-t border-b border-white/10 py-6 mb-12" id="interactionSection"
                                data-post-id="<?php echo $post['post_id']; ?>">
                                <div
                                    class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">

                                    <!-- Star Rating -->
                                    <div class="flex flex-col gap-2">
                                        <span
                                            class="text-sm font-medium text-white/60"><?php _e('blog_page.rate_post'); ?></span>
                                        <div class="flex items-center gap-3">
                                            <div class="star-rating flex gap-1" id="starRating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <button type="button"
                                                        class="star-btn text-2xl text-gray-500 hover:text-yellow-400 transition-colors"
                                                        data-rating="<?php echo $i; ?>">
                                                        <span class="material-symbols-outlined">star</span>
                                                    </button>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm text-white/60">
                                                <span
                                                    id="ratingAvg"><?php echo number_format($post['rating_avg'] ?? 0, 1); ?></span>/5
                                                (<span
                                                    id="ratingCount"><?php echo (int) ($post['rating_count'] ?? 0); ?></span>
                                                <?php _e('blog_page.ratings'); ?>)
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Like Button -->
                                    <div class="flex items-center gap-4">
                                        <button type="button" id="likeBtn"
                                            class="flex items-center gap-2 px-4 py-2 rounded-full border-2 border-red-400/50 text-red-400 hover:bg-red-900/20 transition-all">
                                            <span class="material-symbols-outlined like-icon">favorite</span>
                                            <span
                                                id="likesCount"><?php echo (int) ($post['likes_count'] ?? 0); ?></span>
                                        </button>

                                        <!-- Share Buttons -->
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm text-white/60 mr-2"><?php _e('blog_page.share_post'); ?></span>
                                            <button type="button" class="share-btn-icon facebook"
                                                data-platform="facebook" title="Facebook">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="share-btn-icon twitter" data-platform="twitter"
                                                title="Twitter/X">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="share-btn-icon linkedin"
                                                data-platform="linkedin" title="LinkedIn">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="share-btn-icon copy-link"
                                                data-platform="copy_link" title="Copy Link">
                                                <span class="material-symbols-outlined text-xl">link</span>
                                            </button>
                                        </div>
                                        <span class="text-sm text-white/60">
                                            <span
                                                id="sharesCount"><?php echo (int) ($post['shares_count'] ?? 0); ?></span>
                                            <?php _e('blog_page.shares'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <div class="mb-12">
                                <h3 class="text-2xl font-bold mb-6 text-white">
                                    <?php _e('blog_page.comments_title'); ?> (<?php echo count($blog_comments); ?>)
                                </h3>

                                <!-- Comment Form -->
                                <?php if (isset($_SESSION['user_id']) && (!isset($post['allow_comments']) || (int) $post['allow_comments'] === 1)): ?>
                                    <div class="mb-8 p-6 bg-white/5 rounded-xl border border-white/10">
                                        <?php if ($success): ?>
                                            <div
                                                class="mb-4 p-4 bg-green-900/30 text-green-300 rounded-lg border border-green-500/30">
                                                <?php echo $success; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($error): ?>
                                            <div
                                                class="mb-4 p-4 bg-red-900/30 text-red-300 rounded-lg border border-red-500/30">
                                                <?php echo $error; ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" action="">
                                            <textarea name="content" rows="4"
                                                class="w-full p-4 border border-white/10 rounded-lg bg-black/40 text-white resize-none focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                                                placeholder="<?php _e('blog_page.write_comment'); ?>" required></textarea>
                                            <button type="submit" name="submit_comment" class="mt-4 btn-primary">
                                                <?php _e('blog_page.submit_comment'); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif (isset($_SESSION['user_id']) && isset($post['allow_comments']) && (int) $post['allow_comments'] === 0): ?>
                                    <div class="mb-8 p-6 bg-white/5 rounded-xl text-center">
                                        <p class="mb-0 text-white/70"><?php _e('blog_page.comments_disabled'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-8 p-6 bg-white/5 rounded-xl text-center border border-white/10">
                                        <p class="mb-4 text-white/70"><?php _e('blog_page.login_to_comment'); ?></p>
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
                                                        <div
                                                            class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center flex-shrink-0">
                                                            <span class="material-symbols-outlined text-accent">person</span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <span
                                                                class="font-semibold text-white"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                                            <span class="text-sm text-white/50">
                                                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-white/80">
                                                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center text-white/50 py-8">
                                        <?php _e('blog_page.no_comments'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </article>

                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                    <section class="py-16 bg-black/40">
                        <div class="mx-auto max-w-7xl px-4">
                            <h2 class="text-3xl font-bold mb-8 text-center text-white">
                                <?php _e('blog_page.related_posts'); ?>
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <?php foreach ($related_posts as $related): ?>
                                    <article class="blog-card bg-white/5 border border-white/10">
                                        <a href="blog-detail.php?slug=<?php echo urlencode($related['slug']); ?>"
                                            class="block h-full">
                                            <div class="blog-card-image"
                                                style="background-image: url('<?php echo htmlspecialchars($related['featured_image'] ?? 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>')">
                                            </div>
                                            <div class="blog-card-content">
                                                <h3 class="blog-card-title text-white">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </h3>
                                                <p class="blog-card-excerpt text-white/70">
                                                    <?php echo htmlspecialchars($related['excerpt'] ?? ''); ?>
                                                </p>
                                            </div>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/blog-detail.js"></script>

    <!-- Lightbox HTML -->
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
            <div class="lightbox-counter">
                <span id="lightboxCurrent">1</span> / <span id="lightboxTotal">1</span>
            </div>
        </div>
    </div>

</body>

</html>