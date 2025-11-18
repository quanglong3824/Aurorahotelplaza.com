-- ============================================
-- INSERT COMPLETE ROOM TYPES DATA
-- 4 Phòng + 9 Căn hộ = 13 loại
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ nếu có
DELETE FROM rooms;
DELETE FROM room_types;

-- Reset AUTO_INCREMENT
ALTER TABLE room_types AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;

-- ============================================
-- PHẦN 1: 4 LOẠI PHÒNG (ROOMS)
-- ============================================

-- 1. Deluxe Room
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type, 
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Deluxe',
    'deluxe',
    'room',
    'Phòng Deluxe rộng 35m² được thiết kế hiện đại với giường King size cao cấp, tầm nhìn thành phố tuyệt đẹp. Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với vòi sen massage. Đây là lựa chọn hoàn hảo cho cặp đôi hoặc khách công tác.',
    'Không gian sang trọng với đầy đủ tiện nghi hiện đại',
    2,
    35.00,
    '1 Giường King',
    'WiFi miễn phí,TV màn hình phẳng,Minibar,Két an toàn,Điều hòa,Phòng tắm riêng,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cá nhân,Bàn làm việc,Điện thoại,Dép đi trong phòng,Áo choàng tắm',
    1800000,
    2200000,
    2500000,
    '../assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg',
    '../assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg,../assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg,../assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg',
    'active',
    1,
    NOW()
);

-- 2. Premium Deluxe Room
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Premium Deluxe',
    'premium-deluxe',
    'room',
    'Phòng Premium Deluxe 40m² mang đến trải nghiệm cao cấp hơn với không gian rộng rãi, giường King size sang trọng và khu vực tiếp khách riêng biệt. Phòng có tầm nhìn panorama tuyệt đẹp ra thành phố, được trang bị đầy đủ tiện nghi hiện đại bao gồm TV thông minh, hệ thống âm thanh cao cấp và phòng tắm với bồn tắm nằm.',
    'Không gian cao cấp với tầm nhìn panorama tuyệt đẹp',
    2,
    40.00,
    '1 Giường King',
    'WiFi miễn phí,TV thông minh,Hệ thống âm thanh,Minibar cao cấp,Két an toàn,Điều hòa,Phòng tắm với bồn tắm,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cao cấp,Bàn làm việc,Khu vực tiếp khách,Máy pha cà phê,Áo choàng tắm cao cấp',
    2500000,
    3000000,
    3500000,
    '../assets/img/premium-deluxe/premium-deluxe-1.jpg',
    '../assets/img/premium-deluxe/premium-deluxe-1.jpg,../assets/img/premium-deluxe/premium-deluxe-2.jpg',
    'active',
    2,
    NOW()
);

-- 3. Premium Twin Room
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Premium Twin',
    'premium-twin',
    'room',
    'Phòng Premium Twin 38m² được thiết kế đặc biệt cho nhóm bạn hoặc gia đình nhỏ với 2 giường đơn cao cấp. Không gian hiện đại, thoáng đãng với đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với vòi sen massage. Phòng có tầm nhìn đẹp ra thành phố.',
    'Lý tưởng cho nhóm bạn hoặc gia đình với 2 giường đơn',
    2,
    38.00,
    '2 Giường Đơn',
    'WiFi miễn phí,TV màn hình phẳng,Minibar,Két an toàn,Điều hòa,Phòng tắm riêng,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cá nhân,Bàn làm việc,Điện thoại,Dép đi trong phòng,Áo choàng tắm',
    2200000,
    2700000,
    3000000,
    '../assets/img/premium-twin/premium-twin-1.jpg',
    '../assets/img/premium-twin/premium-twin-1.jpg,../assets/img/premium-twin/premium-twin-2.jpg',
    'active',
    3,
    NOW()
);

-- 4. VIP Suite
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'VIP Suite',
    'vip-suite',
    'room',
    'VIP Suite 60m² là đỉnh cao của sự sang trọng với phòng ngủ riêng biệt, phòng khách rộng rãi và phòng tắm cao cấp với bồn tắm Jacuzzi. Giường King size đặc biệt, tầm nhìn panorama 180 độ ra thành phố. Được trang bị đầy đủ tiện nghi 5 sao bao gồm TV thông minh, hệ thống âm thanh Bose, minibar cao cấp và dịch vụ butler 24/7.',
    'Đỉnh cao sang trọng với không gian riêng tư tuyệt đối',
    3,
    60.00,
    '1 Giường King + Sofa bed',
    'WiFi miễn phí,TV thông minh 55 inch,Hệ thống âm thanh Bose,Minibar cao cấp,Két an toàn điện tử,Điều hòa thông minh,Phòng tắm với Jacuzzi,Vòi sen massage,Máy sấy tóc Dyson,Đồ vệ sinh Hermes,Bàn làm việc executive,Phòng khách riêng,Máy pha cà phê Nespresso,Áo choàng tắm cao cấp,Dịch vụ butler 24/7',
    4500000,
    5500000,
    6500000,
    '../assets/img/vip-suite/vip-suite-1.jpg',
    '../assets/img/vip-suite/vip-suite-1.jpg,../assets/img/vip-suite/vip-suite-2.jpg,../assets/img/vip-suite/vip-suite-3.jpg',
    'active',
    4,
    NOW()
);

-- ============================================
-- PHẦN 2: 9 LOẠI CĂN HỘ (APARTMENTS)
-- ============================================

-- 5. Studio Apartment
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Studio Apartment',
    'studio-apartment',
    'apartment',
    'Căn hộ Studio 45m² được thiết kế thông minh với không gian mở, kết hợp phòng ngủ và phòng khách. Bếp nhỏ đầy đủ tiện nghi, giường Queen size thoải mái, khu vực làm việc riêng biệt. Lý tưởng cho khách lưu trú dài ngày hoặc cặp đôi muốn có không gian riêng tư.',
    'Không gian thông minh cho lưu trú dài ngày',
    2,
    45.00,
    '1 Giường Queen',
    'WiFi miễn phí,TV thông minh,Bếp nhỏ đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm riêng,Vòi sen,Máy sấy tóc,Bàn làm việc,Sofa,Ban công',
    2800000,
    3300000,
    3800000,
    '../assets/img/studio/studio-1.jpg',
    '../assets/img/studio/studio-1.jpg,../assets/img/studio/studio-2.jpg',
    'active',
    5,
    NOW()
);

-- 6. Modern Studio
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Modern Studio',
    'modern-studio',
    'apartment',
    'Modern Studio 48m² với thiết kế hiện đại tối giản, nội thất cao cấp và hệ thống smart home. Không gian mở thoáng đãng, bếp hiện đại đầy đủ thiết bị, giường King size sang trọng. Tầm nhìn đẹp và ánh sáng tự nhiên tràn ngập.',
    'Thiết kế hiện đại với hệ thống smart home',
    2,
    48.00,
    '1 Giường King',
    'WiFi miễn phí,TV thông minh,Smart home system,Bếp hiện đại,Tủ lạnh,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa thông minh,Máy giặt,Phòng tắm cao cấp,Vòi sen massage,Máy sấy tóc,Bàn làm việc,Sofa cao cấp,Ban công rộng',
    3200000,
    3800000,
    4300000,
    '../assets/img/modern-studio/modern-studio-1.jpg',
    '../assets/img/modern-studio/modern-studio-1.jpg,../assets/img/modern-studio/modern-studio-2.jpg',
    'active',
    6,
    NOW()
);

-- 7. Indochine Studio
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Indochine Studio',
    'indochine-studio',
    'apartment',
    'Indochine Studio 46m² mang phong cách Đông Dương độc đáo với nội thất gỗ tự nhiên, họa tiết truyền thống kết hợp hiện đại. Không gian ấm cúng, bếp đầy đủ tiện nghi, giường Queen size thoải mái. Lựa chọn hoàn hảo cho những ai yêu thích văn hóa Việt.',
    'Phong cách Đông Dương độc đáo và ấm cúng',
    2,
    46.00,
    '1 Giường Queen',
    'WiFi miễn phí,TV màn hình phẳng,Bếp đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Sofa,Ban công,Trang trí Đông Dương',
    3000000,
    3500000,
    4000000,
    '../assets/img/indochine-studio/indochine-studio-1.jpg',
    '../assets/img/indochine-studio/indochine-studio-1.jpg,../assets/img/indochine-studio/indochine-studio-2.jpg',
    'active',
    7,
    NOW()
);

-- 8. Premium Apartment
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Premium Apartment',
    'premium-apartment',
    'apartment',
    'Premium Apartment 65m² với phòng ngủ riêng biệt, phòng khách rộng rãi và bếp đầy đủ tiện nghi cao cấp. Giường King size sang trọng, khu vực làm việc executive, phòng tắm với bồn tắm. Tầm nhìn đẹp ra thành phố, lý tưởng cho gia đình hoặc lưu trú dài hạn.',
    'Không gian cao cấp với phòng ngủ riêng biệt',
    3,
    65.00,
    '1 Giường King + Sofa bed',
    'WiFi miễn phí,TV thông minh,Bếp cao cấp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 4 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,Phòng tắm với bồn tắm,Vòi sen massage,Máy sấy tóc,Bàn làm việc,Phòng khách riêng,Sofa cao cấp,Ban công lớn',
    4200000,
    5000000,
    5800000,
    '../assets/img/premium/premium-1.jpg',
    '../assets/img/premium/premium-1.jpg,../assets/img/premium/premium-2.jpg,../assets/img/premium/premium-3.jpg',
    'active',
    8,
    NOW()
);

-- 9. Modern Premium
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Modern Premium',
    'modern-premium',
    'apartment',
    'Modern Premium 68m² với thiết kế hiện đại tối giản, nội thất cao cấp và công nghệ smart home tiên tiến. Phòng ngủ riêng với giường King size, phòng khách sang trọng, bếp hiện đại đầy đủ. Tầm nhìn panorama tuyệt đẹp.',
    'Hiện đại cao cấp với công nghệ smart home',
    3,
    68.00,
    '1 Giường King + Sofa bed',
    'WiFi miễn phí,TV thông minh 55 inch,Smart home system,Bếp hiện đại cao cấp,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Máy pha cà phê,Bàn ăn 4 chỗ,Minibar cao cấp,Két an toàn điện tử,Điều hòa thông minh,Máy giặt sấy,Phòng tắm cao cấp với bồn tắm,Vòi sen massage,Máy sấy tóc Dyson,Bàn làm việc executive,Phòng khách cao cấp,Sofa da,Ban công panorama',
    4800000,
    5700000,
    6500000,
    '../assets/img/modern-premium/modern-premium-1.jpg',
    '../assets/img/modern-premium/modern-premium-1.jpg,../assets/img/modern-premium/modern-premium-2.jpg',
    'active',
    9,
    NOW()
);

-- 10. Classical Premium
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Classical Premium',
    'classical-premium',
    'apartment',
    'Classical Premium 66m² mang phong cách cổ điển sang trọng với nội thất gỗ tự nhiên cao cấp, họa tiết tinh tế. Phòng ngủ riêng, phòng khách ấm cúng, bếp đầy đủ tiện nghi. Không gian thanh lịch, quý phái cho những ai yêu thích sự cổ điển.',
    'Phong cách cổ điển sang trọng và thanh lịch',
    3,
    66.00,
    '1 Giường King + Sofa bed',
    'WiFi miễn phí,TV màn hình phẳng,Bếp cao cấp,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn 4 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm với bồn tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách,Sofa cổ điển,Ban công,Nội thất gỗ cao cấp',
    4500000,
    5300000,
    6000000,
    '../assets/img/classical-premium/classical-premium-1.jpg',
    '../assets/img/classical-premium/classical-premium-1.jpg,../assets/img/classical-premium/classical-premium-2.jpg',
    'active',
    10,
    NOW()
);

-- 11. Family Apartment
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Family Apartment',
    'family-apartment',
    'apartment',
    'Family Apartment 75m² được thiết kế đặc biệt cho gia đình với 2 phòng ngủ riêng biệt, phòng khách rộng rãi và bếp đầy đủ. Phòng ngủ chính có giường King, phòng ngủ phụ có 2 giường đơn. Không gian thoải mái, an toàn cho trẻ em với đầy đủ tiện nghi hiện đại.',
    'Lý tưởng cho gia đình với 2 phòng ngủ riêng',
    5,
    75.00,
    '1 King + 2 Đơn',
    'WiFi miễn phí,TV thông minh,Bếp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,2 Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách rộng,Sofa,Ban công,Khu vực vui chơi trẻ em',
    5500000,
    6500000,
    7500000,
    '../assets/img/family/family-1.jpg',
    '../assets/img/family/family-1.jpg,../assets/img/family/family-2.jpg,../assets/img/family/family-3.jpg',
    'active',
    11,
    NOW()
);

-- 12. Indochine Family
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Indochine Family',
    'indochine-family',
    'apartment',
    'Indochine Family 72m² kết hợp phong cách Đông Dương truyền thống với tiện nghi hiện đại. 2 phòng ngủ riêng biệt, phòng khách ấm cúng với nội thất gỗ tự nhiên, bếp đầy đủ. Không gian văn hóa đậm chất Việt, lý tưởng cho gia đình yêu thích truyền thống.',
    'Phong cách Đông Dương cho gia đình',
    5,
    72.00,
    '1 King + 2 Đơn',
    'WiFi miễn phí,TV màn hình phẳng,Bếp đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt,2 Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách,Sofa,Ban công,Nội thất Đông Dương,Trang trí truyền thống',
    5200000,
    6200000,
    7200000,
    '../assets/img/indochine-family/indochine-family-1.jpg',
    '../assets/img/indochine-family/indochine-family-1.jpg,../assets/img/indochine-family/indochine-family-2.jpg',
    'active',
    12,
    NOW()
);

-- 13. Classical Family
INSERT INTO room_types (
    type_name, slug, category, description, short_description,
    max_occupancy, size_sqm, bed_type,
    amenities, base_price, weekend_price, holiday_price,
    thumbnail, images, status, sort_order, created_at
) VALUES (
    'Classical Family',
    'classical-family',
    'apartment',
    'Classical Family 78m² mang phong cách cổ điển sang trọng với 2 phòng ngủ rộng rãi, phòng khách thanh lịch và bếp cao cấp. Nội thất gỗ tự nhiên cao cấp, họa tiết tinh tế, không gian ấm cúng. Lựa chọn hoàn hảo cho gia đình yêu thích sự quý phái cổ điển.',
    'Sang trọng cổ điển cho gia đình',
    5,
    78.00,
    '1 King + 2 Đơn',
    'WiFi miễn phí,TV màn hình phẳng,Bếp cao cấp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,2 Phòng tắm cao cấp,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách rộng,Sofa cổ điển,Ban công lớn,Nội thất gỗ cao cấp,Trang trí cổ điển',
    5800000,
    6800000,
    7800000,
    '../assets/img/classical-family/classical-family-1.jpg',
    '../assets/img/classical-family/classical-family-1.jpg,../assets/img/classical-family/classical-family-2.jpg',
    'active',
    13,
    NOW()
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUMMARY
-- ============================================
SELECT 
    'Đã insert thành công 13 loại phòng!' as message,
    (SELECT COUNT(*) FROM room_types WHERE category = 'room') as total_rooms,
    (SELECT COUNT(*) FROM room_types WHERE category = 'apartment') as total_apartments,
    (SELECT COUNT(*) FROM room_types) as total;
