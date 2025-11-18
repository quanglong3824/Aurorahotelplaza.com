-- ============================================
-- INSERT ROOMS DATA FOR AURORA HOTEL PLAZA
-- Tầng 7-12 với sơ đồ phòng đã định nghĩa
-- Phân bổ 126 phòng cho 13 loại phòng
-- ============================================

-- Lấy room_type_id cho từng loại phòng
SET @deluxe_id = (SELECT room_type_id FROM room_types WHERE slug = 'deluxe' LIMIT 1);
SET @premium_deluxe_id = (SELECT room_type_id FROM room_types WHERE slug = 'premium-deluxe' LIMIT 1);
SET @premium_twin_id = (SELECT room_type_id FROM room_types WHERE slug = 'premium-twin' LIMIT 1);
SET @vip_suite_id = (SELECT room_type_id FROM room_types WHERE slug = 'vip-suite' LIMIT 1);
SET @studio_id = (SELECT room_type_id FROM room_types WHERE slug = 'studio-apartment' LIMIT 1);
SET @modern_studio_id = (SELECT room_type_id FROM room_types WHERE slug = 'modern-studio' LIMIT 1);
SET @indochine_studio_id = (SELECT room_type_id FROM room_types WHERE slug = 'indochine-studio' LIMIT 1);
SET @premium_apt_id = (SELECT room_type_id FROM room_types WHERE slug = 'premium-apartment' LIMIT 1);
SET @modern_premium_id = (SELECT room_type_id FROM room_types WHERE slug = 'modern-premium' LIMIT 1);
SET @classical_premium_id = (SELECT room_type_id FROM room_types WHERE slug = 'classical-premium' LIMIT 1);
SET @family_id = (SELECT room_type_id FROM room_types WHERE slug = 'family-apartment' LIMIT 1);
SET @indochine_family_id = (SELECT room_type_id FROM room_types WHERE slug = 'indochine-family' LIMIT 1);
SET @classical_family_id = (SELECT room_type_id FROM room_types WHERE slug = 'classical-family' LIMIT 1);

-- Phân bổ phòng:
-- Tầng 7: Deluxe (10) + Premium Deluxe (9) = 19 phòng
-- Tầng 8: Premium Twin (10) + VIP Suite (9) = 19 phòng
-- Tầng 9: Studio (11) + Modern Studio (12) = 23 phòng
-- Tầng 10: Indochine Studio (11) + Premium Apt (12) = 23 phòng
-- Tầng 11: Modern Premium (11) + Classical Premium (12) = 23 phòng
-- Tầng 12: Family (10) + Indochine Family (5) + Classical Family (4) = 19 phòng

-- ============================================
-- TẦNG 7: 19 phòng (701-710, 711-712, 714-720)
-- ============================================

-- Dãy 1: 701-710 (Deluxe)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('701', @deluxe_id, 7, 'Main', 'available', NOW()),
('702', @deluxe_id, 7, 'Main', 'available', NOW()),
('703', @deluxe_id, 7, 'Main', 'available', NOW()),
('704', @deluxe_id, 7, 'Main', 'available', NOW()),
('705', @deluxe_id, 7, 'Main', 'available', NOW()),
('706', @deluxe_id, 7, 'Main', 'available', NOW()),
('707', @deluxe_id, 7, 'Main', 'available', NOW()),
('708', @deluxe_id, 7, 'Main', 'available', NOW()),
('709', @deluxe_id, 7, 'Main', 'available', NOW()),
('710', @deluxe_id, 7, 'Main', 'available', NOW());

-- Dãy 2: 711-712, 714-720 (Premium Deluxe)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('711', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('712', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('714', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('715', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('716', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('717', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('718', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('719', @premium_deluxe_id, 7, 'Main', 'available', NOW()),
('720', @premium_deluxe_id, 7, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 8: 19 phòng (801-810, 811-812, 814-819)
-- ============================================

-- Dãy 1: 801-810 (Premium Twin)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('801', @premium_twin_id, 8, 'Main', 'available', NOW()),
('802', @premium_twin_id, 8, 'Main', 'available', NOW()),
('803', @premium_twin_id, 8, 'Main', 'available', NOW()),
('804', @premium_twin_id, 8, 'Main', 'available', NOW()),
('805', @premium_twin_id, 8, 'Main', 'available', NOW()),
('806', @premium_twin_id, 8, 'Main', 'available', NOW()),
('807', @premium_twin_id, 8, 'Main', 'available', NOW()),
('808', @premium_twin_id, 8, 'Main', 'available', NOW()),
('809', @premium_twin_id, 8, 'Main', 'available', NOW()),
('810', @premium_twin_id, 8, 'Main', 'available', NOW());

-- Dãy 2: 811-812, 814-819 (VIP Suite)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('811', @vip_suite_id, 8, 'Main', 'available', NOW()),
('812', @vip_suite_id, 8, 'Main', 'available', NOW()),
('814', @vip_suite_id, 8, 'Main', 'available', NOW()),
('815', @vip_suite_id, 8, 'Main', 'available', NOW()),
('816', @vip_suite_id, 8, 'Main', 'available', NOW()),
('817', @vip_suite_id, 8, 'Main', 'available', NOW()),
('818', @vip_suite_id, 8, 'Main', 'available', NOW()),
('819', @vip_suite_id, 8, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 9: 23 phòng (901-911, 912, 914-923)
-- ============================================

-- Dãy 1: 901-911
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('901', @default_room_type_id, 9, 'Main', 'available', NOW()),
('902', @default_room_type_id, 9, 'Main', 'available', NOW()),
('903', @default_room_type_id, 9, 'Main', 'available', NOW()),
('904', @default_room_type_id, 9, 'Main', 'available', NOW()),
('905', @default_room_type_id, 9, 'Main', 'available', NOW()),
('906', @default_room_type_id, 9, 'Main', 'available', NOW()),
('907', @default_room_type_id, 9, 'Main', 'available', NOW()),
('908', @default_room_type_id, 9, 'Main', 'available', NOW()),
('909', @default_room_type_id, 9, 'Main', 'available', NOW()),
('910', @default_room_type_id, 9, 'Main', 'available', NOW()),
('911', @default_room_type_id, 9, 'Main', 'available', NOW());

-- Dãy 2: 912, 914-923 (bỏ 913)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('912', @default_room_type_id, 9, 'Main', 'available', NOW()),
('914', @default_room_type_id, 9, 'Main', 'available', NOW()),
('915', @default_room_type_id, 9, 'Main', 'available', NOW()),
('916', @default_room_type_id, 9, 'Main', 'available', NOW()),
('917', @default_room_type_id, 9, 'Main', 'available', NOW()),
('918', @default_room_type_id, 9, 'Main', 'available', NOW()),
('919', @default_room_type_id, 9, 'Main', 'available', NOW()),
('920', @default_room_type_id, 9, 'Main', 'available', NOW()),
('921', @default_room_type_id, 9, 'Main', 'available', NOW()),
('922', @default_room_type_id, 9, 'Main', 'available', NOW()),
('923', @default_room_type_id, 9, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 10: 23 phòng (1001-1011, 1012, 1014-1023)
-- ============================================

-- Dãy 1: 1001-1011
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1001', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1002', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1003', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1004', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1005', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1006', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1007', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1008', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1009', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1010', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1011', @default_room_type_id, 10, 'Main', 'available', NOW());

-- Dãy 2: 1012, 1014-1023 (bỏ 1013)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1012', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1014', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1015', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1016', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1017', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1018', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1019', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1020', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1021', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1022', @default_room_type_id, 10, 'Main', 'available', NOW()),
('1023', @default_room_type_id, 10, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 11: 23 phòng (1101-1111, 1112, 1114-1123)
-- ============================================

-- Dãy 1: 1101-1111
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1101', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1102', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1103', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1104', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1105', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1106', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1107', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1108', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1109', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1110', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1111', @default_room_type_id, 11, 'Main', 'available', NOW());

-- Dãy 2: 1112, 1114-1123 (bỏ 1113)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1112', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1114', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1115', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1116', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1117', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1118', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1119', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1120', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1121', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1122', @default_room_type_id, 11, 'Main', 'available', NOW()),
('1123', @default_room_type_id, 11, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 12: 19 phòng (1201-1210, 1211-1212, 1214-1220)
-- ============================================

-- Dãy 1: 1201-1210
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1201', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1202', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1203', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1204', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1205', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1206', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1207', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1208', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1209', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1210', @default_room_type_id, 12, 'Main', 'available', NOW());

-- Dãy 2: 1211-1212, 1214-1220 (bỏ 1213)
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1211', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1212', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1214', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1215', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1216', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1217', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1218', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1219', @default_room_type_id, 12, 'Main', 'available', NOW()),
('1220', @default_room_type_id, 12, 'Main', 'available', NOW());

-- ============================================
-- SUMMARY
-- ============================================
-- Tầng 7:  19 phòng
-- Tầng 8:  19 phòng  
-- Tầng 9:  23 phòng
-- Tầng 10: 23 phòng
-- Tầng 11: 23 phòng
-- Tầng 12: 19 phòng
-- TỔNG:    126 phòng
-- ============================================

SELECT 'Đã insert thành công 126 phòng vào database!' as message;
