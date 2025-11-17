# Hệ thống đặt phòng Aurora Hotel Plaza

## 📁 Cấu trúc thư mục

```
booking/
├── index.php              # Trang đặt phòng chính (form 3 bước)
├── confirmation.php       # Trang xác nhận đặt phòng
├── vnpay_return.php      # Xử lý callback từ VNPay
├── api/
│   └── create_booking.php # API tạo booking
├── assets/
│   ├── css/
│   │   └── booking.css   # Styles cho booking form
│   ├── js/
│   │   └── booking.js    # Logic xử lý form
│   └── img/
│       └── vnpay-logo.png
└── README.md
```

## 🎯 Tính năng

### 1. Form đặt phòng 3 bước
- **Bước 1**: Chọn loại phòng, ngày nhận/trả, số khách
- **Bước 2**: Nhập thông tin khách hàng
- **Bước 3**: Xác nhận và chọn phương thức thanh toán

### 2. Tích hợp VNPay
- Thanh toán online qua VNPay
- Xử lý callback và cập nhật trạng thái
- Tự động tích điểm sau thanh toán thành công

### 3. Kiểm tra phòng trống
- Tự động kiểm tra availability
- Gán phòng tự động nếu có sẵn
- Đặt trước nếu chưa có phòng cụ thể

### 4. Tính năng bổ sung
- Tính toán tự động số đêm và tổng tiền
- Validate form đầy đủ
- Responsive design
- Dark mode support

## 🔧 Cấu hình

### 1. Database
Đảm bảo đã import schema từ `docs/DATABASE_SCHEMA.sql`

### 2. VNPay
Cập nhật thông tin VNPay trong `/payment/config.php`:
```php
$vnp_TmnCode = "YOUR_TMN_CODE";
$vnp_HashSecret = "YOUR_HASH_SECRET";
$vnp_Returnurl = "http://yourdomain.com/booking/vnpay_return.php";
```

### 3. Session
Đảm bảo session đã được start trong các file cần thiết

## 📝 Sử dụng

### Đặt phòng mới
1. Truy cập `/booking/index.php`
2. Chọn loại phòng và ngày
3. Điền thông tin khách hàng
4. Chọn phương thức thanh toán
5. Xác nhận đặt phòng

### Thanh toán VNPay
- Chọn "Thanh toán qua VNPay"
- Hệ thống redirect đến VNPay
- Sau khi thanh toán, redirect về `vnpay_return.php`
- Tự động cập nhật trạng thái booking

### Thanh toán tại khách sạn
- Chọn "Thanh toán tại khách sạn"
- Booking được tạo với trạng thái pending
- Khách hàng thanh toán khi check-in

## 🔐 Bảo mật

- Validate input server-side
- Prepared statements (PDO)
- XSS prevention
- CSRF token (TODO)
- Secure hash verification cho VNPay

## 📊 Database Tables sử dụng

- `bookings` - Lưu thông tin đặt phòng
- `room_types` - Loại phòng
- `rooms` - Phòng cụ thể
- `payments` - Lịch sử thanh toán
- `users` - Thông tin khách hàng
- `customer_loyalty` - Tích điểm
- `loyalty_transactions` - Lịch sử tích điểm

## 🚀 TODO

- [ ] Thêm CSRF protection
- [ ] Implement email confirmation với PHPMailer
- [ ] Generate QR code cho booking
- [ ] Thêm rate limiting
- [ ] Implement booking cancellation
- [ ] Thêm booking modification
- [ ] Multi-language support
- [ ] Add promo code support
- [ ] Implement booking search
- [ ] Add calendar view

## 📞 Hỗ trợ

Liên hệ: support@aurorahotelplaza.com
