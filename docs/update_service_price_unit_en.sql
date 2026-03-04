-- ============================================================
-- FIX: Thêm cột `price_unit_en` để không phải dùng map cứng trên PHP
-- Dành cho bảng `service_packages`
-- ============================================================

ALTER TABLE `service_packages`
    ADD COLUMN IF NOT EXISTS `price_unit_en` varchar(50) DEFAULT NULL AFTER `price_unit`;

-- Cập nhật đơn vị tiếng Anh tương ứng
UPDATE `service_packages` SET `price_unit_en` = 'VND' WHERE `price_unit` = 'VNĐ';
UPDATE `service_packages` SET `price_unit_en` = 'VND/person' WHERE `price_unit` = 'VNĐ/người';
UPDATE `service_packages` SET `price_unit_en` = 'VND/4 hours' WHERE `price_unit` = 'VNĐ/4 giờ';
UPDATE `service_packages` SET `price_unit_en` = 'VND/8 hours' WHERE `price_unit` = 'VNĐ/8 giờ';
UPDATE `service_packages` SET `price_unit_en` = 'VND/day' WHERE (`price_unit` = 'VNĐ/ngày' OR `price_unit` = 'VND/ngày');
UPDATE `service_packages` SET `price_unit_en` = 'VND/month' WHERE (`price_unit` = 'VNĐ/tháng' OR `price_unit` = 'VND/tháng');

SELECT 'Successfully added and updated price_unit_en for service_packages!' AS status;
