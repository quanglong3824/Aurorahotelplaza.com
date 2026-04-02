<?php
/**
 * 404 Error Page - Aurora Hotel Plaza
 * Một tệp duy nhất tích hợp đầy đủ giao diện, ngôn ngữ và logic
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thử tải cấu hình môi trường và ngôn ngữ
$config_path = __DIR__ . '/config/environment.php';
$lang_path = __DIR__ . '/helpers/language.php';

if (file_exists($config_path)) {
    require_once $config_path;
}
if (file_exists($lang_path)) {
    require_once $lang_path;
    $current_lang = initLanguage();
} else {
    $current_lang = $_SESSION['lang'] ?? 'vi';
}

// Thiết lập tiêu đề và nội dung dựa trên ngôn ngữ
$is_vi = ($current_lang === 'vi');
$title = $is_vi ? "404 - Không tìm thấy trang | Aurora Hotel Plaza" : "404 - Page Not Found | Aurora Hotel Plaza";
$heading = $is_vi ? "Ôi! Trang này đã biến mất" : "Oops! This page is missing";
$message = $is_vi ? "Có vẻ như đường dẫn bạn đang truy cập không tồn tại hoặc đã được di chuyển sang một vị trí mới sang trọng hơn." : "It looks like the link you are accessing does not exist or has been moved to a more luxurious location.";
$btn_home = $is_vi ? "Về trang chủ" : "Back to Home";
$btn_booking = $is_vi ? "Đặt phòng ngay" : "Book Now";

// Xác định base URL để load assets
$base_url = defined('BASE_URL') ? BASE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #020617; /* Slate 950 - Deep dark theme */
            color: #f8fafc;
            overflow: hidden;
        }

        .playfair {
            font-family: 'Playfair Display', serif;
        }

        /* Animated Background Gradient */
        .bg-gradient-animate {
            background: radial-gradient(circle at 50% 50%, #1e293b 0%, #020617 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .bg-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            animation: float 20s infinite alternate;
        }

        @keyframes float {
            0% { transform: translate(-10%, -10%) scale(1); }
            100% { transform: translate(20%, 20%) scale(1.2); }
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .text-gradient {
            background: linear-gradient(to right, #60a5fa, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-aurora {
            background: linear-gradient(45deg, #2563eb, #7c3aed);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-aurora:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
            filter: brightness(1.1);
        }

        .number-404 {
            font-size: clamp(8rem, 25vw, 15rem);
            font-weight: 800;
            line-height: 1;
            opacity: 0.1;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            user-select: none;
            color: #3b82f6;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <!-- Background Elements -->
    <div class="bg-gradient-animate"></div>
    <div class="bg-glow" style="top: -10%; left: -10%;"></div>
    <div class="bg-glow" style="bottom: -10%; right: -10%; animation-delay: -5s; background: radial-gradient(circle, rgba(168, 85, 247, 0.1) 0%, rgba(168, 85, 247, 0) 70%);"></div>

    <div class="number-404 playfair">404</div>

    <!-- Main Content Card -->
    <div class="glass-card max-w-2xl w-full rounded-[2rem] p-8 md:p-16 text-center relative z-10">
        <!-- Logo Placeholder or Icon -->
        <div class="mb-8 inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-500/10 border border-blue-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>

        <h1 class="text-4xl md:text-5xl font-bold mb-6 playfair text-gradient leading-tight">
            <?php echo $heading; ?>
        </h1>
        
        <p class="text-slate-400 text-lg mb-10 leading-relaxed max-w-md mx-auto font-light">
            <?php echo $message; ?>
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?php echo route(''); ?>" class="btn-aurora px-8 py-4 rounded-xl font-semibold text-white w-full sm:w-auto text-lg">
                <?php echo $btn_home; ?>
            </a>
            <a href="<?php echo route('dat-phong'); ?>" class="px-8 py-4 rounded-xl border border-slate-700 hover:bg-slate-800 transition-colors font-semibold text-slate-300 w-full sm:w-auto text-lg">
                <?php echo $btn_booking; ?>
            </a>
        </div>

        <!-- Decorative Footer -->
        <div class="mt-16 pt-8 border-t border-slate-800/50">
            <p class="text-slate-500 text-sm uppercase tracking-widest">
                Aurora Hotel Plaza &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </div>

    <!-- Simple Animation Script -->
    <script>
        document.addEventListener('mousemove', (e) => {
            const glow = document.querySelector('.bg-glow');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            glow.style.transform = `translate(${x * 50}px, ${y * 50}px)`;
        });
    </script>
</body>
</html>