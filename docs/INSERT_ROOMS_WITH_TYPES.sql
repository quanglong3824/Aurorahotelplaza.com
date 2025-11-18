-- ============================================
-- INSERT 126 ROOMS WITH PROPER ROOM TYPES
-- Liên kết đầy đủ giữa rooms và room_types
-- ============================================

-- Lấy room_type_id cho từng loại
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

-- ============================================
-- TẦNG 7: Deluxe (10) + Premium Deluxe (9)
-- ============================================
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
('710', @deluxe_id, 7, 'Main', 'available', NOW()),
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
-- TẦNG 8: Premium Twin (10) + VIP Suite (9)
-- ============================================
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
('810', @premium_twin_id, 8, 'Main', 'available', NOW()),
('811', @vip_suite_id, 8, 'Main', 'available', NOW()),
('812', @vip_suite_id, 8, 'Main', 'available', NOW()),
('814', @vip_suite_id, 8, 'Main', 'available', NOW()),
('815', @vip_suite_id, 8, 'Main', 'available', NOW()),
('816', @vip_suite_id, 8, 'Main', 'available', NOW()),
('817', @vip_suite_id, 8, 'Main', 'available', NOW()),
('818', @vip_suite_id, 8, 'Main', 'available', NOW()),
('819', @vip_suite_id, 8, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 9: Studio (11) + Modern Studio (12)
-- ============================================
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('901', @studio_id, 9, 'Main', 'available', NOW()),
('902', @studio_id, 9, 'Main', 'available', NOW()),
('903', @studio_id, 9, 'Main', 'available', NOW()),
('904', @studio_id, 9, 'Main', 'available', NOW()),
('905', @studio_id, 9, 'Main', 'available', NOW()),
('906', @studio_id, 9, 'Main', 'available', NOW()),
('907', @studio_id, 9, 'Main', 'available', NOW()),
('908', @studio_id, 9, 'Main', 'available', NOW()),
('909', @studio_id, 9, 'Main', 'available', NOW()),
('910', @studio_id, 9, 'Main', 'available', NOW()),
('911', @studio_id, 9, 'Main', 'available', NOW()),
('912', @modern_studio_id, 9, 'Main', 'available', NOW()),
('914', @modern_studio_id, 9, 'Main', 'available', NOW()),
('915', @modern_studio_id, 9, 'Main', 'available', NOW()),
('916', @modern_studio_id, 9, 'Main', 'available', NOW()),
('917', @modern_studio_id, 9, 'Main', 'available', NOW()),
('918', @modern_studio_id, 9, 'Main', 'available', NOW()),
('919', @modern_studio_id, 9, 'Main', 'available', NOW()),
('920', @modern_studio_id, 9, 'Main', 'available', NOW()),
('921', @modern_studio_id, 9, 'Main', 'available', NOW()),
('922', @modern_studio_id, 9, 'Main', 'available', NOW()),
('923', @modern_studio_id, 9, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 10: Indochine Studio (11) + Premium Apt (12)
-- ============================================
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1001', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1002', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1003', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1004', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1005', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1006', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1007', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1008', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1009', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1010', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1011', @indochine_studio_id, 10, 'Main', 'available', NOW()),
('1012', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1014', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1015', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1016', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1017', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1018', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1019', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1020', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1021', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1022', @premium_apt_id, 10, 'Main', 'available', NOW()),
('1023', @premium_apt_id, 10, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 11: Modern Premium (11) + Classical Premium (12)
-- ============================================
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1101', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1102', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1103', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1104', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1105', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1106', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1107', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1108', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1109', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1110', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1111', @modern_premium_id, 11, 'Main', 'available', NOW()),
('1112', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1114', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1115', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1116', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1117', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1118', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1119', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1120', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1121', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1122', @classical_premium_id, 11, 'Main', 'available', NOW()),
('1123', @classical_premium_id, 11, 'Main', 'available', NOW());

-- ============================================
-- TẦNG 12: Family (10) + Indochine Family (5) + Classical Family (4)
-- ============================================
INSERT INTO rooms (room_number, room_type_id, floor, building, status, created_at) VALUES
('1201', @family_id, 12, 'Main', 'available', NOW()),
('1202', @family_id, 12, 'Main', 'available', NOW()),
('1203', @family_id, 12, 'Main', 'available', NOW()),
('1204', @family_id, 12, 'Main', 'available', NOW()),
('1205', @family_id, 12, 'Main', 'available', NOW()),
('1206', @family_id, 12, 'Main', 'available', NOW()),
('1207', @family_id, 12, 'Main', 'available', NOW()),
('1208', @family_id, 12, 'Main', 'available', NOW()),
('1209', @family_id, 12, 'Main', 'available', NOW()),
('1210', @family_id, 12, 'Main', 'available', NOW()),
('1211', @indochine_family_id, 12, 'Main', 'available', NOW()),
('1212', @indochine_family_id, 12, 'Main', 'available', NOW()),
('1214', @indochine_family_id, 12, 'Main', 'available', NOW()),
('1215', @indochine_family_id, 12, 'Main', 'available', NOW()),
('1216', @indochine_family_id, 12, 'Main', 'available', NOW()),
('1217', @classical_family_id, 12, 'Main', 'available', NOW()),
('1218', @classical_family_id, 12, 'Main', 'available', NOW()),
('1219', @classical_family_id, 12, 'Main', 'available', NOW()),
('1220', @classical_family_id, 12, 'Main', 'available', NOW());

-- ============================================
-- SUMMARY & VERIFICATION
-- ============================================
SELECT 
    'Đã insert 126 phòng với liên kết đầy đủ!' as message,
    (SELECT COUNT(*) FROM rooms) as total_rooms,
    (SELECT COUNT(DISTINCT room_type_id) FROM rooms) as types_used;

-- Kiểm tra phân bổ theo loại
SELECT 
    rt.type_name,
    rt.category,
    COUNT(r.room_id) as room_count
FROM room_types rt
LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
GROUP BY rt.room_type_id
ORDER BY rt.sort_order;
