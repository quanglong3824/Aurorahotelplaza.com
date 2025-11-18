-- ============================================
-- COMPLETE SETUP SCRIPT
-- Chạy script này để setup hoàn chỉnh database
-- ============================================

-- BƯỚC 1: Insert 13 loại phòng với đầy đủ thông tin
SOURCE docs/INSERT_ROOM_TYPES_COMPLETE.sql;

-- BƯỚC 2: Cập nhật đường dẫn ảnh đúng
SOURCE docs/UPDATE_ROOM_TYPES_IMAGES.sql;

-- BƯỚC 3: Insert 126 phòng với liên kết đúng loại
SOURCE docs/INSERT_ROOMS_WITH_TYPES.sql;

-- HOÀN TẤT
SELECT 
    'Setup hoàn tất!' as status,
    (SELECT COUNT(*) FROM room_types) as total_room_types,
    (SELECT COUNT(*) FROM room_types WHERE category = 'room') as rooms,
    (SELECT COUNT(*) FROM room_types WHERE category = 'apartment') as apartments,
    (SELECT COUNT(*) FROM rooms) as total_rooms;
