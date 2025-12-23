# Hướng dẫn Refactor Booking Pricing Structure

## Vấn đề hiện tại

Bảng `bookings` có quá nhiều cột giá gây nhầm lẫn:
- `room_price` - Giá phòng mỗi đêm
- `base_price` - Giá cơ bản (room + extra fees) ❌ DUPLICATE
- `service_fee` - Phí dịch vụ
- `service_charges` - Phí dịch vụ ❌ DUPLICATE
- `discount_amount` - Giảm giá
- `final_price` - Giá cuối cùng ❌ DUPLICATE
- `total_amount` - Tổng tiền ✅ KEEP

**Kết quả:** `total_amount` bị ghi sai vì logic phức tạp

## Giải pháp

### 1. Đơn giản hóa cấu trúc database

Chỉ giữ lại các cột cần thiết:
- `room_price` - Giá phòng (đã tính theo số đêm)
- `extra_guest_fee` - Phụ thu khách thêm (tổng)
- `extra_bed_fee` - Phụ thu giường phụ (tổng)
- `service_fee` - Phí dịch vụ (nếu có)
- `discount_amount` - Giảm giá (nếu có)
- `total_amount` - **Tổng tiền cuối cùng**

### 2. Công thức tính đơn giản

```
total_amount = room_price + extra_guest_fee + extra_bed_fee + service_fee - discount_amount
```

## Các bước thực hiện

### Bước 1: Chạy SQL Refactor

Mở phpMyAdmin hoặc MySQL Workbench và chạy file:
```
docs/refactor_booking_pricing.sql
```

Hoặc dùng command line:
```bash
mysql -u root -p aurorahotelplaza < docs/refactor_booking_pricing.sql
```

### Bước 2: Kiểm tra kết quả

Sau khi chạy SQL, kiểm tra:

```sql
SELECT 
    booking_id,
    booking_code,
    room_price,
    extra_guest_fee,
    extra_bed_fee,
    service_fee,
    discount_amount,
    total_amount
FROM bookings
ORDER BY booking_id DESC
LIMIT 5;
```

### Bước 3: Test tạo booking mới

1. Vào trang booking: http://localhost/booking/
2. Chọn phòng Deluxe (1,600,000đ/đêm)
3. Thêm 1 khách (400,000đ)
4. Thêm 1 giường phụ (650,000đ)
5. Kiểm tra tổng tiền: **2,650,000đ**

### Bước 4: Kiểm tra database

```sql
SELECT * FROM bookings ORDER BY booking_id DESC LIMIT 1;
```

Kết quả mong đợi:
- `room_price` = 1,600,000
- `extra_guest_fee` = 400,000
- `extra_bed_fee` = 650,000
- `service_fee` = 0
- `discount_amount` = 0
- `total_amount` = **2,650,000** ✅

## Files đã cập nhật

1. ✅ `booking/api/create_booking.php` - Đơn giản hóa logic tính giá
2. ✅ `docs/refactor_booking_pricing.sql` - SQL refactor database
3. ✅ `docs/REFACTOR_GUIDE.md` - Hướng dẫn này

## Lưu ý

- ⚠️ Backup database trước khi chạy SQL
- ⚠️ Các booking cũ sẽ được tự động cập nhật lại `total_amount`
- ⚠️ Kiểm tra kỹ các file đọc booking (admin, profile, reports)

## Rollback (nếu cần)

Nếu có vấn đề, restore từ backup:
```bash
mysql -u root -p aurorahotelplaza < docs/backup_2025-12-23_11-34-28.sql
```
