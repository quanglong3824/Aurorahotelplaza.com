-- =====================================================
-- Aurora Hotel Plaza - Multi-language Database Schema
-- Thêm cột tiếng Anh cho các bảng cần dịch
-- Phù hợp với cấu trúc databa==========================

-- 1. Room Types - Loại phòng
ALTER TABLE room_types 
ADD COLUMN IF NOT EXISTS type_name_en VARCHAR(100) DEFAULT NULL AFTER type_name,
ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description,
ADD COLUMN IF NOT EXISTS short_description_en VARCHAR(500) DEFAULT NULL AFTER short_description;

-- 2. Services - Dịch vụ
ALTER TABLE services 
ADD COLUMN IF NOT EXISTS service_name_en VARCHAR(100) DEFAULT NULL AFTER service_name,
ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description,
ADD COLUMN IF NOT EXISTS short_description_en VARCHAR(500) DEFAULT NULL AFTER short_description;

-- 3. Service Packages - Gói dịch vụ
ALTER TABLE service_packages 
ADD COLUMN IF NOT EXISTS package_name_en VARCHAR(100) DEFAULT NULL AFTER package_name,
ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description;

-- 4. Amenities - Tiện nghi (SKIP - bảng không tồn tại trong schema hiện tại)
-- ALTER TABLE amenities 
-- ADD COLUMN IF NOT EXISTS amenity_name_en VARCHAR(100) DEFAULT NULL AFTER amenity_name;

-- 5. Membership Tiers - Hạng thành viên
ALTER TABLE membership_tiers 
ADD COLUMN IF NOT EXISTS tier_name_en VARCHAR(50) DEFAULT NULL AFTER tier_name,
ADD COLUMN IF NOT EXISTS benefits_en TEXT DEFAULT NULL AFTER benefits;

-- 6. Blog Posts - Bài viết
ALTER TABLE blog_posts 
ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) DEFAULT NULL AFTER title,
ADD COLUMN IF NOT EXISTS content_en TEXT DEFAULT NULL AFTER content,
ADD COLUMN IF NOT EXISTS excerpt_en VARCHAR(500) DEFAULT NULL AFTER excerpt;

-- 7. FAQs - Câu hỏi thường gặp
ALTER TABLE faqs 
ADD COLUMN IF NOT EXISTS question_en TEXT DEFAULT NULL AFTER question,
ADD COLUMN IF NOT EXISTS answer_en TEXT DEFAULT NULL AFTER answer;

-- 8. Promotions - Khuyến mãi
ALTER TABLE promotions 
ADD COLUMN IF NOT EXISTS promotion_name_en VARCHAR(255) DEFAULT NULL AFTER promotion_name,
ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description;

-- 9. Banners - Banner quảng cáo
ALTER TABLE banners 
ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) DEFAULT NULL AFTER title,
ADD COLUMN IF NOT EXISTS subtitle_en VARCHAR(255) DEFAULT NULL AFTER subtitle,
ADD COLUMN IF NOT EXISTS link_text_en VARCHAR(100) DEFAULT NULL AFTER link_text;

-- =====================================================
-- Sample data updates - Cập nhật dữ liệu mẫu tiếng Anh
-- =====================================================

-- Room Types
UPDATE room_types SET 
    type_name_en = 'Standard Room',
    description_en = 'Comfortable room with modern amenities, perfect for business travelers.',
    short_description_en = 'Cozy room with essential amenities'
WHERE slug = 'standard' OR type_name LIKE '%Standard%';

UPDATE room_types SET 
    type_name_en = 'Superior Room',
    description_en = 'Spacious room with city view and premium amenities.',
    short_description_en = 'Spacious room with city view'
WHERE slug = 'superior' OR type_name LIKE '%Superior%';

UPDATE room_types SET 
    type_name_en = 'Deluxe Room',
    description_en = 'Luxurious room with panoramic view and exclusive services.',
    short_description_en = 'Luxury room with panoramic view'
WHERE slug = 'deluxe' OR type_name LIKE '%Deluxe%';

UPDATE room_types SET 
    type_name_en = 'Suite Room',
    description_en = 'Premium suite with separate living area and VIP services.',
    short_description_en = 'Premium suite with living area'
WHERE slug = 'suite' OR type_name LIKE '%Suite%';

UPDATE room_types SET 
    type_name_en = 'Studio Apartment',
    description_en = 'Modern studio apartment with full kitchen and living space.',
    short_description_en = 'Modern studio with kitchen'
WHERE slug LIKE '%studio%' OR type_name LIKE '%Studio%';

UPDATE room_types SET 
    type_name_en = 'One Bedroom Apartment',
    description_en = 'Spacious one bedroom apartment ideal for extended stays.',
    short_description_en = 'One bedroom apartment'
WHERE slug LIKE '%1-bedroom%' OR type_name LIKE '%1 Phòng ngủ%';

UPDATE room_types SET 
    type_name_en = 'Two Bedroom Apartment',
    description_en = 'Family-friendly two bedroom apartment with full amenities.',
    short_description_en = 'Two bedroom family apartment'
WHERE slug LIKE '%2-bedroom%' OR type_name LIKE '%2 Phòng ngủ%';

-- Membership Tiers
UPDATE membership_tiers SET 
    tier_name_en = 'Bronze',
    benefits_en = 'Basic member benefits, earn points on bookings'
WHERE tier_name = 'Đồng' OR tier_level = 1;

UPDATE membership_tiers SET 
    tier_name_en = 'Silver',
    benefits_en = 'Priority check-in, room upgrade when available'
WHERE tier_name = 'Bạc' OR tier_level = 2;

UPDATE membership_tiers SET 
    tier_name_en = 'Gold',
    benefits_en = 'Free breakfast, late checkout, exclusive offers'
WHERE tier_name = 'Vàng' OR tier_level = 3;

UPDATE membership_tiers SET 
    tier_name_en = 'Platinum',
    benefits_en = 'VIP lounge access, guaranteed room upgrade, personal concierge'
WHERE tier_name = 'Bạch Kim' OR tier_level = 4;

-- Services
UPDATE services SET 
    service_name_en = 'Wedding Services',
    description_en = 'Complete wedding planning and hosting services with elegant venues.',
    short_description_en = 'Elegant wedding venues and planning'
WHERE slug = 'wedding-service';

UPDATE services SET 
    service_name_en = 'Conference & Events',
    description_en = 'Professional conference rooms and event spaces with full equipment.',
    short_description_en = 'Professional meeting spaces'
WHERE slug = 'conference-service';

UPDATE services SET 
    service_name_en = 'Aurora Restaurant',
    description_en = 'Fine dining experience with Vietnamese and international cuisine.',
    short_description_en = 'Fine dining restaurant'
WHERE slug = 'aurora-restaurant';

UPDATE services SET 
    service_name_en = 'Office Rental',
    description_en = 'Premium office spaces for rent with business amenities.',
    short_description_en = 'Premium office spaces'
WHERE slug = 'office-rental';

-- =====================================================
-- Create translations table for dynamic content
-- =====================================================
CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) NOT NULL DEFAULT 'en',
    translated_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (table_name, record_id, field_name, language_code),
    INDEX idx_lookup (table_name, record_id, language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
