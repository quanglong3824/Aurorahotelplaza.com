-- ============================================================
-- FIX: Đổi đơn vị giá sang tiếng Anh bằng cấu trúc thông minh hơn
-- Đảm bảo update thành công dù có khoảng trắng thừa
-- ============================================================

-- Dọn dẹp khoảng trắng trước (nếu có trên CSDL)
UPDATE `service_packages` SET `price_unit` = TRIM(`price_unit`);

-- Xóa VNĐ thành VND (nếu sót)
UPDATE `service_packages` SET `price_unit` = REPLACE(`price_unit`, 'VNĐ', 'VND');

-- Cập nhật đơn vị tiếng Anh bằng LIKE để đảm bảo không trượt phát nào
UPDATE `service_packages` SET `price_unit_en` = 'VND/person' WHERE `price_unit` LIKE '%người%';
UPDATE `service_packages` SET `price_unit_en` = 'VND/4 hours' WHERE `price_unit` LIKE '%4 giờ%';
UPDATE `service_packages` SET `price_unit_en` = 'VND/8 hours' WHERE `price_unit` LIKE '%8 giờ%';
UPDATE `service_packages` SET `price_unit_en` = 'VND/day' WHERE `price_unit` LIKE '%ngày%';
UPDATE `service_packages` SET `price_unit_en` = 'VND/month' WHERE `price_unit` LIKE '%tháng%';

-- Với gói cơ bản chỉ có chữ VND không thì gán VND cho tiếng Anh
UPDATE `service_packages` SET `price_unit_en` = 'VND' WHERE `price_unit` = 'VND' OR `price_unit_en` IS NULL;

SELECT 'Successfully forced update using LIKE for price_unit_en!' AS status;
