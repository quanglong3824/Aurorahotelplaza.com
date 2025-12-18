<?php
/**
 * Related Rooms Section
 * Hiển thị các phòng liên quan (random từ database)
 * 
 * Sử dụng:
 * include '../includes/related-rooms.php';
 * 
 * Biến cần truyền vào (optional):
 * - $currentRoomTypeId: ID của phòng hiện tại (để loại trừ)
 * - $sectionTitle: Tiêu đề section (mặc định: "Phòng khác")
 */

require_once __DIR__ . '/../helpers/room-helper.php';

// Lấy biến từ file gọi (nếu có)
$currentRoomTypeId = $currentRoomTypeId ?? null;
$category = $category ?? (isset($currentRoom['category']) ? $currentRoom['category'] : null);
$sectionTitle = $sectionTitle ?? __('room_detail.other_rooms');

// Lấy danh sách phòng random
$relatedRooms = getRandomRooms($currentRoomTypeId, 3, $category);
?>

<!-- Related Rooms -->
<!-- Related Rooms -->
<section class="py-16 relative z-10">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-white mb-12 font-display">
            <?php echo htmlspecialchars($sectionTitle); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (!empty($relatedRooms)): ?>
                <?php foreach ($relatedRooms as $room): ?>
                    <div class="glass-card overflow-hidden group h-full flex flex-col">
                        <div class="relative h-64 overflow-hidden">
                            <img src="<?php echo htmlspecialchars(getThumbnailPath($room['thumbnail'], $room['category'])); ?>"
                                alt="<?php echo htmlspecialchars($room['name']); ?>"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

                            <!-- Price Tag -->
                            <div class="absolute bottom-4 left-4 glass-price-overlay">
                                <span class="price"><?php echo formatPrice($room['base_price']); ?></span>
                                <span class="unit text-xs text-white/80">/ <?php _e('apartment_detail.per_night'); ?></span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col flex-grow">
                            <h3 class="text-xl font-bold text-white mb-4 group-hover:text-accent transition-colors">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </h3>

                            <div class="mt-auto">
                                <a href="<?php echo htmlspecialchars(getRoomDetailUrl($room['slug'], $room['category'])); ?>"
                                    class="btn-glass-primary w-full text-center justify-center">
                                    <?php _e('common.view_detail'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>