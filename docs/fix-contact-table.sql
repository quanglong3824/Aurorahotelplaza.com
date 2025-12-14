-- Fix bảng contact_submissions
-- Thêm cột id auto-increment và contact_code cho mã 8 số

-- Bước 1: Thêm cột id nếu chưa có
ALTER TABLE contact_submissions 
ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- Bước 2: Thêm cột contact_code cho mã 8 số random
ALTER TABLE contact_submissions 
ADD COLUMN contact_code VARCHAR(20) NULL AFTER id;

-- Bước 3: Cập nhật contact_code cho các record cũ (dùng id)
UPDATE contact_submissions 
SET contact_code = LPAD(id, 8, '0') 
WHERE contact_code IS NULL OR contact_code = '';

-- Bước 4: Xóa cột submission_id cũ nếu nó = 0
-- (Chạy riêng nếu cần)
-- ALTER TABLE contact_submissions DROP COLUMN submission_id;
