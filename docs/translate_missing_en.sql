-- ============================================================
-- 1. Thêm cột tính năng tiếng Anh cho service_packages
-- ============================================================
ALTER TABLE `service_packages`
    ADD COLUMN IF NOT EXISTS `features_en` text DEFAULT NULL AFTER `features`;

-- ============================================================
-- 2. Cập nhật `features_en` cho Service Packages
-- ============================================================

-- Wedding Packages
UPDATE `service_packages` SET `features_en` = 'Banquet hall for 200-300 guests,Basic decoration,8-course menu,Sound & Lighting,Standard furniture,Stage backdrop' WHERE `slug` = 'wedding-basic';
UPDATE `service_packages` SET `features_en` = 'Banquet hall for 400-500 guests,Themed decoration,10-course menu + dessert,Premium sound & lighting,VIP furniture,Backdrop + Flower arch,Professional MC,Basic photography' WHERE `slug` = 'wedding-standard';
UPDATE `service_packages` SET `features_en` = 'Banquet hall for 600-800 guests,Luxury decoration,12-course menu + dessert buffet,3D sound & lighting,Premium VIP furniture,Backdrop + Flower arch + Stage,MC + Live band,Photography + Videography,Bridal makeup,Wedding car' WHERE `slug` = 'wedding-vip';

-- Restaurant Packages
UPDATE `service_packages` SET `features_en` = 'Over 50 dishes,Pho - Noodles - Porridge,Bread - Sausages,Various eggs,Fresh fruits,Juice - Coffee,Time: 6am - 10am' WHERE `slug` = 'restaurant-breakfast';
UPDATE `service_packages` SET `features_en` = 'Over 100 dishes,Fresh seafood,Japanese Sushi,Korean BBQ,Traditional Vietnamese dishes,Premium Western dishes,Various desserts,Unlimited drinks' WHERE `slug` = 'restaurant-lunch-dinner';
UPDATE `service_packages` SET `features_en` = 'Premium appetizer,Special soup,Main course (Beef/Seafood),Side dish,Dessert,Wine,Private service,VIP space' WHERE `slug` = 'restaurant-set-menu';

-- Conference Packages
UPDATE `service_packages` SET `features_en` = 'Meeting room for 50-100 people,Projector + Screen,Wireless microphone,Free WiFi,1 Coffee break,Flipchart' WHERE `slug` = 'conference-half-day';
UPDATE `service_packages` SET `features_en` = 'Meeting room for 100-200 people,Large LED screen,Premium audio system,High-speed WiFi,2 Coffee breaks,Lunch buffet,Flipchart + Pens,Technical support' WHERE `slug` = 'conference-full-day';
UPDATE `service_packages` SET `features_en` = 'Conference room for 200-300 people,3D LED screen,Professional audio,Enterprise WiFi,3 Coffee breaks,Lunch + Dinner buffet,VIP room for organizers,Professional recording,24/7 Technical support' WHERE `slug` = 'conference-vip';

-- Office Packages
UPDATE `service_packages` SET `features_en` = 'Area 20m²,2-3 people,Work desk,Ergonomic chair,Filing cabinet,Air conditioning,1Gbps WiFi' WHERE `slug` = 'office-studio';
UPDATE `service_packages` SET `features_en` = 'Area 40m²,5-8 people,Work desk,Ergonomic chair,Filing cabinet,Air conditioning,1Gbps WiFi,Shared meeting room' WHERE `slug` = 'office-standard';
UPDATE `service_packages` SET `features_en` = 'Area 80m²,10-15 people,Premium work desk,Ergonomic chair,Filing cabinet,Central air conditioning,1Gbps WiFi,Private meeting room,Private pantry area' WHERE `slug` = 'office-premium';


-- ============================================================
-- 3. Cập nhật `bed_type_en` và `amenities_en` cho CÁC CĂN HỘ (Apartments)
-- ============================================================

UPDATE `room_types` SET
    `bed_type_en` = '1 Queen Bed',
    `amenities_en` = 'Free WiFi,Smart TV,Full Kitchen,Refrigerator,Microwave,Kettle,Dining Table,Minibar,Safe,AC,Washing Machine,Private Bathroom,Shower,Hair Dryer,Desk,Sofa,Balcony'
WHERE `slug` = 'studio-apartment';

UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed',
    `amenities_en` = 'Free WiFi,Smart TV,Smart Home System,Modern Kitchen,Refrigerator,Microwave,Dishwasher,Kettle,Dining Table,Minibar,Safe,Smart AC,Washing Machine,Premium Bathroom,Massage Shower,Hair Dryer,Desk,Premium Sofa,Large Balcony'
WHERE `slug` = 'modern-studio';

UPDATE `room_types` SET
    `bed_type_en` = '1 Queen Bed',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Full Kitchen,Refrigerator,Microwave,Kettle,Dining Table,Minibar,Safe,AC,Washing Machine,Bathroom,Shower,Hair Dryer,Desk,Sofa,Balcony,Indochine Decor'
WHERE `slug` = 'indochine-studio';

UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed + Sofa Bed',
    `amenities_en` = 'Free WiFi,Smart TV,Premium Full Kitchen,Large Refrigerator,Microwave,Dishwasher,Kettle,4-seat Dining Table,Minibar,Safe,AC,Washer/Dryer,Bathroom with Bathtub,Massage Shower,Hair Dryer,Desk,Private Living Room,Premium Sofa,Large Balcony'
WHERE `slug` = 'premium-apartment';

UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed + Sofa Bed',
    `amenities_en` = 'Free WiFi,55-inch Smart TV,Smart Home System,Premium Modern Kitchen,Large Refrigerator,Microwave,Dishwasher,Coffee Machine,4-seat Dining Table,Premium Minibar,Electronic Safe,Smart AC,Washer/Dryer,Premium Bathroom with Bathtub,Massage Shower,Dyson Hair Dryer,Executive Desk,Premium Living Room,Leather Sofa,Panoramic Balcony'
WHERE `slug` = 'modern-premium';

UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed + Sofa Bed',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Premium Kitchen,Refrigerator,Microwave,Kettle,4-seat Dining Table,Minibar,Safe,AC,Washing Machine,Bathroom with Bathtub,Shower,Hair Dryer,Desk,Living Room,Classic Sofa,Balcony,Premium Wood Furniture'
WHERE `slug` = 'classical-premium';

UPDATE `room_types` SET
    `bed_type_en` = '1 King + 2 Single Beds',
    `amenities_en` = 'Free WiFi,Smart TV,Full Kitchen,Large Refrigerator,Microwave,Dishwasher,Kettle,6-seat Dining Table,Minibar,Safe,AC,Washer/Dryer,2 Bathrooms,Shower,Hair Dryer,Desk,Spacious Living Room,Sofa,Balcony,Kids Play Area'
WHERE `slug` = 'family-apartment';

UPDATE `room_types` SET
    `bed_type_en` = '1 King + 2 Single Beds',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Full Kitchen,Refrigerator,Microwave,Kettle,6-seat Dining Table,Minibar,Safe,AC,Washing Machine,2 Bathrooms,Shower,Hair Dryer,Desk,Living Room,Sofa,Balcony,Indochine Furniture,Traditional Decor'
WHERE `slug` = 'indochine-family';

UPDATE `room_types` SET
    `bed_type_en` = '1 King + 2 Single Beds',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Premium Full Kitchen,Large Refrigerator,Microwave,Dishwasher,Kettle,6-seat Dining Table,Minibar,Safe,AC,Washer/Dryer,2 Premium Bathrooms,Shower,Hair Dryer,Desk,Spacious Living Room,Classic Sofa,Large Balcony,Premium Wood Furniture,Classic Decor'
WHERE `slug` = 'classical-family';

-- ============================================================
-- The end
-- ============================================================
SELECT 'Additional translations applied successfully!' AS status;
