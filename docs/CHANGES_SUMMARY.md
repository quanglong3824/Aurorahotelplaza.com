# Tóm tắt thay đổi - Refactor Booking Pricing

## Vấn đề đã sửa

❌ **Trước:** `total_amount` = 1,600,000 (SAI)  
✅ **Sau:** `total_amount` = 2,650,000 (ĐÚNG)

## Database Changes

### Bảng `bookings` - Xóa các cột duplicate:

1. ❌ `base_price` - Duplicate, có thể tính = room_price + extra_guest_fee + extra_bed_fee
2. ❌ `service_charges` - Duplicate với `service_fee`
3. ❌ `final_price` - Duplicate với `total_amount`

### Cấu trúc mới (đơn giản):

```sql
bookings:
  - room_price (DECIMAL 12,2) - Giá phòng đã tính theo số đêm
  - extra_guest_fee (DECIMAL 12,2) - Phụ thu khách thêm (tổng)
  - extra_bed_fee (DECIMAL 12,2) - Phụ thu giường phụ (tổng)
  - service_fee (DECIMAL 12,2) - Phí dịch vụ
  - discount_amount (DECIMAL 12,2) - Giảm giá
  - total_amount (DECIMAL 12,2) - TỔNG TIỀN CUỐI CÙNG
```

### Công thức tính:

```
total_amount = room_price + extra_guest_fee + extra_bed_fee + service_fee - discount_amount
```

## Code Changes

### 1. booking/api/create_booking.php

**Thay đổi:**
- Đơn giản hóa logic tính giá
- Xóa các biến `base_price`, `final_price`
- Cập nhật INSERT statement để phù hợp với cấu trúc mới
- Thêm debug log chi tiết

**Trước:**
```php
$base_price = $room_subtotal + $backend_extra_guest_fee + $backend_extra_bed_fee;
$final_price = $base_price - $discount_amount + $service_fee;
$total_amount = $final_price;

INSERT INTO bookings (..., base_price, service_charges, final_price, ...)
```

**Sau:**
```php
$total_amount = $room_subtotal + $backend_extra_guest_fee + $backend_extra_bed_fee + $service_fee - $discount_amount;

INSERT INTO bookings (..., service_fee, ...)
```

### 2. models/Booking.php

**Thay đổi:**
- Cập nhật SELECT query: `service_charges` → `service_fee`

### 3. View Files (6 files)

**Files cập nhật:**
- `profile/view-qrcode.php`
- `profile/booking-detail.php` (2 chỗ)
- `admin/view-qrcode.php`
- `admin/booking-detail.php`

**Thay đổi:**
- `$booking['service_charges']` → `$booking['service_fee']`

## Testing Checklist

### ✅ Bước 1: Chạy SQL Refactor
```bash
mysql -u root -p aurorahotelplaza < docs/refactor_booking_pricing.sql
```

### ✅ Bước 2: Test tạo booking mới

1. Vào: http://localhost/booking/
2. Chọn phòng Deluxe (1,600,000đ/đêm)
3. Thêm 1 khách thêm (400,000đ)
4. Thêm 1 giường phụ (650,000đ)
5. **Kiểm tra:** Tổng tiền = 2,650,000đ ✅

### ✅ Bước 3: Kiểm tra database

```sql
SELECT 
    booking_code,
    room_price,
    extra_guest_fee,
    extra_bed_fee,
    service_fee,
    discount_amount,
    total_amount
FROM bookings 
ORDER BY booking_id DESC 
LIMIT 1;
```

**Kết quả mong đợi:**
```
room_price: 1,600,000
extra_guest_fee: 400,000
extra_bed_fee: 650,000
service_fee: 0
discount_amount: 0
total_amount: 2,650,000 ✅
```

### ✅ Bước 4: Kiểm tra UI

1. **Profile → My Bookings:** Giá hiển thị đúng
2. **Admin → Bookings:** Giá hiển thị đúng
3. **QR Code:** Giá hiển thị đúng
4. **Booking Detail:** Breakdown giá đúng

## Files Changed

### SQL:
- ✅ `docs/refactor_booking_pricing.sql` (NEW)

### PHP:
- ✅ `booking/api/create_booking.php`
- ✅ `models/Booking.php`
- ✅ `profile/view-qrcode.php`
- ✅ `profile/booking-detail.php`
- ✅ `admin/view-qrcode.php`
- ✅ `admin/booking-detail.php`

### Documentation:
- ✅ `docs/REFACTOR_GUIDE.md` (NEW)
- ✅ `docs/CHANGES_SUMMARY.md` (NEW - this file)

## Rollback Plan

Nếu có vấn đề:

```bash
# Restore từ backup
mysql -u root -p aurorahotelplaza < docs/backup_2025-12-23_11-34-28.sql

# Revert code changes
git checkout HEAD -- booking/api/create_booking.php models/Booking.php profile/ admin/
```

## Notes

- ⚠️ Các booking cũ đã được tự động cập nhật lại `total_amount`
- ⚠️ Không ảnh hưởng đến bảng `room_types` (vẫn giữ `base_price`)
- ⚠️ Language files không cần thay đổi (key `service_charges` vẫn dùng được)
