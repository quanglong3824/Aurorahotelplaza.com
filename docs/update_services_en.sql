-- ============================================================
-- Aurora Hotel Plaza - English translations for SERVICES
-- Run in phpMyAdmin → SQL tab on production
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- BƯỚC 1: Thêm cột _en nếu chưa có
-- ============================================================

ALTER TABLE `services`
    ADD COLUMN IF NOT EXISTS `service_name_en`        varchar(255) DEFAULT NULL AFTER `service_name`,
    ADD COLUMN IF NOT EXISTS `description_en`         text         DEFAULT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `short_description_en`   varchar(500) DEFAULT NULL AFTER `short_description`;

ALTER TABLE `service_packages`
    ADD COLUMN IF NOT EXISTS `package_name_en` varchar(200) DEFAULT NULL AFTER `package_name`,
    ADD COLUMN IF NOT EXISTS `description_en`  text         DEFAULT NULL AFTER `description`;

-- ============================================================
-- BƯỚC 2: Cập nhật dịch thuật tiếng Anh cho SERVICES
-- ============================================================

-- 1. Tổ chức tiệc cưới → Wedding Services
UPDATE `services` SET
    `service_name_en`      = 'Wedding Services',
    `short_description_en` = 'Elegant wedding venues and planning for up to 800 guests',
    `description_en`       = 'Aurora Hotel Plaza is proud to be the leading wedding venue in Dong Nai with a luxurious 500m² banquet hall, capacity for up to 800 guests. Our experienced team has successfully organized over 500 weddings.'
WHERE `slug` = 'wedding-service';

-- 2. Tổ chức hội nghị → Conference & Events
UPDATE `services` SET
    `service_name_en`      = 'Conference & Events',
    `short_description_en` = 'Modern conference rooms, full equipment, high-speed WiFi',
    `description_en`       = 'Modern conference center with 5 multi-purpose meeting rooms, capacity from 20 to 300 people. Fully equipped with state-of-the-art technology, high-speed WiFi, coffee break and buffet services.'
WHERE `slug` = 'conference-service';

-- 3. Nhà hàng Aurora → Aurora Restaurant
UPDATE `services` SET
    `service_name_en`      = 'Aurora Restaurant',
    `short_description_en` = 'Buffet with 100+ Asian & European dishes, beautiful views, 5-star chef',
    `description_en`       = 'Luxurious restaurant with capacity for 200 guests and stunning city views. Diverse buffet with over 100 Asian and European dishes: fresh seafood, Japanese sushi, Korean BBQ, traditional Vietnamese cuisine.'
WHERE `slug` = 'aurora-restaurant';

-- 4. Văn phòng cho thuê → Office Rental
UPDATE `services` SET
    `service_name_en`      = 'Office Rental',
    `short_description_en` = 'Fully furnished offices, 1Gbps WiFi, full business amenities',
    `description_en`       = 'Modern offices available in 3 types: Studio 20m², Standard 40m², Premium 80m². Fully furnished, 1Gbps WiFi, shared meeting rooms, 24/7 security.'
WHERE `slug` = 'office-rental';

-- 5. Rooftop Bar
UPDATE `services` SET
    `service_name_en`      = 'Rooftop Bar',
    `short_description_en` = 'Rooftop bar, 360° city view, cocktails, live music',
    `description_en`       = 'Rooftop bar with 360° panoramic views of Bien Hoa city. Luxurious atmosphere with live music every Friday and Saturday. Extensive cocktail menu.'
WHERE `slug` = 'rooftop-bar';

-- 6. Dịch vụ phòng 24/7 → Room Service 24/7
UPDATE `services` SET
    `service_name_en`      = 'Room Service 24/7',
    `short_description_en` = 'Order food and drinks delivered to your room 24/7',
    `description_en`       = '24/7 in-room dining service with a diverse menu of over 50 dishes available at any hour.'
WHERE `slug` = 'room-service-24-7';

-- 7. Dọn phòng hàng ngày → Daily Housekeeping
UPDATE `services` SET
    `service_name_en`      = 'Daily Housekeeping',
    `short_description_en` = 'Professional daily cleaning with fresh linen change',
    `description_en`       = 'Daily housekeeping service by our professional team, ensuring a clean, comfortable room every day.'
WHERE `slug` = 'daily-housekeeping';

-- 8. Giặt ủi cao cấp → Premium Laundry
UPDATE `services` SET
    `service_name_en`      = 'Premium Laundry',
    `short_description_en` = 'Professional laundry, pickup and delivery to your room',
    `description_en`       = 'Professional laundry service using modern machines. We pick up and deliver clean clothes directly to your room.'
WHERE `slug` = 'laundry-service';

-- 9. Massage trị liệu → Therapeutic Massage
UPDATE `services` SET
    `service_name_en`      = 'Therapeutic Massage',
    `short_description_en` = 'Relaxing full-body massage with natural essential oils',
    `description_en`       = 'Specialized therapeutic massage to relieve stress and muscle pain. Our skilled therapists use natural essential oils to provide the most relaxing experience.'
WHERE `slug` = 'therapeutic-massage';

-- 10. Sauna & Jacuzzi
UPDATE `services` SET
    `service_name_en`      = 'Sauna & Jacuzzi',
    `short_description_en` = 'Dry sauna and hot tub massage session',
    `description_en`       = 'Premium dry sauna room and high-end Jacuzzi massage bathtub. Perfect for unwinding after a long day.'
WHERE `slug` = 'sauna-jacuzzi';

-- 11. Hồ bơi & Gym → Swimming Pool & Gym
UPDATE `services` SET
    `service_name_en`      = 'Swimming Pool & Gym',
    `short_description_en` = 'Outdoor pool and modern gym — free for guests',
    `description_en`       = 'Outdoor swimming pool 25m × 12m and 200m² gym equipped with Technogym equipment. Complimentary for all hotel guests.'
WHERE `slug` = 'pool-gym';

-- 12. Đưa đón sân bay → Airport Transfer
UPDATE `services` SET
    `service_name_en`      = 'Airport Transfer',
    `short_description_en` = 'Safe and comfortable airport transfer to/from Tan Son Nhat',
    `description_en`       = 'Premium airport transfer service to and from Tan Son Nhat International Airport in a luxury vehicle. Book in advance for guaranteed pickup.'
WHERE `slug` = 'airport-transfer';

-- 13. Thuê xe tự lái → Car Rental
UPDATE `services` SET
    `service_name_en`      = 'Self-Drive Car Rental',
    `short_description_en` = '4–7 seat self-drive car rental, new vehicles, full insurance',
    `description_en`       = 'Self-drive car rental with a variety of 4–7 seat vehicles. All cars are new, fully insured, available for short or long-term rental.'
WHERE `slug` = 'car-rental';

-- 14. Trông trẻ → Babysitting
UPDATE `services` SET
    `service_name_en`      = 'Babysitting',
    `short_description_en` = 'Professional, safe childcare with camera monitoring',
    `description_en`       = 'Professional babysitting service, safe for children from 6 months to 12 years old. All caregivers are trained and supervised by camera.'
WHERE `slug` = 'babysitting';


-- ============================================================
-- BƯỚC 3: Cập nhật dịch thuật tiếng Anh cho SERVICE PACKAGES
-- ============================================================

-- Wedding packages
UPDATE `service_packages` SET
    `package_name_en` = 'Basic Package',
    `description_en`  = 'Basic wedding package for 200–300 guests.'
WHERE `slug` = 'wedding-basic';

UPDATE `service_packages` SET
    `package_name_en` = 'Standard Package',
    `description_en`  = 'Standard wedding package for 400–500 guests.'
WHERE `slug` = 'wedding-standard';

UPDATE `service_packages` SET
    `package_name_en` = 'VIP Package',
    `description_en`  = 'VIP wedding package for 600–800 guests.'
WHERE `slug` = 'wedding-vip';

-- Restaurant packages
UPDATE `service_packages` SET
    `package_name_en` = 'Breakfast Buffet',
    `description_en`  = 'Breakfast buffet with over 50 Asian and European dishes.'
WHERE `slug` = 'restaurant-breakfast';

UPDATE `service_packages` SET
    `package_name_en` = 'Lunch / Dinner Buffet',
    `description_en`  = 'Lunch or dinner buffet with over 100 dishes.'
WHERE `slug` = 'restaurant-lunch-dinner';

UPDATE `service_packages` SET
    `package_name_en` = 'VIP Set Menu',
    `description_en`  = 'Premium set menu with private dining service.'
WHERE `slug` = 'restaurant-set-menu';

-- Conference packages
UPDATE `service_packages` SET
    `package_name_en` = 'Half-Day Package',
    `description_en`  = 'Half-day conference package (4 hours) for 50–100 attendees.'
WHERE `slug` = 'conference-half-day';

UPDATE `service_packages` SET
    `package_name_en` = 'Full-Day Package',
    `description_en`  = 'Full-day conference package (8 hours) for 100–200 attendees.'
WHERE `slug` = 'conference-full-day';

UPDATE `service_packages` SET
    `package_name_en` = 'VIP Package',
    `description_en`  = 'VIP conference package for 200–300 attendees.'
WHERE `slug` = 'conference-vip';

-- Office rental packages
UPDATE `service_packages` SET
    `package_name_en` = 'Studio 20m²',
    `description_en`  = 'Studio office for 2–3 people.'
WHERE `slug` = 'office-studio';

UPDATE `service_packages` SET
    `package_name_en` = 'Standard 40m²',
    `description_en`  = 'Standard office space for 5–8 people.'
WHERE `slug` = 'office-standard';

UPDATE `service_packages` SET
    `package_name_en` = 'Premium 80m²',
    `description_en`  = 'Premium office space for 10–15 people.'
WHERE `slug` = 'office-premium';


-- ============================================================
SELECT 'Services & packages EN translations applied!' AS status;
