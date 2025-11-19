# 📁 Cấu Hình Subdirectory - Aurora Hotel Plaza

## 🎯 Tổng Quan

Website đang chạy trong **subdirectory** để test production:
- **Production URL**: `https://aurorahotelplaza.com/2025/`
- **Root Domain**: `https://aurorahotelplaza.com` (host chính)
- **Backend Location**: `/public_html/2025/` trên server

## ⚙️ Cấu Hình Đã Thực Hiện

### 1. **Environment Configuration** (`config/environment.php`)
```php
function getBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'aurorahotelplaza.com';
    
    // Tự động detect subdirectory từ $_SERVER['SCRIPT_NAME']
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $rootPath = preg_replace('#/(admin|auth|booking|...).*#', '', $scriptName);
    
    // Trả về: https://aurorahotelplaza.com/2025
    return $protocol . '://' . $host . $rootPath;
}
```

**Kết quả:**
- `BASE_URL` = `https://aurorahotelplaza.com/2025`
- `ASSETS_URL` = `https://aurorahotelplaza.com/2025/assets`
- `ADMIN_URL` = `https://aurorahotelplaza.com/2025/admin`

### 2. **.htaccess Configuration**
```apache
RewriteEngine On
RewriteBase /2025/

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Quan trọng:**
- `RewriteBase /2025/` cho phép URL rewriting hoạt động đúng trong subdirectory
- Tất cả relative URLs sẽ được resolve từ `/2025/`

### 3. **SQL Update Script** (`sql-update-image-paths.sql`)
Tất cả đường dẫn ảnh trong database đã được cập nhật:
```sql
-- Ví dụ:
UPDATE room_types 
SET image_url = REPLACE(image_url, 'localhost/...', 'aurorahotelplaza.com/2025/')
WHERE image_url LIKE '%localhost%';
```

## 📂 Cấu Trúc Thư Mục Trên Server

```
/home/username/public_html/
├── index.html                    ← Root domain page (nếu có)
├── .htaccess                     ← Root .htaccess (nếu có)
└── 2025/                         ← Backend files ở đây
    ├── .htaccess                 ← RewriteBase /2025/
    ├── index.php
    ├── admin/
    ├── assets/
    │   ├── css/
    │   ├── js/
    │   └── img/
    ├── auth/
    ├── booking/
    ├── config/
    │   ├── environment.php       ← Auto-detect subdirectory
    │   └── database.php
    ├── includes/
    └── ...
```

## 🔗 URL Examples

### Frontend URLs:
- Trang chủ: `https://aurorahotelplaza.com/2025/`
- Phòng: `https://aurorahotelplaza.com/2025/rooms.php`
- Đặt phòng: `https://aurorahotelplaza.com/2025/booking/`
- Admin: `https://aurorahotelplaza.com/2025/admin/`

### Asset URLs (tự động):
```php
// Trong PHP code
asset('css/style.css')
// Output: https://aurorahotelplaza.com/2025/assets/css/style.css

url('booking/index.php')
// Output: https://aurorahotelplaza.com/2025/booking/index.php
```

### Image URLs trong Database:
```
https://aurorahotelplaza.com/2025/assets/img/deluxe/room-1.jpg
https://aurorahotelplaza.com/2025/uploads/avatars/user-123.jpg
```

## 🧪 Testing

### 1. Kiểm tra URL Detection
```bash
# Truy cập URL checker
https://aurorahotelplaza.com/2025/url-check.php

# Kết quả mong đợi:
BASE_URL: https://aurorahotelplaza.com/2025
ASSETS_URL: https://aurorahotelplaza.com/2025/assets
ENVIRONMENT: production
```

### 2. Kiểm tra Assets Loading
```bash
# Mở DevTools (F12) -> Network tab
# Kiểm tra các requests:
https://aurorahotelplaza.com/2025/assets/css/style.css ✅
https://aurorahotelplaza.com/2025/assets/js/main.js ✅
https://aurorahotelplaza.com/2025/assets/img/logo/... ✅
```

### 3. Kiểm tra Navigation
```bash
# Click các links trong menu
# Đảm bảo tất cả links có prefix /2025/
https://aurorahotelplaza.com/2025/rooms.php ✅
https://aurorahotelplaza.com/2025/about.php ✅
https://aurorahotelplaza.com/2025/contact.php ✅
```

## 🔄 Chuyển Sang Root Domain (Khi Sẵn Sàng)

Khi muốn chuyển từ `/2025/` sang root domain:

### 1. Di chuyển files:
```bash
# Trên server, di chuyển tất cả files từ /2025/ lên /public_html/
mv /public_html/2025/* /public_html/
```

### 2. Cập nhật .htaccess:
```apache
# Thay đổi từ:
RewriteBase /2025/

# Sang:
RewriteBase /
```

### 3. Cập nhật SQL (nếu cần):
```sql
-- Xóa /2025/ khỏi URLs
UPDATE room_types 
SET image_url = REPLACE(image_url, '/2025/', '/')
WHERE image_url LIKE '%/2025/%';

-- Làm tương tự cho các bảng khác
```

### 4. Test lại:
```bash
# Truy cập root domain
https://aurorahotelplaza.com/

# Kiểm tra BASE_URL
https://aurorahotelplaza.com/url-check.php
# Kết quả: BASE_URL = https://aurorahotelplaza.com
```

## ⚠️ Lưu Ý Quan Trọng

### 1. **Không hardcode URLs**
❌ Sai:
```php
<link href="https://aurorahotelplaza.com/2025/assets/css/style.css">
```

✅ Đúng:
```php
<link href="<?php echo asset('css/style.css'); ?>">
```

### 2. **Sử dụng PHP Functions**
- `getBaseUrl()` - Lấy base URL
- `asset($path)` - Lấy asset URL
- `url($path)` - Tạo full URL
- `redirect($path)` - Redirect

### 3. **Database Images**
- Tất cả image URLs trong database phải có prefix `/2025/`
- Chạy SQL update script sau khi import database

### 4. **.htaccess RewriteBase**
- Phải set `RewriteBase /2025/` để URL rewriting hoạt động
- Nếu chuyển sang root, đổi thành `RewriteBase /`

## 🐛 Troubleshooting

### Lỗi: Assets không load (404)
```bash
# Kiểm tra:
1. File .htaccess có RewriteBase /2025/ chưa?
2. Folder assets có trong /2025/ chưa?
3. Permissions đúng chưa? (755 cho folders, 644 cho files)
```

### Lỗi: Links không đúng
```bash
# Kiểm tra:
1. Có dùng asset() và url() functions không?
2. BASE_URL có đúng không? (check url-check.php)
3. .htaccess có lỗi syntax không?
```

### Lỗi: Images trong database không hiển thị
```bash
# Chạy lại SQL update script:
1. Mở phpMyAdmin
2. Chọn database
3. Paste nội dung sql-update-image-paths.sql
4. Click "Go"
```

## 📞 Support

Nếu gặp vấn đề:
1. Check error logs: `/public_html/2025/error_log`
2. Check PHP errors: Enable display_errors tạm thời
3. Check .htaccess syntax
4. Verify file permissions

---

**Cấu hình này cho phép test production trong subdirectory mà không ảnh hưởng đến root domain!** 🎉
