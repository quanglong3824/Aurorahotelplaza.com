-- Thêm cột guest_id vào bảng chat_messages
-- Chạy lệnh này để hỗ trợ guest chat

ALTER TABLE chat_messages 
ADD COLUMN guest_id VARCHAR(100) DEFAULT NULL AFTER sender_id,
ADD KEY idx_guest_messages (guest_id, created_at);
