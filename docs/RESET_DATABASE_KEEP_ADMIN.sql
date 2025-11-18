-- ============================================
-- RESET DATABASE - GIỮ LẠI TÀI KHOẢN ADMIN
-- Script này sẽ xóa toàn bộ dữ liệu TRỪ tài khoản admin
-- ============================================

-- Tắt foreign key checks để tránh lỗi khi xóa
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. LƯU TẠM TÀI KHOẢN ADMIN
-- ============================================
CREATE TEMPORARY TABLE temp_admin_users AS
SELECT * FROM users WHERE user_role = 'admin';

CREATE TEMPORARY TABLE temp_admin_loyalty AS
SELECT ul.* FROM user_loyalty ul
INNER JOIN users u ON ul.user_id = u.user_id
WHERE u.user_role = 'admin';

-- ============================================
-- 2. XÓA DỮ LIỆU CÁC BẢNG (GIỮ CẤU TRÚC)
-- ============================================

-- Xóa dữ liệu booking và liên quan
TRUNCATE TABLE service_bookings;
TRUNCATE TABLE booking_services;
TRUNCATE TABLE payments;
TRUNCATE TABLE bookings;

-- Xóa dữ liệu phòng
TRUNCATE TABLE rooms;
TRUNCATE TABLE room_types;
TRUNCATE TABLE seasonal_pricing;

-- Xóa dữ liệu khách hàng (trừ admin)
DELETE FROM user_loyalty WHERE user_id NOT IN (SELECT user_id FROM temp_admin_users);
DELETE FROM users WHERE user_role != 'admin';

-- Xóa dữ liệu dịch vụ
TRUNCATE TABLE services;

-- Xóa dữ liệu marketing
TRUNCATE TABLE promotions;
TRUNCATE TABLE banners;

-- Xóa dữ liệu nội dung
TRUNCATE TABLE blog_comments;
TRUNCATE TABLE blog_posts;
TRUNCATE TABLE gallery;
TRUNCATE TABLE faqs;
TRUNCATE TABLE contact_submissions;

-- Xóa dữ liệu đánh giá
TRUNCATE TABLE reviews;

-- Xóa dữ liệu thông báo
TRUNCATE TABLE notifications;

-- Xóa dữ liệu membership
TRUNCATE TABLE membership_tiers;

-- Xóa logs
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE email_logs;

-- Xóa settings (nếu muốn reset về mặc định)
-- TRUNCATE TABLE system_settings;

-- ============================================
-- 3. KHÔI PHỤC TÀI KHOẢN ADMIN
-- ============================================
-- Tài khoản admin đã được giữ lại, không cần khôi phục

-- ============================================
-- 4. RESET AUTO_INCREMENT
-- ============================================
ALTER TABLE bookings AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE room_types AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE services AUTO_INCREMENT = 1;
ALTER TABLE service_bookings AUTO_INCREMENT = 1;
ALTER TABLE promotions AUTO_INCREMENT = 1;
ALTER TABLE banners AUTO_INCREMENT = 1;
ALTER TABLE blog_posts AUTO_INCREMENT = 1;
ALTER TABLE blog_comments AUTO_INCREMENT = 1;
ALTER TABLE gallery AUTO_INCREMENT = 1;
ALTER TABLE faqs AUTO_INCREMENT = 1;
ALTER TABLE reviews AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE membership_tiers AUTO_INCREMENT = 1;
ALTER TABLE seasonal_pricing AUTO_INCREMENT = 1;
ALTER TABLE contact_submissions AUTO_INCREMENT = 1;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE email_logs AUTO_INCREMENT = 1;

-- ============================================
-- 5. DỌN DẸP TEMPORARY TABLES
-- ============================================
DROP TEMPORARY TABLE IF EXISTS temp_admin_users;
DROP TEMPORARY TABLE IF EXISTS temp_admin_loyalty;

-- Bật lại foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- HOÀN TẤT
-- ============================================
SELECT 
    'Database đã được reset thành công!' as status,
    (SELECT COUNT(*) FROM users WHERE user_role = 'admin') as admin_count,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM rooms) as total_rooms,
    (SELECT COUNT(*) FROM bookings) as total_bookings;
