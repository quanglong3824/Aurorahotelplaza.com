-- Add missing price columns to bookings table
-- Run this SQL to fix the database structure

ALTER TABLE `bookings` 
ADD COLUMN `base_price` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Giá cơ bản (room + extra fees)' AFTER `room_price`,
ADD COLUMN `service_fee` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Phí dịch vụ' AFTER `base_price`,
ADD COLUMN `final_price` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Giá cuối cùng (base - discount + service)' AFTER `discount_amount`;

-- Update existing bookings to calculate correct values
UPDATE `bookings` 
SET 
    `base_price` = `room_price` * `total_nights` + IFNULL(`extra_guest_fee`, 0) + IFNULL(`extra_bed_fee`, 0),
    `service_fee` = 0,
    `final_price` = `room_price` * `total_nights` + IFNULL(`extra_guest_fee`, 0) + IFNULL(`extra_bed_fee`, 0) - IFNULL(`discount_amount`, 0),
    `total_amount` = `room_price` * `total_nights` + IFNULL(`extra_guest_fee`, 0) + IFNULL(`extra_bed_fee`, 0) - IFNULL(`discount_amount`, 0)
WHERE `booking_id` > 0;

-- Verify the changes
SELECT 
    booking_code,
    room_price,
    total_nights,
    room_price * total_nights AS room_subtotal,
    extra_guest_fee,
    extra_bed_fee,
    base_price,
    service_fee,
    discount_amount,
    final_price,
    total_amount
FROM bookings
ORDER BY booking_id DESC
LIMIT 10;
