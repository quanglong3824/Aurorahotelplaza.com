<?php

class FrontSharedController {
    public static function getHeaderData() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ─── AI Error Tracker ─────────────────────────────────────────────────────────
        require_once __DIR__ . '/../helpers/error-tracker.php';
        AuroraErrorTracker::init();

        // Prevent browser caching of pages with user data
        if (isset($_SESSION['user_id'])) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }

        // Load environment configuration if not loaded
        if (!defined('BASE_URL')) {
            require_once __DIR__ . '/../config/environment.php';
        }

        // Load language helper
        require_once __DIR__ . '/../helpers/language.php';
        $current_lang = initLanguage();

        // Load DB bilingual helper
        require_once __DIR__ . '/../helpers/lang-db.php';

        // Load session helper
        require_once __DIR__ . '/../helpers/session-helper.php';

        // Check user session
        if (isset($_SESSION['user_id'])) {
            $last_verify = $_SESSION['last_user_verify'] ?? 0;
            if (time() - $last_verify > 300) {
                if (!verifyUserExists()) {
                    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                    $subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin', 'services-pages', 'payment'];
                    $redirect_path = in_array($current_dir, $subdirs) ? '../index.php' : 'index.php';
                    header('Location: ' . $redirect_path . '?logged_out=account_removed');
                    exit;
                }
                $_SESSION['last_user_verify'] = time();
            }
        }

        // Determine base path
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        $subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin'];
        $base_path = in_array($current_dir, $subdirs) ? '../' : '';

        // User info
        $is_logged_in = isset($_SESSION['user_id']);
        $user_name = $_SESSION['user_name'] ?? 'User';
        $user_role = $_SESSION['user_role'] ?? 'customer';

        // Page specific logic
        $current_page = basename($_SERVER['PHP_SELF'], '.php');
        $pages_with_hero = ['index', 'rooms', 'apartments', 'about', 'services', 'gallery', 'explore', 'wedding', 'conference', 'restaurant', 'office', 'contact', 'login', 'register', 'forgot-password', 'reset-password', 'blog', 'confirmation'];
        $has_hero = in_array($current_page, $pages_with_hero) || in_array($current_dir, ['room-details', 'apartment-details', 'booking']);

        $pages_solid_header = [];
        $is_solid_page = in_array($current_page, $pages_solid_header);
        $header_class = $has_hero ? 'header-transparent' : 'header-solid';
        $force_scrolled = $is_solid_page ? 'header-scrolled' : '';

        $pages_fixed_transparent = ['blog', 'blog-detail', 'login', 'register', 'forgot-password', 'services', 'service-detail', 'about', 'contact', 'cancellation-policy', 'privacy', 'terms', 'rooms', 'apartments', 'explore', 'index', 'bookings', 'loyalty', 'edit', 'booking-detail', 'confirmation'];
        $is_fixed_transparent = in_array($current_page, $pages_fixed_transparent) || in_array($current_dir, ['profile', 'room-details', 'apartment-details', 'booking']);

        return [
            'current_lang' => $current_lang,
            'base_path' => $base_path,
            'is_logged_in' => $is_logged_in,
            'user_name' => $user_name,
            'user_role' => $user_role,
            'has_hero' => $has_hero,
            'is_solid_page' => $is_solid_page,
            'header_class' => $header_class,
            'force_scrolled' => $force_scrolled,
            'is_fixed_transparent' => $is_fixed_transparent,
            'current_dir' => $current_dir
        ];
    }

    public static function getFooterData($base_path_override = null) {
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        $base_path = $base_path_override ?? (($current_dir == 'room-details' || $current_dir == 'apartment-details' || $current_dir == 'services-pages') ? '../' : '');

        if (!function_exists('__')) {
            require_once __DIR__ . '/../helpers/language.php';
            initLanguage();
        }

        return [
            'base_path' => $base_path,
            'current_year' => date('Y')
        ];
    }

    /**
     * Khởi tạo Chat Guest (Phải gọi trước khi có output)
     */
    public static function initChatGuest() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $timestamp = time();
            $_SESSION['chat_guest_id'] = 'guest_' . md5($ip . $timestamp);
            $_SESSION['guest_created_at'] = $timestamp;

            if (!isset($_COOKIE['chat_guest_id'])) {
                setcookie('chat_guest_id', $_SESSION['chat_guest_id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }
        }
    }

    public static function getChatWidgetData($base_path = '') {
        // Prevent widget in admin
        $current_path = $_SERVER['PHP_SELF'] ?? '';
        if (strpos($current_path, '/admin/') !== false) {
            return ['show_widget' => false];
        }

        $is_logged = isset($_SESSION['user_id']);
        $user_name = $is_logged
            ? ($_SESSION['user_name'] ?? __('chat.guest'))
            : ('Khách ' . substr($_SESSION['chat_guest_id'] ?? '', -6));
        $user_init = mb_strtoupper(mb_substr($user_name, 0, 1)) ?: '?';

        if (!defined('BASE_URL')) {
            require_once __DIR__ . '/../config/environment.php';
        }
        $cw_base = rtrim(BASE_URL, '/');

        return [
            'show_widget' => true,
            'is_logged' => $is_logged,
            'guest_id' => $_SESSION['chat_guest_id'] ?? null,
            'user_name' => $user_name,
            'user_init' => $user_init,
            'cw_base' => $cw_base,
            'base_path' => $base_path
        ];
    }

    public static function getHeroSliderData() {
        return [
            'current_date' => date('Y-m-d'),
            'next_date' => date('Y-m-d', strtotime('+1 day'))
        ];
    }
}
