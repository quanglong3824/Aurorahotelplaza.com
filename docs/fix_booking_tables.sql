-- =====================================================
-- FIX BOOKING TABLES - Aurora Hotel Plaza
-- Sửa lỗi phòng 706 và thêm AUTO_INCREMENT cho các bảng
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. SỬA LỖI PHÒNG 706 (room_id = 0)
-- =====================================================
DELETE FROM `rooms` WHERE `room_number` = '706' AND `room_id` = 0;

INSERT INTO `rooms` (`room_type_id`, `room_number`, `floor`, `building`, `status`, `notes`, `last_cleaned`) 
VALUES (10, '706', 7, 'Main', 'available', NULL, NULL);

-- =====================================================
-- 2. THÊM AUTO_INCREMENT VÀ PRIMARY KEY CHO CÁC BẢNG
-- =====================================================

-- blog_categories
ALTER TABLE `blog_categories` 
MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`category_id`);

-- chat_conversations
ALTER TABLE `chat_conversations` 
MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`conversation_id`);

-- chat_messages
ALTER TABLE `chat_messages` 
MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`message_id`);

-- csrf_tokens
ALTER TABLE `csrf_tokens` 
MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`token_id`);

-- faqs
ALTER TABLE `faqs` 
MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`faq_id`);

-- membership_tiers
ALTER TABLE `membership_tiers` 
MODIFY `tier_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`tier_id`);

-- notifications
ALTER TABLE `notifications` 
MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`notification_id`);

-- page_content
ALTER TABLE `page_content` 
MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`content_id`);

-- promotion_usage
ALTER TABLE `promotion_usage` 
MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`usage_id`);

-- promotions
ALTER TABLE `promotions` 
MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`promotion_id`);

-- push_subscriptions
ALTER TABLE `push_subscriptions` 
MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`subscription_id`);

-- rate_limits
ALTER TABLE `rate_limits` 
MODIFY `limit_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`limit_id`);

-- refunds
ALTER TABLE `refunds` 
MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`refund_id`);

-- review_responses
ALTER TABLE `review_responses` 
MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`response_id`);

-- reviews
ALTER TABLE `reviews` 
MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`review_id`);

-- role_permissions
ALTER TABLE `role_permissions` 
MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`permission_id`);

-- room_pricing
ALTER TABLE `room_pricing` 
MODIFY `pricing_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`pricing_id`);

-- room_types
ALTER TABLE `room_types` 
MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`room_type_id`);

-- rooms
ALTER TABLE `rooms` 
MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`room_id`);

-- service_bookings
ALTER TABLE `service_bookings` 
MODIFY `service_booking_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`service_booking_id`);

-- service_packages
ALTER TABLE `service_packages` 
MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`package_id`);

-- system_settings
ALTER TABLE `system_settings` 
MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`setting_id`);

-- =====================================================
-- 3. THÊM INDEX CHO BẢNG BOOKINGS (tối ưu query)
-- =====================================================
ALTER TABLE `bookings` 
ADD INDEX IF NOT EXISTS `idx_bookings_user` (`user_id`),
ADD INDEX IF NOT EXISTS `idx_bookings_room_type` (`room_type_id`),
ADD INDEX IF NOT EXISTS `idx_bookings_room` (`room_id`),
ADD INDEX IF NOT EXISTS `idx_bookings_code` (`booking_code`);

-- =====================================================
-- 4. THÊM INDEX CHO BẢNG BOOKING_EXTRA_GUESTS
-- =====================================================
ALTER TABLE `booking_extra_guests`
ADD INDEX IF NOT EXISTS `idx_extra_guests_type` (`guest_type`);

-- =====================================================
-- 5. THÊM INDEX CHO BẢNG PAYMENTS
-- =====================================================
ALTER TABLE `payments`
ADD INDEX IF NOT EXISTS `idx_payments_booking` (`booking_id`),
ADD INDEX IF NOT EXISTS `idx_payments_status` (`status`);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- HOÀN TẤT
-- =====================================================
SELECT 'Database fix completed successfully!' AS message;
