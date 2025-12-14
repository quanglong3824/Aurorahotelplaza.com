-- =====================================================
-- FIX DATABASE STRUCTURE - Aurora Hotel Plaza
-- PHIÊN BẢN ĐƠN GIẢN - Chạy từng bước trên phpMyAdmin
-- =====================================================
-- QUAN TRỌNG: Backup database trước khi chạy!
-- =====================================================

-- BƯỚC 1: Kiểm tra cấu trúc hiện tại
DESCRIBE users;
SELECT user_id, email, full_name, created_at FROM users;

-- =====================================================
-- BƯỚC 2: Sửa bảng users - Thêm PRIMARY KEY và AUTO_INCREMENT
-- Chạy lệnh này TRƯỚC
-- =====================================================

-- Xóa tất cả dữ liệu users (vì tất cả đều có user_id = 0)
-- Lưu ý: Bạn cần tạo lại admin account sau khi chạy
TRUNCATE TABLE users;

-- Thêm PRIMARY KEY và AUTO_INCREMENT
ALTER TABLE `users` 
    MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ADD UNIQUE KEY `email_unique` (`email`);

-- =====================================================
-- BƯỚC 3: Tạo lại tài khoản Admin
-- =====================================================

INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at)
VALUES (
    'admin@aurorahotelplaza.com',
    '$2y$10$CKftn0hq/CpY0h9GmO3siu4T2bydgNesYNZfPzgt/LEBX8HzGvfmK',
    'Administrator',
    '0123456789',
    'admin',
    'active',
    1,
    NOW()
);

-- =====================================================
-- BƯỚC 4: Sửa bảng user_loyalty
-- =====================================================

TRUNCATE TABLE user_loyalty;

ALTER TABLE `user_loyalty`
    MODIFY `loyalty_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ADD UNIQUE KEY `user_id_unique` (`user_id`);

-- Tạo loyalty cho admin
INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
SELECT user_id, 0, 0, NOW() FROM users WHERE user_role = 'admin';

-- =====================================================
-- BƯỚC 5: Sửa bảng activity_logs (nếu cần)
-- =====================================================

-- Xóa logs cũ (tất cả đều có log_id = 0)
TRUNCATE TABLE activity_logs;

ALTER TABLE `activity_logs`
    MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- =====================================================
-- BƯỚC 6: Kiểm tra kết quả
-- =====================================================

-- Kiểm tra users
SELECT user_id, email, full_name, user_role FROM users;

-- Kiểm tra cấu trúc
SHOW CREATE TABLE users;
SHOW CREATE TABLE user_loyalty;

-- =====================================================
-- HOÀN TẤT! Mật khẩu admin mặc định: admin123
-- =====================================================
