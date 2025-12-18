-- ==============================================
-- Aurora Hotel Plaza - Apartment Inquiry System
-- Migration Script for Apartment Consultation
-- ==============================================

-- 1. Add booking_type column to room_types table
-- 'instant' = Direct booking with payment (for rooms)
-- 'inquiry' = Contact for consultation (for apartments)
ALTER TABLE `room_types` 
ADD COLUMN `booking_type` ENUM('instant', 'inquiry') NOT NULL DEFAULT 'instant' 
AFTER `category`;

-- 2. Update existing apartments to inquiry type
UPDATE `room_types` 
SET `booking_type` = 'inquiry' 
WHERE `category` = 'apartment';

-- 3. Keep rooms as instant booking
UPDATE `room_types` 
SET `booking_type` = 'instant' 
WHERE `category` = 'room';

-- ==============================================
-- 4. Create apartment_inquiries table
-- ==============================================
CREATE TABLE IF NOT EXISTS `apartment_inquiries` (
  `inquiry_id` INT(11) NOT NULL AUTO_INCREMENT,
  `inquiry_code` VARCHAR(20) NOT NULL UNIQUE,
  `user_id` INT(11) DEFAULT NULL,
  `room_type_id` INT(11) NOT NULL,
  
  -- Guest Information
  `guest_name` VARCHAR(255) NOT NULL,
  `guest_email` VARCHAR(255) NOT NULL,
  `guest_phone` VARCHAR(20) NOT NULL,
  
  -- Inquiry Details
  `preferred_check_in` DATE DEFAULT NULL,
  `preferred_check_out` DATE DEFAULT NULL,
  `duration_type` ENUM('short_term', 'long_term', 'monthly', 'yearly') DEFAULT 'short_term',
  `num_adults` INT(11) NOT NULL DEFAULT 1,
  `num_children` INT(11) DEFAULT 0,
  `message` TEXT DEFAULT NULL,
  `special_requests` TEXT DEFAULT NULL,
  
  -- Status & Processing
  `status` ENUM('new', 'contacted', 'in_progress', 'converted', 'cancelled', 'closed') DEFAULT 'new',
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `assigned_to` INT(11) DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  
  -- Conversion Tracking (if converted to actual booking)
  `converted_booking_id` INT(11) DEFAULT NULL,
  `conversion_date` TIMESTAMP NULL DEFAULT NULL,
  
  -- Timestamps
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `contacted_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  
  PRIMARY KEY (`inquiry_id`),
  INDEX `idx_inquiry_code` (`inquiry_code`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_room_type_id` (`room_type_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  
  CONSTRAINT `fk_inquiry_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inquiry_room_type` 
    FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`room_type_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inquiry_assigned` 
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inquiry_booking` 
    FOREIGN KEY (`converted_booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- 5. Create inquiry_history table for tracking
-- ==============================================
CREATE TABLE IF NOT EXISTS `apartment_inquiry_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` INT(11) NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `changed_by` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`history_id`),
  INDEX `idx_inquiry_id` (`inquiry_id`),
  
  CONSTRAINT `fk_history_inquiry` 
    FOREIGN KEY (`inquiry_id`) REFERENCES `apartment_inquiries`(`inquiry_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` 
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- 6. Create stored procedure for generating inquiry code
-- ==============================================
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_generate_inquiry_code`$$

CREATE PROCEDURE `sp_generate_inquiry_code` ()
BEGIN
    DECLARE v_code VARCHAR(20);
    DECLARE v_exists INT;
    
    REPEAT
        SET v_code = CONCAT('INQ', DATE_FORMAT(NOW(), '%Y%m%d'), UPPER(SUBSTRING(MD5(RAND()), 1, 5)));
        SELECT COUNT(*) INTO v_exists FROM apartment_inquiries WHERE inquiry_code = v_code;
    UNTIL v_exists = 0 END REPEAT;
    
    SELECT v_code as inquiry_code;
END$$

DELIMITER ;

-- ==============================================
-- 7. Add view for apartment inquiry statistics
-- ==============================================
CREATE OR REPLACE VIEW `v_inquiry_summary` AS
SELECT 
    ai.inquiry_id,
    ai.inquiry_code,
    ai.guest_name,
    ai.guest_email,
    ai.guest_phone,
    rt.type_name as apartment_name,
    rt.slug as apartment_slug,
    ai.preferred_check_in,
    ai.preferred_check_out,
    ai.duration_type,
    ai.status,
    ai.priority,
    u.full_name as assigned_staff,
    ai.created_at,
    ai.contacted_at
FROM apartment_inquiries ai
LEFT JOIN room_types rt ON ai.room_type_id = rt.room_type_id
LEFT JOIN users u ON ai.assigned_to = u.user_id
ORDER BY 
    CASE ai.priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'normal' THEN 3 
        WHEN 'low' THEN 4 
    END,
    ai.created_at DESC;

-- ==============================================
-- VERIFICATION QUERIES (Run after migration)
-- ==============================================
-- Check room_types with new column:
-- SELECT room_type_id, type_name, category, booking_type FROM room_types;

-- Check apartments are set to inquiry:
-- SELECT * FROM room_types WHERE category = 'apartment' AND booking_type = 'inquiry';

-- Check rooms are set to instant:
-- SELECT * FROM room_types WHERE category = 'room' AND booking_type = 'instant';
