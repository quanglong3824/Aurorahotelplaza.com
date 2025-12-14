-- =====================================================
-- FIX DATABASE STRUCTURE - Aurora Hotel Plaza
-- =====================================================
-- Chạy file này trên phpMyAdmin hoặc MySQL CLI để sửa lỗi
-- QUAN TRỌNG: Backup database trước khi chạy!
-- =====================================================

-- Sử dụng database
USE `auroraho_aurorahotelplaza.com`;

-- =====================================================
-- BƯỚC 1: Xóa dữ liệu duplicate trong bảng users
-- =====================================================

-- Tạo bảng tạm để lưu users unique (giữ lại record mới nhất theo email)
CREATE TEMPORARY TABLE temp_users AS
SELECT * FROM users u1
WHERE u1.created_at = (
    SELECT MAX(u2.created_at) 
    FROM users u2 
    WHERE u2.email = u1.email
);

-- Xóa tất cả records trong users
TRUNCATE TABLE users;

-- =====================================================
-- BƯỚC 2: Sửa cấu trúc bảng users - thêm PRIMARY KEY và AUTO_INCREMENT
-- =====================================================

-- Thêm PRIMARY KEY và AUTO_INCREMENT cho user_id
ALTER TABLE `users` 
    MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`user_id`),
    ADD UNIQUE KEY `email` (`email`);

-- =====================================================
-- BƯỚC 3: Insert lại dữ liệu users từ bảng tạm
-- =====================================================

-- Insert lại users (user_id sẽ tự động tăng)
INSERT INTO users (email, password_hash, full_name, phone, address, date_of_birth, gender, avatar, user_role, status, email_verified, created_at, updated_at, last_login)
SELECT email, password_hash, full_name, phone, address, date_of_birth, gender, avatar, user_role, status, email_verified, created_at, updated_at, last_login
FROM temp_users
ORDER BY created_at ASC;

-- Xóa bảng tạm
DROP TEMPORARY TABLE IF EXISTS temp_users;

-- =====================================================
-- BƯỚC 4: Sửa bảng user_loyalty
-- =====================================================

-- Xóa dữ liệu cũ (tất cả đều có user_id = 0)
TRUNCATE TABLE user_loyalty;

-- Sửa cấu trúc bảng user_loyalty
ALTER TABLE `user_loyalty`
    MODIFY `loyalty_id` int(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`loyalty_id`),
    ADD UNIQUE KEY `user_id` (`user_id`);

-- Tạo lại loyalty records cho tất cả users
INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
SELECT user_id, 0, 0, created_at FROM users;

-- =====================================================
-- BƯỚC 5: Sửa bảng activity_logs (nếu cần)
-- =====================================================

-- Thêm PRIMARY KEY cho activity_logs nếu chưa có
ALTER TABLE `activity_logs`
    MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`log_id`);

-- =====================================================
-- BƯỚC 6: Kiểm tra kết quả
-- =====================================================

-- Kiểm tra users
SELECT user_id, email, full_name, user_role, status FROM users ORDER BY user_id;

-- Kiểm tra user_loyalty
SELECT * FROM user_loyalty;

-- Kiểm tra cấu trúc bảng
DESCRIBE users;
DESCRIBE user_loyalty;

-- =====================================================
-- HOÀN TẤT!
-- =====================================================
