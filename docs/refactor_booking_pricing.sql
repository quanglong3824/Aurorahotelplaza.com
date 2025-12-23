-- =====================================================
-- REFACTOR BOOKING PRICING STRUCTURE
-- Đơn giản hóa các cột giá trong bảng bookings
-- Date: 2025-12-23
-- =====================================================

-- Bước 1: Backup dữ liệu hiện tại (đã có trong backup_2025-12-23_11-34-28.sql)

-- Bước 2: Xóa các cột không cần thiết và giữ lại cấu trúc đơn giản
ALTER TABLE `bookings`
  DROP COLUMN IF EXISTS `service_charges`,  -- Duplicate với service_fee
  DROP COLUMN IF EXISTS `base_price`,       -- Sẽ tính = room_price + extra_guest_fee + extra_bed_fee
  DROP COLUMN IF EXISTS `final_price`;      -- Duplicate với total_amount

-- Bước 3: Đảm bảo các cột còn lại có comment rõ ràng
ALTER TABLE `bookings`
  MODIFY COLUMN `room_price` DECIMAL(12,2) NOT NULL COMMENT 'Giá phòng (đã tính theo số đêm)',
  MODIFY COLUMN `extra_guest_fee` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Phụ thu khách thêm (tổng)',
  MODIFY COLUMN `extra_bed_fee` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Phụ thu giường phụ (tổng)',
  MODIFY COLUMN `service_fee` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Phí dịch vụ (nếu có)',
  MODIFY COLUMN `discount_amount` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Giảm giá (nếu có)',
  MODIFY COLUMN `total_amount` DECIMAL(12,2) NOT NULL COMMENT 'Tổng tiền = room_price + extra_guest_fee + extra_bed_fee + service_fee - discount_amount';

-- Bước 4: Cập nhật lại total_amount cho các booking hiện tại
UPDATE `bookings` 
SET `total_amount` = `room_price` + COALESCE(`extra_guest_fee`, 0) + COALESCE(`extra_bed_fee`, 0) + COALESCE(`service_fee`, 0) - COALESCE(`discount_amount`, 0)
WHERE `booking_id` > 0;

-- Bước 5: Kiểm tra kết quả
SELECT 
    booking_id,
    booking_code,
    room_price,
    extra_guest_fee,
    extra_bed_fee,
    service_fee,
    discount_amount,
    total_amount,
    (room_price + COALESCE(extra_guest_fee, 0) + COALESCE(extra_bed_fee, 0) + COALESCE(service_fee, 0) - COALESCE(discount_amount, 0)) AS calculated_total
FROM bookings
ORDER BY booking_id DESC
LIMIT 10;

-- =====================================================
-- CÔNG THỨC TÍNH GIÁ ĐƠN GIẢN:
-- =====================================================
-- total_amount = room_price + extra_guest_fee + extra_bed_fee + service_fee - discount_amount
--
-- Trong đó:
-- - room_price: Giá phòng đã tính theo số đêm (room_price_per_night * num_nights)
-- - extra_guest_fee: Tổng phụ thu khách thêm (đã tính theo số đêm)
-- - extra_bed_fee: Tổng phụ thu giường phụ (đã tính theo số đêm)
-- - service_fee: Phí dịch vụ (nếu có, hiện tại = 0)
-- - discount_amount: Giảm giá (nếu có, hiện tại = 0)
-- =====================================================
