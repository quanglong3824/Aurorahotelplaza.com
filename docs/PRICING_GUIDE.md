# Aurora Hotel Plaza - Hướng dẫn cập nhật Bảng giá Phòng

## 📋 Tổng quan

Dựa trên bảng giá phòng do lễ tân cung cấp (`docs/price_room.json`), hệ thống đã được cập nhật với các tính năng sau:

### 🏨 Phòng Khách sạn (Hotel Rooms)

| Loại phòng | Diện tích | View | Loại giường | Giá công bố | Giá 2 người | Giá 1 người | Giá ngắn hạn |
|------------|-----------|------|-------------|-------------|-------------|-------------|--------------|
| Deluxe | 32m² | Thành phố | 1 giường đôi lớn (1m8x2m) | 1.900.000đ | **1.600.000đ** | 1.400.000đ | 1.100.000đ |
| Premium Deluxe Double | 48m² | Thành phố | 1 giường đôi lớn (1m8x2m) | 2.200.000đ | **1.900.000đ** | 1.700.000đ | 1.300.000đ |
| Premium Deluxe Twin | 48m² | Thành phố | 2 giường đơn (1m4x2m) | 2.200.000đ | **1.900.000đ** | 1.700.000đ | - |
| Aurora Studio | 54m² | Thành phố | 1 giường siêu lớn (2mx2m) | 2.950.000đ | **2.300.000đ** | 2.200.000đ | 1.900.000đ |

> **Ghi chú nghỉ ngắn hạn:** Dưới 4h và trả phòng trước 22h, không bao gồm bữa sáng

### 🏠 Căn hộ (Apartments)

| Loại căn hộ | Diện tích | Số người | Giá/ngày | Giá/tuần | TB/đêm (tuần) |
|-------------|-----------|----------|----------|----------|---------------|
| Modern Studio / Indochine Studio | 35m² | 1 người | 1.850.000đ | 12.250.000đ | 1.750.000đ |
| Modern Studio / Indochine Studio | 35m² | 2 người | 2.250.000đ | 15.050.000đ | 2.150.000đ |
| Modern Premium / Classical Premium | 60m² | 1 người | 2.050.000đ | 13.650.000đ | 1.950.000đ |
| Modern Premium / Classical Premium | 60m² | 2 người | 2.450.000đ | 16.450.000đ | 2.350.000đ |
| Classical Family / Indochine Family | 82m² | 2 người | 2.550.000đ | 17.150.000đ | 2.450.000đ |

### 📋 Chính sách phụ thu

#### Khách thêm (bao gồm ăn sáng)
| Đối tượng | Phụ thu |
|-----------|---------|
| Trẻ em dưới 1m (chiều cao) | **Miễn phí** |
| Trẻ em 1m - 1m3 | 200.000đ |
| Người lớn & trẻ trên 1m3 | 400.000đ |

#### Giường phụ
| Mục | Giá |
|-----|-----|
| Giường phụ/đêm | 650.000đ |

> ⚠️ **Lưu ý:** Giường phụ không áp dụng cho căn hộ

---

## 🚀 Hướng dẫn cài đặt & Chạy Migration

### Bước 1: Chạy Migration tự động

Truy cập trang admin và chạy migration:

```
http://your-domain.com/admin/run_pricing_migration.php
```

Hoặc chạy qua command line:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Github/Aurorahotelplaza.com
php admin/run_pricing_migration.php
```

### Bước 2: Kiểm tra bảng giá

Truy cập trang quản lý giá chi tiết:
```
http://your-domain.com/admin/pricing-detailed.php
```

---

## � Cập nhật Booking Flow cho Khách hàng

Các tính năng mới đã được tích hợp vào trang đặt phòng (`booking/index.php`):

### Tính năng mới:

1. **Giá tự động theo số khách:**
   - Khi khách chọn 1 người → Hiển thị giá 1 người
   - Khi khách chọn 2+ người → Hiển thị giá 2 người
   - Giá tự động cập nhật khi thay đổi số khách

2. **Hiển thị giá chi tiết:**
   - Giá gốc (công bố) được gạch ngang nếu có giảm giá
   - Loại giá đang áp dụng (badge): "Giá 1 người", "Giá 2 người", "Giá tuần"
   - Thông tin thuế đã bao gồm

3. **Căn hộ - Giá theo tuần:**
   - Tự động áp dụng giá tuần khi đặt từ 7 đêm trở lên
   - Hiển thị giá trung bình/đêm cho tuần

4. **API tính giá động:**
   - Endpoint: `POST /booking/api/calculate_price.php`
   - Hỗ trợ tất cả loại giá: single, double, short_stay, daily, weekly

### Files đã cập nhật:

| File | Thay đổi |
|------|----------|
| `booking/index.php` | Thêm data attributes cho giá chi tiết, cải thiện Price Summary Box |
| `booking/assets/js/booking.js` | Cập nhật `calculateTotal()` để tính giá theo số khách |
| `booking/api/create_booking.php` | Cập nhật logic tính giá backend với cấu trúc mới |
| `booking/api/calculate_price.php` | API mới để tính giá động |

---

## �📁 Danh sách các file đã tạo/cập nhật

### Files mới:
| File | Mô tả |
|------|-------|
| `docs/migration_pricing_2025-12-21.sql` | File SQL migration đầy đủ |
| `admin/run_pricing_migration.php` | Script PHP chạy migration |
| `admin/pricing-detailed.php` | Trang quản lý bảng giá chi tiết |
| `helpers/pricing_calculator.php` | Helper functions tính giá |
| `booking/api/calculate_price.php` | API tính giá đặt phòng động |

### Cấu trúc Database được cập nhật:

#### Bảng `room_types` - Cột mới:
- `price_published` - Giá công bố (niêm yết)
- `price_single_occupancy` - Giá phòng đơn (1 người)
- `price_double_occupancy` - Giá phòng đôi (2 người)
- `price_short_stay` - Giá nghỉ ngắn hạn
- `short_stay_description` - Mô tả điều kiện nghỉ ngắn
- `view_type` - Loại view phòng
- `price_daily_single` - Căn hộ: Giá ngày 1 người
- `price_daily_double` - Căn hộ: Giá ngày 2 người
- `price_weekly_single` - Căn hộ: Giá tuần 1 người
- `price_weekly_double` - Căn hộ: Giá tuần 2 người
- `price_avg_weekly_single` - Căn hộ: Giá TB/đêm tuần 1 người
- `price_avg_weekly_double` - Căn hộ: Giá TB/đêm tuần 2 người

#### Bảng mới `pricing_policies`:
Lưu trữ chính sách phụ thu (khách thêm, giường phụ)

#### Bảng mới `booking_extra_guests`:
Lưu trữ thông tin khách thêm cho mỗi booking

#### Bảng `bookings` - Cột mới:
- `booking_type` - Loại đặt phòng (standard, short_stay, weekly, inquiry)
- `occupancy_type` - Loại lưu trú (single, double, family)
- `extra_guest_fee` - Phí khách thêm
- `extra_bed_fee` - Phí giường phụ
- `extra_beds` - Số giường phụ
- `price_type_used` - Loại giá được áp dụng

---

## 💡 Cách sử dụng Helper Functions

### Tính giá phòng:

```php
require_once 'helpers/pricing_calculator.php';

// Tính giá cho phòng khách sạn
$result = calculateRoomPrice($roomType, $numAdults, $numNights, 'standard');
echo "Giá: " . formatCurrency($result['price']);
echo "Loại giá: " . $result['price_type'];

// Tính phí khách thêm
$guestFee = calculateExtraGuestFee(1.25, true); // Chiều cao 1.25m, có ăn sáng
echo "Phí: " . formatCurrency($guestFee['fee']);

// Tính tổng booking
$total = calculateBookingTotal([
    'room_type' => $roomType,
    'check_in' => '2025-12-25',
    'check_out' => '2025-12-27',
    'num_adults' => 2,
    'num_children' => 1,
    'extra_beds' => 0,
    'booking_type' => 'standard',
    'extra_guests' => [
        ['height_m' => 1.2, 'includes_breakfast' => true]
    ]
]);
echo "Tổng tiền: " . formatCurrency($total['total_amount']);
```

### API tính giá:

```javascript
// POST /booking/api/calculate_price.php
fetch('/booking/api/calculate_price.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        room_type_id: 1,
        check_in: '2025-12-25',
        check_out: '2025-12-27',
        num_adults: 2,
        num_children: 1,
        booking_type: 'standard',
        extra_beds: 0,
        extra_guests: [
            { height_m: 1.2, includes_breakfast: true }
        ]
    })
})
.then(res => res.json())
.then(data => {
    console.log('Tổng tiền:', data.data.formatted.total_amount);
    console.log('Chi tiết:', data.data);
});
```

---

## 📌 Thông tin quan trọng

### Thuế và phí dịch vụ
> **Đã bao gồm 5% phí dịch vụ và 8% VAT**

Tất cả giá niêm yết đã bao gồm thuế và phí, khách hàng không phải trả thêm.

### Liên hệ
- **Hotline:** 0251 3918 888
- **Địa chỉ:** 253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Tỉnh Đồng Nai
- **Đánh giá:** ⭐⭐⭐⭐ (4 sao)

---

*Cập nhật lần cuối: 2025-12-21*
