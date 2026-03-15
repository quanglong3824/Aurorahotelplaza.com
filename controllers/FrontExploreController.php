<?php
require_once 'config/environment.php';
require_once 'config/database.php';
require_once 'config/performance.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';

class FrontExploreController {
    public function getData() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        initLanguage();

        $db = getDB();
        
        // Fetch featured rooms
        $featured_rooms = [];
        try {
            $stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' AND category = 'room' ORDER BY sort_order ASC LIMIT 4");
            $stmt->execute();
            $featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Explore page error: " . $e->getMessage());
        }

        // Fetch featured apartments
        $featured_apartments = [];
        try {
            $stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' AND category = 'apartment' ORDER BY sort_order ASC LIMIT 4");
            $stmt->execute();
            $featured_apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Explore page (apartments) error: " . $e->getMessage());
        }

        // Fetch featured services
        $featured_services = [];
        try {
            $stmt = $db->prepare("SELECT * FROM services WHERE is_available = 1 AND is_featured = 1 ORDER BY sort_order ASC LIMIT 6");
            $stmt->execute();
            $featured_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Explore page (services) error: " . $e->getMessage());
        }

        // Fetch latest blog posts
        $latest_posts = [];
        try {
            $stmt = $db->prepare("SELECT p.*, u.full_name as author_name FROM blog_posts p LEFT JOIN users u ON p.author_id = u.user_id WHERE p.status = 'published' ORDER BY p.published_at DESC LIMIT 3");
            $stmt->execute();
            $latest_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Explore page (blog) error: " . $e->getMessage());
        }

        // Stats
        $stats = ['total_rooms' => 150, 'happy_customers' => 5000, 'years_experience' => 10];
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'inactive'");
            $stats['total_rooms'] = $stmt->fetchColumn() ?: 150;
            $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE status IN ('completed', 'checked_out')");
            $stats['happy_customers'] = $stmt->fetchColumn() ?: 5000;
        } catch (Exception $e) {
        }

        return [
            'featured_rooms' => $featured_rooms,
            'featured_apartments' => $featured_apartments,
            'featured_services' => $featured_services,
            'latest_posts' => $latest_posts,
            'stats' => $stats,
            'lang' => getLang()
        ];
    }
}
