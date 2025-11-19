<?php
// Start session for user authentication
session_start();

// Load environment configuration
require_once __DIR__ . '/config/environment.php';
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Aurora Hotel Plaza - Khách sạn sang trọng tại Biên Hòa</title>

<!-- Tailwind CSS -->
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>

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

<!-- FeatureSection (About Us) -->
<section class="w-full justify-center py-16 sm:py-24" id="about">
<div class="mx-auto flex max-w-7xl flex-col gap-10 px-4">
<div class="flex flex-col gap-4 text-center">
<h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">Chào mừng đến với Aurora Hotel Plaza</h2>
<p class="mx-auto max-w-3xl text-base leading-relaxed text-text-secondary-light dark:text-text-secondary-dark">Tọa lạc tại trung tâm thành phố Biên Hòa, Aurora Hotel Plaza mang đến trải nghiệm sang trọng và thanh bình vô song. Cam kết về sự xuất sắc được thể hiện trong từng chi tiết, từ các phòng được trang bị trang nhã đến các tiện nghi đẳng cấp thế giới.</p>
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

<!-- Featured Rooms Carousel -->
<section class="w-full justify-center bg-primary-light/30 py-16 dark:bg-surface-dark sm:py-24" id="rooms">
<div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
<div class="flex flex-col gap-2 text-center">
<h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">Phòng &amp; Suite</h2>
<p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Được thiết kế cho sự thoải mái, tạo nên những giấc mơ.</p>
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
<div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700">
<div class="aspect-video w-full bg-cover bg-center" style='background-image: url("<?php echo asset('img/deluxe/DELUXE-ROOM-AURORA-1.jpg'); ?>?v=<?php echo time(); ?>");'></div>
<div class="flex flex-1 flex-col justify-between p-6">
<div>
<h3 class="text-xl font-bold">Phòng Deluxe</h3>
<p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">35 m², Tầm nhìn thành phố, Giường King</p>
</div>
<button class="mt-4 flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
<span class="truncate">Xem chi tiết</span>
</button>
</div>
</div>
<div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700">
<div class="aspect-video w-full bg-cover bg-center" style='background-image: url("<?php echo asset('img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg'); ?>?v=<?php echo time(); ?>");'></div>
<div class="flex flex-1 flex-col justify-between p-6">
<div>
<h3 class="text-xl font-bold">Premium Deluxe</h3>
<p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">45 m², Khu vực sinh hoạt, Tầm nhìn đẹp</p>
</div>
<button class="mt-4 flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
<span class="truncate">Xem chi tiết</span>
</button>
</div>
</div>
<div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700">
<div class="aspect-video w-full bg-cover bg-center" style='background-image: url("<?php echo asset('img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg'); ?>?v=<?php echo time(); ?>");'></div>
<div class="flex flex-1 flex-col justify-between p-6">
<div>
<h3 class="text-xl font-bold">VIP Suite</h3>
<p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">80 m², Ban công riêng, Dịch vụ Butler</p>
</div>
<button class="mt-4 flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
<span class="truncate">Xem chi tiết</span>
</button>
</div>
</div>
</div>
</div>
</section>

<!-- Testimonials Section -->
<section class="w-full justify-center py-16 sm:py-24">
<div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
<div class="flex flex-col gap-2 text-center">
<h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">Khách hàng nói gì về chúng tôi</h2>
<p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Những câu chuyện từ những người đã trải nghiệm sự khác biệt của Aurora.</p>
</div>
<div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
<div class="flex flex-col items-center gap-4 rounded-xl bg-surface-light p-8 text-center dark:bg-surface-dark">
<img class="h-20 w-20 rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB3iQfcLWjVQnE7z6ul25hh57_8ascyanGLHWzoB-ZA_y-kp80tdSQ8XeA4RshfpgNEZ_WzBoEco52XlmMaqCRnLolIZfPyNXktxz8V-UUwkgbjjkOeQHhTPcEVmOEaayteYWre3lNHeLK0rENF0rZaUPYt8ACe0uFoRIR_7ZtnhQ1jEA4Ysn1LnNcUxLw_sxXqXgUausyqR6WuduA0qiuJKAoZOs9zg8wiDBxgBGb2JwqYc1QPBGHkaM6-Nd-l7_XnFBUi4MR0j_o" alt="Khách hàng"/>
<p class="text-text-secondary-light dark:text-text-secondary-dark">"Một kỳ nghỉ khó quên! Sự chú ý đến từng chi tiết và dịch vụ hoàn hảo đã làm cho chuyến đi kỷ niệm của chúng tôi thực sự đặc biệt."</p>
<div class="font-bold text-text-primary-light dark:text-text-primary-dark"> - Chị Lan</div>
</div>
<div class="flex flex-col items-center gap-4 rounded-xl bg-surface-light p-8 text-center dark:bg-surface-dark">
<img class="h-20 w-20 rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDjgKS5qxnGVyzm_owPwHWKKs9pg0Vtkxs0FXwlUr5u13_hKAGQyUNrg1mMUb8W6AvKySxp2PG0aeoi1QFTz4cX5ex-jrMSOqC7xKURm0BbQe0uZrHYO7vLgTB5efHJNEti3v2g0HJyYQywl7OiIgoSeN0pK9ZkYDs_6haQ0h6-mfigv6FHs7mH9pCbzv7WhazBJg1rQerhhURvoyx028n3IbDotzSOu2pei_2-5i_guv_f8e4fMQdGy1K50W0OIqdDS1hhtGyyHjY" alt="Khách hàng"/>
<p class="text-text-secondary-light dark:text-text-secondary-dark">"Sự kết hợp hoàn hảo giữa sang trọng và thoải mái. Phòng tuyệt đẹp, đồ ăn ngon và nhân viên rất chu đáo."</p>
<div class="font-bold text-text-primary-light dark:text-text-primary-dark"> - Anh Minh</div>
</div>
<div class="flex flex-col items-center gap-4 rounded-xl bg-surface-light p-8 text-center dark:bg-surface-dark">
<img class="h-20 w-20 rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBNqAY03zsvyISdI5Gpg6bTUEkJj_eXu3yAmsnkLln2ChWEydaqDdqB_tz-NZP9BLBJmCCm9pPIz5FzTBcK95OYiYV-FSIRGfo0BeHfr7GG0hW967owYpbTXHM8NDAvLPhT91jfFfsRgAdeGGL35rIEB3MNawZLe3FI4R7BllFZHFjI46zfT4w37kBAqGADfMdtM790YfOHb1BKTYv3DYUOMXsH-DHu7c4HAqrK8YSVhAdoKm6p_oxsledRjGV13V0atn2o4Ja18ec" alt="Khách hàng"/>
<p class="text-text-secondary-light dark:text-text-secondary-dark">"Tôi thường xuyên đi công tác và Aurora Hotel Plaza giờ là lựa chọn hàng đầu của tôi. Tiện nghi tuyệt vời và là nơi nghỉ ngơi yên bình."</p>
<div class="font-bold text-text-primary-light dark:text-text-primary-dark"> - Chị Hương</div>
</div>
</div>
</div>
</section>
</main>

<?php include 'includes/footer.php'; ?>

</div>

<!-- Main JavaScript -->
<script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>

</body>
</html>
