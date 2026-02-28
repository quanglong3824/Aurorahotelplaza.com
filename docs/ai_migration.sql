-- ============================================================
-- CHAT SYSTEM MIGRATION - AI Bot Integration
-- Mô tả: Thêm người gửi là AI (bot) và bảng lưu kiến thức RAG cơ bản
-- Ngày tạo: 2026-02-28
-- Tác giả: AI Assistant
-- ============================================================

-- BƯỚC 1: Thêm type 'bot' vào `sender_type` trong bảng `chat_messages`
ALTER TABLE `chat_messages` 
MODIFY COLUMN `sender_type` enum('customer','staff','system','bot') NOT NULL DEFAULT 'customer';

-- BƯỚC 2: Tạo bảng `bot_knowledge` lưu trữ kiến thức cho AI
DROP TABLE IF EXISTS `bot_knowledge`;
CREATE TABLE `bot_knowledge` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `topic` VARCHAR(255) NOT NULL COMMENT 'Chủ đề kiến thức (ví dụ: chinh-sach-huy-phong, checkin)',
    `content` TEXT NOT NULL COMMENT 'Nội dung kiến thức cho AI học',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_topic` (`topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng tri thức RAG cho AI Chatbot';

-- Thêm một số kiến thức Demo
INSERT INTO `bot_knowledge` (`topic`, `content`) VALUES
('general_info', 'Aurora Hotel Plaza là khách sạn đạt tiêu chuẩn chất lượng cao. Giờ nhận phòng (Check-in) là 14:00 và giờ trả phòng (Check-out) là 12:00. Vị trí ở trung tâm thành phố.'),
('cancellation_policy', 'Chính sách hủy phòng: Đơn đặt phòng có thể hủy miễn phí trước 24 giờ so với giờ nhận phòng. Hủy trong vòng 24 giờ sẽ tính phí một đêm đầu tiên.'),
('payment_method', 'Khách sạn chấp nhận thanh toán qua VNPay, tiền mặt và chuyển khoản ngân hàng.');
