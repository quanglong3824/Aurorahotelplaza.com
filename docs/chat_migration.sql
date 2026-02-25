-- ============================================================
-- CHAT SYSTEM MIGRATION - Aurora Hotel Plaza
-- Mô tả: Nâng cấp hệ thống chat real-time (SSE + AJAX POST)
-- Ngày tạo: 2026-02-25
-- Tác giả: Developer
-- ============================================================
-- Hướng dẫn: Chạy file này trên cả localhost và production
--            Không cần xóa dữ liệu cũ, dùng ALTER TABLE an toàn
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;
SET time_zone = '+07:00';

-- ============================================================
-- BƯỚC 1: NÂNG CẤP BẢNG `chat_conversations`
-- Bảng cũ thiếu: booking_id, subject, unread counts, metadata
-- ============================================================

ALTER TABLE `chat_conversations`
    -- Gắn với booking cụ thể (khách hỏi về đơn đặt phòng)
    ADD COLUMN `booking_id` int(11) DEFAULT NULL
        COMMENT 'Liên kết đến booking nếu khách chat về đặt phòng'
        AFTER `customer_id`,

    -- Chủ đề hội thoại
    ADD COLUMN `subject` varchar(255) DEFAULT 'Hỗ trợ khách hàng'
        COMMENT 'Chủ đề / tiêu đề cuộc hội thoại'
        AFTER `booking_id`,

    -- Đếm tin nhắn chưa đọc (tránh query COUNT liên tục)
    ADD COLUMN `unread_customer` int(11) NOT NULL DEFAULT 0
        COMMENT 'Số tin nhắn staff gửi mà customer chưa đọc'
        AFTER `status`,

    ADD COLUMN `unread_staff` int(11) NOT NULL DEFAULT 0
        COMMENT 'Số tin nhắn customer gửi mà staff chưa đọc'
        AFTER `unread_customer`,

    -- Tin nhắn gần nhất (preview)
    ADD COLUMN `last_message_preview` varchar(255) DEFAULT NULL
        COMMENT 'Nội dung tóm tắt tin nhắn cuối (hiển thị ở danh sách)'
        AFTER `last_message_at`,

    -- Nguồn gốc chat
    ADD COLUMN `source` enum('website','booking','profile') DEFAULT 'website'
        COMMENT 'Khách chat từ trang nào'
        AFTER `last_message_preview`,

    -- Index để query nhanh
    ADD INDEX `idx_customer_status` (`customer_id`, `status`),
    ADD INDEX `idx_staff_open` (`staff_id`, `status`),
    ADD INDEX `idx_booking_conv` (`booking_id`),
    ADD INDEX `idx_last_msg` (`last_message_at` DESC);


-- ============================================================
-- BƯỚC 2: NÂNG CẤP BẢNG `chat_messages`
-- Bảng cũ thiếu: sender_type, message_type, metadata, index
-- ============================================================

ALTER TABLE `chat_messages`
    -- Phân biệt người gửi là customer hay staff
    ADD COLUMN `sender_type` enum('customer','staff','system') NOT NULL DEFAULT 'customer'
        COMMENT 'Loại người gửi: customer=khách, staff=nhân viên, system=thông báo tự động'
        AFTER `sender_id`,

    -- Loại tin nhắn
    ADD COLUMN `message_type` enum('text','image','file','system_note') NOT NULL DEFAULT 'text'
        COMMENT 'Loại tin nhắn: text, image, file đính kèm, hoặc ghi chú nội bộ'
        AFTER `message`,

    -- Ghi chú nội bộ (chỉ staff thấy, customer không thấy)
    ADD COLUMN `is_internal` tinyint(1) NOT NULL DEFAULT 0
        COMMENT '1 = Ghi chú nội bộ, chỉ staff thấy'
        AFTER `message_type`,

    -- Thêm index để SSE query nhanh
    ADD INDEX `idx_conv_id_msg` (`conversation_id`, `message_id`),
    ADD INDEX `idx_conv_created` (`conversation_id`, `created_at`),
    ADD INDEX `idx_unread` (`conversation_id`, `is_read`, `sender_type`);


-- ============================================================
-- BƯỚC 3: TẠO BẢNG `chat_typing`
-- Lưu trạng thái "đang gõ..." (tự động xóa sau 5 giây)
-- ============================================================

DROP TABLE IF EXISTS `chat_typing`;
CREATE TABLE `chat_typing` (
    `id`              int(11) NOT NULL AUTO_INCREMENT,
    `conversation_id` int(11) NOT NULL,
    `user_id`         int(11) NOT NULL,
    `user_type`       enum('customer','staff') NOT NULL,
    `is_typing`       tinyint(1) NOT NULL DEFAULT 1,
    `updated_at`      timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_typer` (`conversation_id`, `user_id`),
    KEY `idx_conv_typing` (`conversation_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lưu trạng thái đang gõ - SSE sẽ đọc bảng này';


-- ============================================================
-- BƯỚC 4: TẠO BẢNG `chat_quick_replies`
-- Câu trả lời mẫu cho nhân viên (tăng tốc phản hồi)
-- ============================================================

DROP TABLE IF EXISTS `chat_quick_replies`;
CREATE TABLE `chat_quick_replies` (
    `reply_id`    int(11) NOT NULL AUTO_INCREMENT,
    `category`    varchar(50) NOT NULL DEFAULT 'general'
        COMMENT 'Nhóm: general, booking, payment, complaint, ...',
    `shortcut`    varchar(50) DEFAULT NULL
        COMMENT 'Phím tắt, ví dụ: /hello, /checkout',
    `title`       varchar(100) NOT NULL
        COMMENT 'Tên hiển thị trong danh sách gợi ý',
    `content`     text NOT NULL
        COMMENT 'Nội dung tin nhắn mẫu',
    `sort_order`  int(11) DEFAULT 0,
    `is_active`   tinyint(1) DEFAULT 1,
    `created_by`  int(11) DEFAULT NULL
        COMMENT 'Admin tạo mẫu này',
    `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),

    PRIMARY KEY (`reply_id`),
    KEY `idx_category` (`category`, `sort_order`),
    KEY `idx_shortcut` (`shortcut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Câu trả lời mẫu cho staff - gõ /shortcut để dùng nhanh';

-- Dữ liệu mẫu cho quick replies
INSERT INTO `chat_quick_replies`
    (`category`, `shortcut`, `title`, `content`, `sort_order`, `created_by`)
VALUES
    ('general', '/xin-chao',
     'Chào mừng',
     'Xin chào! Chào mừng Quý khách đến với Aurora Hotel Plaza. Em có thể hỗ trợ gì cho Quý khách hôm nay ạ?',
     1, 7),

    ('general', '/cam-on',
     'Cảm ơn',
     'Cảm ơn Quý khách đã liên hệ với Aurora Hotel Plaza. Chúc Quý khách một ngày tốt lành!',
     2, 7),

    ('booking', '/gia-phong',
     'Hỏi về giá phòng',
     'Quý khách có thể xem chi tiết giá phòng tại trang Phòng của chúng tôi, hoặc em có thể tư vấn trực tiếp. Quý khách dự định lưu trú từ ngày nào đến ngày nào ạ?',
     3, 7),

    ('booking', '/check-in',
     'Giờ check-in/out',
     'Giờ nhận phòng (Check-in) là 14:00 và giờ trả phòng (Check-out) là 12:00. Quý khách có thể đặt dịch vụ nhận phòng sớm hoặc trả phòng muộn với phụ phí nhỏ.',
     4, 7),

    ('booking', '/huy-phong',
     'Chính sách hủy phòng',
     'Đơn đặt phòng có thể hủy miễn phí trước 24 giờ so với giờ nhận phòng. Hủy trong vòng 24 giờ sẽ tính phí một đêm đầu tiên. Quý khách cần hỗ trợ hủy đặt phòng nào ạ?',
     5, 7),

    ('payment', '/thanh-toan',
     'Phương thức thanh toán',
     'Aurora Hotel Plaza chấp nhận thanh toán qua VNPay, tiền mặt và chuyển khoản ngân hàng. Quý khách cần hỗ trợ về thanh toán cụ thể nào ạ?',
     6, 7),

    ('complaint', '/xin-loi',
     'Xin lỗi khách',
     'Em xin thành thật xin lỗi Quý khách về sự bất tiện này. Em sẽ ngay lập tức chuyển phản hồi của Quý khách đến bộ phận phụ trách để được xử lý sớm nhất. Quý khách vui lòng để lại thông tin liên hệ để em theo dõi và phản hồi ạ.',
     7, 7),

    ('general', '/cho-doi',
     'Yêu cầu chờ',
     'Em đang kiểm tra thông tin cho Quý khách, vui lòng chờ em trong giây lát ạ (khoảng 1-2 phút). Em sẽ phản hồi ngay!',
     8, 7);


-- ============================================================
-- BƯỚC 5: TẠO BẢNG `chat_settings`
-- Cấu hình giờ làm việc, tự động trả lời ngoài giờ
-- ============================================================

DROP TABLE IF EXISTS `chat_settings`;
CREATE TABLE `chat_settings` (
    `setting_id`          int(11) NOT NULL AUTO_INCREMENT,
    `setting_key`         varchar(100) NOT NULL,
    `setting_value`       text NOT NULL,
    `description`         varchar(255) DEFAULT NULL,
    `updated_at`          timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `unique_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cấu hình module chat';

-- Cài đặt mặc định
INSERT INTO `chat_settings` (`setting_key`, `setting_value`, `description`) VALUES
    ('chat_enabled',        '1',
     'Bật/tắt tính năng chat (1=bật, 0=tắt)'),

    ('working_hours_start', '07:00',
     'Giờ bắt đầu làm việc (hỗ trợ trực tiếp)'),

    ('working_hours_end',   '23:00',
     'Giờ kết thúc làm việc'),

    ('offline_message',
     'Hiện tại chúng tôi đã hết giờ làm việc (7:00 - 23:00). Quý khách vui lòng để lại tin nhắn, chúng tôi sẽ phản hồi vào đầu giờ làm việc tiếp theo. Hotline: 0251 3918 888',
     'Tin nhắn tự động khi ngoài giờ làm việc'),

    ('auto_reply_enabled',  '1',
     'Bật tin nhắn tự động chào khi khách bắt đầu chat'),

    ('auto_reply_message',
     'Xin chào! Cảm ơn Quý khách đã liên hệ Aurora Hotel Plaza 🌟. Nhân viên hỗ trợ sẽ phản hồi Quý khách trong vài phút. Hotline: 0251 3918 888',
     'Tin nhắn tự động chào khách'),

    ('max_file_size_mb',    '5',
     'Dung lượng file đính kèm tối đa (MB)'),

    ('allowed_file_types',  'jpg,jpeg,png,gif,pdf,doc,docx',
     'Định dạng file cho phép đính kèm'),

    ('sse_interval_seconds','2',
     'Tần suất SSE poll tin nhắn mới (giây)');


-- ============================================================
-- BƯỚC 6: THÊM PERMISSIONS CHO MODULE CHAT
-- Dựa trên bảng role_permissions hiện có
-- ============================================================

-- Xóa permissions chat cũ nếu có (tránh duplicate)
DELETE FROM `role_permissions` WHERE `module` = 'chat';

-- Admin: full quyền
INSERT INTO `role_permissions` (`role`, `module`, `action`, `allowed`) VALUES
    ('admin', 'chat', 'view',            1),
    ('admin', 'chat', 'reply',           1),
    ('admin', 'chat', 'assign',          1),
    ('admin', 'chat', 'lock',            1),
    ('admin', 'chat', 'close',           1),
    ('admin', 'chat', 'delete',          1),
    ('admin', 'chat', 'manage_settings', 1),
    ('admin', 'chat', 'view_internal',   1),

-- Lễ tân: phụ trách chat chính
    ('receptionist', 'chat', 'view',         1),
    ('receptionist', 'chat', 'reply',        1),
    ('receptionist', 'chat', 'assign',       1),
    ('receptionist', 'chat', 'lock',         1),
    ('receptionist', 'chat', 'close',        1),
    ('receptionist', 'chat', 'delete',       0),
    ('receptionist', 'chat', 'view_internal',1),

-- Sale: xem và trả lời
    ('sale', 'chat', 'view',         1),
    ('sale', 'chat', 'reply',        1),
    ('sale', 'chat', 'assign',       0),
    ('sale', 'chat', 'lock',         0),
    ('sale', 'chat', 'close',        1),
    ('sale', 'chat', 'delete',       0),
    ('sale', 'chat', 'view_internal',1),

-- Customer: chỉ xem chat của mình, gửi tin
    ('customer', 'chat', 'view',   1),
    ('customer', 'chat', 'send',   1),
    ('customer', 'chat', 'close',  0);


SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- TỔNG KẾT CẤU TRÚC SAU MIGRATION
-- ============================================================
--
--  chat_conversations (nâng cấp)
--  ├── conversation_id     INT PK
--  ├── customer_id         INT (→ users)
--  ├── booking_id          INT NULL (→ bookings) [MỚI]
--  ├── subject             VARCHAR(255) [MỚI]
--  ├── staff_id            INT NULL (→ users)
--  ├── status              ENUM(open, assigned, closed)
--  ├── locked_by           INT NULL
--  ├── locked_at           TIMESTAMP NULL
--  ├── unread_customer     INT DEFAULT 0 [MỚI]
--  ├── unread_staff        INT DEFAULT 0 [MỚI]
--  ├── last_message_at     TIMESTAMP NULL
--  ├── last_message_preview VARCHAR(255) NULL [MỚI]
--  ├── source              ENUM(website,booking,profile) [MỚI]
--  ├── created_at          TIMESTAMP
--  └── updated_at          TIMESTAMP
--
--  chat_messages (nâng cấp)
--  ├── message_id          INT PK
--  ├── conversation_id     INT (→ chat_conversations)
--  ├── sender_id           INT (→ users)
--  ├── sender_type         ENUM(customer, staff, system) [MỚI]
--  ├── message             TEXT
--  ├── message_type        ENUM(text,image,file,system_note) [MỚI]
--  ├── is_internal         TINYINT(1) DEFAULT 0 [MỚI]
--  ├── attachment          VARCHAR(255) NULL
--  ├── is_read             TINYINT(1) DEFAULT 0
--  ├── read_at             TIMESTAMP NULL
--  └── created_at          TIMESTAMP
--
--  chat_typing (MỚI)
--  ├── id                  INT PK
--  ├── conversation_id     INT
--  ├── user_id             INT
--  ├── user_type           ENUM(customer, staff)
--  ├── is_typing           TINYINT(1)
--  └── updated_at          TIMESTAMP (tự cập nhật)
--
--  chat_quick_replies (MỚI)
--  ├── reply_id            INT PK
--  ├── category            VARCHAR(50)
--  ├── shortcut            VARCHAR(50) (vd: /hello)
--  ├── title               VARCHAR(100)
--  ├── content             TEXT
--  ├── sort_order          INT
--  ├── is_active           TINYINT(1)
--  ├── created_by          INT
--  └── created_at          TIMESTAMP
--
--  chat_settings (MỚI)
--  ├── setting_id          INT PK
--  ├── setting_key         VARCHAR(100) UNIQUE
--  ├── setting_value       TEXT
--  ├── description         VARCHAR(255)
--  └── updated_at          TIMESTAMP
--
-- ============================================================
