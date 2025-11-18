-- ============================================
-- CREATE SERVICES TABLE
-- Bảng quản lý dịch vụ của khách sạn
-- ============================================

CREATE TABLE IF NOT EXISTS `services` (
    `service_id` INT(11) NOT NULL AUTO_INCREMENT,
    `service_name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `category` ENUM('room_service', 'spa', 'restaurant', 'event', 'transport', 'other') NOT NULL DEFAULT 'other',
    `description` TEXT,
    `short_description` VARCHAR(500),
    `icon` VARCHAR(100) DEFAULT 'room_service',
    `thumbnail` VARCHAR(255),
    `images` TEXT,
    `price` DECIMAL(10,2) DEFAULT 0.00,
    `price_unit` VARCHAR(50) DEFAULT 'VNĐ',
    `is_available` TINYINT(1) DEFAULT 1,
    `is_featured` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`service_id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_category` (`category`),
    KEY `idx_available` (`is_available`),
    KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE SERVICES DATA
-- ============================================

-- Room Services
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Dịch vụ phòng 24/7', 'room-service-24-7', 'room_service', 
'Dịch vụ phòng hoạt động 24/7 với thực đơn đa dạng từ món Á đến món Âu. Phục vụ tận phòng nhanh chóng, chuyên nghiệp.', 
'Gọi món ăn, đồ uống phục vụ tận phòng 24/7',
'room_service', 0, 'Miễn phí', 1, 1),

('Dọn phòng hàng ngày', 'daily-housekeeping', 'room_service',
'Dịch vụ dọn phòng hàng ngày với đội ngũ nhân viên chuyên nghiệp, chu đáo. Thay khăn, ga giường, vệ sinh phòng sạch sẽ.',
'Dọn dẹp và thay đổi khăn giường mỗi ngày',
'cleaning_services', 0, 'Miễn phí', 0, 2),

('Giặt ủi cao cấp', 'laundry-service', 'room_service',
'Dịch vụ giặt ủi chuyên nghiệp với máy móc hiện đại, chất lượng cao. Nhận và trả đồ tại phòng.',
'Giặt ủi quần áo chuyên nghiệp, giao nhận tận phòng',
'local_laundry_service', 50000, 'VNĐ/kg', 0, 3);

-- Spa & Wellness
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Massage trị liệu', 'therapeutic-massage', 'spa',
'Massage trị liệu chuyên sâu giúp giảm căng thẳng, đau nhức cơ bắp. Đội ngũ kỹ thuật viên chuyên nghiệp với nhiều năm kinh nghiệm.',
'Massage thư giãn toàn thân với tinh dầu thiên nhiên',
'spa', 500000, 'VNĐ/60 phút', 1, 4),

('Sauna & Jacuzzi', 'sauna-jacuzzi', 'spa',
'Phòng xông hơi khô và bồn tắm massage cao cấp giúp thư giãn, thải độc cơ thể.',
'Xông hơi khô và bồn tắm nước nóng massage',
'hot_tub', 300000, 'VNĐ/45 phút', 0, 5);

-- Restaurant
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Nhà hàng Aurora', 'aurora-restaurant', 'restaurant',
'Nhà hàng sang trọng phục vụ buffet sáng, trưa, tối với hơn 100 món ăn Á - Âu. View thành phố tuyệt đẹp.',
'Buffet đa dạng món Á - Âu, view đẹp',
'restaurant', 350000, 'VNĐ/người', 1, 6),

('Rooftop Bar', 'rooftop-bar', 'restaurant',
'Quầy bar trên tầng thượng với không gian sang trọng, view 360 độ thành phố. Cocktail và đồ uống cao cấp.',
'Bar tầng thượng với cocktail và view đẹp',
'local_bar', 150000, 'VNĐ/ly', 1, 7);

-- Events
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Tổ chức tiệc cưới', 'wedding-service', 'event',
'Dịch vụ tổ chức tiệc cưới trọn gói với sảnh tiệc sang trọng, âm thanh ánh sáng hiện đại, thực đơn đa dạng.',
'Tổ chức tiệc cưới sang trọng, chuyên nghiệp',
'celebration', 0, 'Liên hệ', 1, 8),

('Hội nghị - Hội thảo', 'conference-service', 'event',
'Phòng hội nghị đầy đủ trang thiết bị: máy chiếu, âm thanh, wifi tốc độ cao. Phục vụ coffee break.',
'Phòng họp hiện đại với đầy đủ thiết bị',
'meeting_room', 0, 'Liên hệ', 0, 9);

-- Transport
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Đưa đón sân bay', 'airport-transfer', 'transport',
'Dịch vụ đưa đón sân bay bằng xe sang, tài xế chuyên nghiệp. Đón tận cửa, giao tận nơi.',
'Xe đưa đón sân bay tiện lợi, an toàn',
'local_taxi', 500000, 'VNĐ/chuyến', 0, 10),

('Thuê xe tự lái', 'car-rental', 'transport',
'Cho thuê xe tự lái đa dạng dòng xe: 4 chỗ, 7 chỗ. Xe mới, bảo hiểm đầy đủ.',
'Thuê xe tự lái theo ngày hoặc giờ',
'directions_car', 800000, 'VNĐ/ngày', 0, 11);

-- Other Services
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, price, price_unit, is_featured, sort_order) VALUES
('Hồ bơi & Gym', 'pool-gym', 'other',
'Hồ bơi ngoài trời và phòng gym hiện đại với đầy đủ trang thiết bị. Mở cửa từ 6h - 22h hàng ngày.',
'Hồ bơi và phòng tập gym miễn phí cho khách',
'pool', 0, 'Miễn phí', 1, 12),

('Trông trẻ', 'babysitting', 'other',
'Dịch vụ trông trẻ chuyên nghiệp, an toàn. Nhân viên được đào tạo bài bản.',
'Dịch vụ trông trẻ an toàn, chuyên nghiệp',
'child_care', 100000, 'VNĐ/giờ', 0, 13);

-- Verify
SELECT service_id, service_name, category, price, price_unit, is_featured 
FROM services 
ORDER BY sort_order;
