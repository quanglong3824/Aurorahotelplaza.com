<?php
// Start session for user authentication
session_start();

// Load environment configuration
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/image-helper.php';

// Fetch featured rooms from database
$featured_rooms = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = 'room'
        ORDER BY sort_order ASC
        LIMIT 3
    ");
    $stmt->execute();
    $featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Aurora Hotel Plaza - Khách sạn sang trọng tại Biên Hòa</title>

    <!-- Tailwind CSS -->
    <script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
    <link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet" />

    <!-- Tailwind Configuration -->
    <script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <?php include 'includes/hero-slider.php'; ?>

            <!-- About Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="about">
                <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4">
                    <div class="flex flex-col gap-4 text-center"></div>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Chào mừng đến với Aurora Hotel Plaza</h2>
                        <p class="mx-auto max-w-3xl text-base leading-relaxed text-text-secondary-light dark:text-text-secondary-dark">
                            Tọa lạc tại trung tâm thành phố Biên Hòa, Aurora Hotel Plaza mang đến trải nghiệm sang trọng
                            và thanh bình vô song. Cam kết về sự xuất sắc được thể hiện trong từng chi tiết, từ các
                            phòng được trang bị trang nhã đến các tiện nghi đẳng cấp thế giới.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-col items-center gap-3 rounded-xl bg-surface-light p-6 text-center dark:bg-surface-dark">
                            <span class="material-symbols-outlined text-3xl text-accent">restaurant</span>
                            <h3 class="text-lg font-bold">Ẩm thực tinh tế</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thưởng thức những kiệt tác ẩm thực tại nhà hàng sang trọng của chúng tôi.</p>
                        </div>
                        <div class="flex flex-col items-center gap-3 rounded-xl bg-surface-light p-6 text-center dark:bg-surface-dark">
                            <span class="material-symbols-outlined text-3xl text-accent">spa</span>
                            <h3 class="text-lg font-bold">Spa thư giãn</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Phục hồi cơ thể và tâm hồn tại spa hiện đại của chúng tôi.</p>
                        </div>
                        <div class="flex flex-col items-center gap-3 rounded-xl bg-surface-light p-6 text-center dark:bg-surface-dark">
                            <span class="material-symbols-outlined text-3xl text-accent">pool</span>
                            <h3 class="text-lg font-bold">Hồ bơi vô cực</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thư giãn với tầm nhìn thành phố tuyệt đẹp từ hồ bơi vô cực của chúng tôi.</p>
                        </div>
                        <div class="flex flex-col items-center gap-3 rounded-xl bg-surface-light p-6 text-center dark:bg-surface-dark">
                            <span class="material-symbols-outlined text-3xl text-accent">business_center</span>
                            <h3 class="text-lg font-bold">Trung tâm kinh doanh</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Làm việc hiệu quả với các tiện nghi kinh doanh hiện đại đầy đủ.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Rooms Section -->
            <section class="w-full justify-center bg-primary-light/30 py-16 dark:bg-surface-dark sm:py-24" id="rooms">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl"></h2>
                            Phòng &amp; Suite</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Được thiết kế cho sự thoải mái, tạo nên những giấc mơ.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($featured_rooms)): ?>
                            <?php foreach ($featured_rooms as $room): 
                                // Parse thumbnail image path
                                $thumbnail = normalizeImagePath($room['thumbnail']);
                                $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                            ?>
                                <div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700">
                                    <div class="aspect-video w-full bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>');"></div>
                                    <div class="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                                            <p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                                <?php echo number_format($room['size_sqm'], 0); ?> m², 
                                                <?php echo htmlspecialchars($room['bed_type']); ?>, 
                                                <?php echo $room['max_occupancy']; ?> người
                                            </p>
                                        </div>
                                        <div class="mt-4 flex flex-col gap-2">
                                            <div class="text-lg font-bold text-accent">
                                                <?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ <span class="text-sm font-normal">/đêm</span>
                                            </div>
                                            <a href="room-details/<?php echo htmlspecialchars($room['slug']); ?>.php" 
                                               class="flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
                                                <span class="truncate">Xem chi tiết</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-gray-500 text-lg">Không có phòng nào</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-center pt-4">
                        <a href="rooms.php" class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-white rounded-lg font-bold hover:opacity-90 transition-opacity">
                            Xem tất cả phòng
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="services">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Dịch vụ &amp; Tiện nghi</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Trải nghiệm dịch vụ 5 sao với chất lượng quốc tế.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">room_service</span>
                            <h3 class="text-lg font-bold">Phục vụ phòng 24/7</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Dịch vụ phục vụ phòng toàn thời gian với thực đơn đa dạng.</p>
                        </div>
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">fitness_center</span>
                            <h3 class="text-lg font-bold">Phòng tập thể dục</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Trang thiết bị hiện đại với huấn luyện viên chuyên nghiệp.</p>
                        </div>
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">local_parking</span>
                            <h3 class="text-lg font-bold">Bãi đỗ xe</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Bãi đỗ xe an toàn với dịch vụ valet parking.</p>
                        </div>
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">wifi</span>
                            <h3 class="text-lg font-bold">WiFi miễn phí</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Kết nối internet tốc độ cao trong toàn bộ khách sạn.</p>
                        </div>
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">concierge</span>
                            <h3 class="text-lg font-bold">Lễ tân 24/7</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Hỗ trợ khách hàng chuyên nghiệp mọi lúc.</p>
                        </div>
                        <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-background-dark border border-gray-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-accent">event</span>
                            <h3 class="text-lg font-bold">Sự kiện &amp; Hội nghị</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Không gian tổ chức sự kiện chuyên nghiệp.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section class="w-full justify-center bg-primary-light/30 py-16 dark:bg-surface-dark sm:py-24">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Tại sao chọn Aurora Hotel Plaza</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Những lý do khiến chúng tôi trở thành lựa chọn hàng đầu.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-accent text-white">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Vị trí chiến lược</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">Nằm ở trung tâm Biên Hòa, gần các điểm du lịch và trung tâm thương mại.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-accent text-white">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Dịch vụ chuyên nghiệp</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">Đội ngũ nhân viên được đào tạo chuyên nghiệp, thân thiện và tận tâm.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-accent text-white">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mbold -2">Giá cạnh tranh</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">Cung cấp dịch vụ 5 sao với giá hợp lý, có nhiều gói khuyến mãi.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-accent text-white">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Tiện nghi hiện đại</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">Trang bị công nghệ mới nhất, phòng ốc sạch sẽ và thoải mái.</p>
                     </div>
                        </div>
                </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="w-full justify-center py-16 sm:py-24">
                <div class="mx-auto flex max-w-4xl flex-col gap-8 px-4 text-center">
                    <div class="flex flex-col gap-4">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Sẵn sàng cho kỳ nghỉ của bạn?</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">
                            Đặt phòng ngay hôm nay và nhận ưu đãi đặc biệt cho khách hàng mới.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="booking/index.php" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-accent text-white rounded-lg font-bold hover:opacity-90 transition-opacity">
                            Đặt phòng ngay
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <a href="contact.php" class="inline-flex items-center justify-center gap-2 px-8 py-4 border-2 border-accent text-accent rounded-lg font-bold hover:bg-accent/10 transition-colors">
                            Liên hệ với chúng tôi
                            <span class="material-symbols-outlined">phone</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <?php include 'includes/footer.php'; ?>

    </div>

    <!-- Main JavaScript -->
    <script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>

</body>

</html>text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Phòng & Suite cao cấp
                        </h2>
                        <p class="mt-3 text-base text-text-secondary-light dark:text-text-secondary-dark">
                            Không gian nghỉ dưỡng sang trọng, được thiết kế để mang lại sự thoải mái tối đa
                        </p>