# Hệ thống Authentication - Aurora Hotel Plaza

## 📁 Cấu trúc thư mục

```
auth/
├── login.php              # Trang đăng nhập
├── register.php           # Trang đăng ký
├── logout.php             # Xử lý đăng xuất
├── forgot-password.php    # Quên mật khẩu
├── reset-password.php     # Đặt lại mật khẩu
├── assets/
│   └── css/
│       └── auth.css       # Styles cho auth pages
└── README.md
```

## 🎯 Tính năng

### 1. Đăng nhập (login.php)
- Đăng nhập bằng email và mật khẩu
- Ghi nhớ đăng nhập (Remember me)
- Redirect về trang trước đó sau khi đăng nhập
- Cập nhật last_login_at và last_login_ip

### 2. Đăng ký (register.php)
- Đăng ký tài khoản mới
- Validate đầy đủ (email, phone, password)
- Tự động tạo username unique
- Hash password với bcrypt
- Redirect đến login sau khi đăng ký thành công

### 3. Quên mật khẩu (forgot-password.php)
- Nhập email để nhận link reset
- Generate token reset password
- Token có thời hạn 1 giờ
- TODO: Gửi email với PHPMailer

### 4. Đặt lại mật khẩu (reset-password.php)
- Verify token hợp lệ
- Đặt mật khẩu mới
- Clear token sau khi reset thành công

### 5. Đăng xuất (logout.php)
- Clear session
- Clear cookies
- Redirect về trang chủ

## 🔐 Bảo mật

### Đã implement:
- ✅ Password hashing với bcrypt
- ✅ Prepared statements (PDO)
- ✅ XSS prevention (htmlspecialchars)
- ✅ Session management
- ✅ Token-based password reset
- ✅ Input validation

### TODO:
- [ ] CSRF protection
- [ ] Rate limiting (login attempts)
- [ ] Email verification
- [ ] Two-factor authentication
- [ ] Account lockout after failed attempts
- [ ] Password strength meter
- [ ] Captcha for registration

## 📊 Database Tables

### users
```sql
- id
- username (unique)
- email (unique)
- password_hash
- full_name
- phone
- role (customer, receptionist, sale, admin)
- status (active, inactive, banned, pending)
- email_verified_at
- last_login_at
- last_login_ip
- password_reset_token
- password_reset_expires_at
```

## 🚀 Sử dụng

### Đăng ký tài khoản mới
```php
// Truy cập /auth/register.php
// Điền form và submit
// Redirect đến login.php
```

### Đăng nhập
```php
// Truy cập /auth/login.php
// Nhập email và password
// Session được tạo với:
$_SESSION['user_id']
$_SESSION['user_email']
$_SESSION['user_name']
$_SESSION['user_role']
```

### Kiểm tra đăng nhập
```php
// Trong bất kỳ page nào
if (isset($_SESSION['user_id'])) {
    // User đã đăng nhập
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
}
```

### Phân quyền
```php
// Check role
if ($_SESSION['user_role'] === 'admin') {
    // Admin only
}

// Multiple roles
if (in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    // Admin or Sale
}
```

## 🎨 UI/UX

- Responsive design
- Dark mode support
- Form validation
- Error messages
- Success messages
- Loading states
- Smooth transitions

## 📝 Session Variables

```php
$_SESSION['user_id']      // User ID
$_SESSION['user_email']   // Email
$_SESSION['user_name']    // Full name or username
$_SESSION['user_role']    // Role (customer, receptionist, sale, admin)
```

## 🔗 Integration với Header

Header tự động hiển thị:
- **Chưa đăng nhập**: Nút "Đăng nhập"
- **Đã đăng nhập**: User menu với:
  - Thông tin cá nhân
  - Lịch sử đặt phòng
  - Điểm thưởng
  - Quản trị (nếu là staff)
  - Đăng xuất

## 📧 Email Templates (TODO)

Cần implement với PHPMailer:

1. **Welcome Email** - Sau khi đăng ký
2. **Email Verification** - Xác thực email
3. **Password Reset** - Link reset password
4. **Password Changed** - Thông báo đổi mật khẩu thành công

## 🧪 Testing

### Test Cases:
1. Đăng ký với email đã tồn tại
2. Đăng nhập với sai password
3. Đăng nhập với tài khoản inactive
4. Reset password với email không tồn tại
5. Reset password với token hết hạn
6. Remember me functionality
7. Session timeout
8. XSS attempts
9. SQL injection attempts

## 🔧 Configuration

### Session Settings (php.ini hoặc code)
```php
session.cookie_httponly = 1
session.cookie_secure = 1 (nếu dùng HTTPS)
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 3600 (1 hour)
```

### Password Policy
- Minimum 6 characters
- TODO: Add complexity requirements

## 📞 Hỗ trợ

Liên hệ: support@aurorahotelplaza.com
