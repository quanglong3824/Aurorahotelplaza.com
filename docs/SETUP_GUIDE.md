# 🏨 HƯỚNG DẪN SETUP DATABASE - AURORA HOTEL PLAZA

## 📋 Tổng Quan
Hệ thống gồm **13 loại phòng** (4 Phòng + 9 Căn hộ) và **126 phòng thực tế** trên 6 tầng (7-12).

---

## 🚀 BƯỚC 1: Reset Database (Tùy chọn)

Nếu muốn bắt đầu lại từ đầu:

### Cách 1: Dùng Admin Panel
1. Đăng nhập admin
2. Vào **Settings → Reset Database**
3. Nhập `RESET DATABASE` để xác nhận

### Cách 2: Chạy SQL
```sql
source docs/RESET_DATABASE_KEEP_ADMIN.sql;
```

---

## 🏗️ BƯỚC 2: Insert 13 Loại Phòng

```sql
source docs/INSERT_ROOM_TYPES_COMPLETE.sql;
```

### Kết quả:
✅ **4 Loại Phòng:**
- Deluxe (35m², 2 người, 1.8tr)
- Premium Deluxe (40m², 2 người, 2.5tr)
- Premium Twin (38m², 2 người, 2.2tr)
- VIP Suite (60m², 3 người, 4.5tr)

✅ **9 Loại Căn Hộ:**
- Studio Apartment (45m², 2 người, 2.8tr)
- Modern Studio (48m², 2 người, 3.2tr)
- Indochine Studio (46m², 2 người, 3.0tr)
- Premium Apartment (65m², 3 người, 4.2tr)
- Modern Premium (68m², 3 người, 4.8tr)
- Classical Premium (66m², 3 người, 4.5tr)
- Family Apartment (75m², 5 người, 5.5tr)
- Indochine Family (72m², 5 người, 5.2tr)
- Classical Family (78m², 5 người, 5.8tr)

---

## 🗺️ BƯỚC 3: Insert 126 Phòng với Liên Kết

```sql
source docs/INSERT_ROOMS_WITH_TYPES.sql;
```

### Phân Bổ Phòng:

**Tầng 7 (19 phòng):**
- 701-710: Deluxe (10 phòng)
- 711-712, 714-720: Premium Deluxe (9 phòng)

**Tầng 8 (19 phòng):**
- 801-810: Premium Twin (10 phòng)
- 811-812, 814-819: VIP Suite (9 phòng)

**Tầng 9 (23 phòng):**
- 901-911: Studio Apartment (11 phòng)
- 912, 914-923: Modern Studio (12 phòng)

**Tầng 10 (23 phòng):**
- 1001-1011: Indochine Studio (11 phòng)
- 1012, 1014-1023: Premium Apartment (12 phòng)

**Tầng 11 (23 phòng):**
- 1101-1111: Modern Premium (11 phòng)
- 1112, 1114-1123: Classical Premium (12 phòng)

**Tầng 12 (19 phòng):**
- 1201-1210: Family Apartment (10 phòng)
- 1211-1212, 1214-1216: Indochine Family (5 phòng)
- 1217-1220: Classical Family (4 phòng)

**Lưu ý:** Bỏ số 13 ở tất cả các tầng

---

## ✅ BƯỚC 4: Kiểm Tra Kết Quả

### 1. Kiểm tra trong Admin Panel:
- Vào **Phòng → Loại phòng**: Thấy 13 loại với tabs
- Vào **Phòng → Sơ đồ phòng**: Thấy 126 phòng theo tầng
- Click vào phòng: Thấy thông tin loại phòng đầy đủ

### 2. Kiểm tra bằng SQL:
```sql
-- Tổng số loại phòng
SELECT COUNT(*) FROM room_types;
-- Kết quả: 13

-- Tổng số phòng
SELECT COUNT(*) FROM rooms;
-- Kết quả: 126

-- Phân bổ theo loại
SELECT 
    rt.type_name,
    rt.category,
    COUNT(r.room_id) as room_count
FROM room_types rt
LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
GROUP BY rt.room_type_id
ORDER BY rt.sort_order;
```

---

## 🎯 TÍNH NĂNG HOẠT ĐỘNG

### ✅ Admin Panel:
1. **Quản lý loại phòng** (`admin/room-types.php`)
   - Xem theo tabs: Tất cả / Phòng / Căn hộ
   - Sửa tất cả thông tin: tên, giá, amenities, hình ảnh
   - Thêm/xóa loại phòng

2. **Sơ đồ phòng** (`admin/room-map.php`)
   - Xem theo tầng hoặc tất cả
   - Màu sắc theo trạng thái
   - Click phòng → Xem chi tiết + lịch sử booking
   - Tạo booking trực tiếp từ sơ đồ

3. **Quản lý phòng** (`admin/rooms.php`)
   - Danh sách tất cả 126 phòng
   - Filter theo loại, tầng, trạng thái
   - Sửa thông tin từng phòng

### ✅ User Frontend:
- Xem danh sách phòng theo loại
- Xem chi tiết từng loại với đầy đủ thông tin
- Đặt phòng theo loại
- Chọn phòng cụ thể khi đặt

---

## 📊 THỐNG KÊ

| Loại | Số Phòng | Tầng |
|------|----------|------|
| Deluxe | 10 | 7 |
| Premium Deluxe | 9 | 7 |
| Premium Twin | 10 | 8 |
| VIP Suite | 9 | 8 |
| Studio Apartment | 11 | 9 |
| Modern Studio | 12 | 9 |
| Indochine Studio | 11 | 10 |
| Premium Apartment | 12 | 10 |
| Modern Premium | 11 | 11 |
| Classical Premium | 12 | 11 |
| Family Apartment | 10 | 12 |
| Indochine Family | 5 | 12 |
| Classical Family | 4 | 12 |
| **TỔNG** | **126** | **6 tầng** |

---

## 🔗 LIÊN KẾT DỮ LIỆU

### Database Schema:
```
room_types (13 records)
    ↓ (room_type_id)
rooms (126 records)
    ↓ (room_id)
bookings
    ↓
payments
```

### Workflow:
1. User chọn loại phòng → Xem thông tin từ `room_types`
2. User đặt phòng → Chọn phòng cụ thể từ `rooms`
3. Booking được tạo → Liên kết với `room_id` và `room_type_id`
4. Admin xem sơ đồ → Hiển thị trạng thái real-time từ `bookings`

---

## 🛠️ TROUBLESHOOTING

### Lỗi: "Column 'slug' doesn't have a default value"
→ Chạy lại `INSERT_ROOM_TYPES_COMPLETE.sql`

### Lỗi: "Column 'role' not found"
→ Đã fix, dùng `user_role` thay vì `role`

### Phòng không hiển thị loại:
→ Kiểm tra `room_type_id` có NULL không:
```sql
SELECT * FROM rooms WHERE room_type_id IS NULL;
```

### Reset lại toàn bộ:
```sql
source docs/RESET_DATABASE_KEEP_ADMIN.sql;
source docs/INSERT_ROOM_TYPES_COMPLETE.sql;
source docs/INSERT_ROOMS_WITH_TYPES.sql;
```

---

## 📝 GHI CHÚ

- Tất cả giá đã bao gồm: Giá cơ bản, Cuối tuần, Ngày lễ
- Amenities được lưu dạng CSV, có thể parse thành array
- Images được lưu dạng CSV paths
- Status mặc định: `available`
- Building mặc định: `Main`

---

## ✨ HOÀN TẤT!

Sau khi chạy xong 3 bước trên, hệ thống đã sẵn sàng với:
- ✅ 13 loại phòng đầy đủ thông tin
- ✅ 126 phòng được liên kết đúng
- ✅ Sơ đồ phòng hoạt động
- ✅ Có thể đặt phòng và quản lý
- ✅ Admin có thể sửa tất cả thông tin

🎉 **Chúc mừng! Hệ thống đã sẵn sàng hoạt động!**
