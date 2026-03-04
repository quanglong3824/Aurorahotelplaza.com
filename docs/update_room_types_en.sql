-- ============================================================
-- Aurora Hotel Plaza - English translations for ROOMS (Amenities, Bed Type)
-- Run in phpMyAdmin → SQL tab on production
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- BƯỚC 1: Thêm cột bed_type_en và amenities_en nếu chưa có
-- ============================================================
ALTER TABLE `room_types`
    ADD COLUMN IF NOT EXISTS `bed_type_en` varchar(100) DEFAULT NULL AFTER `bed_type`,
    ADD COLUMN IF NOT EXISTS `amenities_en` text DEFAULT NULL AFTER `amenities`;

-- ============================================================
-- BƯỚC 2: Cập nhật dịch thuật tiếng Anh tùy theo hạng phòng
-- ============================================================

-- Deluxe Room (deluxe-room)
UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed (1.8×2m)',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Minibar,In-room Safe'
WHERE `slug` = 'deluxe-room';

-- Premium Deluxe Room (premium-deluxe-room)
UPDATE `room_types` SET
    `bed_type_en` = '1 Super King Bed (2×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Premium Minibar,Electronic Safe,Bathtub'
WHERE `slug` = 'premium-deluxe-room';

-- Premium Twin Room (premium-twin-room)
UPDATE `room_types` SET
    `bed_type_en` = '2 Single Beds (1.4×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Minibar,In-room Safe,Massage Shower'
WHERE `slug` = 'premium-twin-room';

-- VIP Suite (vip-suite)
UPDATE `room_types` SET
    `bed_type_en` = '1 Super King Bed (2×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Premium Minibar,Electronic Safe,Bathtub,Separate Living Room,Coffee Machine,Audio System,24/7 Butler Service'
WHERE `slug` = 'vip-suite';

-- Executive Suite (executive-suite)
UPDATE `room_types` SET
    `bed_type_en` = '1 Super King Bed (2×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Premium Minibar,Electronic Safe,Bathtub,Separate Living Room'
WHERE `slug` = 'executive-suite';

-- Family Suite (family-suite)
UPDATE `room_types` SET
    `bed_type_en` = '1 King + 2 Single Beds',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Minibar,In-room Safe,Bathtub'
WHERE `slug` = 'family-suite';

-- Presidential Suite (presidential-suite)
UPDATE `room_types` SET
    `bed_type_en` = '1 Super King Bed (2×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Premium Minibar,Electronic Safe,Bathtub,Separate Living Room,Coffee Machine,Audio System,24/7 Butler Service'
WHERE `slug` = 'presidential-suite';

-- ============================================================
-- THE END
-- ============================================================
SELECT 'Room amenities and bed types EN translations applied!' AS status;
