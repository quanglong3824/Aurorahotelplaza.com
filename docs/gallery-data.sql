-- =====================================================
-- Gallery Data Insert - Aurora Hotel Plaza
-- Chạy file này để thêm dữ liệu vào bảng gallery
-- =====================================================

-- Xóa dữ liệu cũ (nếu có)
TRUNCATE TABLE `gallery`;

-- Reset AUTO_INCREMENT
ALTER TABLE `gallery` AUTO_INCREMENT = 1;

-- =====================================================
-- PHÒNG NGHỈ (rooms)
-- =====================================================

-- Phòng Deluxe
INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
('Phòng Deluxe', 'Phòng Deluxe sang trọng với đầy đủ tiện nghi', 'assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg', 'rooms', 1, 'active'),
('Phòng Deluxe - Góc nhìn', 'Góc nhìn tổng quan phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg', 'rooms', 2, 'active'),
('Phòng Deluxe - Nội thất', 'Nội thất phòng Deluxe hiện đại', 'assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg', 'rooms', 3, 'active'),
('Phòng Deluxe - Giường', 'Giường ngủ êm ái phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-4.jpg', 'rooms', 4, 'active'),
('Phòng Deluxe - Phòng tắm', 'Phòng tắm sang trọng', 'assets/img/deluxe/DELUXE-ROOM-AURORA-5.jpg', 'rooms', 5, 'active'),
('Phòng Deluxe - Tiện nghi', 'Tiện nghi đầy đủ trong phòng', 'assets/img/deluxe/DELUXE-ROOM-AURORA-6.jpg', 'rooms', 6, 'active'),
('Phòng Deluxe - Giường đôi', 'Phòng Deluxe với giường đôi', 'assets/img/deluxe/DELUXE-ROOM-AURORA-7.jpg', 'rooms', 7, 'active'),
('Phòng Deluxe - View', 'View đẹp từ phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-8.jpg', 'rooms', 8, 'active'),
('Phòng Deluxe - Không gian', 'Không gian rộng rãi phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-9.jpg', 'rooms', 9, 'active'),
('Phòng Deluxe - Toàn cảnh', 'Toàn cảnh phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-10.jpg', 'rooms', 10, 'active'),

-- Phòng Premium Deluxe
('Premium Deluxe', 'Phòng Premium Deluxe cao cấp', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-1.jpg', 'rooms', 11, 'active'),
('Premium Deluxe - Nội thất', 'Nội thất Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-2.jpg', 'rooms', 12, 'active'),
('Premium Deluxe - Giường', 'Giường ngủ Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-3.jpg', 'rooms', 13, 'active'),
('Premium Deluxe - View', 'View từ phòng Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-5.jpg', 'rooms', 14, 'active'),
('Premium Deluxe - Phòng tắm', 'Phòng tắm Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-6.jpg', 'rooms', 15, 'active'),

-- Phòng Premium Twin
('Premium Twin', 'Phòng Premium Twin 2 giường', 'assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg', 'rooms', 16, 'active'),
('Premium Twin - Giường đôi', 'Hai giường đơn Premium Twin', 'assets/img/premium-twin/premium-deluxe-twin-aurora-2.jpg', 'rooms', 17, 'active'),
('Premium Twin - Nội thất', 'Nội thất phòng Premium Twin', 'assets/img/premium-twin/premium-deluxe-twin-aurora-3.jpg', 'rooms', 18, 'active'),

-- Phòng VIP
('Phòng VIP', 'Phòng VIP đẳng cấp nhất', 'assets/img/vip/vip-room-aurora-hotel-1.jpg', 'rooms', 19, 'active'),
('Phòng VIP - Sang trọng', 'Không gian sang trọng phòng VIP', 'assets/img/vip/vip-room-aurora-hotel-3.jpg', 'rooms', 20, 'active'),
('Phòng VIP - Nội thất', 'Nội thất cao cấp phòng VIP', 'assets/img/vip/vip-room-aurora-hotel-4.jpg', 'rooms', 21, 'active'),
('Phòng VIP - Phòng khách', 'Phòng khách riêng VIP', 'assets/img/vip/vip-room-aurora-hotel-5.jpg', 'rooms', 22, 'active'),
('Phòng VIP - Phòng tắm', 'Phòng tắm VIP sang trọng', 'assets/img/vip/vip-room-aurora-hotel-6.jpg', 'rooms', 23, 'active');

-- =====================================================
-- CĂN HỘ (apartments)
-- =====================================================

INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
-- Căn hộ Studio
('Căn hộ Studio', 'Căn hộ Studio tiện nghi', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg', 'apartments', 24, 'active'),
('Studio - Phòng khách', 'Phòng khách căn hộ Studio', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-2.jpg', 'apartments', 25, 'active'),
('Studio - Bếp', 'Bếp đầy đủ tiện nghi', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-3.jpg', 'apartments', 26, 'active'),

-- Căn hộ Family
('Căn hộ Family', 'Căn hộ Family rộng rãi', 'assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg', 'apartments', 27, 'active'),
('Family - Phòng ngủ', 'Phòng ngủ căn hộ Family', 'assets/img/family-apartment/can-ho-family-aurora-hotel-5.jpg', 'apartments', 28, 'active'),
('Family - Phòng khách', 'Phòng khách căn hộ Family', 'assets/img/family-apartment/can-ho-family-aurora-hotel-6.jpg', 'apartments', 29, 'active'),

-- Căn hộ Premium
('Căn hộ Premium', 'Căn hộ Premium cao cấp', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg', 'apartments', 30, 'active'),
('Premium - Nội thất', 'Nội thất căn hộ Premium', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-2.jpg', 'apartments', 31, 'active'),
('Premium - Phòng ngủ', 'Phòng ngủ căn hộ Premium', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-3.jpg', 'apartments', 32, 'active'),

-- Căn hộ Indochine
('Indochine Family', 'Căn hộ phong cách Indochine', 'assets/img/indochine-family-apartment/indochine-family-apartment-1.jpg', 'apartments', 33, 'active'),
('Indochine - Phong cách', 'Phong cách Đông Dương đặc trưng', 'assets/img/indochine-family-apartment/indochine-family-apartment-2.jpg', 'apartments', 34, 'active'),
('Indochine - Nội thất', 'Nội thất Indochine tinh tế', 'assets/img/indochine-family-apartment/indochine-family-apartment-3.jpg', 'apartments', 35, 'active'),
('Indochine Studio', 'Căn hộ Studio Indochine', 'assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg', 'apartments', 36, 'active'),

-- Căn hộ Modern
('Modern Studio', 'Căn hộ Studio hiện đại', 'assets/img/modern-studio-apartment/modern-studio-apartment-1.jpg', 'apartments', 37, 'active'),
('Modern - Thiết kế', 'Thiết kế hiện đại', 'assets/img/modern-studio-apartment/modern-studio-apartment-2.jpg', 'apartments', 38, 'active'),
('Modern Premium', 'Căn hộ Premium hiện đại', 'assets/img/modern-premium-apartment/modern-premium-apartment-1.jpg', 'apartments', 39, 'active'),

-- Căn hộ Classical
('Classical Family', 'Căn hộ phong cách cổ điển', 'assets/img/classical-family-apartment/classical-family-apartment1.jpg', 'apartments', 40, 'active'),
('Classical - Cổ điển', 'Nét đẹp cổ điển sang trọng', 'assets/img/classical-family-apartment/classical-family-apartment2.jpg', 'apartments', 41, 'active');

-- =====================================================
-- NHÀ HÀNG (restaurant)
-- =====================================================

INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
('Nhà hàng Aurora', 'Nhà hàng sang trọng Aurora', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-1.jpg', 'restaurant', 42, 'active'),
('Không gian nhà hàng', 'Không gian ẩm thực đẳng cấp', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-2.jpg', 'restaurant', 43, 'active'),
('Khu vực ăn uống', 'Khu vực ăn uống thoáng đãng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-3.jpg', 'restaurant', 44, 'active'),
('Buffet sáng', 'Buffet sáng phong phú', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg', 'restaurant', 45, 'active'),
('Bàn tiệc', 'Bàn tiệc sang trọng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-5.jpg', 'restaurant', 46, 'active'),
('Nội thất nhà hàng', 'Nội thất nhà hàng tinh tế', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg', 'restaurant', 47, 'active'),
('Khu vực VIP', 'Khu vực VIP riêng tư', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-7.jpg', 'restaurant', 48, 'active'),
('Quầy bar', 'Quầy bar hiện đại', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-8.jpg', 'restaurant', 49, 'active'),
('Góc nhìn nhà hàng', 'Góc nhìn đẹp nhà hàng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-9.jpg', 'restaurant', 50, 'active'),
('Tiệc buffet', 'Tiệc buffet đa dạng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-10.jpg', 'restaurant', 51, 'active'),
('Khu vực buffet', 'Khu vực buffet rộng rãi', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-11.jpg', 'restaurant', 52, 'active'),
('Toàn cảnh nhà hàng', 'Toàn cảnh nhà hàng Aurora', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-14.jpg', 'restaurant', 53, 'active');

-- =====================================================
-- TIỆN NGHI (facilities)
-- =====================================================

INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
('Lễ tân', 'Quầy lễ tân chuyên nghiệp', 'assets/img/src/ui/horizontal/Le_tan_Aurora.jpg', 'facilities', 54, 'active'),
('Sảnh khách sạn', 'Sảnh đón tiếp sang trọng', 'assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg', 'facilities', 55, 'active'),
('Phòng Studio', 'Phòng Studio tiện nghi', 'assets/img/src/ui/horizontal/phong-studio-khach-san-aurora-bien-hoa.jpg', 'facilities', 56, 'active'),
('Phòng Gym', 'Phòng tập Gym hiện đại', 'assets/img/service/gym/GYM-AURORA-HOTEL-1.jpg', 'facilities', 57, 'active'),
('Thiết bị Gym', 'Thiết bị tập luyện chất lượng', 'assets/img/service/gym/GYM-AURORA-HOTEL-2.jpg', 'facilities', 58, 'active'),
('Khu vực tập luyện', 'Khu vực tập luyện rộng rãi', 'assets/img/service/gym/GYM-AURORA-HOTEL-3.jpg', 'facilities', 59, 'active'),
('Hồ bơi', 'Hồ bơi ngoài trời', 'assets/img/service/pool/pool.jpg', 'facilities', 60, 'active'),
('Văn phòng cho thuê', 'Văn phòng cho thuê chuyên nghiệp', 'assets/img/service/office/Van-phong-cho-thue-Aurora-1.jpg', 'facilities', 61, 'active'),
('Không gian làm việc', 'Không gian làm việc hiện đại', 'assets/img/service/office/Van-phong-cho-thue-Aurora-2.jpg', 'facilities', 62, 'active'),
('Phòng họp', 'Phòng họp đầy đủ tiện nghi', 'assets/img/service/office/Van-phong-cho-thue-Aurora-3.jpg', 'facilities', 63, 'active');

-- =====================================================
-- SỰ KIỆN (events)
-- =====================================================

INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
-- Tiệc cưới
('Tiệc cưới Aurora', 'Tiệc cưới sang trọng tại Aurora', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-1.jpg', 'events', 64, 'active'),
('Sảnh tiệc cưới', 'Sảnh tiệc cưới rộng lớn', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-2.jpg', 'events', 65, 'active'),
('Trang trí tiệc cưới', 'Trang trí tiệc cưới tinh tế', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-3.jpg', 'events', 66, 'active'),
('Bàn tiệc cưới', 'Bàn tiệc cưới sang trọng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-4.jpg', 'events', 67, 'active'),
('Không gian tiệc cưới', 'Không gian tiệc cưới lãng mạn', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg', 'events', 68, 'active'),
('Sân khấu tiệc cưới', 'Sân khấu tiệc cưới hoành tráng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-6.jpg', 'events', 69, 'active'),
('Tiệc cưới sang trọng', 'Tiệc cưới đẳng cấp 5 sao', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-7.jpg', 'events', 70, 'active'),
('Tiệc cưới hoành tráng', 'Tiệc cưới quy mô lớn', 'assets/img/post/wedding/Tiec-cuoi-tai-Aurora-8.jpg', 'events', 71, 'active'),
('Tiệc cưới đẳng cấp', 'Tiệc cưới phong cách hiện đại', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-9.jpg', 'events', 72, 'active'),
('Tiệc cưới lãng mạn', 'Tiệc cưới lãng mạn và ấm cúng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-10.jpg', 'events', 73, 'active'),

-- Hội nghị
('Phòng hội nghị', 'Phòng hội nghị chuyên nghiệp', 'assets/img/src/ui/horizontal/hoi-nghi-khach-san-o-bien-hoa.jpg', 'events', 74, 'active'),
('Sự kiện hội nghị', 'Tổ chức sự kiện hội nghị', 'assets/img/src/ui/horizontal/Hoi-nghi-aurora-8.jpg', 'events', 75, 'active'),
('Hội nghị Aurora', 'Hội nghị tại Aurora Hotel', 'assets/img/service/meet/Hoi-nghi-aurora-5.jpg', 'events', 76, 'active'),
('Phòng họp lớn', 'Phòng họp quy mô lớn', 'assets/img/service/meet/Hoi-nghi-aurora-6.jpg', 'events', 77, 'active');

-- =====================================================
-- NGOẠI CẢNH (exterior)
-- =====================================================

INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `sort_order`, `status`) VALUES
('Aurora Hotel Plaza', 'Toàn cảnh Aurora Hotel Plaza', 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg', 'exterior', 78, 'active'),
('Mặt tiền khách sạn', 'Mặt tiền khách sạn ấn tượng', 'assets/img/hero-banner/aurora-hotel-bien-hoa-2.jpg', 'exterior', 79, 'active'),
('Khách sạn về đêm', 'Aurora Hotel lung linh về đêm', 'assets/img/hero-banner/aurora-hotel-bien-hoa-3.jpg', 'exterior', 80, 'active'),
('Toàn cảnh Aurora', 'Toàn cảnh khách sạn từ xa', 'assets/img/hero-banner/aurora-hotel-bien-hoa-4.jpg', 'exterior', 81, 'active'),
('Cafe Aurora', 'Quán cafe Aurora Hotel', 'assets/img/hero-banner/caffe-aurora-hotel-1.jpg', 'exterior', 82, 'active');

-- =====================================================
-- Cập nhật AUTO_INCREMENT cho bảng gallery
-- =====================================================
SELECT CONCAT('Total images inserted: ', COUNT(*)) as result FROM gallery;
