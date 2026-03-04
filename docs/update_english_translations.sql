-- ============================================================
-- Aurora Hotel Plaza - English Translations for Production DB
-- SAFE version: ALTER TABLE trước, sau đó UPDATE data
-- Chạy toàn bộ file này trong phpMyAdmin → SQL
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- BƯỚC 1: THÊM CÁC CỘT _EN CÒN THIẾU (nếu chưa có)
-- ============================================================

-- blog_categories
ALTER TABLE `blog_categories`
    ADD COLUMN IF NOT EXISTS `category_name_en` varchar(100) DEFAULT NULL AFTER `category_name`;

-- blog_posts (kiểm tra xem các cột _en đã có chưa)
ALTER TABLE `blog_posts`
    ADD COLUMN IF NOT EXISTS `title_en`   varchar(255) DEFAULT NULL AFTER `title`,
    ADD COLUMN IF NOT EXISTS `excerpt_en` varchar(500) DEFAULT NULL AFTER `excerpt`,
    ADD COLUMN IF NOT EXISTS `content_en` longtext     DEFAULT NULL AFTER `content`;

-- room_types
ALTER TABLE `room_types`
    ADD COLUMN IF NOT EXISTS `type_name_en`         varchar(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `description_en`       text         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `short_description_en` varchar(255) DEFAULT NULL;

-- promotions
ALTER TABLE `promotions`
    ADD COLUMN IF NOT EXISTS `promotion_name_en` varchar(255) DEFAULT NULL AFTER `promotion_name`,
    ADD COLUMN IF NOT EXISTS `description_en`    text         DEFAULT NULL AFTER `description`;

-- membership_tiers
ALTER TABLE `membership_tiers`
    ADD COLUMN IF NOT EXISTS `tier_name_en` varchar(100) DEFAULT NULL AFTER `tier_name`,
    ADD COLUMN IF NOT EXISTS `benefits_en`  text         DEFAULT NULL AFTER `benefits`;

-- faqs
ALTER TABLE `faqs`
    ADD COLUMN IF NOT EXISTS `question_en` varchar(500) DEFAULT NULL AFTER `question`,
    ADD COLUMN IF NOT EXISTS `answer_en`   text         DEFAULT NULL AFTER `answer`;

-- pricing_policies
ALTER TABLE `pricing_policies`
    ADD COLUMN IF NOT EXISTS `policy_name_en` varchar(255) DEFAULT NULL AFTER `policy_name`,
    ADD COLUMN IF NOT EXISTS `description_en` text         DEFAULT NULL AFTER `description`;

-- amenities: bảng này đã có name_en sẵn từ data gốc, không cần ALTER


-- ============================================================
-- BƯỚC 2: UPDATE DỮ LIỆU TIẾNG ANH
-- ============================================================

-- ------------------------------------------------------------
-- A. ROOM TYPES
-- ------------------------------------------------------------

UPDATE `room_types` SET
    `type_name_en`         = 'Premium Deluxe Twin',
    `description_en`       = 'The Premium Twin room at 38m² is specially designed for friend groups or small families with 2 premium single beds. Modern, airy space fully equipped with amenities including flat-screen TV, minibar, in-room safe, and private bathroom with massage shower. The room offers beautiful city views.',
    `short_description_en` = 'Ideal for groups or families with 2 premium single beds'
WHERE `room_type_id` = 3;

UPDATE `room_types` SET
    `type_name_en`         = 'Premium Apartment',
    `description_en`       = 'The Premium Apartment at 65m² features a separate bedroom, spacious living room, and a fully equipped premium kitchen. Luxurious King-size bed, executive workspace, bathroom with soaking tub. Beautiful city views, ideal for families or long-stay guests.',
    `short_description_en` = 'Premium space with a separate bedroom'
WHERE `room_type_id` = 8;

UPDATE `room_types` SET
    `type_name_en`         = 'Modern Premium',
    `description_en`       = 'The Modern Premium at 68m² features a minimalist modern design, premium furnishings, and advanced smart home technology. Separate bedroom with King-size bed, luxurious living room, fully equipped modern kitchen. Stunning panoramic views.',
    `short_description_en` = 'Modern premium with smart home technology'
WHERE `room_type_id` = 9;

UPDATE `room_types` SET
    `type_name_en`         = 'Classical Premium',
    `description_en`       = 'The Classical Premium at 66m² brings a luxurious classic style with premium natural wood furniture and intricate detailing. Separate bedroom, cozy living room, fully equipped kitchen. Elegant, aristocratic space for those who love classic aesthetics.',
    `short_description_en` = 'Luxurious classic style, elegant and refined'
WHERE `room_type_id` = 10;

UPDATE `room_types` SET
    `type_name_en`         = 'Family Apartment',
    `description_en`       = 'The Family Apartment at 75m² is specially designed for families with 2 separate bedrooms, a spacious living room, and a full kitchen. The master bedroom has a King bed, the secondary bedroom has 2 single beds. Comfortable, safe space for children with all modern amenities.',
    `short_description_en` = 'Ideal for families with 2 separate bedrooms'
WHERE `room_type_id` = 11;

UPDATE `room_types` SET
    `type_name_en`         = 'Indochine Family',
    `description_en`       = 'The Indochine Family at 72m² combines traditional Indochine style with modern amenities. 2 separate bedrooms, cozy living room with natural wood furniture, and a full kitchen. Cultural space rich in Vietnamese character, ideal for families who love tradition.',
    `short_description_en` = 'Indochine style for families'
WHERE `room_type_id` = 12;

UPDATE `room_types` SET
    `type_name_en`         = 'Classical Family',
    `description_en`       = 'The Classical Family at 78m² features a luxurious classic style with 2 spacious bedrooms, an elegant living room, and a premium kitchen. Premium natural wood furniture, intricate detailing, and a cozy atmosphere. The perfect choice for families who love classic refinement.',
    `short_description_en` = 'Luxurious classic style for families'
WHERE `room_type_id` = 13;

-- Các loại phòng còn lại (đã có EN từ bản gốc, chỉ update nếu thiếu)
UPDATE `room_types` SET `type_name_en` = 'Deluxe Room',      `short_description_en` = 'Luxury room with panoramic view'      WHERE `room_type_id` = 1 AND (`type_name_en` IS NULL OR `type_name_en` = '');
UPDATE `room_types` SET `type_name_en` = 'Premium Deluxe',   `short_description_en` = 'Premium luxury with panoramic view'    WHERE `room_type_id` = 2 AND (`type_name_en` IS NULL OR `type_name_en` = '');
UPDATE `room_types` SET `type_name_en` = 'Aurora Suite',     `short_description_en` = 'Premium suite with living area'        WHERE `room_type_id` = 4 AND (`type_name_en` IS NULL OR `type_name_en` = '');
UPDATE `room_types` SET `type_name_en` = 'Studio Apartment', `short_description_en` = 'Modern studio with kitchen'            WHERE `room_type_id` = 5 AND (`type_name_en` IS NULL OR `type_name_en` = '');
UPDATE `room_types` SET `type_name_en` = 'Modern Studio',    `short_description_en` = 'Modern studio with smart home'         WHERE `room_type_id` = 6 AND (`type_name_en` IS NULL OR `type_name_en` = '');
UPDATE `room_types` SET `type_name_en` = 'Indochine Studio', `short_description_en` = 'Indochine-style studio apartment'     WHERE `room_type_id` = 7 AND (`type_name_en` IS NULL OR `type_name_en` = '');


-- ------------------------------------------------------------
-- B. MEMBERSHIP TIERS
-- ------------------------------------------------------------

UPDATE `membership_tiers` SET
    `tier_name_en` = 'Member',
    `benefits_en`  = 'Earn reward points for every booking. 10,000 VND = 1 point. Points valid for 365 days.'
WHERE `tier_id` = 1;

UPDATE `membership_tiers` SET
    `tier_name_en` = 'Silver',
    `benefits_en`  = '5% discount on stay. 1.5x point earning. Priority check-in. Welcome gift.'
WHERE `tier_id` = 2;

UPDATE `membership_tiers` SET
    `tier_name_en` = 'Gold',
    `benefits_en`  = '10% discount. 2x points. Free late checkout to 13:00. Priority booking. 1 complimentary breakfast.'
WHERE `tier_id` = 3;

UPDATE `membership_tiers` SET
    `tier_name_en` = 'Platinum',
    `benefits_en`  = '15% discount. 3x points. Late checkout 14:00. Early check-in 10:00. Free room upgrade. Daily breakfast. 20% spa discount. VIP welcome.'
WHERE `tier_id` = 4;


-- ------------------------------------------------------------
-- C. BLOG CATEGORIES
-- ------------------------------------------------------------

UPDATE `blog_categories` SET `category_name_en` = 'Hotel News'      WHERE `category_name` LIKE '%Tin tức%';
UPDATE `blog_categories` SET `category_name_en` = 'Promotions'      WHERE `category_name` LIKE '%Khuyến mãi%';
UPDATE `blog_categories` SET `category_name_en` = 'Travel Guide'    WHERE `category_name` LIKE '%Du lịch%';
UPDATE `blog_categories` SET `category_name_en` = 'Food & Beverage' WHERE `category_name` LIKE '%Ẩm thực%';
UPDATE `blog_categories` SET `category_name_en` = 'Guide & Tips'    WHERE `category_name` LIKE '%Hướng dẫn%';
UPDATE `blog_categories` SET `category_name_en` = 'Events'          WHERE `category_name` LIKE '%Sự kiện%';
UPDATE `blog_categories` SET `category_name_en` = 'Lifestyle'       WHERE `category_name` LIKE '%Phong cách%';

-- Fallback: category nào chưa có EN thì lấy tên VI tạm
UPDATE `blog_categories`
SET `category_name_en` = `category_name`
WHERE `category_name_en` IS NULL OR `category_name_en` = '';


-- ------------------------------------------------------------
-- D. BLOG POSTS (fallback: copy VI tạm thời)
-- ------------------------------------------------------------

UPDATE `blog_posts`
SET
    `title_en`   = `title`,
    `excerpt_en` = IFNULL(`excerpt`, '')
WHERE (`title_en` IS NULL OR `title_en` = '')
  AND `status` = 'published';


-- ------------------------------------------------------------
-- E. PROMOTIONS
-- ------------------------------------------------------------

UPDATE `promotions` SET
    `promotion_name_en` = 'National Holiday 30/4 – 2 Free Buffet Tickets',
    `description_en`    = 'Special National Holiday offer: Book a stay and receive 2 complimentary buffet tickets.'
WHERE `promotion_code` = 'LE3004BUFFET';

UPDATE `promotions` SET
    `promotion_name_en` = 'Teachers Day Special',
    `description_en`    = 'Special offer celebrating Teachers Day. Limited time only.'
WHERE `promotion_code` = 'TEACHERDAYS2025';

-- Fallback
UPDATE `promotions`
SET
    `promotion_name_en` = `promotion_name`,
    `description_en`    = IFNULL(`description`, '')
WHERE `promotion_name_en` IS NULL OR `promotion_name_en` = '';


-- ------------------------------------------------------------
-- F. FAQS (fallback: copy VI tạm thời)
-- ------------------------------------------------------------

UPDATE `faqs`
SET
    `question_en` = `question`,
    `answer_en`   = IFNULL(`answer`, '')
WHERE (`question_en` IS NULL OR `question_en` = '')
   OR (`answer_en`   IS NULL OR `answer_en`   = '');


-- ------------------------------------------------------------
-- G. PRICING POLICIES (fallback: copy VI tạm thời)
-- ------------------------------------------------------------

UPDATE `pricing_policies`
SET
    `policy_name_en` = `policy_name`,
    `description_en` = IFNULL(`description`, '')
WHERE (`policy_name_en` IS NULL OR `policy_name_en` = '')
   OR (`description_en`  IS NULL OR `description_en`  = '');


-- ============================================================
-- HOÀN TẤT
-- ============================================================
SELECT 'English translations applied to production successfully!' AS status;
