-- ============================================
-- UPDATE SERVICES TABLE & INSERT FULL DATA
-- Cập nhật bảng services và thêm đầy đủ dữ liệu
-- ============================================

-- Thêm cột icon nếu chưa có
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `icon` VARCHAR(100) DEFAULT 'room_service' AFTER `short_description`;

-- Thêm cột is_featured nếu chưa có
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `available`;

-- Cập nhật category enum để thêm các loại mới
ALTER TABLE `services` 
MODIFY COLUMN `category` ENUM('room_service', 'spa', 'restaurant', 'event', 'transport', 'laundry', 'other') NOT NULL;

-- Đổi tên cột để đồng nhất
ALTER TABLE `services` 
CHANGE COLUMN `image` `thumbnail` VARCHAR(255);

ALTER TABLE `services` 
CHANGE COLUMN `available` `is_available` TINYINT(1) DEFAULT 1;

ALTER TABLE `services` 
CHANGE COLUMN `unit` `price_unit` VARCHAR(50);

-- Thêm cột images để lưu nhiều ảnh
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `images` TEXT AFTER `thumbnail`;

-- Xóa dữ liệu cũ nếu có (disable foreign key check)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `services`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- INSERT FULL SERVICES DATA
-- Dữ liệu từ các trang: wedding, conference, restaurant, office
-- ============================================

-- 1. TIỆC CƯỚI (Wedding)
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Tổ chức tiệc cưới', 'wedding-service', 'event',
'Aurora Hotel Plaza tự hào là địa điểm tổ chức tiệc cưới hàng đầu tại Đồng Nai với sảnh tiệc sang trọng 500m², sức chứa lên đến 800 khách. Đội ngũ chuyên nghiệp với hơn 500 tiệc cưới thành công. Chúng tôi cung cấp dịch vụ trọn gói: trang trí theo chủ đề, menu đa dạng Á-Âu, âm thanh ánh sáng hiện đại, MC chuyên nghiệp, nhiếp ảnh - quay phim, và dịch vụ trang điểm cô dâu.',
'Sảnh tiệc 500m², sức chứa 800 khách, dịch vụ trọn gói chuyên nghiệp',
'celebration',
'assets/img/post/wedding/Tiec-cuoi-tai-aurora-1.jpg',
0, 'Liên hệ', 1, 1, 1);

-- 2. HỘI NGHỊ - Gói Nửa ngày
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Hội nghị - Gói Nửa ngày', 'conference-half-day', 'event',
'Gói hội nghị nửa ngày (4 giờ) phù hợp cho phòng họp 50-100 người. Bao gồm: Phòng họp với Projector + Màn hình, Micro không dây, WiFi miễn phí, Coffee break 1 lần, Bảng Flipchart. Thời gian linh hoạt: buổi sáng (8h-12h) hoặc buổi chiều (14h-18h).',
'Phòng họp 50-100 người, Projector, WiFi, Coffee break 1 lần',
'meeting_room',
'assets/img/post/conference/conference-1.jpg',
5000000, 'VNĐ/4 giờ', 0, 1, 2);

-- 3. HỘI NGHỊ - Gói Cả ngày (Phổ biến)
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Hội nghị - Gói Cả ngày', 'conference-full-day', 'event',
'Gói hội nghị cả ngày (8 giờ) cho phòng họp 100-200 người. Bao gồm: Phòng họp với Màn hình LED lớn, Hệ thống âm thanh cao cấp, WiFi tốc độ cao, Coffee break 2 lần, Buffet trưa, Bảng Flipchart + Bút, Hỗ trợ kỹ thuật. Thời gian: 8h-17h.',
'Phòng họp 100-200 người, Màn hình LED, WiFi cao cấp, Buffet trưa',
'meeting_room',
'assets/img/post/conference/conference-2.jpg',
9000000, 'VNĐ/8 giờ', 1, 1, 3);

-- 4. HỘI NGHỊ - Gói VIP
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Hội nghị - Gói VIP', 'conference-vip', 'event',
'Gói hội nghị VIP cho phòng họp 200-300 người. Bao gồm: Phòng hội nghị 200-300 người, Màn hình LED 3D, Âm thanh chuyên nghiệp, WiFi doanh nghiệp, Coffee break 3 lần, Buffet trưa + tối, Phòng VIP cho ban tổ chức, Ghi hình chuyên nghiệp, Hỗ trợ kỹ thuật 24/7. Phù hợp cho hội nghị lớn, sự kiện quan trọng.',
'Phòng hội nghị 200-300 người, LED 3D, Buffet trưa+tối, Ghi hình',
'meeting_room',
'assets/img/post/conference/conference-3.jpg',
15000000, 'VNĐ/ngày', 1, 1, 4);

-- 5. NHÀ HÀNG (Restaurant)
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Nhà hàng Aurora', 'aurora-restaurant', 'restaurant',
'Nhà hàng sang trọng với sức chứa 200 khách, view thành phố tuyệt đẹp. Buffet đa dạng hơn 100 món Á-Âu: hải sản tươi sống, sushi Nhật Bản, BBQ Hàn Quốc, món Việt truyền thống, và dessert cao cấp. Đội ngũ đầu bếp 5 sao, phục vụ chuyên nghiệp. Mở cửa: Sáng 6h-10h, Trưa 11h30-14h, Tối 18h-22h.',
'Buffet 100+ món Á-Âu, view đẹp, đầu bếp 5 sao',
'restaurant',
'assets/img/post/restaurant/restaurant-1.jpg',
350000, 'VNĐ/người', 1, 1, 5);

-- 6. VĂN PHÒNG CHO THUÊ (Office)
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Văn phòng cho thuê', 'office-rental', 'other',
'Văn phòng hiện đại với 3 loại: Studio 20m² (2-3 người), Standard 40m² (5-8 người), Premium 80m² (10-15 người). Đầy đủ nội thất: bàn làm việc, ghế ergonomic, tủ tài liệu, điều hòa. Tiện ích: WiFi 1Gbps, phòng họp chung, khu pantry, bảo vệ 24/7, bãi đỗ xe miễn phí. Hợp đồng linh hoạt: theo tháng, quý, năm.',
'Văn phòng đầy đủ nội thất, WiFi 1Gbps, tiện ích đầy đủ',
'business_center',
'assets/img/post/office/office-1.jpg',
8000000, 'VNĐ/tháng', 1, 1, 6);

-- 7. ROOFTOP BAR
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Rooftop Bar', 'rooftop-bar', 'restaurant',
'Quầy bar tầng thượng với view 360 độ thành phố Biên Hòa. Không gian sang trọng, âm nhạc sống mỗi tối thứ 6-7. Menu cocktail đa dạng: Classic, Signature, Mocktail. Đồ uống cao cấp: rượu vang, whisky, bia nhập khẩu. Mở cửa 17h-24h hàng ngày.',
'Bar tầng thượng, view 360°, cocktail đa dạng, nhạc sống',
'local_bar',
'assets/img/post/restaurant/rooftop-bar-1.jpg',
150000, 'VNĐ/ly', 1, 1, 7);

-- 8. DỊCH VỤ PHÒNG 24/7
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Dịch vụ phòng 24/7', 'room-service-24-7', 'room_service',
'Dịch vụ phòng hoạt động 24/7 với thực đơn đa dạng hơn 50 món: món Việt, món Á, món Âu, đồ uống, tráng miệng. Phục vụ tận phòng trong 30 phút. Đặc biệt: bữa sáng tại phòng miễn phí (6h-10h), late night menu (22h-6h), và kids menu.',
'Gọi món ăn, đồ uống phục vụ tận phòng 24/7',
'room_service',
NULL,
0, 'Miễn phí', 0, 1, 8);

-- 9. DỌN PHÒNG HÀNG NGÀY
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Dọn phòng hàng ngày', 'daily-housekeeping', 'room_service',
'Dịch vụ dọn phòng hàng ngày với đội ngũ nhân viên chuyên nghiệp, chu đáo. Thay khăn tắm, ga giường, vệ sinh phòng sạch sẽ. Bổ sung đồ dùng: dầu gội, sữa tắm, kem đánh răng. Thời gian: 9h-16h hàng ngày. Dịch vụ turn-down buổi tối (19h-21h) theo yêu cầu.',
'Dọn dẹp và thay đổi khăn giường mỗi ngày',
'cleaning_services',
NULL,
0, 'Miễn phí', 0, 1, 9);

-- 10. GIẶT ỦI CAO CẤP
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Giặt ủi cao cấp', 'laundry-service', 'laundry',
'Dịch vụ giặt ủi chuyên nghiệp với máy móc hiện đại từ Đức. Quy trình 3 bước: phân loại - giặt - ủi. Nhận và trả đồ tại phòng. Thời gian: giao trước 10h sáng, nhận chiều cùng ngày. Dịch vụ express (4 giờ) có phụ thu 50%. Giặt khô cho đồ cao cấp.',
'Giặt ủi quần áo chuyên nghiệp, giao nhận tận phòng',
'local_laundry_service',
NULL,
50000, 'VNĐ/kg', 0, 1, 10);

-- 11. MASSAGE TRỊ LIỆU
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Massage trị liệu', 'therapeutic-massage', 'spa',
'Massage trị liệu chuyên sâu giúp giảm căng thẳng, đau nhức cơ bắp. Đội ngũ kỹ thuật viên chuyên nghiệp với chứng chỉ quốc tế. Các liệu trình: Massage Thái, Massage Thụy Điển, Massage đá nóng, Foot massage. Sử dụng tinh dầu thiên nhiên 100%. Phòng massage riêng tư, yên tĩnh.',
'Massage thư giãn toàn thân với tinh dầu thiên nhiên',
'spa',
NULL,
500000, 'VNĐ/60 phút', 1, 1, 11);

-- 12. SAUNA & JACUZZI
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Sauna & Jacuzzi', 'sauna-jacuzzi', 'spa',
'Phòng xông hơi khô (sauna) và bồn tắm massage (jacuzzi) cao cấp. Sauna nhiệt độ 70-90°C giúp thải độc, giảm stress. Jacuzzi với 12 vòi massage thủy lực. Khu vực riêng tư, sang trọng. Bao gồm: khăn tắm, áo choàng, dép, nước uống. Mở cửa 6h-22h.',
'Xông hơi khô và bồn tắm nước nóng massage',
'hot_tub',
NULL,
300000, 'VNĐ/45 phút', 0, 1, 12);

-- 13. HỒ BƠI & GYM
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Hồ bơi & Gym', 'pool-gym', 'other',
'Hồ bơi ngoài trời 25m x 12m, sâu 1.2m-1.8m. Khu vực trẻ em riêng biệt. Ghế tắm nắng, ô che. Phòng gym 200m² với trang thiết bị Technogym: máy chạy bộ, xe đạp, tạ, yoga area. Huấn luyện viên cá nhân theo yêu cầu. Mở cửa 6h-22h. Miễn phí cho khách lưu trú.',
'Hồ bơi ngoài trời và phòng gym hiện đại miễn phí',
'pool',
NULL,
0, 'Miễn phí', 1, 1, 13);

-- 14. ĐƯA ĐÓN SÂN BAY
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Đưa đón sân bay', 'airport-transfer', 'transport',
'Dịch vụ đưa đón sân bay Tân Sơn Nhất bằng xe sang: Mercedes E-Class, BMW 5 Series. Tài xế chuyên nghiệp, lịch sự, đúng giờ. Đón tận cửa khách sạn, giao tận cổng sân bay. Hỗ trợ hành lý. Thời gian di chuyển: 45-60 phút. Đặt trước 24h để có giá tốt nhất.',
'Xe đưa đón sân bay Tân Sơn Nhất tiện lợi, an toàn',
'local_taxi',
NULL,
500000, 'VNĐ/chuyến', 0, 1, 14);

-- 15. THUÊ XE TỰ LÁI
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Thuê xe tự lái', 'car-rental', 'transport',
'Cho thuê xe tự lái đa dạng dòng xe: Toyota Vios (4 chỗ), Toyota Innova (7 chỗ), Ford Everest (7 chỗ). Xe mới 2022-2024, bảo hiểm vật chất và dân sự đầy đủ. Giao nhận tại khách sạn. Hỗ trợ GPS, bản đồ. Giá thuê theo ngày, giảm giá thuê dài hạn. Yêu cầu: GPLX hạng B2, đặt cọc 5 triệu.',
'Thuê xe tự lái 4-7 chỗ, xe mới, bảo hiểm đầy đủ',
'directions_car',
NULL,
800000, 'VNĐ/ngày', 0, 1, 15);

-- 16. TRÔNG TRẺ
INSERT INTO `services` (service_name, slug, category, description, short_description, icon, thumbnail, price, price_unit, is_featured, is_available, sort_order) VALUES
('Trông trẻ', 'babysitting', 'other',
'Dịch vụ trông trẻ chuyên nghiệp, an toàn cho trẻ từ 6 tháng đến 12 tuổi. Nhân viên được đào tạo bài bản về chăm sóc trẻ, sơ cứu cơ bản. Khu vui chơi trẻ em với đồ chơi an toàn, sách tranh, TV. Giám sát camera 24/7. Phụ huynh có thể theo dõi qua app. Đặt trước 4 giờ.',
'Dịch vụ trông trẻ an toàn, chuyên nghiệp, giám sát camera',
'child_care',
NULL,
100000, 'VNĐ/giờ', 0, 1, 16);

-- Verify
SELECT service_id, service_name, category, price, price_unit, is_featured, is_available 
FROM services 
ORDER BY sort_order;
