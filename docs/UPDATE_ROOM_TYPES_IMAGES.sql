-- ============================================
-- UPDATE ROOM TYPES WITH CORRECT IMAGE PATHS
-- Cập nhật đúng đường dẫn ảnh từ assets/img
-- Dùng cho cả admin và user website
-- ============================================

-- 1. Deluxe (Room)
UPDATE room_types SET 
    thumbnail = 'assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg',
    images = 'assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg,assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg,assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg',
    category = 'room',
    sort_order = 1
WHERE slug = 'deluxe';

-- 2. Premium Deluxe (Room)
UPDATE room_types SET 
    thumbnail = 'assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg',
    images = 'assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg,assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-2.jpg',
    category = 'room',
    sort_order = 2
WHERE slug = 'premium-deluxe';

-- 3. Premium Twin (Room)
UPDATE room_types SET 
    thumbnail = 'assets/img/premium twin/premium-twin-1.jpg',
    images = 'assets/img/premium twin/premium-twin-1.jpg,assets/img/premium twin/premium-twin-2.jpg',
    category = 'room',
    sort_order = 3
WHERE slug = 'premium-twin';

-- 4. VIP Suite (Room)
UPDATE room_types SET 
    thumbnail = 'assets/img/vip /vip-suite-1.jpg',
    images = 'assets/img/vip /vip-suite-1.jpg,assets/img/vip /vip-suite-2.jpg,assets/img/vip /vip-suite-3.jpg',
    category = 'room',
    sort_order = 4
WHERE slug = 'vip-suite';

-- ============================================
-- 6 CĂN HỘ MỚI (Trên cùng)
-- ============================================

-- 5. Modern Studio (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/modern studio apartment/modern-studio-1.jpg',
    images = 'assets/img/modern studio apartment/modern-studio-1.jpg,assets/img/modern studio apartment/modern-studio-2.jpg',
    category = 'apartment',
    sort_order = 5
WHERE slug = 'modern-studio';

-- 6. Indochine Studio (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/indochine studio apartment/indochine-studio-1.jpg',
    images = 'assets/img/indochine studio apartment/indochine-studio-1.jpg,assets/img/indochine studio apartment/indochine-studio-2.jpg',
    category = 'apartment',
    sort_order = 6
WHERE slug = 'indochine-studio';

-- 7. Modern Premium (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/modern premium apartment/modern-premium-1.jpg',
    images = 'assets/img/modern premium apartment/modern-premium-1.jpg,assets/img/modern premium apartment/modern-premium-2.jpg',
    category = 'apartment',
    sort_order = 7
WHERE slug = 'modern-premium';

-- 8. Classical Premium (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/classical premium apartment/classical-premium-1.jpg',
    images = 'assets/img/classical premium apartment/classical-premium-1.jpg,assets/img/classical premium apartment/classical-premium-2.jpg',
    category = 'apartment',
    sort_order = 8
WHERE slug = 'classical-premium';

-- 9. Indochine Family (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/indochine family apartment/indochine-family-1.jpg',
    images = 'assets/img/indochine family apartment/indochine-family-1.jpg,assets/img/indochine family apartment/indochine-family-2.jpg',
    category = 'apartment',
    sort_order = 9
WHERE slug = 'indochine-family';

-- 10. Classical Family (Mới)
UPDATE room_types SET 
    thumbnail = 'assets/img/classical family apartment/classical-family-1.jpg',
    images = 'assets/img/classical family apartment/classical-family-1.jpg,assets/img/classical family apartment/classical-family-2.jpg',
    category = 'apartment',
    sort_order = 10
WHERE slug = 'classical-family';

-- ============================================
-- 3 CĂN HỘ CŨ (Dưới cùng)
-- ============================================

-- 11. Studio Apartment (Cũ)
UPDATE room_types SET 
    thumbnail = 'assets/img/studio apartment/studio-1.jpg',
    images = 'assets/img/studio apartment/studio-1.jpg,assets/img/studio apartment/studio-2.jpg',
    category = 'apartment',
    sort_order = 11
WHERE slug = 'studio-apartment';

-- 12. Premium Apartment (Cũ)
UPDATE room_types SET 
    thumbnail = 'assets/img/premium apartment/premium-1.jpg',
    images = 'assets/img/premium apartment/premium-1.jpg,assets/img/premium apartment/premium-2.jpg,assets/img/premium apartment/premium-3.jpg',
    category = 'apartment',
    sort_order = 12
WHERE slug = 'premium-apartment';

-- 13. Family Apartment (Cũ)
UPDATE room_types SET 
    thumbnail = 'assets/img/family apartment/family-1.jpg',
    images = 'assets/img/family apartment/family-1.jpg,assets/img/family apartment/family-2.jpg,assets/img/family apartment/family-3.jpg',
    category = 'apartment',
    sort_order = 13
WHERE slug = 'family-apartment';

-- Verify
SELECT slug, type_name, category, thumbnail FROM room_types ORDER BY sort_order;
