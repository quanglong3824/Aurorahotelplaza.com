# Hướng dẫn Setup Booking & VNPay

## 🚀 Quick Start (3 bước)

### Bước 1: Import Database Schema
```bash
mysql -u root -p aurorahotelplaza.com < docs/DATABASE_SCHEMA_COMPLETE.sql
```

### Bước 2: Import Dữ liệu mẫu
Truy cập: **http://localhost/GitHub/Aurorahotelplaza.com/docs/import_sample_data.php**

### Bước 3: Test Booking
Truy cập: **http://localhost/GitHub/Aurorahotelplaza.com/booking/**

✅ Xong! Bạn đã có 7 loại phòng với 25 phòng trống để test.

---

## ✅ Đã hoàn thành

### 1. **Sửa Database Schema**
- Cập nhật tất cả tên cột để khớp với schema mới:
  - `id` → `room_type_id`, `booking_id`, `user_id`
  - `name` → `type_name`
  - `is_active` → `status`
  - `max_guests` → `max_occupancy`
- Thêm đầy đủ các trường theo schema: `num_adults`, `num_children`, `num_rooms`, `total_nights`

### 2. **Tích hợp VNPay**
- Cấu hình VNPay return URL: `http://localhost/GitHub/Aurorahotelplaza.com/booking/vnpay_return.php`
- Xử lý callback thanh toán
- Lưu thông tin payment vào database
- Tích hợp loyalty points system

### 3. **Booking Flow**
```
Bước 1: Chọn phòng & ngày
  ↓
Bước 2: Nhập thông tin khách
  ↓
Bước 3: Xác nhận & thanh toán
  ↓
API: create_booking.php (Lưu vào DB)
  ↓
VNPay Payment (nếu chọn VNPay)
  ↓
vnpay_return.php (Xử lý kết quả)
  ↓
confirmation.php (Hiển thị kết quả)
```

## 🔧 Cần cấu hình

### 1. **VNPay Credentials**
File: `/payment/config.php`

```php
$vnp_TmnCode = "YOUR_TMN_CODE";     // Mã merchant
$vnp_HashSecret = "YOUR_HASH_SECRET"; // Secret key
```

**Lấy thông tin:**
- Đăng ký tài khoản sandbox: https://sandbox.vnpayment.vn/
- Lấy TmnCode và HashSecret từ dashboard

### 2. **Database Import**
File: `/docs/DATABASE_SCHEMA_COMPLETE.sql`

```bash
# Import vào MySQL
mysql -u root -p aurorahotelplaza.com < docs/DATABASE_SCHEMA_COMPLETE.sql
```

**Lưu ý:** File đã được sửa để tương thích với hosting (loại bỏ các lệnh yêu cầu SUPER privilege)

### 3. **Insert Sample Data**

**Cách 1: Dùng PHP Script (Khuyến nghị - Dễ nhất)**

Truy cập: `http://localhost/GitHub/Aurorahotelplaza.com/docs/import_sample_data.php`

Script sẽ tự động import:
- ✅ 7 loại phòng (Deluxe, Premium, VIP, Apartments...)
- ✅ 25 phòng cụ thể
- ✅ 3 tài khoản test (admin, receptionist, customer)
- ✅ 5 hạng thành viên (Bronze → Diamond)
- ✅ 6 dịch vụ (Spa, Restaurant, Transport...)
- ✅ 2 mã khuyến mãi
- ✅ Cài đặt hệ thống

**Cách 2: Import SQL trực tiếp**

```bash
# Từ thư mục docs/
mysql -u root -p aurorahotelplaza.com < INSERT_SAMPLE_DATA.sql
```

**Cách 3: Dùng phpMyAdmin**

1. Mở phpMyAdmin
2. Chọn database `aurorahotelplaza.com`
3. Tab "Import"
4. Chọn file `docs/INSERT_SAMPLE_DATA.sql`
5. Click "Go"

## 📋 Checklist trước khi test

- [ ] Import database schema
- [ ] Insert sample room types và rooms
- [ ] Cấu hình VNPay credentials
- [ ] Kiểm tra database connection
- [ ] Test booking flow không VNPay (thanh toán tại khách sạn)
- [ ] Test booking flow với VNPay

## 🧪 Test Booking

### Test Case 1: Booking thành công (Cash)
1. Truy cập: `http://localhost/GitHub/Aurorahotelplaza.com/booking/`
2. Chọn loại phòng, ngày nhận/trả
3. Nhập thông tin khách hàng
4. Chọn "Thanh toán tại khách sạn"
5. Submit form
6. Kiểm tra database:
```sql
SELECT * FROM bookings ORDER BY booking_id DESC LIMIT 1;
SELECT * FROM users WHERE email = 'test@example.com';
```

### Test Case 2: Booking với VNPay
1. Làm theo Test Case 1 nhưng chọn "Thanh toán qua VNPay"
2. Sẽ redirect đến VNPay sandbox
3. Dùng thẻ test:
   - Số thẻ: `9704198526191432198`
   - Tên: `NGUYEN VAN A`
   - Ngày phát hành: `07/15`
   - Mật khẩu OTP: `123456`
4. Sau khi thanh toán, sẽ redirect về vnpay_return.php
5. Kiểm tra database:
```sql
SELECT * FROM bookings WHERE payment_status = 'paid' ORDER BY booking_id DESC LIMIT 1;
SELECT * FROM payments ORDER BY payment_id DESC LIMIT 1;
SELECT * FROM user_loyalty ORDER BY loyalty_id DESC LIMIT 1;
SELECT * FROM points_transactions ORDER BY transaction_id DESC LIMIT 1;
```

## 🐛 Troubleshooting

### Lỗi: "Loại phòng không tồn tại"
- Kiểm tra đã insert room_types chưa
- Kiểm tra status = 'active'

### Lỗi: "Column not found"
- Database schema chưa đúng
- Re-import DATABASE_SCHEMA_COMPLETE.sql

### VNPay không redirect về
- Kiểm tra vnp_Returnurl trong config.php
- Đảm bảo URL đúng: `http://localhost/GitHub/Aurorahotelplaza.com/booking/vnpay_return.php`

### Không tạo được user
- Kiểm tra table `users` có đúng cấu trúc không
- Kiểm tra email đã tồn tại chưa

## 📊 Database Tables được sử dụng

1. **bookings** - Lưu thông tin đặt phòng
2. **users** - Thông tin khách hàng
3. **room_types** - Loại phòng
4. **rooms** - Phòng cụ thể
5. **payments** - Lịch sử thanh toán
6. **user_loyalty** - Điểm tích lũy
7. **points_transactions** - Lịch sử giao dịch điểm

## 🔐 Security Notes

- Password được hash bằng `password_hash()`
- VNPay secure hash được verify
- SQL injection được prevent bằng prepared statements
- XSS được prevent bằng htmlspecialchars (nếu cần)

## 📝 Next Steps

1. Thêm email confirmation sau khi đặt phòng
2. Thêm QR code cho booking
3. Thêm admin panel để quản lý bookings
4. Thêm calendar view để xem phòng trống
5. Thêm review system sau check-out
