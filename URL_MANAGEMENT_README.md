# 🔗 Quản lý URL - Aurora Hotel Plaza

## Tổng quan

Hệ thống tự động phát hiện môi trường (localhost hoặc production) và sử dụng URL phù hợp.

## ✅ Đã cập nhật

### Files đã được cập nhật để sử dụng hàm helper:

1. **config/environment.php** - File chính chứa các hàm helper
2. **payment/config.php** - VNPay configuration
3. **helpers/email.php** - Email helper
4. **helpers/email-templates.php** - Email templates
5. **helpers/email-templates-old.php** - Email templates (old version)
6. **helpers/mailer.php** - PHPMailer wrapper

### Files mới được tạo:

1. **helpers/url-checker.php** - Helper để kiểm tra và quản lý URL
2. **url-check.php** - Trang test URL (chỉ dùng cho development)

## 🎯 Cách sử dụng

### 1. Trong PHP code

```php
<?php
// Load environment helper
require_once __DIR__ . '/config/environment.php';

// Lấy base URL (tự động detect môi trường)
$baseUrl = getBaseUrl();
// Localhost: http://localhost/GitHub/Aurorahotelplaza.com
// Production: https://aurorahotelplaza.com

// Tạo URL đầy đủ cho một path
$bookingUrl = url('booking/index.php');
// Localhost: http://localhost/GitHub/Aurorahotelplaza.com/booking/index.php
// Production: https://aurorahotelplaza.com/booking/index.php

// Lấy assets URL
$cssUrl = asset('css/style.css');
// Localhost: http://localhost/GitHub/Aurorahotelplaza.com/assets/css/style.css
// Production: https://aurorahotelplaza.com/assets/css/style.css

// Kiểm tra môi trường
if (isLocalhost()) {
    // Code chỉ chạy trên localhost
    error_reporting(E_ALL);
} else {
    // Code chỉ chạy trên production
    error_reporting(0);
}

// Redirect
redirect('profile/bookings.php');
```

### 2. Sử dụng Constants

```php
<?php
// Các constants có sẵn sau khi require environment.php

echo BASE_URL;      // http://localhost/GitHub/Aurorahotelplaza.com hoặc https://aurorahotelplaza.com
echo SITE_URL;      // Giống BASE_URL nhưng có trailing slash
echo ASSETS_URL;    // BASE_URL/assets
echo ADMIN_URL;     // BASE_URL/admin
echo UPLOADS_URL;   // BASE_URL/uploads
echo API_URL;       // BASE_URL/api

echo ENVIRONMENT;   // 'development' hoặc 'production'
echo IS_LOCALHOST;  // true hoặc false
echo DOMAIN;        // 'localhost' hoặc 'aurorahotelplaza.com'
echo DEBUG_MODE;    // true (localhost) hoặc false (production)
```

### 3. Trong HTML/Views

```php
<!-- Link tới trang -->
<a href="<?php echo url('rooms.php'); ?>">Xem phòng</a>

<!-- Load CSS -->
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">

<!-- Load JS -->
<script src="<?php echo asset('js/main.js'); ?>"></script>

<!-- Load image -->
<img src="<?php echo asset('img/logo.png'); ?>" alt="Logo">

<!-- Form action -->
<form action="<?php echo url('booking/create.php'); ?>" method="POST">
    <!-- form fields -->
</form>
```

### 4. Trong Email Templates

```php
<?php
// Trong email template
$bookingDetailUrl = url("profile/booking-detail.php?code={$booking_code}");

$html = "
<a href='{$bookingDetailUrl}'>Xem chi tiết đặt phòng</a>
";
```

## 🔍 Kiểm tra URL

### Truy cập trang test (chỉ localhost):

```
http://localhost/GitHub/Aurorahotelplaza.com/url-check.php
```

Trang này sẽ hiển thị:
- Thông tin môi trường hiện tại
- Các constants có sẵn
- Ví dụ sử dụng
- Hướng dẫn chi tiết

## 📋 Các hàm có sẵn

### Trong `config/environment.php`:

| Hàm | Mô tả | Ví dụ |
|-----|-------|-------|
| `isLocalhost()` | Kiểm tra có đang ở localhost không | `if (isLocalhost()) { ... }` |
| `getBaseUrl()` | Lấy base URL | `http://localhost/...` hoặc `https://aurorahotelplaza.com` |
| `getSiteUrl()` | Lấy site URL (có trailing slash) | `http://localhost/.../` |
| `getAssetsUrl()` | Lấy assets URL | `http://localhost/.../assets` |
| `getUploadsUrl()` | Lấy uploads URL | `http://localhost/.../uploads` |
| `getAdminUrl()` | Lấy admin URL | `http://localhost/.../admin` |
| `getApiUrl()` | Lấy API URL | `http://localhost/.../api` |
| `getEnvironment()` | Lấy tên môi trường | `'development'` hoặc `'production'` |
| `getDomain()` | Lấy domain | `'localhost'` hoặc `'aurorahotelplaza.com'` |
| `url($path)` | Tạo URL đầy đủ | `url('booking/index.php')` |
| `asset($path)` | Tạo asset URL | `asset('css/style.css')` |
| `redirect($path)` | Redirect tới path | `redirect('profile.php')` |
| `currentUrl()` | Lấy URL hiện tại | `http://localhost/.../current-page.php` |
| `isCurrentUrl($path)` | Kiểm tra URL hiện tại | `isCurrentUrl('rooms.php')` |

### Trong `helpers/url-checker.php`:

| Hàm | Mô tả |
|-----|-------|
| `URLChecker::checkEnvironment()` | Lấy thông tin môi trường chi tiết |
| `URLChecker::getFullUrl($path)` | Lấy URL đầy đủ |
| `URLChecker::isLocalhostUrl($url)` | Kiểm tra URL có phải localhost không |
| `URLChecker::convertToProductionUrl($url)` | Chuyển localhost URL sang production |
| `URLChecker::displayEnvironmentInfo()` | Hiển thị thông tin môi trường (HTML) |
| `URLChecker::runTests()` | Chạy test các hàm URL |

## 🔒 Bảo mật

### Trước khi deploy lên production:

1. **Xóa hoặc bảo vệ file test:**
   ```bash
   rm url-check.php
   ```

2. **Hoặc thêm vào `.htaccess`:**
   ```apache
   <Files "url-check.php">
       Require ip 127.0.0.1
       Require ip YOUR_IP_ADDRESS
   </Files>
   ```

3. **Kiểm tra không còn hardcode localhost:**
   ```bash
   grep -r "localhost" --include="*.php" --exclude-dir="config/phpqrcode" .
   ```

## 🎨 Ví dụ thực tế

### Ví dụ 1: Tạo link trong navigation

```php
<nav>
    <a href="<?php echo url(''); ?>">Trang chủ</a>
    <a href="<?php echo url('rooms.php'); ?>">Phòng</a>
    <a href="<?php echo url('services.php'); ?>">Dịch vụ</a>
    <a href="<?php echo url('contact.php'); ?>">Liên hệ</a>
</nav>
```

### Ví dụ 2: Load assets

```php
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
</head>
<body>
    <!-- content -->
    <script src="<?php echo asset('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>
</html>
```

### Ví dụ 3: Form submission

```php
<form action="<?php echo url('booking/create.php'); ?>" method="POST">
    <input type="text" name="guest_name" required>
    <button type="submit">Đặt phòng</button>
</form>
```

### Ví dụ 4: AJAX request

```javascript
// Trong file JS
const baseUrl = '<?php echo getBaseUrl(); ?>';

fetch(`${baseUrl}/api/check-availability.php`, {
    method: 'POST',
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => console.log(data));
```

### Ví dụ 5: Email template

```php
<?php
function sendBookingEmail($booking) {
    $detailUrl = url("profile/booking-detail.php?code={$booking['code']}");
    
    $html = "
    <h2>Xác nhận đặt phòng</h2>
    <p>Mã đặt phòng: {$booking['code']}</p>
    <a href='{$detailUrl}'>Xem chi tiết</a>
    ";
    
    // Send email...
}
```

## 📝 Lưu ý quan trọng

1. **Luôn require environment.php đầu tiên:**
   ```php
   require_once __DIR__ . '/config/environment.php';
   ```

2. **Không hardcode URL:**
   ❌ `http://localhost/GitHub/Aurorahotelplaza.com/booking/index.php`
   ✅ `url('booking/index.php')`

3. **Sử dụng hàm helper thay vì tự build URL:**
   ❌ `$_SERVER['HTTP_HOST'] . '/booking/index.php'`
   ✅ `url('booking/index.php')`

4. **Kiểm tra môi trường khi cần:**
   ```php
   if (isLocalhost()) {
       // Development-only code
   } else {
       // Production-only code
   }
   ```

5. **Debug mode tự động:**
   - Localhost: `DEBUG_MODE = true`, hiển thị errors
   - Production: `DEBUG_MODE = false`, ẩn errors

## 🚀 Deployment Checklist

Trước khi deploy lên production:

- [ ] Kiểm tra không còn hardcode localhost
- [ ] Xóa hoặc bảo vệ `url-check.php`
- [ ] Xóa hoặc bảo vệ `security-check.php`
- [ ] Kiểm tra HTTPS được bật
- [ ] Kiểm tra domain đúng: `aurorahotelplaza.com`
- [ ] Test các link và assets
- [ ] Test email templates
- [ ] Test payment return URL

## 🆘 Troubleshooting

### Vấn đề: URL không đúng trên localhost

**Giải pháp:** Kiểm tra `getBaseUrl()` trong `config/environment.php`. Hàm tự động phát hiện path dựa trên `$_SERVER['SCRIPT_NAME']`.

### Vấn đề: Assets không load được

**Giải pháp:** Đảm bảo đã require `environment.php` và sử dụng hàm `asset()`:
```php
require_once __DIR__ . '/config/environment.php';
echo asset('css/style.css');
```

### Vấn đề: Email link không đúng

**Giải pháp:** Sử dụng hàm `url()` trong email template:
```php
$link = url("profile/booking-detail.php?code={$code}");
```

## 📞 Hỗ trợ

Nếu có vấn đề, kiểm tra:
1. File `config/environment.php` đã được require chưa
2. Truy cập `url-check.php` để xem thông tin môi trường
3. Kiểm tra console/error log

---

**Cập nhật:** 2025-11-19
**Version:** 1.0
