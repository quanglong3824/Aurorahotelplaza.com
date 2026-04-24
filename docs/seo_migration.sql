-- SEO Migration for Aurora Hotel Plaza
-- Compatible với database hiện tại
-- Chạy migration này để thêm SEO tables và data

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ========================================
-- 1. SEO Settings Table (NEW)
-- ========================================
CREATE TABLE IF NOT EXISTS `seo_settings` (
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_type` ENUM('text', 'json', 'boolean', 'number') DEFAULT 'text',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert SEO settings (INSERT IGNORE để tránh conflict)
INSERT IGNORE INTO `seo_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Aurora Hotel Plaza', 'text', 'Website name'),
('site_tagline_vi', 'Khách sạn sang trọng tại Biên Hòa', 'text', 'Site tagline Vietnamese'),
('site_tagline_en', 'Luxury Hotel in Bien Hoa', 'text', 'Site tagline English'),
('default_og_image', '/assets/img/og-image.jpg', 'text', 'Default Open Graph image'),
('twitter_handle', '@aurorahotelplaza', 'text', 'Twitter handle'),
('google_site_verification', '', 'text', 'Google Search Console verification'),
('bing_site_verification', '', 'text', 'Bing verification code'),
('facebook_pixel_id', '', 'text', 'Facebook Pixel ID'),
('google_analytics_id', '', 'text', 'Google Analytics ID'),
('schema_star_rating', '4', 'number', 'Hotel star rating'),
('schema_price_range', '$$', 'text', 'Price range notation'),
('enable_sitemap_auto', 'true', 'boolean', 'Auto-generate sitemap'),
('enable_structured_data', 'true', 'boolean', 'Enable structured data');

-- ========================================
-- 2. SEO FAQ Table (NEW)
-- ========================================
CREATE TABLE IF NOT EXISTS `seo_faqs` (
    `faq_id` INT(11) NOT NULL AUTO_INCREMENT,
    `page_slug` VARCHAR(100) NOT NULL COMMENT 'Page where FAQ appears',
    `question_vi` VARCHAR(200) NOT NULL,
    `question_en` VARCHAR(200) NOT NULL,
    `answer_vi` TEXT NOT NULL,
    `answer_en` TEXT NOT NULL,
    `display_order` INT(3) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`faq_id`),
    INDEX `idx_page_slug` (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert FAQ for structured data
INSERT IGNORE INTO `seo_faqs` (`page_slug`, `question_vi`, `question_en`, `answer_vi`, `answer_en`, `display_order`) VALUES
('index', 'Aurora Hotel Plaza ở đâu?', 'Where is Aurora Hotel Plaza located?', 'Aurora Hotel Plaza tọa lạc tại 253 Phạm Văn Thuận, Khu phố 2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai.', 'Aurora Hotel Plaza is located at 253 Pham Van Thuan, KP2, Tam Hiep Ward, Bien Hoa City, Dong Nai Province, Vietnam.', 1),
('index', 'Aurora Hotel Plaza là khách sạn mấy sao?', 'What star rating does Aurora Hotel Plaza have?', 'Aurora Hotel Plaza là khách sạn 4 sao với hồ bơi, gym, nhà hàng, spa, hội nghị.', 'Aurora Hotel Plaza is a 4-star hotel with swimming pool, gym, restaurant, spa, conference center.', 2),
('index', 'Giá phòng Aurora Hotel Plaza là bao nhiêu?', 'What are the room rates at Aurora Hotel Plaza?', 'Giá phòng từ 1.600.000 VND/đêm (Deluxe) đến 2.300.000 VND/đêm (Suite). Căn hộ từ 800.000 VND/ngày.', 'Room rates from 1,600,000 VND/night (Deluxe) to 2,300,000 VND/night (Suite). Apartments from 800,000 VND/day.', 3),
('index', 'Hotline Aurora Hotel Plaza?', 'What is Aurora Hotel Plaza hotline?', 'Hotline: 0251 3918 888. Email: info@aurorahotelplaza.com - Hỗ trợ 24/7.', 'Hotline: 0251 3918 888. Email: info@aurorahotelplaza.com - 24/7 support.', 4),
('dat-phong', 'Đặt phòng Aurora có cần thanh toán trước?', 'Do I need to pay in advance?', 'Có thể thanh toán sau hoặc trả trước qua VNPay. Check-in nhanh với QR code.', 'Pay later or pay in advance via VNPay. Quick check-in with QR code.', 1),
('dat-phong', 'Thời gian check-in/check-out Aurora?', 'Check-in and check-out times?', 'Check-in: 14:00. Check-out: 12:00. Early check-in/late check-out theo yêu cầu.', 'Check-in: 14:00. Check-out: 12:00. Early check-in/late check-out available upon request.', 2);

-- ========================================
-- 3. SEO Pages Data (INSERT IGNORE)
-- ========================================
-- seo_pages table đã tồn tại, chỉ insert data
INSERT IGNORE INTO `seo_pages` (`page_slug`, `page_type`, `meta_title_vi`, `meta_title_en`, `meta_description_vi`, `meta_description_en`, `meta_keywords_vi`, `meta_keywords_en`, `priority`, `changefreq`, `structured_data_type`) VALUES
('index', 'main', 'Aurora Hotel Plaza - Khách sạn 4 sao Biên Hòa Đồng Nai', 'Aurora Hotel Plaza - 4-Star Hotel Bien Hoa Dong Nai', 'Khách sạn Aurora 4 sao tại Biên Hòa, Đồng Nai. Phòng Deluxe, Premium, Suite. Căn hộ Indochine. Tiệc cưới, hội nghị, nhà hàng.', 'Aurora 4-star hotel in Bien Hoa, Dong Nai. Deluxe, Premium, Suite rooms. Indochine apartments. Wedding, conference, restaurant.', 'khách sạn biên hòa, aurora hotel, khách sạn 4 sao, đặt phòng biên hòa, khách sạn đồng nai', 'hotel bien hoa, aurora hotel, 4 star hotel, bien hoa booking, dong nai hotel', 1.0, 'daily', 'Hotel'),
('phong-khach-san', 'main', 'Phòng khách sạn Aurora - Deluxe, Premium, Suite', 'Aurora Hotel Rooms - Deluxe, Premium, Suite', 'Phòng khách sạn Aurora: Deluxe, Premium Deluxe, VIP Suite với đầy đủ tiện nghi, view thành phố Biên Hòa.', 'Aurora hotel rooms: Deluxe, Premium Deluxe, VIP Suite with full amenities, Bien Hoa city view.', 'phòng deluxe, phòng premium, phòng suite, giá phòng aurora, đặt phòng khách sạn', 'deluxe room, premium room, suite room, aurora room price, hotel booking', 0.9, 'weekly', 'Room'),
('can-ho', 'main', 'Căn hộ Aurora - Modern & Indochine Studio, Family', 'Aurora Apartments - Modern & Indochine Studio, Family', 'Căn hộ Aurora: Modern Studio, Indochine Studio, Premium, Family. Bếp đầy đủ, dịch vụ khách sạn 4 sao.', 'Aurora apartments: Modern Studio, Indochine Studio, Premium, Family. Full kitchen, 4-star hotel service.', 'căn hộ aurora, studio apartment, căn hộ indochine, căn hộ biên hòa', 'aurora apartment, studio apartment, indochine apartment, bien hoa apartment', 0.9, 'weekly', 'Apartment'),
('dat-phong', 'main', 'Đặt phòng Aurora Hotel Plaza - Booking Online', 'Book Aurora Hotel Plaza - Online Booking', 'Đặt phòng Aurora online. Giá tốt nhất, VNPay, QR code check-in. Hotline 0251 3918 888.', 'Book Aurora online. Best rates, VNPay, QR check-in. Hotline 0251 3918 888.', 'đặt phòng aurora, booking khách sạn, đặt phòng biên hòa', 'aurora booking, hotel booking, bien hoa booking', 0.95, 'daily', NULL),
('dich-vu', 'main', 'Dịch vụ Aurora - Tiệc cưới, Hội nghị, Nhà hàng', 'Aurora Services - Wedding, Conference, Restaurant', 'Dịch vụ Aurora: Tiệc cưới, hội nghị, nhà hàng, cho thuê văn phòng. Hotline 0251 3918 888.', 'Aurora services: Wedding, conference, restaurant, office rental. Hotline 0251 3918 888.', 'tiệc cưới aurora, hội nghị khách sạn, nhà hàng aurora', 'aurora wedding, hotel conference, aurora restaurant', 0.8, 'weekly', 'Service'),
('wedding-service', 'service', 'Tiệc cưới Aurora - Trung tâm tiệc cưới Biên Hòa', 'Aurora Wedding - Wedding Center Bien Hoa', 'Trung tâm tiệc cưới Aurora: Sảnh sang trọng, menu đa dạng. Hotline 0251 3918 888.', 'Aurora Wedding Center: Elegant venue, diverse menu. Hotline 0251 3918 888.', 'tiệc cưới biên hòa, trung tâm tiệc cưới, tiệc cưới aurora', 'wedding bien hoa, wedding center, aurora wedding', 0.7, 'monthly', 'Service'),
('conference-service', 'service', 'Hội nghị Aurora - Phòng họp Biên Hòa', 'Aurora Conference - Meeting Rooms Bien Hoa', 'Phòng hội nghị Aurora: 50-500 người, thiết bị hiện đại. Tổ chức workshop, training.', 'Aurora conference rooms: 50-500 seats, modern equipment. Workshop, training venue.', 'hội nghị biên hòa, phòng họp aurora, meeting room', 'conference bien hoa, aurora meeting, meeting room', 0.7, 'monthly', 'Service'),
('aurora-restaurant', 'service', 'Nhà hàng Aurora - Ẩm thực cao cấp Biên Hòa', 'Aurora Restaurant - Fine Dining Bien Hoa', 'Nhà hàng Aurora: Ẩm thực Việt & quốc tế, buffet sáng. Đặt bàn 0251 3918 888.', 'Aurora Restaurant: Vietnamese & international cuisine, breakfast buffet. Reserve 0251 3918 888.', 'nhà hàng aurora, ẩm thực biên hòa, buffet khách sạn', 'aurora restaurant, bien hoa dining, hotel buffet', 0.7, 'monthly', 'Service'),
('gioi-thieu', 'main', 'Giới thiệu Aurora Hotel Plaza - About Us', 'About Aurora Hotel Plaza', 'Giới thiệu Aurora: Khách sạn 4 sao, 200+ phòng, căn hộ Indochine tại Biên Hòa.', 'About Aurora: 4-star hotel, 200+ rooms, Indochine apartments in Bien Hoa.', 'giới thiệu aurora, về aurora hotel, khách sạn biên hòa', 'about aurora, aurora hotel, bien hoa hotel', 0.6, 'monthly', NULL),
('lien-he', 'main', 'Liên hệ Aurora - Hotline 0251 3918 888', 'Contact Aurora - Hotline 0251 3918 888', 'Liên hệ Aurora: 253 Phạm Văn Thuận, Biên Hòa. Hotline 0251 3918 888. Email info@aurorahotelplaza.com.', 'Contact Aurora: 253 Pham Van Thuan, Bien Hoa. Hotline 0251 3918 888. Email info@aurorahotelplaza.com.', 'liên hệ aurora, hotline aurora, địa chỉ khách sạn', 'contact aurora, aurora hotline, hotel address', 0.6, 'monthly', NULL),
('tin-tuc', 'main', 'Tin tức Aurora - Cẩm nang du lịch Biên Hòa', 'Aurora Blog - Bien Hoa Travel Guide', 'Tin tức, cẩm nang du lịch Biên Hòa, Đồng Nai. Ưu đãi từ Aurora Hotel Plaza.', 'News, travel guide Bien Hoa, Dong Nai. Aurora Hotel Plaza promotions.', 'tin tức aurora, du lịch biên hòa, blog khách sạn', 'aurora blog, bien hoa travel, hotel blog', 0.7, 'daily', 'BlogPosting'),
('thu-vien-anh', 'main', 'Thư viện ảnh Aurora - Gallery', 'Aurora Gallery - Hotel Photos', 'Thư viện ảnh Aurora: Hình ảnh phòng, tiệc cưới, hội nghị, nhà hàng.', 'Aurora Gallery: Room photos, wedding, conference, restaurant images.', 'hình ảnh aurora, gallery khách sạn, ảnh phòng', 'aurora photos, hotel gallery, room photos', 0.6, 'weekly', NULL);

SET FOREIGN_KEY_CHECKS=1;

-- ========================================
-- MIGRATION COMPLETE
-- ========================================
-- Sau khi chạy migration:
-- 1. Truy cập admin/seo.php để quản lý SEO
-- 2. seo_pages đã có trong database, chỉ thêm data mới
-- 3. seo_settings và seo_faqs là bảng mới