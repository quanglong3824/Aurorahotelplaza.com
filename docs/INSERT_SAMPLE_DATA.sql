-- ============================================================================
-- AURORA HOTEL - INSERT SAMPLE DATA
-- ============================================================================

-- Clear existing sample data (if any)
DELETE FROM points_transactions WHERE transaction_type = 'earn';
DELETE FROM user_loyalty;
DELETE FROM payments;
DELETE FROM bookings;
DELETE FROM rooms;
DELETE FROM room_types;
DELETE FROM promotion_usage;
DELETE FROM promotions;
DELETE FROM services;
DELETE FROM blog_categories;
DELETE FROM system_settings;
DELETE FROM membership_tiers;
DELETE FROM users WHERE email IN ('admin@aurorahotel.com', 'receptionist@aurorahotel.com', 'customer@test.com');

-- Reset auto increment
ALTER TABLE room_types AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- Insert room types (Loại phòng)
INSERT INTO `room_types` (
    `type_name`, `slug`, `category`, `description`, `short_description`,
    `max_occupancy`, `size_sqm`, `bed_type`, `base_price`, `weekend_price`, 
    `holiday_price`, `status`, `sort_order`, `amenities`, `thumbnail`
) VALUES
('Phòng Deluxe', 'deluxe', 'room', 
    'Phòng Deluxe rộng 35m² được thiết kế hiện đại với giường King size cao cấp, tầm nhìn thành phố tuyệt đẹp. Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với vòi sen massage.',
    'Phòng sang trọng với đầy đủ tiện nghi hiện đại',
    2, 35.00, '1 Giường King', 1200000, 1400000, 1600000, 'active', 1,
    'WiFi miễn phí,TV 43",Điều hòa,Minibar,Két an toàn,Bàn làm việc,Phòng tắm riêng,Vòi sen massage',
    'assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg'
),
('Premium Deluxe', 'premium-deluxe', 'room',
    'Phòng Premium Deluxe 45m² với không gian rộng rãi, khu vực sinh hoạt riêng biệt. Trang bị giường King size cao cấp, sofa thư giãn, bàn làm việc rộng và ban công riêng với tầm nhìn đẹp.',
    'Phòng cao cấp với không gian rộng rãi',
    2, 45.00, '1 Giường King', 1800000, 2000000, 2200000, 'active', 2,
    'WiFi miễn phí,TV 50",Điều hòa,Minibar,Két an toàn,Sofa,Ban công,Bồn tắm',
    'assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg'
),
('Premium Twin', 'premium-twin', 'room',
    'Phòng Premium Twin 40m² với 2 giường đơn cao cấp, phù hợp cho bạn bè hoặc đồng nghiệp. Thiết kế hiện đại với đầy đủ tiện nghi và không gian làm việc thoải mái.',
    'Phòng 2 giường đơn cao cấp',
    2, 40.00, '2 Giường đơn', 1600000, 1800000, 2000000, 'active', 3,
    'WiFi miễn phí,TV 43",Điều hòa,Minibar,Két an toàn,2 bàn làm việc,Phòng tắm riêng',
    'assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg'
),
('VIP Suite', 'vip-suite', 'room',
    'VIP Suite 80m² sang trọng với phòng ngủ riêng biệt, phòng khách rộng, ban công lớn và dịch vụ Butler 24/7. Đây là lựa chọn hoàn hảo cho những vị khách đặc biệt.',
    'Suite VIP với dịch vụ đẳng cấp',
    4, 80.00, '1 Giường King + Sofa bed', 3500000, 4000000, 4500000, 'active', 4,
    'WiFi miễn phí,TV 65",Điều hòa,Minibar,Két an toàn,Phòng khách riêng,Ban công lớn,Bồn tắm Jacuzzi,Dịch vụ Butler',
    'assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg'
),
('Studio Apartment', 'studio-apartment', 'apartment',
    'Căn hộ Studio 45m² với không gian mở, bếp nhỏ, khu vực sinh hoạt và ngủ nghỉ. Phù hợp cho lưu trú dài ngày với đầy đủ tiện nghi như ở nhà.',
    'Căn hộ Studio tiện nghi',
    2, 45.00, '1 Giường Queen', 1500000, 1700000, 1900000, 'active', 5,
    'WiFi miễn phí,TV 43",Điều hòa,Bếp nhỏ,Tủ lạnh,Máy giặt,Khu vực ăn uống,Phòng tắm riêng',
    'assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-1.jpg'
),
('Premium Apartment', 'premium-apartment', 'apartment',
    'Căn hộ Premium 60m² với 1 phòng ngủ riêng, phòng khách, bếp đầy đủ tiện nghi. Lý tưởng cho gia đình nhỏ hoặc lưu trú dài hạn.',
    'Căn hộ 1 phòng ngủ cao cấp',
    3, 60.00, '1 Giường King', 2200000, 2500000, 2800000, 'active', 6,
    'WiFi miễn phí,TV 50",Điều hòa,Bếp đầy đủ,Tủ lạnh,Máy giặt,Bàn ăn 4 chỗ,Sofa,Ban công',
    'assets/img/premium apartment/CAN-HO-PREMIUM-AURORA-HOTEL-1.jpg'
),
('Family Apartment', 'family-apartment', 'apartment',
    'Căn hộ Family 75m² với 2 phòng ngủ, phòng khách rộng, bếp hiện đại. Hoàn hảo cho gia đình 4-5 người với không gian thoải mái.',
    'Căn hộ 2 phòng ngủ cho gia đình',
    5, 75.00, '1 King + 2 Single', 2800000, 3200000, 3600000, 'active', 7,
    'WiFi miễn phí,2 TV,Điều hòa,Bếp đầy đủ,Tủ lạnh,Máy giặt,Bàn ăn 6 chỗ,Sofa,2 phòng tắm',
    'assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-3.jpg'
);

-- Insert rooms (Phòng cụ thể)
INSERT INTO `rooms` (`room_type_id`, `room_number`, `floor`, `building`, `status`) VALUES
-- Deluxe rooms (Floor 1-3)
(1, '101', 1, 'A', 'available'),
(1, '102', 1, 'A', 'available'),
(1, '103', 1, 'A', 'available'),
(1, '201', 2, 'A', 'available'),
(1, '202', 2, 'A', 'available'),
(1, '301', 3, 'A', 'available'),

-- Premium Deluxe rooms (Floor 4-5)
(2, '401', 4, 'A', 'available'),
(2, '402', 4, 'A', 'available'),
(2, '501', 5, 'A', 'available'),
(2, '502', 5, 'A', 'available'),

-- Premium Twin rooms (Floor 2-3)
(3, '203', 2, 'A', 'available'),
(3, '204', 2, 'A', 'available'),
(3, '302', 3, 'A', 'available'),

-- VIP Suite (Floor 6)
(4, '601', 6, 'A', 'available'),
(4, '602', 6, 'A', 'available'),

-- Studio Apartments (Building B, Floor 1-2)
(5, 'B101', 1, 'B', 'available'),
(5, 'B102', 1, 'B', 'available'),
(5, 'B201', 2, 'B', 'available'),

-- Premium Apartments (Building B, Floor 3-4)
(6, 'B301', 3, 'B', 'available'),
(6, 'B302', 3, 'B', 'available'),
(6, 'B401', 4, 'B', 'available'),

-- Family Apartments (Building B, Floor 5-6)
(7, 'B501', 5, 'B', 'available'),
(7, 'B502', 5, 'B', 'available'),
(7, 'B601', 6, 'B', 'available');

-- Insert admin user (password: admin123)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `phone`, `user_role`, `status`, `email_verified`) VALUES
('admin@aurorahotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '+84 123 456 789', 'admin', 'active', 1),
('receptionist@aurorahotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lễ tân', '+84 987 654 321', 'receptionist', 'active', 1);

-- Insert test customer
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `phone`, `user_role`, `status`, `email_verified`) VALUES
('customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '+84 901 234 567', 'customer', 'active', 1);

-- Insert membership tiers
INSERT INTO `membership_tiers` (`tier_name`, `tier_level`, `min_points`, `discount_percentage`, `benefits`, `color_code`) VALUES
('Bronze', 1, 0, 0.00, 'Ưu tiên check-in sớm', '#CD7F32'),
('Silver', 2, 1000, 5.00, 'Giảm 5%, Late checkout miễn phí', '#C0C0C0'),
('Gold', 3, 5000, 10.00, 'Giảm 10%, Nâng cấp phòng miễn phí, Welcome drink', '#FFD700'),
('Platinum', 4, 15000, 15.00, 'Giảm 15%, Ưu tiên đặt phòng, Spa miễn phí', '#E5E4E2'),
('Diamond', 5, 50000, 20.00, 'Giảm 20%, Phòng VIP, Dịch vụ cao cấp', '#B9F2FF');

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Aurora Hotel Plaza', 'string', 'Tên website'),
('site_email', 'info@aurorahotel.com', 'string', 'Email liên hệ'),
('site_phone', '+84 251 3511 888', 'string', 'Số điện thoại'),
('booking_advance_days', '365', 'number', 'Số ngày tối đa có thể đặt trước'),
('cancellation_hours', '24', 'number', 'Số giờ trước khi check-in có thể hủy miễn phí'),
('points_per_vnd', '0.0001', 'number', 'Số điểm tích lũy trên 1 VND (1 điểm/10,000 VND)'),
('min_booking_amount', '500000', 'number', 'Số tiền đặt phòng tối thiểu'),
('tax_percentage', '10', 'number', 'Thuế VAT (%)'),
('service_charge_percentage', '5', 'number', 'Phí dịch vụ (%)');

-- Insert sample services
INSERT INTO `services` (`service_name`, `slug`, `category`, `description`, `short_description`, `price`, `unit`, `available`) VALUES
('Massage toàn thân', 'massage-toan-than', 'spa', 'Massage thư giãn toàn thân với tinh dầu thiên nhiên, giúp giảm căng thẳng và mệt mỏi', 'Massage 60 phút', 500000, '60 phút', 1),
('Giặt ủi', 'giat-ui', 'laundry', 'Dịch vụ giặt ủi quần áo chuyên nghiệp, trả trong ngày', 'Giặt ủi nhanh', 50000, 'kg', 1),
('Đưa đón sân bay', 'dua-don-san-bay', 'transport', 'Dịch vụ đưa đón sân bay Tân Sơn Nhất bằng xe sang', 'Xe 4-7 chỗ', 300000, 'chuyến', 1),
('Ăn sáng buffet', 'an-sang-buffet', 'restaurant', 'Buffet sáng đa dạng món Á - Âu tại nhà hàng Aurora', 'Buffet 6:00-10:00', 200000, 'người', 1),
('Thuê xe máy', 'thue-xe-may', 'transport', 'Thuê xe máy tự động, bao xăng trong ngày', 'Honda Vision/SH', 150000, 'ngày', 1),
('Spa chăm sóc da mặt', 'spa-cham-soc-da-mat', 'spa', 'Chăm sóc da mặt chuyên sâu với sản phẩm cao cấp', 'Spa 90 phút', 800000, '90 phút', 1);

-- Insert sample blog categories
INSERT INTO `blog_categories` (`category_name`, `slug`, `description`) VALUES
('Tin tức', 'tin-tuc', 'Tin tức và sự kiện của khách sạn'),
('Khuyến mãi', 'khuyen-mai', 'Các chương trình khuyến mãi'),
('Du lịch', 'du-lich', 'Thông tin du lịch và điểm đến'),
('Ẩm thực', 'am-thuc', 'Món ăn và nhà hàng'),
('Hướng dẫn', 'huong-dan', 'Hướng dẫn sử dụng dịch vụ');

-- Insert sample promotions
INSERT INTO `promotions` (
    `promotion_code`, `promotion_name`, `description`, 
    `discount_type`, `discount_value`, `min_booking_amount`, `max_discount`,
    `usage_limit`, `usage_per_user`, `applicable_to`,
    `start_date`, `end_date`, `status`
) VALUES
('WELCOME2024', 'Chào mừng khách hàng mới', 'Giảm 10% cho lần đặt phòng đầu tiên', 
    'percentage', 10.00, 1000000, 500000, 
    100, 1, 'all',
    NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH), 'active'),
('WEEKEND50', 'Giảm giá cuối tuần', 'Giảm 50,000đ cho đặt phòng cuối tuần',
    'fixed_amount', 50000, 500000, 50000,
    NULL, 5, 'rooms',
    NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 'active');

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check inserted data
SELECT 'Room Types:' as Info, COUNT(*) as Count FROM room_types;
SELECT 'Rooms:' as Info, COUNT(*) as Count FROM rooms;
SELECT 'Users:' as Info, COUNT(*) as Count FROM users;
SELECT 'Services:' as Info, COUNT(*) as Count FROM services;
SELECT 'Promotions:' as Info, COUNT(*) as Count FROM promotions;

-- Show available rooms by type
SELECT 
    rt.type_name,
    rt.base_price,
    COUNT(r.room_id) as available_rooms
FROM room_types rt
LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id AND r.status = 'available'
WHERE rt.status = 'active'
GROUP BY rt.room_type_id
ORDER BY rt.sort_order;
