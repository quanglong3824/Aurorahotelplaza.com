<?php
// Admin Header with Sidebar Navigation

// Load session helper
require_once __DIR__ . '/../../helpers/session-helper.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kiểm tra user còn tồn tại và active (mỗi 5 phút)
$last_verify = $_SESSION['last_user_verify'] ?? 0;
if (time() - $last_verify > 300) {
    if (!verifyUserExists('../auth/login.php')) {
        exit; // verifyUserExists đã redirect
    }
    $_SESSION['last_user_verify'] = time();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $page_title ?? 'Quản trị'; ?> - Aurora Hotel Plaza</title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/admin-enhanced.css">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            background: #f8fafc;
        }
        
        .dark body {
            background: #0f172a;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }
        
        .sidebar-link:hover {
            background: #f1f5f9;
            color: #6366f1;
            transform: translateX(4px);
        }
        
        .dark .sidebar-link:hover {
            background: #1e293b;
        }
        
        .sidebar-link.active {
            background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }
        
        .sidebar-link .material-symbols-outlined {
            font-size: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .dark .stat-card {
            background: #1e293b;
            border-color: #334155;
        }
        .data-table {
            @apply w-full border-collapse;
        }
        .data-table th {
            @apply bg-surface-light dark:bg-surface-dark px-4 py-3 text-left text-sm font-semibold;
        }
        .data-table td {
            @apply px-4 py-3 border-t border-border-light dark:border-border-dark;
        }
        .badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .badge-success {
            @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
        }
        .badge-warning {
            @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
        }
        .badge-danger {
            @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
        }
        .badge-info {
            @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
        }
        .badge-secondary {
            @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">

<!-- Sidebar Toggle Button (Mobile) -->
<button id="sidebarToggle" class="fixed top-4 left-4 z-50 lg:hidden bg-accent text-white p-2 rounded-lg shadow-lg">
    <span class="material-symbols-outlined">menu</span>
</button>

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 overflow-y-auto shadow-xl">
    <div class="p-5">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-8 px-2">
            <div class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center shadow-lg relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent"></div>
                <span class="material-symbols-outlined text-white text-2xl relative z-10 font-bold">hotel</span>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Aurora Hotel Plaza</h1>
                <p class="text-xs font-semibold" style="color: #d4af37;">★★★★★ Luxury</p>
            </div>
        </div>

        <!-- User Info -->
        <div class="mb-6 p-4 bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 dark:from-slate-800 dark:to-slate-800 rounded-xl border-2 border-[#d4af37]/30 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center shadow-md relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent"></div>
                    <span class="material-symbols-outlined text-white text-xl relative z-10 font-bold">person</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate text-gray-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?php 
                        $role_names = [
                            'admin' => 'Quản trị viên',
                            'sale' => 'Sale',
                            'receptionist' => 'Lễ tân'
                        ];
                        echo $role_names[$_SESSION['user_role']] ?? $_SESSION['user_role'];
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-1">
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>

            <!-- Bookings -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Đặt phòng</p>
            </div>
            <a href="bookings.php" class="sidebar-link <?php echo $current_page === 'bookings' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">book_online</span>
                <span>Quản lý đặt phòng</span>
            </a>
            <a href="calendar.php" class="sidebar-link <?php echo $current_page === 'calendar' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>Lịch đặt phòng</span>
            </a>
            <a href="refunds.php" class="sidebar-link <?php echo $current_page === 'refunds' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">payments</span>
                <span>Hoàn tiền</span>
            </a>

            <!-- Rooms -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Phòng</p>
            </div>
            <a href="room-types.php" class="sidebar-link <?php echo $current_page === 'room-types' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">meeting_room</span>
                <span>Loại phòng</span>
            </a>
            <a href="rooms.php" class="sidebar-link <?php echo $current_page === 'rooms' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">hotel</span>
                <span>Danh sách phòng</span>
            </a>
            <a href="room-map.php" class="sidebar-link <?php echo $current_page === 'room-map' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">map</span>
                <span>Sơ đồ phòng</span>
            </a>
            <a href="pricing.php" class="sidebar-link <?php echo $current_page === 'pricing' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">payments</span>
                <span>Quản lý giá</span>
            </a>

            <!-- Customers -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Khách hàng</p>
            </div>
            <a href="customers.php" class="sidebar-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">people</span>
                <span>Khách hàng</span>
            </a>
            <a href="loyalty.php" class="sidebar-link <?php echo $current_page === 'loyalty' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">loyalty</span>
                <span>Chương trình thành viên</span>
            </a>
            <a href="reviews.php" class="sidebar-link <?php echo $current_page === 'reviews' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">star</span>
                <span>Đánh giá</span>
            </a>
            <a href="contacts.php" class="sidebar-link <?php echo $current_page === 'contacts' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">contact_mail</span>
                <span>Liên hệ</span>
            </a>

            <!-- Services -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Dịch vụ</p>
            </div>
            <a href="service-packages.php" class="sidebar-link <?php echo $current_page === 'service-packages' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">inventory_2</span>
                <span>Dịch vụ & Gói</span>
            </a>
            <a href="services.php" class="sidebar-link <?php echo $current_page === 'services' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">room_service</span>
                <span>Dịch vụ phụ</span>
            </a>
            <a href="service-bookings.php" class="sidebar-link <?php echo $current_page === 'service-bookings' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">list_alt</span>
                <span>Đơn dịch vụ</span>
            </a>

            <!-- Marketing -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Marketing</p>
            </div>
            <a href="promotions.php" class="sidebar-link <?php echo $current_page === 'promotions' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">local_offer</span>
                <span>Khuyến mãi</span>
            </a>
            <a href="banners.php" class="sidebar-link <?php echo $current_page === 'banners' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">image</span>
                <span>Banner</span>
            </a>

            <!-- Content -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Nội dung</p>
            </div>
            <a href="blog.php" class="sidebar-link <?php echo $current_page === 'blog' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">article</span>
                <span>Blog</span>
            </a>
            <a href="gallery.php" class="sidebar-link <?php echo $current_page === 'gallery' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">photo_library</span>
                <span>Thư viện ảnh</span>
            </a>
            <a href="faqs.php" class="sidebar-link <?php echo $current_page === 'faqs' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">help</span>
                <span>FAQs</span>
            </a>

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <!-- System -->
            <div class="mt-6 mb-2">
                <p class="px-4 text-xs font-semibold text-text-secondary-light dark:text-text-secondary-dark uppercase tracking-wider">Hệ thống</p>
            </div>
            <a href="users.php" class="sidebar-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">manage_accounts</span>
                <span>Người dùng</span>
            </a>
            <a href="permissions.php" class="sidebar-link <?php echo $current_page === 'permissions' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span>Phân quyền</span>
            </a>
            <a href="activity-logs.php" class="sidebar-link <?php echo $current_page === 'activity-logs' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">history</span>
                <span>Nhật ký hoạt động</span>
            </a>
            <a href="reports.php" class="sidebar-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">analytics</span>
                <span>Báo cáo</span>
            </a>
            <a href="notifications.php" class="sidebar-link <?php echo $current_page === 'notifications' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">notifications</span>
                <span>Thông báo</span>
            </a>
            <a href="settings.php" class="sidebar-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">settings</span>
                <span>Cài đặt</span>
            </a>
            <?php endif; ?>

            <!-- Logout -->
            <div class="mt-6 pt-6 border-t border-border-light dark:border-border-dark">
                <a href="../auth/logout.php" class="sidebar-link text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </nav>
    </div>
</aside>

<!-- Main Content Area -->
<div class="lg:ml-64">
    <!-- Top Header -->
    <header class="sticky top-0 z-30 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 backdrop-blur-sm bg-white/95 dark:bg-slate-900/95">
        <div class="flex items-center justify-between px-8 py-5">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $page_title ?? 'Admin Panel'; ?></h2>
                <?php if (isset($page_subtitle)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo $page_subtitle; ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
                <!-- Notifications -->
                <div class="relative notification-dropdown">
                    <button onclick="toggleNotifications()" class="relative p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-gray-600 dark:text-gray-300">notifications</span>
                        <span id="notificationBadge" class="absolute top-2 right-2 w-5 h-5 bg-red-500 text-white text-xs rounded-full ring-2 ring-white dark:ring-slate-900 flex items-center justify-center font-bold hidden">0</span>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 z-50">
                        <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900 dark:text-white">Thông báo</h3>
                            <a href="notifications.php" class="text-sm text-accent hover:underline">Xem tất cả</a>
                        </div>
                        <div id="notificationList" class="max-h-96 overflow-y-auto">
                            <div class="p-8 text-center text-gray-500">
                                <span class="material-symbols-outlined text-4xl mb-2">notifications_off</span>
                                <p>Đang tải...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Theme Toggle -->
                <button id="themeToggle" class="p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-gray-600 dark:text-gray-300">dark_mode</span>
                </button>
                
                <!-- Back to Site -->
                <a href="../index.php" target="_blank" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 font-bold relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    <span class="material-symbols-outlined text-sm relative z-10">open_in_new</span>
                    <span class="text-sm relative z-10">Xem website</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="p-8 min-h-screen">

<script>
// Notification System
let notificationDropdownOpen = false;

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    notificationDropdownOpen = !notificationDropdownOpen;
    
    if (notificationDropdownOpen) {
        dropdown.classList.remove('hidden');
        loadNotifications();
    } else {
        dropdown.classList.add('hidden');
    }
}

function loadNotifications() {
    fetch('api/get-notifications.php?limit=5')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function displayNotifications(notifications) {
    const list = document.getElementById('notificationList');
    
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="p-8 text-center text-gray-500">
                <span class="material-symbols-outlined text-4xl mb-2">notifications_off</span>
                <p>Không có thông báo mới</p>
            </div>
        `;
        return;
    }
    
    const typeColors = {
        'booking': 'blue',
        'payment': 'green',
        'review': 'yellow',
        'service': 'purple',
        'system': 'gray',
        'user': 'indigo'
    };
    
    list.innerHTML = notifications.map(notif => {
        const color = typeColors[notif.type] || 'gray';
        const timeAgo = getTimeAgo(notif.created_at);
        const unreadClass = notif.is_read == 0 ? 'bg-blue-50 dark:bg-blue-900/20' : '';
        
        return `
            <div class="p-4 border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors ${unreadClass}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-${color}-100 dark:bg-${color}-900 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-${color}-600 text-xl">${notif.icon}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-semibold text-sm text-gray-900 dark:text-white">
                                ${notif.title}
                                ${notif.is_read == 0 ? '<span class="inline-block w-2 h-2 bg-red-500 rounded-full ml-1"></span>' : ''}
                            </h4>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">${notif.message}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-gray-500">${timeAgo}</span>
                            ${notif.link ? `<a href="${notif.link}" class="text-xs text-accent hover:underline">Xem chi tiết</a>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

function getTimeAgo(datetime) {
    const time = new Date(datetime).getTime();
    const now = new Date().getTime();
    const diff = Math.floor((now - time) / 1000);
    
    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';
    
    return new Date(time).toLocaleDateString('vi-VN');
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load initial count
    fetch('api/get-notifications.php?limit=1&unread_only=true')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
            }
        });
    
    // Refresh every 30 seconds
    setInterval(() => {
        fetch('api/get-notifications.php?limit=1&unread_only=true')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                }
            });
    }, 30000);
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const button = event.target.closest('.notification-dropdown');
    
    if (!button && notificationDropdownOpen) {
        dropdown.classList.add('hidden');
        notificationDropdownOpen = false;
    }
});
</script>
