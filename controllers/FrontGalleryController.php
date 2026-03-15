<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
require_once 'helpers/image-helper.php';

class FrontGalleryController {
    public function getData() {
        initLanguage();

        // Cấu hình phân trang
        $images_per_page = 12;
        $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $current_category = isset($_GET['category']) ? $_GET['category'] : 'all';

        // Lấy dữ liệu từ database
        $all_images = [];
        $category_counts = [];

        try {
            $db = getDB();

            // Lấy tất cả ảnh active
            $stmt = $db->prepare("
                SELECT gallery_id, title, image_url as src, category 
                FROM gallery 
                WHERE status = 'active' 
                ORDER BY sort_order ASC, gallery_id ASC
            ");
            $stmt->execute();
            $all_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Đếm số lượng theo category
            $stmt = $db->prepare("
                SELECT category, COUNT(*) as count 
                FROM gallery 
                WHERE status = 'active' 
                GROUP BY category
            ");
            $stmt->execute();
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($counts as $row) {
                $category_counts[$row['category']] = $row['count'];
            }
        } catch (Exception $e) {
            error_log("Gallery error: " . $e->getMessage());
            $all_images = [];
        }

        // Lọc theo danh mục
        if ($current_category === 'all') {
            $filtered_images = $all_images;
        } else {
            $filtered_images = array_filter($all_images, function ($img) use ($current_category) {
                return $img['category'] === $current_category;
            });
        }
        $filtered_images = array_values($filtered_images);

        // Tính toán phân trang
        $total_images = count($filtered_images);
        $total_pages = max(1, ceil($total_images / $images_per_page));
        $current_page = max(1, min($current_page, $total_pages));
        $offset = ($current_page - 1) * $images_per_page;
        $page_images = array_slice($filtered_images, $offset, $images_per_page);

        // Danh mục với số lượng từ database
        $categories = [
            'all' => ['name' => __('gallery_page.all'), 'icon' => 'apps', 'count' => count($all_images)],
            'rooms' => ['name' => __('gallery_page.rooms'), 'icon' => 'hotel', 'count' => $category_counts['rooms'] ?? 0],
            'apartments' => ['name' => __('gallery_page.apartments'), 'icon' => 'apartment', 'count' => $category_counts['apartments'] ?? 0],
            'restaurant' => ['name' => __('gallery_page.restaurant'), 'icon' => 'restaurant', 'count' => $category_counts['restaurant'] ?? 0],
            'facilities' => ['name' => __('gallery_page.facilities'), 'icon' => 'fitness_center', 'count' => $category_counts['facilities'] ?? 0],
            'events' => ['name' => __('gallery_page.events'), 'icon' => 'celebration', 'count' => $category_counts['events'] ?? 0],
            'exterior' => ['name' => __('gallery_page.exterior'), 'icon' => 'location_city', 'count' => $category_counts['exterior'] ?? 0],
        ];

        // Category names for display
        $category_names = [
            'rooms' => __('gallery_page.rooms'),
            'apartments' => __('gallery_page.apartments'),
            'restaurant' => __('gallery_page.restaurant'),
            'facilities' => __('gallery_page.facilities'),
            'events' => __('gallery_page.events'),
            'exterior' => __('gallery_page.exterior'),
        ];

        return [
            'all_images' => $all_images,
            'category_counts' => $category_counts,
            'filtered_images' => $filtered_images,
            'page_images' => $page_images,
            'total_images' => $total_images,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'current_category' => $current_category,
            'categories' => $categories,
            'category_names' => $category_names,
            'offset' => $offset,
            'lang' => getLang()
        ];
    }
}
