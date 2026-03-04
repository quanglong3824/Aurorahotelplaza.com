-- ============================================================
-- FIX: Update `bed_type_en` and `amenities_en` cho CÁC PHÒNG CHUẨN (Rooms)
-- Do slug trong DB trước đó không có chữ "-room".
-- ============================================================

-- Deluxe
UPDATE `room_types` SET
    `bed_type_en` = '1 King Bed (1.8×2m)',
    `amenities_en` = 'Free WiFi,Flat-screen TV,Minibar,In-room Safe'
WHERE `slug` = 'deluxe';

-- Premium Deluxe Double
UPDATE `room_types` SET
    `bed_type_en` = '1 Super King Bed (2×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Premium Minibar,Electronic Safe,Bathtub'
WHERE `slug` = 'premium-deluxe';

-- Premium Twin
UPDATE `room_types` SET
    `bed_type_en` = '2 Single Beds (1.4×2m)',
    `amenities_en` = 'High-speed WiFi,Smart TV,Minibar,In-room Safe,Massage Shower'
WHERE `slug` = 'premium-twin';

SELECT 'Room updates applied successfully for deluxe, premium-deluxe, and premium-twin!' AS status;
