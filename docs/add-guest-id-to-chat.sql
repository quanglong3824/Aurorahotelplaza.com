-- Thêm cột guest_id vào bảng chat_conversations
-- Chạy lệnh này để hỗ trợ guest chat

ALTER TABLE chat_conversations 
ADD COLUMN guest_id VARCHAR(100) DEFAULT NULL AFTER customer_id,
ADD KEY idx_guest_status (guest_id, status);

-- Cập nhật các conversation hiện tại có customer_id < 0 thành guest_id
UPDATE chat_conversations 
SET guest_id = CONCAT('guest_', ABS(customer_id))
WHERE customer_id < 0;

-- Thay đổi customer_id thành nullable để hỗ trợ cả guest
ALTER TABLE chat_conversations 
MODIFY COLUMN customer_id int(11) DEFAULT NULL;
