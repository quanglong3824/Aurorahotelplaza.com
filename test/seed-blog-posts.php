<?php
// A simple script to seed the 'posts' table with sample data.
// How to run:
// 1. Log in to your admin account on the website.
// 2. In the same browser, access this file: http://your-domain.com/test/seed-blog-posts.php

// This script MUST be run from a browser with an active admin session.

session_start();

// Must be logged in to run this script.
if (!isset($_SESSION['user_id'])) {
    die('<pre>Error: You must be logged in as an admin to run this seeder. Please log in to the website and try again.</pre>');
}

echo "<pre>"; // For cleaner browser output

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php'; // Assuming slug function might be here, or define it locally.

// Function to create a URL-friendly slug if not available globally
if (!function_exists('createSlug')) {
    function createSlug($string) {
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $string);
        $string = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $string);
        $string = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $string);
        $string = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $string);
        $string = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $string);
        $string = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $string);
        $string = preg_replace('/(đ)/', 'd', $string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = trim($string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return $string;
    }
}

// Function to get or create a category ID
function getOrCreateCategoryId(PDO $db, $categoryName, $author_id) {
    global $createSlug; // Access the createSlug function

    // Check if category exists
    $stmt = $db->prepare("SELECT category_id FROM blog_categories WHERE category_name = :category_name");
    $stmt->execute([':category_name' => $categoryName]);
    $categoryId = $stmt->fetchColumn();

    if ($categoryId) {
        return $categoryId;
    }

    // If not, create it
    $slug = $createSlug($categoryName); // Use the global createSlug function
    $stmt = $db->prepare(
        "INSERT INTO blog_categories (category_name, slug, description, created_at)
         VALUES (:category_name, :slug, :description, NOW())"
    );
    $stmt->execute([
        ':category_name' => $categoryName,
        ':slug' => $slug,
        ':description' => "Category for " . $categoryName
    ]);
    return $db->lastInsertId();
}


try {
    $db = getDB();
    // Set PDO to throw exceptions on error
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to the database successfully.\n";

    // --- Sample Blog Posts ---
    // Use the logged-in user's ID as the author ID.
    $author_id = $_SESSION['user_id'];

    echo "Using logged-in user ID: {$author_id} as author.\n\n";

    $posts = [
        [
            'title' => 'Chào Mừng Đến Aurora Hotel Plaza: Ngôi Nhà Mới Của Bạn Tại Biên Hòa',
            'excerpt' => 'Khám phá sự sang trọng và tiện nghi ngay tại trung tâm thành phố Biên Hòa. Aurora Hotel Plaza không chỉ là một nơi để ở, mà là một trải nghiệm.',
            'content' => '<p>Chào mừng quý khách đến với Aurora Hotel Plaza! Tọa lạc tại vị trí đắc địa của thành phố Biên Hòa sôi động, khách sạn của chúng tôi là sự kết hợp hoàn hảo giữa thiết kế hiện đại, dịch vụ đẳng cấp và lòng hiếu khách nồng hậu.</p><p>Tại đây, chúng tôi cung cấp đa dạng các loại phòng và căn hộ cao cấp, đáp ứng mọi nhu cầu từ du lịch nghỉ dưỡng đến công tác dài ngày. Mỗi không gian đều được trang bị đầy đủ tiện nghi, mang lại cho bạn cảm giác thoải mái như ở nhà.</p><p>Hãy bắt đầu hành trình khám phá Biên Hòa cùng chúng tôi!</p>',
            'category' => 'Thông Báo',
            'featured_image' => 'assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg'
        ],
        [
            'title' => 'Top 5 Địa Điểm Không Thể Bỏ Lỡ Khi Du Lịch Biên Hòa',
            'excerpt' => 'Biên Hòa không chỉ là trung tâm công nghiệp. Hãy cùng Aurora Hotel Plaza khám phá những viên ngọc ẩn mình của thành phố này.',
            'content' => '<p>Bạn đang lên kế hoạch cho chuyến đi đến Biên Hòa? Đừng bỏ lỡ những địa điểm thú vị này:</p><ul><li><strong>Khu du lịch Bửu Long:</strong> Được mệnh danh là "Vịnh Hạ Long thu nhỏ", nơi đây có cảnh quan sơn thủy hữu tình, thích hợp cho các hoạt động dã ngoại.</li><li><strong>Làng gốm Tân Vạn:</strong> Khám phá nét văn hóa truyền thống và tự tay làm nên những sản phẩm gốm độc đáo.</li><li><strong>Văn miếu Trấn Biên:</strong> Văn miếu đầu tiên được xây dựng tại xứ Đàng Trong, một di tích lịch sử và văn hóa quan trọng.</li><li><strong>Chùa Ông (Thất Phủ Cổ Miếu):</strong> Một ngôi chùa cổ kính với kiến trúc đặc trưng của người Hoa.</li><li><strong>Công viên Nguyễn Văn Trị:</strong> Dạo mát bên bờ sông Đồng Nai và thưởng thức ẩm thực đường phố.</li></ul><p>Aurora Hotel Plaza có vị trí thuận lợi để bạn dễ dàng khám phá tất cả những địa điểm trên.</p>',
            'category' => 'Du Lịch',
            'featured_image' => 'assets/img/gallery/gallery-5.jpg'
        ],
        [
            'title' => 'Thưởng Thức Ẩm Thực Tinh Tế Tại Nhà Hàng Aurora',
            'excerpt' => 'Nhà hàng của chúng tôi phục vụ một thực đơn đa dạng từ các món ăn truyền thống Việt Nam đến ẩm thực quốc tế, được chế biến bởi các đầu bếp tài hoa.',
            'content' => '<p>Trải nghiệm ẩm thực là một phần không thể thiếu trong mỗi kỳ nghỉ. Tại nhà hàng Aurora, chúng tôi tự hào mang đến cho thực khách một hành trình vị giác khó quên.</p><p>Thực đơn của chúng tôi được lấy cảm hứng từ những nguyên liệu tươi ngon nhất theo mùa, kết hợp giữa hương vị truyền thống và phong cách chế biến hiện đại. Không gian nhà hàng sang trọng, ấm cúng là nơi lý tưởng cho những bữa tối lãng mạn, những buổi gặp gỡ đối tác hay những bữa ăn gia đình đầm ấm.</p><p>Đừng quên thử món Phở Thìn trứ danh hoặc bò bít-tết mềm tan của chúng tôi. Chúng tôi luôn sẵn sàng phục vụ!</p>',
            'category' => 'Ẩm Thực',
            'featured_image' => 'assets/img/services/nha-hang.jpg'
        ],
        [
            'title' => 'Ưu Đãi Đặc Biệt: Gói "Kỳ Nghỉ Trọn Vẹn" Cuối Năm',
            'excerpt' => 'Lên kế hoạch cho kỳ nghỉ cuối năm của bạn ngay hôm nay với gói ưu đãi đặc biệt từ Aurora Hotel Plaza. Tiết kiệm hơn, trải nghiệm nhiều hơn.',
            'content' => '<p>Chào đón mùa lễ hội, Aurora Hotel Plaza trân trọng giới thiệu gói ưu đãi "Kỳ Nghỉ Trọn Vẹn" dành cho tất cả khách hàng.</p><p><strong>Gói ưu đãi bao gồm:</strong></p><ul><li>Giảm giá 20% trên giá phòng công bố.</li><li>Miễn phí bữa sáng buffet hàng ngày cho 2 người.</li><li>Tặng một voucher trị giá 200,000 VND tại nhà hàng Aurora.</li><li>Miễn phí sử dụng hồ bơi và phòng gym.</li></ul><p>Chương trình áp dụng từ hôm nay đến hết ngày 31/12. Đặt phòng ngay để không bỏ lỡ ưu đãi hấp dẫn này!</p>',
            'category' => 'Khuyến Mãi',
            'featured_image' => 'assets/img/backgrounds/event-bg.jpeg'
        ]
    ];

    // Prepare statement
    $stmt = $db->prepare(
        "INSERT INTO blog_posts (title, slug, excerpt, content, author_id, status, category_id, featured_image, published_at, created_at, updated_at) 
         VALUES (:title, :slug, :excerpt, :content, :author_id, 'published', :category, :featured_image, NOW(), NOW(), NOW())"
    );

    $count = 0;
    foreach ($posts as $post) {
        $slug = createSlug($post['title']);

        // Get or create category ID
        $categoryId = getOrCreateCategoryId($db, $post['category'], $author_id);

        // Check if slug already exists to avoid duplicates
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE slug = ?");
        $checkStmt->execute([$slug]);
        if ($checkStmt->fetchColumn() > 0) {
            echo "Skipping post '{$post['title']}' - slug '{$slug}' already exists.\n";
            continue;
        }

        $stmt->execute([
            ':title' => $post['title'],
            ':slug' => $slug,
            ':excerpt' => $post['excerpt'],
            ':content' => $post['content'],
            ':author_id' => $author_id,
            ':category_id' => $categoryId,
            ':featured_image' => $post['featured_image']
        ]);
        $count++;
        echo "Inserted post: '{$post['title']}'\n";
    }

    echo "\nSuccessfully inserted {$count} sample blog posts.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("General error: " . $e->getMessage());
}

echo "</pre>";
?>
