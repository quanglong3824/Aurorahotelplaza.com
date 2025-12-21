-- =====================================================
-- Aurora Hotel Plaza - Pricing Migration
-- Date: 2025-12-21
-- Description: Cập nhật giá phòng theo bảng giá lễ tân
-- Author: System Administrator
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- =====================================================
-- 1. THÊM CỘT MỚI CHO BẢNG room_types (nếu chưa có)
-- =====================================================

-- Thêm các cột giá mới theo nghiệp vụ lễ tân
ALTER TABLE `room_types`
ADD COLUMN IF NOT EXISTS `price_published` DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá công bố (giá niêm yết)',
ADD COLUMN IF NOT EXISTS `price_single_occupancy` DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá phòng đơn (1 người)',
ADD COLUMN IF NOT EXISTS `price_double_occupancy` DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá phòng đôi (2 người)',
ADD COLUMN IF NOT EXISTS `price_short_stay` DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá nghỉ ngắn hạn (dưới 4h)',
ADD COLUMN IF NOT EXISTS `short_stay_description` VARCHAR(255) DEFAULT NULL COMMENT 'Mô tả điều kiện nghỉ ngắn hạn',
ADD COLUMN IF NOT EXISTS `view_type` VARCHAR(100) DEFAULT 'Thành phố' COMMENT 'Loại view phòng',
ADD COLUMN IF NOT EXISTS `price_daily_single` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá ngày 1 người',
ADD COLUMN IF NOT EXISTS `price_daily_double` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá ngày 2 người',
ADD COLUMN IF NOT EXISTS `price_weekly_single` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá tuần 1 người',
ADD COLUMN IF NOT EXISTS `price_weekly_double` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá tuần 2 người',
ADD COLUMN IF NOT EXISTS `price_avg_weekly_single` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá TB/đêm tuần 1 người',
ADD COLUMN IF NOT EXISTS `price_avg_weekly_double` DECIMAL(12,2) DEFAULT NULL COMMENT 'Căn hộ: Giá TB/đêm tuần 2 người';

-- =====================================================
-- 2. CẬP NHẬT GIÁ PHÒNG KHÁCH SẠN (HOTEL ROOMS)
-- =====================================================

-- 2.1 Deluxe Room (32m2)
UPDATE `room_types` SET
    `type_name` = 'Deluxe',
    `type_name_en` = 'Deluxe Room',
    `size_sqm` = 32.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường đôi lớn (1m8x2m)',
    `price_published` = 1900000,
    `price_double_occupancy` = 1600000,
    `price_single_occupancy` = 1400000,
    `base_price` = 1600000, -- Giá cơ sở lấy giá 2 người
    `price_short_stay` = 1100000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng',
    `max_occupancy` = 2,
    `max_adults` = 2,
    `max_children` = 1,
    `updated_at` = NOW()
WHERE `slug` = 'deluxe';

-- 2.2 Premium Deluxe Double (48m2)
UPDATE `room_types` SET
    `type_name` = 'Premium Deluxe Double',
    `type_name_en` = 'Premium Deluxe Double Room',
    `size_sqm` = 48.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường đôi lớn (1m8x2m)',
    `price_published` = 2200000,
    `price_double_occupancy` = 1900000,
    `price_single_occupancy` = 1700000,
    `base_price` = 1900000,
    `price_short_stay` = 1300000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng',
    `max_occupancy` = 2,
    `max_adults` = 2,
    `max_children` = 1,
    `is_twin` = 0,
    `updated_at` = NOW()
WHERE `slug` = 'premium-deluxe';

-- 2.3 Premium Deluxe Twin (48m2)
UPDATE `room_types` SET
    `type_name` = 'Premium Deluxe Twin',
    `type_name_en` = 'Premium Deluxe Twin Room',
    `size_sqm` = 48.00,
    `view_type` = 'Thành phố',
    `bed_type` = '2 giường đơn (1m4x2m)',
    `price_published` = 2200000,
    `price_double_occupancy` = 1900000,
    `price_single_occupancy` = 1700000,
    `base_price` = 1900000,
    `price_short_stay` = NULL,
    `short_stay_description` = NULL,
    `max_occupancy` = 2,
    `max_adults` = 2,
    `max_children` = 2,
    `is_twin` = 1,
    `updated_at` = NOW()
WHERE `slug` = 'premium-twin';

-- 2.4 Aurora Studio (54m2) - Phòng VIP
UPDATE `room_types` SET
    `type_name` = 'Aurora Studio',
    `type_name_en` = 'Aurora Studio Suite',
    `size_sqm` = 54.00,
    `view_type` = 'Thành phố',
    `bed_type` = '1 giường siêu lớn (2mx2m)',
    `price_published` = 2950000,
    `price_double_occupancy` = 2300000,
    `price_single_occupancy` = 2200000,
    `base_price` = 2300000,
    `price_short_stay` = 1900000,
    `short_stay_description` = 'Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng',
    `max_occupancy` = 3,
    `max_adults` = 2,
    `max_children` = 2,
    `updated_at` = NOW()
WHERE `slug` = 'vip-suite';

-- =====================================================
-- 3. CẬP NHẬT GIÁ CĂN HỘ (APARTMENTS)
-- =====================================================

-- 3.1 Modern Studio Apartment (35m2)
UPDATE `room_types` SET
    `type_name` = 'Modern Studio Apartment',
    `type_name_en` = 'Modern Studio Apartment',
    `size_sqm` = 35.00,
    `price_daily_single` = 1850000,
    `price_daily_double` = 2250000,
    `price_weekly_single` = 12250000,
    `price_weekly_double` = 15050000,
    `price_avg_weekly_single` = 1750000,
    `price_avg_weekly_double` = 2150000,
    `base_price` = 1850000, -- Giá 1 người/ngày làm giá cơ sở
    `updated_at` = NOW()
WHERE `slug` = 'modern-studio';

-- 3.2 Indochine Studio Apartment (35m2)
UPDATE `room_types` SET
    `type_name` = 'Indochine Studio Apartment',
    `type_name_en` = 'Indochine Studio Apartment',
    `size_sqm` = 35.00,
    `price_daily_single` = 1850000,
    `price_daily_double` = 2250000,
    `price_weekly_single` = 12250000,
    `price_weekly_double` = 15050000,
    `price_avg_weekly_single` = 1750000,
    `price_avg_weekly_double` = 2150000,
    `base_price` = 1850000,
    `updated_at` = NOW()
WHERE `slug` = 'indochine-studio';

-- 3.3 Modern Premium Apartment (60m2)
UPDATE `room_types` SET
    `type_name` = 'Modern Premium Apartment',
    `type_name_en` = 'Modern Premium Apartment',
    `size_sqm` = 60.00,
    `price_daily_single` = 2050000,
    `price_daily_double` = 2450000,
    `price_weekly_single` = 13650000,
    `price_weekly_double` = 16450000,
    `price_avg_weekly_single` = 1950000,
    `price_avg_weekly_double` = 2350000,
    `base_price` = 2050000,
    `updated_at` = NOW()
WHERE `slug` = 'modern-premium';

-- 3.4 Classical Premium Apartment (60m2)
UPDATE `room_types` SET
    `type_name` = 'Classical Premium Apartment',
    `type_name_en` = 'Classical Premium Apartment',
    `size_sqm` = 60.00,
    `price_daily_single` = 2050000,
    `price_daily_double` = 2450000,
    `price_weekly_single` = 13650000,
    `price_weekly_double` = 16450000,
    `price_avg_weekly_single` = 1950000,
    `price_avg_weekly_double` = 2350000,
    `base_price` = 2050000,
    `updated_at` = NOW()
WHERE `slug` = 'classical-premium';

-- 3.5 Classical Family Apartment (82m2)
UPDATE `room_types` SET
    `type_name` = 'Classical Family Apartment',
    `type_name_en` = 'Classical Family Apartment',
    `size_sqm` = 82.00,
    `price_daily_single` = NULL, -- Không có giá 1 người
    `price_daily_double` = 2550000,
    `price_weekly_single` = NULL,
    `price_weekly_double` = 17150000,
    `price_avg_weekly_single` = NULL,
    `price_avg_weekly_double` = 2450000,
    `base_price` = 2550000,
    `updated_at` = NOW()
WHERE `slug` = 'classical-family';

-- 3.6 Indochine Family Apartment (82m2)
UPDATE `room_types` SET
    `type_name` = 'Indochine Family Apartment',
    `type_name_en` = 'Indochine Family Apartment',
    `size_sqm` = 82.00,
    `price_daily_single` = NULL,
    `price_daily_double` = 2550000,
    `price_weekly_single` = NULL,
    `price_weekly_double` = 17150000,
    `price_avg_weekly_single` = NULL,
    `price_avg_weekly_double` = 2450000,
    `base_price` = 2550000,
    `updated_at` = NOW()
WHERE `slug` = 'indochine-family';

-- =====================================================
-- 4. TẠO BẢNG CHÍNH SÁCH PHỤ THU
-- =====================================================

DROP TABLE IF EXISTS `pricing_policies`;
CREATE TABLE `pricing_policies` (
    `policy_id` INT(11) NOT NULL AUTO_INCREMENT,
    `policy_type` ENUM('extra_guest', 'extra_bed', 'early_checkin', 'late_checkout', 'short_stay') NOT NULL,
    `policy_name` VARCHAR(100) NOT NULL,
    `policy_name_en` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `description_en` TEXT DEFAULT NULL,
    `condition_type` VARCHAR(50) DEFAULT NULL COMMENT 'height, age, etc.',
    `condition_min` DECIMAL(10,2) DEFAULT NULL,
    `condition_max` DECIMAL(10,2) DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `is_percentage` TINYINT(1) DEFAULT 0,
    `applicable_to` ENUM('room', 'apartment', 'all') DEFAULT 'all',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu chính sách phụ thu theo bảng giá lễ tân
INSERT INTO `pricing_policies` (`policy_type`, `policy_name`, `policy_name_en`, `description`, `condition_type`, `condition_min`, `condition_max`, `price`, `applicable_to`, `sort_order`) VALUES
('extra_guest', 'Trẻ em dưới 1m (bao gồm ăn sáng)', 'Children under 1m (with breakfast)', 'Miễn phí cho trẻ em dưới 1m', 'height', 0, 1.00, 0, 'all', 1),
('extra_guest', 'Trẻ em 1m - 1m3 (bao gồm ăn sáng)', 'Children 1m - 1.3m (with breakfast)', 'Phụ thu 200,000 VND', 'height', 1.00, 1.30, 200000, 'all', 2),
('extra_guest', 'Người lớn và trẻ trên 1m3 (bao gồm ăn sáng)', 'Adults and children over 1.3m (with breakfast)', 'Phụ thu 400,000 VND', 'height', 1.30, NULL, 400000, 'all', 3),
('extra_bed', 'Giường phụ', 'Extra Bed', 'Phí 650,000 VND - Không áp dụng cho căn hộ', NULL, NULL, NULL, 650000, 'room', 4);

-- =====================================================
-- 5. TẠO BẢNG QUẢN LÝ GIÁ THEO MÙA/NGÀY LỄ
-- =====================================================

DROP TABLE IF EXISTS `seasonal_pricing`;
CREATE TABLE `seasonal_pricing` (
    `pricing_id` INT(11) NOT NULL AUTO_INCREMENT,
    `room_type_id` INT(11) DEFAULT NULL COMMENT 'NULL = áp dụng tất cả',
    `season_name` VARCHAR(100) NOT NULL,
    `season_name_en` VARCHAR(100) DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `price_adjustment` DECIMAL(12,2) NOT NULL COMMENT 'Số tiền cộng thêm hoặc phần trăm',
    `is_percentage` TINYINT(1) DEFAULT 0,
    `min_nights` INT(11) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`pricing_id`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_room_type` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. TẠO BẢNG BOOKING POLICIES (Điều khoản đặt phòng)
-- =====================================================

DROP TABLE IF EXISTS `booking_policies`;
CREATE TABLE `booking_policies` (
    `policy_id` INT(11) NOT NULL AUTO_INCREMENT,
    `policy_key` VARCHAR(50) NOT NULL UNIQUE,
    `policy_name` VARCHAR(255) NOT NULL,
    `policy_name_en` VARCHAR(255) DEFAULT NULL,
    `policy_value` TEXT NOT NULL,
    `policy_type` ENUM('time', 'number', 'text', 'boolean') DEFAULT 'text',
    `is_active` TINYINT(1) DEFAULT 1,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `booking_policies` (`policy_key`, `policy_name`, `policy_name_en`, `policy_value`, `policy_type`) VALUES
('check_in_time', 'Giờ nhận phòng', 'Check-in Time', '14:00', 'time'),
('check_out_time', 'Giờ trả phòng', 'Check-out Time', '12:00', 'time'),
('short_stay_max_hours', 'Thời gian nghỉ ngắn hạn tối đa', 'Max Short Stay Hours', '4', 'number'),
('short_stay_checkout_before', 'Trả phòng nghỉ ngắn trước', 'Short Stay Checkout Before', '22:00', 'time'),
('short_stay_includes_breakfast', 'Nghỉ ngắn bao gồm bữa sáng', 'Short Stay Includes Breakfast', '0', 'boolean'),
('tax_rate', 'Thuế VAT', 'VAT Rate', '8', 'number'),
('service_charge_rate', 'Phí dịch vụ', 'Service Charge Rate', '5', 'number'),
('min_advance_booking_hours', 'Đặt phòng trước tối thiểu (giờ)', 'Min Advance Booking Hours', '2', 'number'),
('max_advance_booking_days', 'Đặt phòng trước tối đa (ngày)', 'Max Advance Booking Days', '365', 'number'),
('weekly_stay_min_nights', 'Số đêm tối thiểu cho giá tuần', 'Min Nights for Weekly Rate', '7', 'number');

-- =====================================================
-- 7. CẬP NHẬT BẢNG BOOKINGS - THÊM CỘT MỚI
-- =====================================================

ALTER TABLE `bookings`
ADD COLUMN IF NOT EXISTS `booking_type` ENUM('standard', 'short_stay', 'weekly', 'inquiry') DEFAULT 'standard' COMMENT 'Loại đặt phòng',
ADD COLUMN IF NOT EXISTS `occupancy_type` ENUM('single', 'double', 'family') DEFAULT 'double' COMMENT '1 người, 2 người, hoặc gia đình',
ADD COLUMN IF NOT EXISTS `extra_guest_fee` DECIMAL(12,2) DEFAULT 0 COMMENT 'Phí khách thêm',
ADD COLUMN IF NOT EXISTS `extra_bed_fee` DECIMAL(12,2) DEFAULT 0 COMMENT 'Phí giường phụ',
ADD COLUMN IF NOT EXISTS `extra_beds` INT(11) DEFAULT 0 COMMENT 'Số giường phụ',
ADD COLUMN IF NOT EXISTS `short_stay_hours` INT(11) DEFAULT NULL COMMENT 'Số giờ nghỉ ngắn',
ADD COLUMN IF NOT EXISTS `expected_checkin_time` TIME DEFAULT NULL COMMENT 'Giờ check-in dự kiến',
ADD COLUMN IF NOT EXISTS `expected_checkout_time` TIME DEFAULT NULL COMMENT 'Giờ check-out dự kiến',
ADD COLUMN IF NOT EXISTS `price_type_used` ENUM('published', 'single', 'double', 'short_stay', 'daily', 'weekly') DEFAULT 'double' COMMENT 'Loại giá áp dụng';

-- =====================================================
-- 8. TẠO BẢNG EXTRA GUESTS (Khách thêm)
-- =====================================================

DROP TABLE IF EXISTS `booking_extra_guests`;
CREATE TABLE `booking_extra_guests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `guest_type` ENUM('adult', 'child_over_1m3', 'child_1m_1m3', 'child_under_1m', 'infant') NOT NULL,
    `guest_name` VARCHAR(255) DEFAULT NULL,
    `height_cm` DECIMAL(5,2) DEFAULT NULL COMMENT 'Chiều cao (cm)',
    `age` INT(11) DEFAULT NULL,
    `fee` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `includes_breakfast` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    CONSTRAINT `fk_booking_extra_guest` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. TẠO VIEW TỔNG HỢP GIÁ PHÒNG CHO LỄ TÂN
-- =====================================================

DROP VIEW IF EXISTS `v_room_pricing_summary`;
CREATE VIEW `v_room_pricing_summary` AS
SELECT 
    rt.room_type_id,
    rt.type_name,
    rt.type_name_en,
    rt.category,
    rt.slug,
    rt.size_sqm,
    rt.bed_type,
    rt.view_type,
    rt.max_occupancy,
    rt.max_adults,
    rt.max_children,
    -- Giá phòng khách sạn
    rt.price_published,
    rt.price_single_occupancy,
    rt.price_double_occupancy,
    rt.price_short_stay,
    rt.short_stay_description,
    -- Giá căn hộ
    rt.price_daily_single,
    rt.price_daily_double,
    rt.price_weekly_single,
    rt.price_weekly_double,
    rt.price_avg_weekly_single,
    rt.price_avg_weekly_double,
    -- Giá hiển thị (giá cơ sở)
    rt.base_price,
    rt.weekend_price,
    rt.holiday_price,
    -- Thông tin khác
    rt.status,
    rt.booking_type,
    rt.thumbnail,
    -- Đếm số phòng available
    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') AS available_rooms,
    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id) AS total_rooms
FROM room_types rt
WHERE rt.status = 'active'
ORDER BY rt.sort_order;

-- =====================================================
-- 10. TẠO STORED PROCEDURE TÍNH GIÁ PHÒNG
-- =====================================================

DROP PROCEDURE IF EXISTS `sp_calculate_room_price`;
DELIMITER //
CREATE PROCEDURE `sp_calculate_room_price`(
    IN p_room_type_id INT,
    IN p_checkin_date DATE,
    IN p_checkout_date DATE,
    IN p_num_adults INT,
    IN p_num_children INT,
    IN p_booking_type VARCHAR(20), -- 'standard', 'short_stay', 'weekly'
    IN p_extra_beds INT,
    OUT p_room_price DECIMAL(12,2),
    OUT p_extra_guest_fee DECIMAL(12,2),
    OUT p_extra_bed_fee DECIMAL(12,2),
    OUT p_total_nights INT,
    OUT p_total_amount DECIMAL(12,2)
)
BEGIN
    DECLARE v_base_price DECIMAL(12,2);
    DECLARE v_category VARCHAR(20);
    DECLARE v_price_type VARCHAR(20);
    DECLARE v_nights INT;
    DECLARE v_extra_bed_unit_price DECIMAL(12,2) DEFAULT 650000;
    
    -- Tính số đêm
    SET v_nights = DATEDIFF(p_checkout_date, p_checkin_date);
    IF v_nights < 1 THEN SET v_nights = 1; END IF;
    SET p_total_nights = v_nights;
    
    -- Lấy thông tin loại phòng
    SELECT category,
        CASE 
            WHEN category = 'room' THEN
                CASE 
                    WHEN p_booking_type = 'short_stay' AND price_short_stay IS NOT NULL THEN price_short_stay
                    WHEN p_num_adults = 1 AND price_single_occupancy IS NOT NULL THEN price_single_occupancy
                    ELSE COALESCE(price_double_occupancy, base_price)
                END
            WHEN category = 'apartment' THEN
                CASE 
                    WHEN p_booking_type = 'weekly' AND v_nights >= 7 THEN
                        CASE WHEN p_num_adults = 1 THEN COALESCE(price_avg_weekly_single, price_daily_single) ELSE COALESCE(price_avg_weekly_double, price_daily_double) END
                    ELSE
                        CASE WHEN p_num_adults = 1 THEN COALESCE(price_daily_single, price_daily_double) ELSE COALESCE(price_daily_double, base_price) END
                END
            ELSE base_price
        END
    INTO v_category, v_base_price
    FROM room_types
    WHERE room_type_id = p_room_type_id;
    
    -- Tính giá phòng
    IF p_booking_type = 'short_stay' THEN
        SET p_room_price = v_base_price; -- Giá ngắn hạn không nhân số đêm
    ELSE
        SET p_room_price = v_base_price * v_nights;
    END IF;
    
    -- Tính phí khách thêm (tạm tính)
    SET p_extra_guest_fee = 0;
    
    -- Tính phí giường phụ (chỉ áp dụng cho phòng, không áp dụng căn hộ)
    IF v_category = 'room' AND p_extra_beds > 0 THEN
        SET p_extra_bed_fee = p_extra_beds * v_extra_bed_unit_price * v_nights;
    ELSE
        SET p_extra_bed_fee = 0;
    END IF;
    
    -- Tổng tiền
    SET p_total_amount = p_room_price + p_extra_guest_fee + p_extra_bed_fee;
    
END //
DELIMITER ;

-- =====================================================
-- 11. CẬP NHẬT SYSTEM_SETTINGS VỚI THÔNG TIN MỚI
-- =====================================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) 
VALUES 
    ('hotel_star_rating', '4', 'number', 'Số sao khách sạn'),
    ('hotel_address', '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Tỉnh Đồng Nai', 'string', 'Địa chỉ khách sạn'),
    ('hotel_hotline', '0251 3918 888', 'string', 'Hotline khách sạn'),
    ('currency', 'VNĐ', 'string', 'Đơn vị tiền tệ'),
    ('tax_info', 'Đã bao gồm 5% phí dịch vụ và 8% VAT', 'string', 'Thông tin thuế và phí')
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `description` = VALUES(`description`);

-- =====================================================
-- 12. TẠO INDEX TỐI ƯU TRUY VẤN
-- =====================================================

-- Index cho bảng bookings
CREATE INDEX IF NOT EXISTS `idx_bookings_dates` ON `bookings` (`check_in_date`, `check_out_date`);
CREATE INDEX IF NOT EXISTS `idx_bookings_status` ON `bookings` (`status`, `payment_status`);
CREATE INDEX IF NOT EXISTS `idx_bookings_user_status` ON `bookings` (`user_id`, `status`);

-- Index cho bảng rooms
CREATE INDEX IF NOT EXISTS `idx_rooms_status_type` ON `rooms` (`status`, `room_type_id`);
CREATE INDEX IF NOT EXISTS `idx_rooms_floor` ON `rooms` (`floor`, `status`);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- HOÀN THÀNH MIGRATION
-- =====================================================
