-- ============================================================
-- Aurora Hotel Plaza - English Translations for Database
-- Run this file in phpMyAdmin or mysql CLI to populate _en fields
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 1. ROOM TYPES - Cập nhật description_en, short_description_en
-- ============================================================

-- ID 3: Premium Deluxe Twin
UPDATE `room_types` SET
    `type_name_en`         = 'Premium Deluxe Twin',
    `description_en`       = 'The Premium Twin room at 38m² is specially designed for friend groups or small families with 2 premium single beds. Modern, airy space fully equipped with amenities including flat-screen TV, minibar, in-room safe, and private bathroom with massage shower. The room offers beautiful city views.',
    `short_description_en` = 'Ideal for groups or families with 2 premium single beds'
WHERE `room_type_id` = 3;

-- ID 8: Premium Apartment
UPDATE `room_types` SET
    `type_name_en`         = 'Premium Apartment',
    `description_en`       = 'The Premium Apartment at 65m² features a separate bedroom, spacious living room, and a fully equipped premium kitchen. Luxurious King-size bed, executive workspace, bathroom with soaking tub. Beautiful city views, ideal for families or long-stay guests.',
    `short_description_en` = 'Premium space with a separate bedroom'
WHERE `room_type_id` = 8;

-- ID 9: Modern Premium
UPDATE `room_types` SET
    `type_name_en`         = 'Modern Premium',
    `description_en`       = 'The Modern Premium at 68m² features a minimalist modern design, premium furnishings, and advanced smart home technology. Separate bedroom with King-size bed, luxurious living room, fully equipped modern kitchen. Stunning panoramic views.',
    `short_description_en` = 'Modern premium with smart home technology'
WHERE `room_type_id` = 9;

-- ID 10: Classical Premium
UPDATE `room_types` SET
    `type_name_en`         = 'Classical Premium',
    `description_en`       = 'The Classical Premium at 66m² brings a luxurious classic style with premium natural wood furniture and intricate detailing. Separate bedroom, cozy living room, fully equipped kitchen. Elegant, aristocratic space for those who love classic aesthetics.',
    `short_description_en` = 'Luxurious classic style, elegant and refined'
WHERE `room_type_id` = 10;

-- ID 11: Family Apartment
UPDATE `room_types` SET
    `type_name_en`         = 'Family Apartment',
    `description_en`       = 'The Family Apartment at 75m² is specially designed for families with 2 separate bedrooms, a spacious living room, and a full kitchen. The master bedroom has a King bed, the secondary bedroom has 2 single beds. Comfortable, safe space for children with all modern amenities.',
    `short_description_en` = 'Ideal for families with 2 separate bedrooms'
WHERE `room_type_id` = 11;

-- ID 12: Indochine Family
UPDATE `room_types` SET
    `type_name_en`         = 'Indochine Family',
    `description_en`       = 'The Indochine Family at 72m² combines traditional Indochine style with modern amenities. 2 separate bedrooms, cozy living room with natural wood furniture, and a full kitchen. Cultural space rich in Vietnamese character, ideal for families who love tradition.',
    `short_description_en` = 'Indochine style for families'
WHERE `room_type_id` = 12;

-- ID 13: Classical Family
UPDATE `room_types` SET
    `type_name_en`         = 'Classical Family',
    `description_en`       = 'The Classical Family at 78m² features a luxurious classic style with 2 spacious bedrooms, an elegant living room, and a premium kitchen. Premium natural wood furniture, intricate detailing, and a cozy atmosphere. The perfect choice for families who love classic refinement.',
    `short_description_en` = 'Luxurious classic style for families'
WHERE `room_type_id` = 13;


-- ============================================================
-- 2. MEMBERSHIP TIERS - Đã có bản dịch EN trong DB, chỉ bổ sung nếu thiếu
-- ============================================================
-- (membership_tiers đã có benefits_en từ INSERT gốc, không cần UPDATE)


-- ============================================================
-- 3. BLOG POSTS - Cập nhật title_en, excerpt_en, content_en
-- ============================================================

-- Lấy danh sách posts hiện có và update title/excerpt phổ biến
-- (Cập nhật theo post_id thực tế trong DB của bạn)

UPDATE `blog_posts` SET
    `title_en`   = 'Exploring the Weekend Experiences at Aurora Hotel Plaza',
    `excerpt_en` = 'Discover the amazing weekend getaway packages with special offers just for you at Aurora Hotel Plaza, Bien Hoa.'
WHERE `slug` = 'kham-pha-trai-nghiem-cuoi-tuan' AND (`title_en` IS NULL OR `title_en` = '');

UPDATE `blog_posts` SET
    `title_en`   = 'Aurora Hotel Plaza – The Perfect Choice for Business Travel',
    `excerpt_en` = 'With modern conference facilities and professional services, Aurora Hotel Plaza is the ideal destination for business travelers.'
WHERE `slug` = 'aurora-hotel-plaza-lua-chon-hoan-hao' AND (`title_en` IS NULL OR `title_en` = '');

UPDATE `blog_posts` SET
    `title_en`   = 'Top 5 Experiences Not to Miss at Aurora Hotel Plaza',
    `excerpt_en` = 'From the Rooftop Bar to the Spa & Massage, here are 5 experiences you absolutely must try during your stay.'
WHERE `slug` = 'top-5-trai-nghiem' AND (`title_en` IS NULL OR `title_en` = '');

-- Cập nhật tất cả blog posts còn thiếu title_en và excerpt_en (dùng title/excerpt gốc làm fallback tạm thời)
UPDATE `blog_posts`
SET `title_en` = `title`, `excerpt_en` = `excerpt`
WHERE (`title_en` IS NULL OR `title_en` = '')
  AND `status` = 'published';


-- ============================================================
-- 4. BLOG CATEGORIES - Thêm category_name_en nếu chưa có
-- ============================================================

-- Cập nhật dịch thuật cho các category phổ biến
UPDATE `blog_categories` SET `category_name_en` = 'Hotel News'       WHERE `category_name` LIKE '%Tin tức%'     AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Promotions'       WHERE `category_name` LIKE '%Khuyến mãi%'  AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Travel Guide'     WHERE `category_name` LIKE '%Du lịch%'     AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Lifestyle'        WHERE `category_name` LIKE '%Phong cách%'  AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Events'           WHERE `category_name` LIKE '%Sự kiện%'     AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Food & Beverage'  WHERE `category_name` LIKE '%Ẩm thực%'    AND (`category_name_en` IS NULL OR `category_name_en` = '');
UPDATE `blog_categories` SET `category_name_en` = 'Tips & Advice'    WHERE `category_name` LIKE '%Mẹo%'        AND (`category_name_en` IS NULL OR `category_name_en` = '');

-- Fallback: category nào chưa có EN thì dùng tạm category_name gốc
UPDATE `blog_categories`
SET `category_name_en` = `category_name`
WHERE `category_name_en` IS NULL OR `category_name_en` = '';


-- ============================================================
-- 5. PROMOTIONS - Cập nhật bản ghi còn thiếu promotion_name_en/description_en
-- ============================================================

UPDATE `promotions` SET
    `promotion_name_en` = 'National Holiday 30/4 – 2 Free Buffet Tickets',
    `description_en`    = 'Special National Holiday offer: Book a stay and receive 2 complimentary buffet tickets.'
WHERE `promotion_code` = 'LE3004BUFFET' AND (`promotion_name_en` IS NULL OR `promotion_name_en` = '');


-- ============================================================
-- 6. FAQS - Kiểm tra và bổ sung bản dịch EN còn thiếu
-- ============================================================

-- Lấy danh sách FAQs từ DB của bạn và update theo id thực tế
-- Ví dụ mẫu (thay ID thực tế):
UPDATE `faqs` SET
    `question_en` = 'What time is check-in and check-out?',
    `answer_en`   = 'Check-in is from 14:00 and check-out is before 12:00 noon. Early check-in and late check-out are available upon request, subject to availability.'
WHERE `question` LIKE '%check-in%' OR `question` LIKE '%nhận phòng%' AND (`question_en` IS NULL OR `question_en` = '');

UPDATE `faqs` SET
    `question_en` = 'Does the hotel have free parking?',
    `answer_en`   = 'Yes, Aurora Hotel Plaza offers complimentary parking for all guests.'
WHERE `question` LIKE '%đỗ xe%' OR `question` LIKE '%bãi xe%' AND (`question_en` IS NULL OR `question_en` = '');

UPDATE `faqs` SET
    `question_en` = 'Does the hotel offer airport transfer service?',
    `answer_en`   = 'Yes, we offer airport transfer service to and from Tan Son Nhat and Long Thanh airports. Please contact reception to arrange.'
WHERE `question` LIKE '%đưa đón%' OR `question` LIKE '%sân bay%' AND (`question_en` IS NULL OR `question_en` = '');

-- Fallback: bất kỳ FAQ nào còn thiếu EN thì dùng bản VI tạm thời
UPDATE `faqs`
SET `question_en` = `question`, `answer_en` = `answer`
WHERE (`question_en` IS NULL OR `question_en` = '')
   OR (`answer_en`   IS NULL OR `answer_en`   = '');


-- ============================================================
-- 7. PRICING POLICIES - Bổ sung bản dịch EN còn thiếu
-- ============================================================

UPDATE `pricing_policies`
SET `policy_name_en` = `policy_name`, `description_en` = `description`
WHERE (`policy_name_en` IS NULL OR `policy_name_en` = '')
   OR (`description_en`  IS NULL OR `description_en`  = '');


-- ============================================================
-- HOÀN TẤT
-- ============================================================
-- Sau khi chạy file này, tất cả các bảng sẽ có dữ liệu tiếng Anh.
-- Những bản ghi dùng fallback (copy từ bản VI) sẽ hiển thị tiếng Việt
-- khi người dùng chọn EN - bạn có thể vào phpMyAdmin để sửa từng bản ghi.
SELECT 'English translations updated successfully!' AS status;
