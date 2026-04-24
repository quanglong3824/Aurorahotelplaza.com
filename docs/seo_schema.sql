-- SEO Schema for Aurora Hotel Plaza
-- Database tables for dynamic SEO management

-- 1. SEO Pages Table - Meta tags for each page
CREATE TABLE IF NOT EXISTS `seo_pages` (
    `seo_id` INT(11) NOT NULL AUTO_INCREMENT,
    `page_slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. index, rooms, apartments, about',
    `page_type` ENUM('main', 'room', 'apartment', 'service', 'blog', 'policy') DEFAULT 'main',
    `meta_title_vi` VARCHAR(70) NOT NULL COMMENT 'Title for Vietnamese (max 70 chars)',
    `meta_title_en` VARCHAR(70) NOT NULL COMMENT 'Title for English',
    `meta_description_vi` VARCHAR(160) NOT NULL COMMENT 'Description for Vietnamese (max 160 chars)',
    `meta_description_en` VARCHAR(160) NOT NULL COMMENT 'Description for English',
    `meta_keywords_vi` VARCHAR(300) DEFAULT NULL COMMENT 'Keywords separated by comma',
    `meta_keywords_en` VARCHAR(300) DEFAULT NULL COMMENT 'Keywords separated by comma',
    `og_image` VARCHAR(255) DEFAULT '/assets/img/og-image.jpg',
    `canonical_url` VARCHAR(255) DEFAULT NULL,
    `robotsDirective` VARCHAR(50) DEFAULT 'index, follow',
    `priority` DECIMAL(2,1) DEFAULT 0.8 COMMENT 'Sitemap priority (0.0-1.0)',
    `changefreq` ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'weekly',
    `structured_data_type` VARCHAR(50) DEFAULT NULL COMMENT 'Hotel, Room, Service, BlogPosting, FAQ',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seo_id`),
    INDEX `idx_page_slug` (`page_slug`),
    INDEX `idx_page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SEO Blog Posts Table - Dynamic meta for blog
CREATE TABLE IF NOT EXISTS `seo_blog` (
    `seo_blog_id` INT(11) NOT NULL AUTO_INCREMENT,
    `blog_id` INT(11) NOT NULL,
    `meta_title_vi` VARCHAR(70) NOT NULL,
    `meta_title_en` VARCHAR(70) NOT NULL,
    `meta_description_vi` VARCHAR(160) NOT NULL,
    `meta_description_en` VARCHAR(160) NOT NULL,
    `meta_keywords_vi` VARCHAR(300) DEFAULT NULL,
    `meta_keywords_en` VARCHAR(300) DEFAULT NULL,
    `og_image` VARCHAR(255) DEFAULT NULL,
    `focus_keyword_vi` VARCHAR(100) DEFAULT NULL COMMENT 'Main keyword for SEO',
    `focus_keyword_en` VARCHAR(100) DEFAULT NULL,
    `seo_score` INT(3) DEFAULT 0 COMMENT 'SEO score 0-100',
    `is_indexed` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seo_blog_id`),
    UNIQUE KEY `uk_blog_id` (`blog_id`),
    FOREIGN KEY (`blog_id`) REFERENCES `blog_posts`(`blog_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SEO Rooms/Apartments Table - Dynamic meta for rooms
CREATE TABLE IF NOT EXISTS `seo_rooms` (
    `seo_room_id` INT(11) NOT NULL AUTO_INCREMENT,
    `room_type_id` INT(11) NOT NULL,
    `meta_title_vi` VARCHAR(70) NOT NULL,
    `meta_title_en` VARCHAR(70) NOT NULL,
    `meta_description_vi` VARCHAR(160) NOT NULL,
    `meta_description_en` VARCHAR(160) NOT NULL,
    `meta_keywords_vi` VARCHAR(300) DEFAULT NULL,
    `meta_keywords_en` VARCHAR(300) DEFAULT NULL,
    `og_image` VARCHAR(255) DEFAULT NULL,
    `focus_keyword_vi` VARCHAR(100) DEFAULT NULL,
    `focus_keyword_en` VARCHAR(100) DEFAULT NULL,
    `schema_price` DECIMAL(12,2) DEFAULT NULL COMMENT 'Price for structured data',
    `is_indexed` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seo_room_id`),
    UNIQUE KEY `uk_room_type_id` (`room_type_id`),
    FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. SEO Services Table
CREATE TABLE IF NOT EXISTS `seo_services` (
    `seo_service_id` INT(11) NOT NULL AUTO_INCREMENT,
    `service_id` INT(11) NOT NULL,
    `meta_title_vi` VARCHAR(70) NOT NULL,
    `meta_title_en` VARCHAR(70) NOT NULL,
    `meta_description_vi` VARCHAR(160) NOT NULL,
    `meta_description_en` VARCHAR(160) NOT NULL,
    `meta_keywords_vi` VARCHAR(300) DEFAULT NULL,
    `meta_keywords_en` VARCHAR(300) DEFAULT NULL,
    `og_image` VARCHAR(255) DEFAULT NULL,
    `focus_keyword_vi` VARCHAR(100) DEFAULT NULL,
    `focus_keyword_en` VARCHAR(100) DEFAULT NULL,
    `is_indexed` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seo_service_id`),
    UNIQUE KEY `uk_service_id` (`service_id`),
    FOREIGN KEY (`service_id`) REFERENCES `services`(`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. FAQ Schema Table - For FAQ structured data
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

-- 6. SEO Settings Table - Global SEO configuration
CREATE TABLE IF NOT EXISTS `seo_settings` (
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_type` ENUM('text', 'json', 'boolean', 'number') DEFAULT 'text',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default SEO settings
INSERT INTO `seo_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Aurora Hotel Plaza', 'text', 'Website name'),
('site_tagline_vi', 'Khách sạn sang trọng tại Biên Hòa', 'text', 'Site tagline Vietnamese'),
('site_tagline_en', 'Luxury Hotel in Bien Hoa', 'text', 'Site tagline English'),
('default_og_image', '/assets/img/og-image.jpg', 'text', 'Default Open Graph image'),
('twitter_handle', '@aurorahotelplaza', 'text', 'Twitter handle'),
('google_site_verification', '', 'text', 'Google Search Console verification code'),
('bing_site_verification', '', 'text', 'Bing verification code'),
('facebook_pixel_id', '', 'text', 'Facebook Pixel ID'),
('google_analytics_id', '', 'text', 'Google Analytics ID'),
('schema_organization', 'Hotel', 'text', 'Schema.org organization type'),
('schema_star_rating', '4', 'number', 'Hotel star rating'),
('schema_price_range', '$$', 'text', 'Price range notation'),
('enable_sitemap_auto', 'true', 'boolean', 'Auto-generate sitemap'),
('sitemap_last_generated', '2026-01-01 00:00:00', 'text', 'Last sitemap generation time'),
('enable_structured_data', 'true', 'boolean', 'Enable all structured data'),
('enable_hreflang', 'true', 'boolean', 'Enable hreflang tags');

-- Insert default SEO for main pages
INSERT INTO `seo_pages` (`page_slug`, `page_type`, `meta_title_vi`, `meta_title_en`, `meta_description_vi`, `meta_description_en`, `meta_keywords_vi`, `meta_keywords_en`, `priority`, `changefreq`) VALUES
('index', 'main', 'Aurora Hotel Plaza - Khách sạn 4 sao Biên Hòa Đồng Nai', 'Aurora Hotel Plaza - 4-Star Hotel in Bien Hoa Dong Nai', 'Khách sạn Aurora Hotel Plaza 4 sao hàng đầu tại Biên Hòa, Đồng Nai. Phòng nghỉ cao cấp, căn hộ Indochine, tiệc cưới, hội nghị, nhà hàng sang trọng.', 'Aurora Hotel Plaza - Premier 4-star hotel in Bien Hoa, Dong Nai. Luxury rooms, Indochine apartments, wedding venue, conference center, fine dining restaurant.', 'khách sạn biên hòa, aurora hotel plaza, khách sạn 4 sao, khách sạn đồng nai, đặt phòng khách sạn, phòng nghỉ cao cấp, căn hộ indochine', 'hotel bien hoa, aurora hotel plaza, 4 star hotel, dong nai hotel, hotel booking, luxury rooms, indochine apartment', 1.0, 'daily'),
('phong-khach-san', 'main', 'Phòng khách sạn Aurora Hotel Plaza - Deluxe, Premium, Suite', 'Hotel Rooms Aurora Hotel Plaza - Deluxe, Premium, Suite', 'Khám phá các loại phòng khách sạn cao cấp tại Aurora: Deluxe, Premium Deluxe, VIP Suite với đầy đủ tiện nghi hiện đại, view thành phố Biên Hòa.', 'Discover luxury hotel rooms at Aurora: Deluxe, Premium Deluxe, VIP Suite with modern amenities and city view of Bien Hoa.', 'phòng khách sạn, phòng deluxe, phòng premium, phòng suite, giá phòng khách sạn, đặt phòng aurora', 'hotel room, deluxe room, premium room, suite room, hotel room price, aurora booking', 0.9, 'weekly'),
('can-ho', 'main', 'Căn hộ Aurora Hotel Plaza - Modern & Indochine Studio, Family', 'Apartments Aurora Hotel Plaza - Modern & Indochine Studio, Family', 'Căn hộ cao cấp cho thuê dài hạn tại Aurora: Modern Studio, Indochine Studio, Premium, Family. Đầy đủ bếp, máy giặt, dịch vụ khách sạn 4 sao.', 'Premium apartments for long-term rent at Aurora: Modern Studio, Indochine Studio, Premium, Family. Fully equipped kitchen, laundry, 4-star hotel service.', 'căn hộ biên hòa, căn hộ aurora, studio apartment, căn hộ cho thuê, căn hộ dài hạn, indochine apartment', 'apartment bien hoa, aurora apartment, studio apartment, apartment rental, long term rental, indochine apartment', 0.9, 'weekly'),
('dat-phong', 'main', 'Đặt phòng Aurora Hotel Plaza - Khách sạn Biên Hòa Đồng Nai', 'Book Aurora Hotel Plaza - Hotel Booking Bien Hoa Dong Nai', 'Đặt phòng trực tuyến Aurora Hotel Plaza. Giá tốt nhất, thanh toán VNPay, nhận QR code check-in nhanh. Hotline 0251 3918 888.', 'Book Aurora Hotel Plaza online. Best rates, VNPay payment, quick QR code check-in. Hotline 0251 3918 888.', 'đặt phòng khách sạn, đặt phòng aurora, booking khách sạn, đặt phòng biên hòa, đặt phòng online', 'hotel booking, aurora booking, book hotel, bien hoa booking, online booking', 0.95, 'daily'),
('dich-vu', 'main', 'Dịch vụ Aurora Hotel Plaza - Tiệc cưới, Hội nghị, Nhà hàng', 'Services Aurora Hotel Plaza - Wedding, Conference, Restaurant', 'Dịch vụ đẳng cấp Aurora: Trung tâm tiệc cưới, hội nghị hội thảo, nhà hàng Aurora, cho thuê văn phòng. phục vụ chuyên nghiệp 24/7.', 'Premium services at Aurora: Wedding center, conference venue, Aurora restaurant, office rental. Professional service 24/7.', 'tiệc cưới biên hòa, hội nghị khách sạn, nhà hàng aurora, cho thuê văn phòng, dịch vụ khách sạn', 'wedding bien hoa, hotel conference, aurora restaurant, office rental, hotel service', 0.8, 'weekly'),
('wedding-service', 'service', 'Tiệc cưới Aurora Hotel Plaza - Trung tâm tiệc cưới Biên Hòa', 'Wedding Aurora Hotel Plaza - Wedding Center Bien Hoa', 'Trung tâm tiệc cưới Aurora - Không gian sang trọng, menu tiệc cưới đa dạng, dịch vụ chuyên nghiệp. Đặt tiệc cưới Hotline 0251 3918 888.', 'Aurora Wedding Center - Elegant venue, diverse wedding menu, professional service. Book wedding hotline 0251 3918 888.', 'tiệc cưới biên hòa, trung tâm tiệc cưới, tiệc cưới khách sạn, menu tiệc cưới, đặt tiệc cưới aurora', 'wedding bien hoa, wedding center, hotel wedding, wedding menu, aurora wedding', 0.7, 'monthly'),
('conference-service', 'service', 'Hội nghị Aurora Hotel Plaza - Trung tâm hội nghị Biên Hòa', 'Conference Aurora Hotel Plaza - Conference Center Bien Hoa', 'Trung tâm hội nghị Aurora - Phòng họp đa năng, thiết bị hiện đại, catering chuyên nghiệp. Tổ chức hội nghị, workshop, training.', 'Aurora Conference Center - Multi-function meeting rooms, modern equipment, professional catering. Host conferences, workshops, training.', 'hội nghị biên hòa, trung tâm hội nghị, phòng họp khách sạn, tổ chức hội nghị, meeting room aurora', 'conference bien hoa, conference center, hotel meeting room, organize conference, aurora meeting', 0.7, 'monthly'),
('aurora-restaurant', 'service', 'Nhà hàng Aurora - Ẩm thực cao cấp Biên Hòa Đồng Nai', 'Aurora Restaurant - Fine Dining Bien Hoa Dong Nai', 'Nhà hàng Aurora - Ẩm thực Việt Nam & quốc tế, không gian sang trọng, buffet sáng, tiệc tối. Đặt bàn Hotline 0251 3918 888.', 'Aurora Restaurant - Vietnamese & international cuisine, elegant ambiance, breakfast buffet, dinner parties. Reserve hotline 0251 3918 888.', 'nhà hàng biên hòa, nhà hàng aurora, ẩm thực cao cấp, buffet khách sạn, nhà hàng đồng nai', 'restaurant bien hoa, aurora restaurant, fine dining, hotel buffet, dong nai restaurant', 0.7, 'monthly'),
('office-rental', 'service', 'Cho thuê văn phòng Aurora - Văn phòng Biên Hòa Đồng Nai', 'Office Rental Aurora - Office Space Bien Hoa Dong Nai', 'Cho thuê văn phòng Aurora - Không gian chuyên nghiệp, nội thất đầy đủ, hỗ trợ IT, reception. Văn phòng cho doanh nghiệp tại Biên Hòa.', 'Aurora Office Rental - Professional space, fully furnished, IT support, reception. Office space for businesses in Bien Hoa.', 'cho thuê văn phòng, văn phòng biên hòa, office rental, văn phòng doanh nghiệp, aurora office', 'office rental, bien hoa office, office space, business office, aurora office', 0.7, 'monthly'),
('gioi-thieu', 'main', 'Giới thiệu Aurora Hotel Plaza - Lịch sử, Sứ mệnh', 'About Aurora Hotel Plaza - History, Mission', 'Giới thiệu Aurora Hotel Plaza - Khách sạn 4 sao với hơn 200 phòng, căn hộ Indochine, phục vụ khách du lịch và công tác tại Biên Hòa.', 'About Aurora Hotel Plaza - 4-star hotel with over 200 rooms, Indochine apartments, serving tourists and business travelers in Bien Hoa.', 'giới thiệu aurora, về aurora hotel, lịch sử khách sạn, sứ mệnh aurora, khách sạn 4 sao biên hòa', 'about aurora, aurora hotel history, hotel mission, 4 star hotel bien hoa', 0.6, 'monthly'),
('thu-vien-anh', 'main', 'Thư viện ảnh Aurora Hotel Plaza - Hình ảnh khách sạn', 'Gallery Aurora Hotel Plaza - Hotel Photos', 'Thư viện ảnh Aurora - Hình ảnh phòng, căn hộ, tiệc cưới, hội nghị, nhà hàng. Xem ảnh để cảm nhận không gian sang trọng.', 'Aurora Gallery - Photos of rooms, apartments, weddings, conferences, restaurant. View images to experience our elegant space.', 'hình ảnh khách sạn, thư viện ảnh aurora, ảnh phòng khách sạn, ảnh tiệc cưới, gallery hotel', 'hotel photos, aurora gallery, hotel room photos, wedding photos, hotel gallery', 0.6, 'weekly'),
('tin-tuc', 'main', 'Tin tức Aurora Hotel Plaza - Cẩm nang du lịch Biên Hòa', 'Blog Aurora Hotel Plaza - Bien Hoa Travel Guide', 'Tin tức, cẩm nang du lịch Biên Hòa, Đồng Nai. Khám phá địa danh, kinh nghiệm đặt phòng, ưu đãi từ Aurora Hotel Plaza.', 'News and travel guide for Bien Hoa, Dong Nai. Explore destinations, booking tips, promotions from Aurora Hotel Plaza.', 'tin tức aurora, cẩm nang du lịch, du lịch biên hòa, kinh nghiệm đặt phòng, blog khách sạn', 'aurora blog, travel guide, bien hoa travel, booking tips, hotel blog', 0.7, 'daily'),
('lien-he', 'main', 'Liên hệ Aurora Hotel Plaza - Địa chỉ, Hotline, Email', 'Contact Aurora Hotel Plaza - Address, Hotline, Email', 'Liên hệ Aurora Hotel Plaza - 253 Phạm Văn Thuận, KP2, Tam Hiệp, Biên Hòa. Hotline: 0251 3918 888. Email: info@aurorahotelplaza.com.', 'Contact Aurora Hotel Plaza - 253 Pham Van Thuan, KP2, Tam Hiep, Bien Hoa. Hotline: 0251 3918 888. Email: info@aurorahotelplaza.com.', 'liên hệ aurora, địa chỉ khách sạn, hotline aurora, email khách sạn, aurora contact', 'aurora contact, hotel address, aurora hotline, hotel email, contact aurora', 0.6, 'monthly'),
('kham-pha', 'main', 'Khám phá Biên Hòa - Địa danh du lịch Đồng Nai', 'Explore Bien Hoa - Dong Nai Tourist Attractions', 'Khám phá địa danh du lịch quanh Aurora: Khu Bửu Long, Văn miếu Trấn Biên, Đồng Nai river. Cẩm nang du lịch Biên Hòa.', 'Explore attractions near Aurora: Buu Long Park, Tran Bien Temple, Dong Nai river. Bien Hoa travel guide.', 'khám phá biên hòa, du lịch đồng nai, bửu long, văn miếu trấn biên, địa danh du lịch', 'explore bien hoa, dong nai tourism, buu long, tran bien temple, tourist attractions', 0.7, 'weekly'),
('chinh-sach-huy', 'policy', 'Chính sách hủy phòng Aurora Hotel Plaza', 'Cancellation Policy Aurora Hotel Plaza', 'Chính sách hủy phòng Aurora Hotel Plaza - Hủy miễn phí trước 24h, phí hủy theo thời gian. Chi tiết chính sách đặt phòng.', 'Aurora Hotel Plaza cancellation policy - Free cancellation 24h before, cancellation fees by time. Booking policy details.', 'chính sách hủy, hủy phòng khách sạn, cancellation policy, aurora policy', 'cancellation policy, hotel cancellation, cancellation policy, aurora policy', 0.4, 'monthly'),
('chinh-sach-bao-mat', 'policy', 'Chính sách bảo mật Aurora Hotel Plaza', 'Privacy Policy Aurora Hotel Plaza', 'Chính sách bảo mật Aurora Hotel Plaza - Bảo vệ thông tin cá nhân, thanh toán an toàn, GDPR compliant.', 'Aurora Hotel Plaza privacy policy - Personal data protection, secure payment, GDPR compliant.', 'chính sách bảo mật, privacy policy, bảo vệ dữ liệu, aurora privacy', 'privacy policy, data protection, gdpr, aurora privacy', 0.4, 'monthly'),
('dieu-khoan', 'policy', 'Điều khoản sử dụng Aurora Hotel Plaza', 'Terms of Service Aurora Hotel Plaza', 'Điều khoản sử dụng Aurora Hotel Plaza - Quy định đặt phòng, thanh toán, trách nhiệm khách hàng và khách sạn.', 'Aurora Hotel Plaza terms of service - Booking rules, payment, customer and hotel responsibilities.', 'điều khoản sử dụng, terms of service, quy định khách sạn, aurora terms', 'terms of service, hotel rules, aurora terms', 0.4, 'monthly');

-- Insert FAQ for structured data
INSERT INTO `seo_faqs` (`page_slug`, `question_vi`, `question_en`, `answer_vi`, `answer_en`, `display_order`) VALUES
('index', 'Aurora Hotel Plaza ở đâu?', 'Where is Aurora Hotel Plaza located?', 'Aurora Hotel Plaza tọa lạc tại 253 Phạm Văn Thuận, Khu phố 2, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai, Việt Nam.', 'Aurora Hotel Plaza is located at 253 Pham Van Thuan, KP2, Tam Hiep Ward, Bien Hoa City, Dong Nai Province, Vietnam.', 1),
('index', 'Aurora Hotel Plaza là khách sạn mấy sao?', 'What star rating does Aurora Hotel Plaza have?', 'Aurora Hotel Plaza là khách sạn 4 sao với đầy đủ tiện nghi cao cấp: hồ bơi, gym, nhà hàng, spa, hội nghị.', 'Aurora Hotel Plaza is a 4-star hotel with premium amenities: swimming pool, gym, restaurant, spa, conference center.', 2),
('index', 'Giá phòng Aurora Hotel Plaza là bao nhiêu?', 'What are the room rates at Aurora Hotel Plaza?', 'Giá phòng Aurora Hotel Plaza từ 1.600.000 VND/đêm (Deluxe) đến 2.300.000 VND/đêm (Suite). Căn hộ từ 800.000 VND/ngày.', 'Aurora Hotel Plaza room rates range from 1,600,000 VND/night (Deluxe) to 2,300,000 VND/night (Suite). Apartments from 800,000 VND/day.', 3),
('index', 'Hotline Aurora Hotel Plaza là gì?', 'What is Aurora Hotel Plaza hotline?', 'Hotline Aurora Hotel Plaza: 0251 3918 888. Hoặc email: info@aurorahotelplaza.com để được hỗ trợ 24/7.', 'Aurora Hotel Plaza hotline: 0251 3918 888. Or email: info@aurorahotelplaza.com for 24/7 support.', 4),
('dat-phong', 'Đặt phòng Aurora có cần thanh toán trước?', 'Do I need to pay in advance for Aurora booking?', 'Bạn có thể đặt phòng Aurora và thanh toán sau hoặc trả trước qua VNPay. Check-in nhanh với QR code.', 'You can book Aurora and pay later or pay in advance via VNPay. Quick check-in with QR code.', 1),
('dat-phong', 'Thời gian check-in và check-out Aurora?', 'What are check-in and check-out times at Aurora?', 'Check-in: 14:00. Check-out: 12:00. Có thể yêu cầu early check-in hoặc late check-out tùy tình trạng phòng.', 'Check-in: 14:00. Check-out: 12:00. Early check-in or late check-out available upon request based on room availability.', 2),
('wedding-service', 'Aurora có tổ chức tiệc cưới không?', 'Does Aurora host weddings?', 'Aurora Hotel Plaza có trung tâm tiệc cưới chuyên nghiệp với sảnh tiệc sang trọng, menu đa dạng, decor đẹp.', 'Aurora Hotel Plaza has a professional wedding center with elegant banquet halls, diverse menus, beautiful decor.', 1),
('conference-service', 'Aurora có phòng hội nghị không?', 'Does Aurora have conference rooms?', 'Aurora có phòng hội nghị đa năng sức chứa 50-500 người, thiết bị âm thanh, hình ảnh hiện đại.', 'Aurora has multi-function conference rooms seating 50-500 people, with modern audio and visual equipment.', 1);