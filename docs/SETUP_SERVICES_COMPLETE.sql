-- ============================================
-- COMPLETE SERVICES SETUP
-- Tạo bảng service_packages và insert đầy đủ dữ liệu
-- ============================================

-- Tạo bảng service_packages để lưu các gói/options
CREATE TABLE IF NOT EXISTS `service_packages` (
    `package_id` INT(11) NOT NULL AUTO_INCREMENT,
    `service_id` INT(11) NOT NULL,
    `package_name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `features` TEXT COMMENT 'Danh sách tính năng, phân cách bởi dấu phẩy',
    `price` DECIMAL(10,2) NOT NULL,
    `price_unit` VARCHAR(50) DEFAULT 'VNĐ',
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_available` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`package_id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `fk_package_service` (`service_id`),
    CONSTRAINT `fk_package_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cập nhật bảng services (chỉ thêm cột mới, không đổi tên cột đã tồn tại)
ALTER TABLE `services` 
MODIFY COLUMN `category` ENUM('room_service', 'spa', 'restaurant', 'event', 'transport', 'laundry', 'other') NOT NULL;

-- Thêm cột icon nếu chưa có
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `icon` VARCHAR(100) DEFAULT 'room_service' AFTER `short_description`;

-- Thêm cột is_featured nếu chưa có (kiểm tra available hoặc is_available)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'is_featured');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `services` ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `is_available`',
    'SELECT "Column is_featured already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm cột images nếu chưa có
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `images` TEXT AFTER `thumbnail`;

-- Xóa dữ liệu cũ (sử dụng DELETE thay vì TRUNCATE để tránh lỗi foreign key)
-- Xóa theo thứ tự: bookings → packages → services
DELETE FROM `service_bookings` WHERE service_id IN (SELECT service_id FROM services);
DELETE FROM `service_packages`;
DELETE FROM `services`;

-- Reset AUTO_INCREMENT
ALTER TABLE `service_bookings` AUTO_INCREMENT = 1;
ALTER TABLE `service_packages` AUTO_INCREMENT = 1;
ALTER TABLE `services` AUTO_INCREMENT = 1;

-- ============================================
-- INSERT SERVICES DATA
-- ============================================

-- 1. TIỆC CƯỚI
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Tổ chức tiệc cưới', 'wedding-service', 'event',
'Aurora Hotel Plaza tự hào là địa điểm tổ chức tiệc cưới hàng đầu tại Đồng Nai với sảnh tiệc sang trọng 500m², sức chứa lên đến 800 khách. Đội ngũ chuyên nghiệp với hơn 500 tiệc cưới thành công.',
'Sảnh tiệc 500m², sức chứa 800 khách, dịch vụ trọn gói',
'celebration',
'assets/img/post/wedding/Tiec-cuoi-tai-aurora-1.jpg',
0, 'Liên hệ', 1, 1, 1);

-- 2. HỘI NGHỊ
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Tổ chức hội nghị', 'conference-service', 'event',
'Phòng hội nghị hiện đại với 5 phòng họp đa năng, sức chứa từ 20-300 người. Trang bị đầy đủ thiết bị hiện đại, WiFi tốc độ cao, dịch vụ coffee break và buffet.',
'Phòng họp hiện đại, thiết bị đầy đủ, WiFi tốc độ cao',
'meeting_room',
'assets/img/post/conference/conference-1.jpg',
5000000, 'VNĐ', 1, 1, 2);

-- 3. NHÀ HÀNG
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Nhà hàng Aurora', 'aurora-restaurant', 'restaurant',
'Nhà hàng sang trọng với sức chứa 200 khách, view thành phố tuyệt đẹp. Buffet đa dạng hơn 100 món Á-Âu: hải sản tươi sống, sushi Nhật Bản, BBQ Hàn Quốc, món Việt truyền thống.',
'Buffet 100+ món Á-Âu, view đẹp, đầu bếp 5 sao',
'restaurant',
'assets/img/post/restaurant/restaurant-1.jpg',
350000, 'VNĐ/người', 1, 1, 3);

-- 4. VĂN PHÒNG
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Văn phòng cho thuê', 'office-rental', 'other',
'Văn phòng hiện đại với 3 loại: Studio 20m², Standard 40m², Premium 80m². Đầy đủ nội thất, WiFi 1Gbps, phòng họp chung, bảo vệ 24/7.',
'Văn phòng đầy đủ nội thất, WiFi 1Gbps, tiện ích đầy đủ',
'business_center',
'assets/img/post/office/office-1.jpg',
8000000, 'VNĐ/tháng', 1, 1, 4);

-- 5. ROOFTOP BAR
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Rooftop Bar', 'rooftop-bar', 'restaurant',
'Quầy bar tầng thượng với view 360 độ thành phố Biên Hòa. Không gian sang trọng, âm nhạc sống mỗi tối thứ 6-7. Menu cocktail đa dạng.',
'Bar tầng thượng, view 360°, cocktail đa dạng, nhạc sống',
'local_bar',
'assets/img/post/restaurant/rooftop-bar-1.jpg',
150000, 'VNĐ/ly', 1, 1, 5);

-- 6-16: Các dịch vụ khác (Room service, Spa, Transport, etc.)
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, is_available, sort_order) VALUES
('Dịch vụ phòng 24/7', 'room-service-24-7', 'room_service', 'Dịch vụ phòng hoạt động 24/7 với thực đơn đa dạng hơn 50 món.', 'Gọi món ăn, đồ uống phục vụ tận phòng 24/7', 'room_service', 0, 'Miễn phí', 0, 1, 6),
('Dọn phòng hàng ngày', 'daily-housekeeping', 'room_service', 'Dịch vụ dọn phòng hàng ngày với đội ngũ chuyên nghiệp.', 'Dọn dẹp và thay đổi khăn giường mỗi ngày', 'cleaning_services', 0, 'Miễn phí', 0, 1, 7),
('Giặt ủi cao cấp', 'laundry-service', 'laundry', 'Dịch vụ giặt ủi chuyên nghiệp với máy móc hiện đại.', 'Giặt ủi quần áo chuyên nghiệp, giao nhận tận phòng', 'local_laundry_service', 50000, 'VNĐ/kg', 0, 1, 8),
('Massage trị liệu', 'therapeutic-massage', 'spa', 'Massage trị liệu chuyên sâu giúp giảm căng thẳng, đau nhức cơ bắp.', 'Massage thư giãn toàn thân với tinh dầu thiên nhiên', 'spa', 500000, 'VNĐ/60 phút', 1, 1, 9),
('Sauna & Jacuzzi', 'sauna-jacuzzi', 'spa', 'Phòng xông hơi khô và bồn tắm massage cao cấp.', 'Xông hơi khô và bồn tắm nước nóng massage', 'hot_tub', 300000, 'VNĐ/45 phút', 0, 1, 10),
('Hồ bơi & Gym', 'pool-gym', 'other', 'Hồ bơi ngoài trời 25m x 12m và phòng gym 200m² với trang thiết bị Technogym.', 'Hồ bơi ngoài trời và phòng gym hiện đại miễn phí', 'pool', 0, 'Miễn phí', 1, 1, 11),
('Đưa đón sân bay', 'airport-transfer', 'transport', 'Dịch vụ đưa đón sân bay Tân Sơn Nhất bằng xe sang.', 'Xe đưa đón sân bay Tân Sơn Nhất tiện lợi, an toàn', 'local_taxi', 500000, 'VNĐ/chuyến', 0, 1, 12),
('Thuê xe tự lái', 'car-rental', 'transport', 'Cho thuê xe tự lái đa dạng dòng xe 4-7 chỗ.', 'Thuê xe tự lái 4-7 chỗ, xe mới, bảo hiểm đầy đủ', 'directions_car', 800000, 'VNĐ/ngày', 0, 1, 13),
('Trông trẻ', 'babysitting', 'other', 'Dịch vụ trông trẻ chuyên nghiệp, an toàn cho trẻ từ 6 tháng đến 12 tuổi.', 'Dịch vụ trông trẻ an toàn, chuyên nghiệp, giám sát camera', 'child_care', 100000, 'VNĐ/giờ', 0, 1, 14);

-- ============================================
-- INSERT SERVICE PACKAGES (Gói dịch vụ)
-- ============================================

-- Gói TIỆC CƯỚI (service_id = 1)
INSERT INTO `service_packages` (service_id, package_name, slug, description, features, price, price_unit, is_featured, sort_order) VALUES
(1, 'Gói Cơ bản', 'wedding-basic', 
'Gói tiệc cưới cơ bản cho 200-300 khách.',
'Sảnh tiệc 200-300 khách,Trang trí cơ bản,Menu 8 món,Âm thanh ánh sáng,Bàn ghế tiêu chuẩn,Backdrop sân khấu',
15000000, 'VNĐ', 0, 1),

(1, 'Gói Tiêu chuẩn', 'wedding-standard',
'Gói tiệc cưới tiêu chuẩn cho 400-500 khách.',
'Sảnh tiệc 400-500 khách,Trang trí theo chủ đề,Menu 10 món + tráng miệng,Âm thanh ánh sáng cao cấp,Bàn ghế VIP,Backdrop + Cổng hoa,MC chuyên nghiệp,Nhiếp ảnh cơ bản',
25000000, 'VNĐ', 1, 2),

(1, 'Gói VIP', 'wedding-vip',
'Gói tiệc cưới VIP cho 600-800 khách.',
'Sảnh tiệc 600-800 khách,Trang trí sang trọng,Menu 12 món + buffet tráng miệng,Âm thanh ánh sáng 3D,Bàn ghế VIP cao cấp,Backdrop + Cổng hoa + Sân khấu,MC + Ban nhạc,Nhiếp ảnh + Quay phim,Trang điểm cô dâu,Xe hoa',
40000000, 'VNĐ', 1, 3);

-- Gói NHÀ HÀNG (service_id = 3)
INSERT INTO `service_packages` (service_id, package_name, slug, description, features, price, price_unit, is_featured, sort_order) VALUES
(3, 'Buffet Sáng', 'restaurant-breakfast', 
'Buffet sáng với hơn 50 món Á - Âu.',
'Hơn 50 món ăn,Phở - Bún - Cháo,Bánh mì - Xúc xích,Trứng các loại,Hoa quả tươi,Nước ép - Cà phê,Thời gian: 6h-10h',
200000, 'VNĐ/người', 0, 1),

(3, 'Buffet Trưa/Tối', 'restaurant-lunch-dinner',
'Buffet trưa hoặc tối với hơn 100 món.',
'Hơn 100 món ăn,Hải sản tươi sống,Sushi Nhật Bản,BBQ Hàn Quốc,Món Việt truyền thống,Món Âu cao cấp,Tráng miệng đa dạng,Nước uống không giới hạn',
350000, 'VNĐ/người', 1, 2),

(3, 'Set Menu VIP', 'restaurant-set-menu',
'Set menu cao cấp phục vụ riêng.',
'Khai vị cao cấp,Súp đặc biệt,Món chính (Bò/Hải sản),Món phụ,Tráng miệng,Rượu vang,Phục vụ riêng tư,Không gian VIP',
800000, 'VNĐ/người', 1, 3);

-- Gói HỘI NGHỊ (service_id = 2)
INSERT INTO `service_packages` (service_id, package_name, slug, description, features, price, price_unit, is_featured, sort_order) VALUES
(2, 'Gói Nửa ngày', 'conference-half-day', 
'Gói hội nghị nửa ngày (4 giờ) phù hợp cho 50-100 người.',
'Phòng họp 50-100 người,Projector + Màn hình,Micro không dây,WiFi miễn phí,Coffee break 1 lần,Bảng Flipchart',
5000000, 'VNĐ/4 giờ', 0, 1),

(2, 'Gói Cả ngày', 'conference-full-day',
'Gói hội nghị cả ngày (8 giờ) cho 100-200 người.',
'Phòng họp 100-200 người,Màn hình LED lớn,Hệ thống âm thanh cao cấp,WiFi tốc độ cao,Coffee break 2 lần,Buffet trưa,Bảng Flipchart + Bút,Hỗ trợ kỹ thuật',
9000000, 'VNĐ/8 giờ', 1, 2),

(2, 'Gói VIP', 'conference-vip',
'Gói hội nghị VIP cho 200-300 người.',
'Phòng hội nghị 200-300 người,Màn hình LED 3D,Âm thanh chuyên nghiệp,WiFi doanh nghiệp,Coffee break 3 lần,Buffet trưa + tối,Phòng VIP cho ban tổ chức,Ghi hình chuyên nghiệp,Hỗ trợ kỹ thuật 24/7',
15000000, 'VNĐ/ngày', 1, 3);

-- Gói VĂN PHÒNG (service_id = 4)
INSERT INTO `service_packages` (service_id, package_name, slug, description, features, price, price_unit, is_featured, sort_order) VALUES
(4, 'Studio 20m²', 'office-studio', 
'Văn phòng Studio cho 2-3 người.',
'Diện tích 20m²,2-3 người,Bàn làm việc,Ghế ergonomic,Tủ tài liệu,Điều hòa,WiFi 1Gbps',
3000000, 'VNĐ/tháng', 0, 1),

(4, 'Standard 40m²', 'office-standard',
'Văn phòng Standard cho 5-8 người.',
'Diện tích 40m²,5-8 người,Bàn làm việc,Ghế ergonomic,Tủ tài liệu,Điều hòa,WiFi 1Gbps,Phòng họp chung',
5500000, 'VNĐ/tháng', 1, 2),

(4, 'Premium 80m²', 'office-premium',
'Văn phòng Premium cho 10-15 người.',
'Diện tích 80m²,10-15 người,Bàn làm việc cao cấp,Ghế ergonomic,Tủ tài liệu,Điều hòa trung tâm,WiFi 1Gbps,Phòng họp riêng,Khu pantry riêng',
8000000, 'VNĐ/tháng', 1, 3);

-- Verify
SELECT 
    s.service_id,
    s.service_name,
    s.category,
    COUNT(sp.package_id) as package_count
FROM services s
LEFT JOIN service_packages sp ON s.service_id = sp.service_id
GROUP BY s.service_id
ORDER BY s.sort_order;

SELECT * FROM service_packages ORDER BY service_id, sort_order;
