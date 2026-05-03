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
    <link rel="stylesheet" href="<?php echo assetVersion('css/blog.css'); ?>">
    <!-- Google Fonts for Premium Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const API_URL = '<?php echo API_URL; ?>';
    </script>
    <style>
        body.blog-detail-new {
            background-color: #f8fafc; /* slate-50 */
            color: #334155; /* slate-700 */
        }
        .hero-section {
            background-image: url('<?php echo $bgImage; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(15,23,42,0.5) 0%, rgba(15,23,42,0.85) 100%);
        }
        .content-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
            transform: translateY(-80px);
            position: relative;
            z-index: 10;
        }
        .sidebar-sticky {
            transform: translateY(-80px);
            position: relative;
            z-index: 10;
        }
        @media (max-width: 1023px) {
            .sidebar-sticky {
                transform: none;
                margin-top: 2rem;
            }
        }
        
        .prose img {
            border-radius: 1rem;
            margin: 2rem auto;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            max-width: 100%;
        }
        .prose p { margin-bottom: 1.25em; line-height: 1.8; }
        .prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.25em; }
        .prose ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 1.25em; }
        .prose h2, .prose h3, .prose h4 {
            font-family: 'Playfair Display', serif;
            color: #0f172a;
            margin-top: 2em;
            margin-bottom: 1em;
            font-weight: 700;
        }
        .prose h2 { font-size: 2rem; }
        .prose h3 { font-size: 1.5rem; }
        .prose a {
            color: #d4af37;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .prose a:hover {
            color: #b8962e;
            text-decoration: underline;
        }
        .prose blockquote {
            border-left: 4px solid #d4af37;
            padding-left: 1.5rem;
            font-style: italic;
            color: #64748b;
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0 1rem 1rem 0;
            margin: 2rem 0;
        }

        #lightbox.active {
            opacity: 1;
            pointer-events: auto;
        }
        #lightbox.active #lightboxImg {
            transform: scale(1);
        }
    </style>
</head>

<body class="blog-detail-new font-body">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <header class="hero-section w-full h-[65vh] min-h-[500px] flex items-center justify-center pt-20">
        <div class="hero-overlay"></div>
        <div class="relative z-10 w-full max-w-5xl mx-auto px-4 text-center text-white mt-8">
            <nav class="flex justify-center items-center gap-2 text-[13px] font-semibold text-white/80 mb-6 tracking-widest uppercase">
                <a href="<?php echo route(''); ?>" class="hover:text-[#d4af37] transition-colors">Home</a>
                <span class="opacity-50">/</span>
                <a href="<?php echo route('tin-tuc'); ?>" class="hover:text-[#d4af37] transition-colors"><?php _e('blog_page.posts'); ?></a>
                <?php if ($post['category_name']): ?>
                <span class="opacity-50">/</span>
                <span class="text-[#d4af37]"><?php echo htmlspecialchars(_f($post, 'category_name')); ?></span>
                <?php endif; ?>
            </nav>
            
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-playfair font-bold mb-8 leading-tight drop-shadow-xl px-4">
                <?php echo htmlspecialchars(_f($post, 'title')); ?>
            </h1>
            
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm font-medium text-white/90">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/10">
                    <span class="material-symbols-outlined text-[18px] text-[#d4af37]">person</span>
                    <span><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/10">
                    <span class="material-symbols-outlined text-[18px] text-[#d4af37]">calendar_today</span>
                    <span><?php echo date('M d, Y', strtotime($post['published_at'])); ?></span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/10">
                    <span class="material-symbols-outlined text-[18px] text-[#d4af37]">visibility</span>
                    <span><?php echo number_format($post['views']); ?> <?php _e('blog_page.views'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-24">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-10">
            
            <!-- Main Content Area -->
            <div class="w-full lg:w-[68%]">
                <article class="content-card p-6 md:p-10 lg:p-14 mb-8">
                    
                    <!-- Layout Media -->
                    <?php if ($layout === 'gallery' && !empty($gallery_images)): ?>
                        <div class="grid grid-cols-2 gap-4 mb-10">
                            <?php foreach ($gallery_images as $index => $img): ?>
                                <div class="rounded-2xl overflow-hidden cursor-pointer h-48 sm:h-64 md:h-80 shadow-md group" onclick="openLightbox(<?php echo $index; ?>)">
                                    <img src="<?php echo imgUrl($img); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700 ease-in-out">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($layout === 'slider' && !empty($gallery_images)): ?>
                        <div class="mb-12 relative rounded-2xl overflow-hidden shadow-xl group" id="imageSlider">
                            <div class="flex transition-transform duration-700 ease-in-out" id="sliderTrack">
                                <?php if ($post['featured_image']): ?>
                                    <div class="min-w-full"><img src="<?php echo imgUrl($post['featured_image']); ?>" class="w-full h-[350px] sm:h-[450px] md:h-[550px] object-cover"></div>
                                <?php endif; ?>
                                <?php foreach ($gallery_images as $index => $img): ?>
                                    <div class="min-w-full"><img src="<?php echo imgUrl($img); ?>" class="w-full h-[350px] sm:h-[450px] md:h-[550px] object-cover"></div>
                                <?php endforeach; ?>
                            </div>
                            <button class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 hover:bg-[#d4af37] text-gray-900 hover:text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition duration-300" onclick="slideImage(-1)"><span class="material-symbols-outlined">chevron_left</span></button>
                            <button class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 hover:bg-[#d4af37] text-gray-900 hover:text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition duration-300" onclick="slideImage(1)"><span class="material-symbols-outlined">chevron_right</span></button>
                            <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-3" id="sliderDots">
                                <?php
                                $totalSlides = ($post['featured_image'] ? 1 : 0) + count($gallery_images);
                                for ($i = 0; $i < $totalSlides; $i++):
                                ?>
                                    <button class="w-2.5 h-2.5 rounded-full <?php echo $i === 0 ? 'bg-[#d4af37] scale-125' : 'bg-white/60 hover:bg-white'; ?> transition-all duration-300" onclick="goToSlide(<?php echo $i; ?>)"></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php elseif ($layout === 'video' && !empty($video_url)): ?>
                        <div class="mb-12 rounded-2xl overflow-hidden shadow-xl aspect-video bg-gray-900">
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
                                <iframe src="<?php echo htmlspecialchars($embed_url); ?>" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Content -->
                    <div class="prose max-w-none text-slate-700 text-[16px] md:text-[17px]">
                        <?php echo _f($post, 'content'); ?>
                    </div>

                    <!-- Tags -->
                    <?php if ($post['tags']): ?>
                        <div class="mt-14 pt-8 border-t border-gray-100 flex flex-wrap gap-3 items-center">
                            <span class="text-sm font-bold text-gray-400 uppercase tracking-widest mr-2 flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">sell</span> Tags</span>
                            <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                <span class="px-4 py-1.5 bg-gray-50 text-gray-600 rounded-lg text-sm font-medium hover:bg-[#d4af37] hover:text-white transition-colors cursor-pointer border border-gray-200 shadow-sm">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Interaction Section -->
                    <div class="mt-12 bg-white rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-8 border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)]" id="interactionSection" data-post-id="<?php echo $post['post_id']; ?>">
                        <div class="flex flex-col items-center md:items-start w-full md:w-auto">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"><?php _e('blog_page.rate_post'); ?></span>
                            <div class="flex items-center gap-4">
                                <div class="flex text-[#d4af37]" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="star-btn-modern hover:scale-110 transition-transform" data-rating="<?php echo $i; ?>">
                                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-sm font-bold text-gray-700 bg-gray-50 px-4 py-1.5 rounded-lg border border-gray-200">
                                    <span id="ratingAvg" class="text-[#d4af37] text-lg"><?php echo number_format($post['rating_avg'] ?? 0, 1); ?></span> / 5 
                                    <span class="text-gray-400 font-medium ml-1">(<span id="ratingCount"><?php echo (int) ($post['rating_count'] ?? 0); ?></span>)</span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full h-px md:w-px md:h-16 bg-gray-100"></div>

                        <div class="flex items-center justify-between w-full md:w-auto gap-6 md:gap-8">
                            <button type="button" id="likeBtn" class="flex flex-col items-center gap-1 group">
                                <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center border border-gray-200 group-hover:border-red-200 group-hover:bg-red-50 group-[.liked]:bg-red-500 group-[.liked]:border-red-500 transition-all shadow-sm">
                                    <svg class="w-7 h-7 text-gray-400 group-hover:text-red-500 group-[.liked]:text-white group-[.liked]:fill-white transition-colors like-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                </div>
                                <span class="font-bold text-gray-700 mt-1" id="likesCount"><?php echo (int) ($post['likes_count'] ?? 0); ?> Likes</span>
                            </button>

                            <div class="flex gap-3">
                                <button type="button" class="w-11 h-11 rounded-full bg-gray-50 text-blue-600 flex items-center justify-center border border-gray-200 hover:bg-blue-600 hover:text-white transition-colors fb blog-share-modern shadow-sm" data-platform="facebook" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" /></svg>
                                </button>
                                <button type="button" class="w-11 h-11 rounded-full bg-gray-50 text-gray-900 flex items-center justify-center border border-gray-200 hover:bg-gray-900 hover:text-white transition-colors tw blog-share-modern shadow-sm" data-platform="twitter" title="Twitter/X">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.727 8.79 7.292 11.71h-6.09l-4.64-6.17-5.277 6.17H2.25l8.09-9.39L2.25 4.5h6.19l4.24 5.56 5.764-5.56zm-1.08 16.5h1.72L8.7 7.21H6.92l10.244 11.54z"/></svg>
                                </button>
                                <button type="button" class="w-11 h-11 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center border border-gray-200 hover:bg-gray-200 transition-colors link blog-share-modern shadow-sm" data-platform="copy" title="Copy Link">
                                    <span class="material-symbols-outlined text-[20px]">link</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Comments Area -->
                <div class="content-card p-6 md:p-10 lg:p-14 transform-none mt-0 mb-0">
                    <h3 class="text-2xl font-playfair font-bold text-gray-900 mb-8 flex items-center gap-3 border-b border-gray-100 pb-6">
                        <?php _e('blog_page.comments_title'); ?> 
                        <span class="bg-[#d4af37]/10 text-[#d4af37] text-sm py-1 px-3 rounded-lg font-sans font-semibold"><?php echo count($blog_comments); ?></span>
                    </h3>

                    <?php if (isset($_SESSION['user_id']) && (!isset($post['allow_comments']) || (int) $post['allow_comments'] === 1)): ?>
                        <div class="mb-12 bg-gray-50/50 p-6 md:p-8 rounded-2xl border border-gray-100">
                            <?php if ($success): ?>
                                <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-200 flex items-center gap-3"><span class="material-symbols-outlined">check_circle</span> <?php echo $success; ?></div>
                            <?php endif; ?>
                            <?php if ($error): ?>
                                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 flex items-center gap-3"><span class="material-symbols-outlined">error</span> <?php echo $error; ?></div>
                            <?php endif; ?>
                            <form method="POST" action="" class="relative">
                                <div class="flex gap-4 md:gap-6">
                                    <div class="hidden sm:block shrink-0">
                                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-white flex items-center justify-center border border-gray-200 text-[#d4af37] font-bold text-xl shadow-sm">
                                            <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <textarea name="content" rows="4" class="w-full bg-white border border-gray-200 rounded-xl p-4 md:p-5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/50 focus:border-[#d4af37] transition-all resize-none shadow-sm" placeholder="<?php _e('blog_page.write_comment'); ?>..." required></textarea>
                                        <div class="flex justify-end mt-4">
                                            <button type="submit" name="submit_comment" class="bg-[#d4af37] hover:bg-[#b8962e] text-white px-8 py-3 rounded-lg font-semibold flex items-center gap-2 shadow-md transition-all">
                                                <?php _e('blog_page.submit_comment'); ?> <span class="material-symbols-outlined text-[18px]">send</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php elseif (isset($_SESSION['user_id']) && isset($post['allow_comments']) && (int) $post['allow_comments'] === 0): ?>
                        <div class="p-8 text-center bg-gray-50 rounded-2xl border border-gray-200 mb-10 text-gray-500">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">comments_disabled</span>
                            <p><?php _e('blog_page.comments_disabled'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center bg-gray-50 rounded-2xl border border-gray-200 mb-10">
                            <span class="material-symbols-outlined text-4xl mb-3 text-gray-400">lock</span>
                            <p class="text-gray-600 mb-5 font-medium"><?php _e('blog_page.login_to_comment'); ?></p>
                            <a href="<?php echo route('dang-nhap', ['redirect' => $_SERVER['REQUEST_URI']]); ?>" class="inline-flex items-center gap-2 bg-[#d4af37] hover:bg-[#b8962e] text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-md">
                                <?php _e('blog_page.login'); ?> <span class="material-symbols-outlined text-[18px]">login</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($blog_comments)): ?>
                        <div class="space-y-6 md:space-y-8 mt-8">
                            <?php foreach ($blog_comments as $comment): ?>
                                <div class="flex gap-4 md:gap-6 group">
                                    <?php if (!empty($comment['avatar'])): ?>
                                        <img src="<?php echo imgUrl($comment['avatar']); ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>" class="w-12 h-12 md:w-14 md:h-14 rounded-full object-cover shrink-0 shadow-sm border border-gray-100">
                                    <?php else: ?>
                                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-slate-100 flex items-center justify-center shrink-0 text-slate-500 font-bold text-lg shadow-sm border border-gray-200">
                                            <?php echo substr($comment['user_name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 bg-white p-5 md:p-6 rounded-2xl border border-gray-100 shadow-sm group-hover:shadow-md transition-shadow">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                            <span class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                            <span class="text-xs text-gray-400 font-medium flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo date('M d, Y - H:i', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                        <p class="text-gray-600 leading-relaxed text-[15px]"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Sidebar -->
            <aside class="w-full lg:w-[32%]">
                <div class="sidebar-sticky space-y-8">
                    
                    <!-- Author Widget -->
                    <div class="bg-white rounded-2xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col items-center text-center">
                        <?php if (!empty($post['avatar'])): ?>
                            <img src="<?php echo imgUrl($post['avatar']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="w-28 h-28 rounded-full object-cover mb-4 shadow-md border-4 border-white ring-2 ring-gray-100">
                        <?php else: ?>
                            <div class="w-28 h-28 rounded-full bg-gray-50 flex items-center justify-center mb-4 shadow-sm border-4 border-white ring-2 ring-gray-100">
                                <span class="material-symbols-outlined text-[#d4af37] text-5xl">person</span>
                            </div>
                        <?php endif; ?>
                        <h4 class="text-xl font-playfair font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></h4>
                        <p class="text-xs text-[#d4af37] font-bold uppercase tracking-widest mb-4">Content Creator</p>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6">Bringing you the latest insights, news, and exclusive updates from Aurora Hotel Plaza.</p>
                        <div class="w-full h-px bg-gray-100 mb-6"></div>
                        <a href="<?php echo route('tin-tuc'); ?>" class="text-sm font-bold text-gray-700 hover:text-[#d4af37] transition-colors uppercase tracking-wider flex items-center gap-2">View All Posts <span class="material-symbols-outlined text-[18px]">arrow_forward</span></a>
                    </div>

                    <!-- Search Widget -->
                    <div class="bg-white rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
                        <form action="<?php echo route('tin-tuc'); ?>" method="GET" class="relative">
                            <input type="text" name="q" placeholder="Search articles..." class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-4 pr-12 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/50 focus:border-[#d4af37] transition-all">
                            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#d4af37]"><span class="material-symbols-outlined">search</span></button>
                        </form>
                    </div>

                    <!-- Recent Posts Widget -->
                    <?php if (!empty($recent_posts)): ?>
                    <div class="bg-white rounded-2xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
                        <h3 class="text-xl font-playfair font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                            <span class="material-symbols-outlined text-[#d4af37]">history_edu</span> Latest News
                        </h3>
                        <div class="space-y-6">
                            <?php foreach ($recent_posts as $recent): ?>
                                <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $recent['slug']]); ?>" class="flex gap-4 group items-start">
                                    <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden relative shadow-sm">
                                        <img src="<?php echo imgUrl($recent['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <h4 class="text-[14px] font-bold text-gray-800 group-hover:text-[#d4af37] leading-snug line-clamp-2 mb-2 transition-colors"><?php echo htmlspecialchars(_f($recent, 'title')); ?></h4>
                                        <span class="text-xs text-gray-400 font-medium flex items-center gap-1 uppercase tracking-wider"><span class="material-symbols-outlined text-[12px]">calendar_today</span> <?php echo date('M d, Y', strtotime($recent['published_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Categories Widget -->
                    <div class="bg-white rounded-2xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
                        <h3 class="text-xl font-playfair font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                            <span class="material-symbols-outlined text-[#d4af37]">folder_open</span> Explore More
                        </h3>
                        <div class="space-y-2">
                            <a href="<?php echo route('tin-tuc'); ?>" class="flex items-center justify-between text-gray-600 hover:text-[#d4af37] py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="font-medium">All Posts</span>
                                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                            </a>
                            <a href="<?php echo route('phong'); ?>" class="flex items-center justify-between text-gray-600 hover:text-[#d4af37] py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="font-medium">Rooms & Suites</span>
                                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                            </a>
                            <a href="<?php echo route('dich-vu'); ?>" class="flex items-center justify-between text-gray-600 hover:text-[#d4af37] py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="font-medium">Services & Facilities</span>
                                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                            </a>
                        </div>
                    </div>

                    <!-- Suggested Accommodations -->
                    <?php if (!empty($suggested_rooms) || !empty($suggested_apartments)): ?>
                        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
                            <h3 class="text-xl font-playfair font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                <span class="material-symbols-outlined text-[#d4af37]">king_bed</span> Luxury Stay
                            </h3>
                            <div class="space-y-5">
                                <?php foreach (array_merge($suggested_rooms, $suggested_apartments) as $sug): ?>
                                    <div class="relative group rounded-xl overflow-hidden aspect-[16/10] block shadow-md">
                                        <img src="<?php echo imgUrl($sug['thumbnail'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/20 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 right-0 p-5">
                                            <h4 class="text-white font-bold text-lg mb-1 drop-shadow-md"><?php echo htmlspecialchars(_f($sug, 'type_name')); ?></h4>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[#d4af37] font-bold bg-white/10 px-2.5 py-1 rounded backdrop-blur-md shadow-sm"><?php echo number_format($sug['base_price'], 0, ',', '.'); ?> ₫</span>
                                                <a href="<?php echo route($sug['category'] === 'apartment' ? 'chi-tiet-can-ho' : 'chi-tiet-phong', ['slug' => $sug['slug']]); ?>" class="w-9 h-9 rounded-full bg-[#d4af37] flex items-center justify-center text-white hover:bg-white hover:text-[#d4af37] transition-colors shadow-lg">
                                                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </aside>
        </div>
    </main>

    <!-- Related Posts Section -->
    <?php if (!empty($related_posts)): ?>
    <section class="py-24 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-[#d4af37] font-bold tracking-widest uppercase text-sm mb-3 block">Keep Reading</span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-playfair font-bold text-gray-900"><?php _e('blog_page.related_posts'); ?></h2>
                <div class="w-24 h-1 bg-[#d4af37] mx-auto mt-6 rounded-full"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-10">
                <?php foreach ($related_posts as $related): ?>
                    <a href="<?php echo route('chi-tiet-tin-tuc', ['slug' => $related['slug']]); ?>" class="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full transform hover:-translate-y-1">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="<?php echo imgUrl($related['featured_image'], 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 shadow-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-[#d4af37]">visibility</span> <?php echo number_format($related['views'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="p-6 md:p-8 flex-1 flex flex-col">
                            <span class="text-xs font-bold text-[#d4af37] uppercase tracking-wider mb-3 block"><?php echo date('M d, Y', strtotime($related['published_at'])); ?></span>
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-[#d4af37] transition-colors mb-3 line-clamp-2 leading-snug font-playfair"><?php echo htmlspecialchars(_f($related, 'title')); ?></h3>
                            <p class="text-gray-500 text-sm line-clamp-3 leading-relaxed mb-6 flex-1"><?php echo htmlspecialchars(_f($related, 'excerpt') ?? ''); ?></p>
                            <div class="mt-auto flex items-center text-sm font-bold text-gray-900 group-hover:text-[#d4af37] transition-colors uppercase tracking-wider">
                                Read Article <span class="material-symbols-outlined text-[18px] ml-2 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="<?php echo assetVersion('js/main.js'); ?>"></script>
    <script src="<?php echo assetVersion('js/blog-detail.js'); ?>"></script>

    <!-- Lightbox -->
    <div class="fixed inset-0 z-[100] bg-gray-900/95 backdrop-blur-sm flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300" id="lightbox">
        <span class="absolute top-6 right-6 text-white/70 cursor-pointer hover:text-white hover:scale-110 transition-all material-symbols-outlined text-4xl bg-black/20 rounded-full p-2" onclick="closeLightbox()">close</span>
        <button class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-white/10 hover:bg-[#d4af37] text-white flex items-center justify-center transition-all shadow-lg backdrop-blur-sm" onclick="lightboxNav(-1)">
            <span class="material-symbols-outlined text-3xl">chevron_left</span>
        </button>
        <img src="" alt="Lightbox image" id="lightboxImg" class="max-h-[85vh] max-w-[90vw] object-contain rounded-xl shadow-2xl transform scale-95 transition-transform duration-300">
        <button class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-white/10 hover:bg-[#d4af37] text-white flex items-center justify-center transition-all shadow-lg backdrop-blur-sm" onclick="lightboxNav(1)">
            <span class="material-symbols-outlined text-3xl">chevron_right</span>
        </button>
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/90 font-bold bg-black/40 backdrop-blur-md px-6 py-2 rounded-full tracking-widest shadow-lg"><span id="lightboxCurrent">1</span> / <span id="lightboxTotal">1</span></div>
    </div>
</body>
</html>