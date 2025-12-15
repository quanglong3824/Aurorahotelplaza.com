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
$sectionTitle = $sectionTitle ?? __('room_detail.other_rooms');

// Lấy danh sách phòng random
$relatedRooms = getRandomRooms($currentRoomTypeId, 3);
?>

<!-- Related Rooms -->
<section class="related-section">
    <div class="container-custom">
        <h2 class="section-title-center"><?php echo htmlspecialchars($sectionTitle); ?></h2>
        <div class="related-grid">
            <?php if (!empty($relatedRooms)): ?>
                <?php foreach ($relatedRooms as $room): ?>
                    <div class="related-card">
                        <img src="<?php echo htmlspecialchars(getThumbnailPath($room['thumbnail'], $room['category'])); ?>" 
                             alt="<?php echo htmlspecialchars($room['name']); ?>" 
                             class="related-image">
                        <div class="related-content">
                            <h3 class="related-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <div class="related-price"><?php echo formatPrice($room['base_price']); ?><?php _e('apartment_detail.per_night'); ?></div>
                            <a href="<?php echo htmlspecialchars(getRoomDetailUrl($room['slug'], $room['category'])); ?>" 
                               class="btn-view"><?php _e('common.view_details'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback nếu không có dữ liệu từ database -->
                <div class="related-card">
                    <img src="../assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg" alt="Premium Deluxe" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Deluxe</h3>
                        <div class="related-price">1.800.000đ<?php _e('apartment_detail.per_night'); ?></div>
                        <a href="../room-details/premium-deluxe.php" class="btn-view"><?php _e('common.view_details'); ?></a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg" alt="VIP Suite" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">VIP Suite</h3>
                        <div class="related-price">3.500.000đ<?php _e('apartment_detail.per_night'); ?></div>
                        <a href="../room-details/vip-suite.php" class="btn-view"><?php _e('common.view_details'); ?></a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg" alt="Premium Twin" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Twin</h3>
                        <div class="related-price">1.600.000đ<?php _e('apartment_detail.per_night'); ?></div>
                        <a href="../room-details/premium-twin.php" class="btn-view"><?php _e('common.view_details'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
