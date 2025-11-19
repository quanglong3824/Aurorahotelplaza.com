# 🚀 Hướng Dẫn Deploy Production - Aurora Hotel Plaza

## 📍 Cấu Hình Subdirectory

**Production URL**: `https://aurorahotelplaza.com/2025/`
- Root domain: `https://aurorahotelplaza.com` (host chính)
- Backend files: Trong folder `/2025/` trên server
- Đang test production trong subdirectory `/2025`

## ✅ Đã Hoàn Thành

### 1. **Cấu Hình Môi Trường (Environment Configuration)**
- ✅ Xóa toàn bộ logic localhost từ `config/environment.php`
- ✅ Hỗ trợ subdirectory `/2025` với dynamic URL detection
- ✅ Tự động phát hiện HTTPS và domain
- ✅ Bật secure cookies cho production
- ✅ Tắt display_errors, chỉ log errors

### 2. **Cấu Hình Database**
- ✅ Xóa localhost fallback từ `config/database.php`
- ✅ Chỉ giữ production database credentials
- ✅ Đơn giản hóa error handling

### 3. **URL Management**
- ✅ Cập nhật `url-check.php` để hoạt động trên hosting
- ✅ Cập nhật `helpers/url-checker.php` - xóa localhost checks
- ✅ Tất cả URL helpers sử dụng dynamic detection

### 4. **Assets & Resources**
- ✅ Cập nhật `index.php` - sử dụng `asset()` helper với timestamp
- ✅ Cập nhật `includes/header.php` - sử dụng `asset()` helper
- ✅ Tất cả JS, CSS, images sử dụng PHP functions với cache busting

### 5. **SQL Update Script**
- ✅ Tạo file `sql-update-image-paths.sql` với câu lệnh UPDATE đầy đủ
- ✅ Cập nhật tất cả bảng: room_types, rooms, services, gallery, blog_posts, users, settings

---

## 📋 Checklist Deploy Lên cPanel Hosting

### **Bước 1: Chuẩn Bị Files**
```bash
# 1. Backup toàn bộ project hiện tại
# 2. Đảm bảo tất cả files đã được commit vào Git
# 3. Kiểm tra file .gitignore đã loại trừ các file không cần thiết
```

### **Bước 2: Upload Files Lên Hosting**
1. **Đăng nhập cPanel**
2. **Vào File Manager**
3. **Upload toàn bộ files vào thư mục `public_html/2025/`**
   - Tạo folder `2025` trong `public_html` nếu chưa có
   - Upload tất cả files vào folder này
4. **Extract files nếu upload dạng ZIP**

**Cấu trúc thư mục trên server:**
```
/home/username/public_html/
  └── 2025/                    ← Backend files ở đây
      ├── admin/
      ├── assets/
      ├── auth/
      ├── booking/
      ├── config/
      ├── includes/
      ├── .htaccess            ← Đã set RewriteBase /2025/
      └── index.php
```

### **Bước 3: Cấu Hình Database**
1. **Tạo Database mới trên cPanel** (nếu chưa có)
   - Tên DB: `auroraho_aurorahotelplaza.com`
   - User: `auroraho_longdev`
   - Password: `@longdev3824`

2. **Import Database**
   - Vào phpMyAdmin
   - Chọn database vừa tạo
   - Import file `aurorahotelplaza_com.sql`

3. **Chạy SQL Update Script**
   - Mở file `sql-update-image-paths.sql`
   - Copy toàn bộ nội dung
   - Paste vào SQL tab trong phpMyAdmin
   - Click "Go" để chạy

### **Bước 4: Cấu Hình File Permissions**
```bash
# Đặt permissions cho các thư mục cần write access
chmod 755 uploads/
chmod 755 uploads/qrcodes/
chmod 755 uploads/avatars/
chmod 755 logs/ (nếu có)

# Đảm bảo các file config không thể truy cập trực tiếp
chmod 644 config/*.php
```

### **Bước 5: Kiểm Tra .htaccess**
File `.htaccess` đã có sẵn, đảm bảo nó có các rules:
- Redirect HTTP -> HTTPS
- Security headers
- URL rewriting (nếu cần)

### **Bước 6: Test Website**
1. **Truy cập domain**: https://aurorahotelplaza.com/2025/
2. **Test các chức năng chính**:
   - ✅ Trang chủ load đúng
   - ✅ CSS, JS, images hiển thị
   - ✅ Database connection OK
   - ✅ Đăng nhập/đăng ký hoạt động
   - ✅ Booking system hoạt động
   - ✅ Admin panel truy cập được

3. **Kiểm tra URL Checker**:
   - Truy cập: https://aurorahotelplaza.com/2025/url-check.php
   - Xem thông tin môi trường
   - Đảm bảo BASE_URL = `https://aurorahotelplaza.com/2025`
   - Đảm bảo không còn localhost URLs

4. **Kiểm tra Console Errors**:
   - Mở DevTools (F12)
   - Xem Console tab
   - Đảm bảo không có 404 errors cho assets

---

## 🔧 Cấu Hình Đặc Biệt Cho cPanel

### **PHP Settings** (trong php.ini hoặc .htaccess)
```ini
# Tăng memory limit nếu cần
memory_limit = 256M

# Tăng upload size
upload_max_filesize = 20M
post_max_size = 20M

# Session settings
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax

# Error logging
display_errors = Off
log_errors = On
error_log = /home/username/error_log
```

### **SSL Certificate**
- Đảm bảo SSL đã được cài đặt (Let's Encrypt free SSL)
- Force HTTPS trong .htaccess:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📊 Kiểm Tra Sau Deploy

### **1. Database Connection**
```php
// Truy cập: https://aurorahotelplaza.com/2025/security-check.php
// Kiểm tra database status
```

### **2. Assets Loading**
```bash
# Kiểm tra các file assets có load không
https://aurorahotelplaza.com/2025/assets/css/style.css
https://aurorahotelplaza.com/2025/assets/js/main.js
https://aurorahotelplaza.com/2025/assets/img/logo/logo-white-ui.png
```

### **3. URL Functions**
```php
// Truy cập: https://aurorahotelplaza.com/2025/url-check.php
// Xem các constants:
// - BASE_URL: https://aurorahotelplaza.com/2025
// - ASSETS_URL: https://aurorahotelplaza.com/2025/assets
// - ENVIRONMENT: production
```

### **4. Error Logs**
```bash
# Kiểm tra error logs trong cPanel
# File Manager -> error_log
# Hoặc trong thư mục logs/
```

---

## 🔒 Security Checklist

- ✅ Xóa hoặc bảo vệ các file test/debug:
  - `url-check.php` (có thể xóa sau khi test xong)
  - `security-check.php` (chỉ cho admin)
  - Các file `.sql` backup

- ✅ Đảm bảo file permissions đúng:
  - Files: 644
  - Folders: 755
  - Config files: 644 (không execute)

- ✅ Bật HTTPS và secure cookies
- ✅ Tắt display_errors
- ✅ Bật error logging
- ✅ Cấu hình CORS nếu cần
- ✅ Thêm security headers trong .htaccess

---

## 🐛 Troubleshooting

### **Lỗi: Database Connection Failed**
```php
// Kiểm tra trong config/database.php:
define( 'DB_NAME', 'auroraho_aurorahotelplaza.com' );
define( 'DB_USER', 'auroraho_longdev' );
define( 'DB_PASSWORD', '@longdev3824' );
define( 'DB_HOST', 'localhost:3306' );

// Đảm bảo user có quyền truy cập database
```

### **Lỗi: 404 Not Found cho Assets**
```bash
# Kiểm tra đường dẫn assets folder
# Đảm bảo folder structure:
/public_html/
  /assets/
    /css/
    /js/
    /img/
```

### **Lỗi: Images không hiển thị**
```sql
-- Chạy lại SQL update script
-- File: sql-update-image-paths.sql
-- Kiểm tra đường dẫn trong database
SELECT image_url FROM room_types LIMIT 5;
```

### **Lỗi: Session không hoạt động**
```php
// Kiểm tra session path có write permission
// Trong cPanel -> PHP Settings -> session.save_path
```

---

## 📝 Notes Quan Trọng

1. **Cache Busting**: Tất cả assets đã có `?v=<?php echo time(); ?>` để tránh cache
2. **Dynamic URLs**: Tất cả URLs tự động detect từ `$_SERVER` variables
3. **No Hardcoded Paths**: Không còn hardcoded localhost hoặc absolute paths
4. **Production Ready**: Code đã được optimize cho production environment

---

## 🎯 Kết Quả Mong Đợi

Sau khi deploy thành công:
- ✅ Website chạy hoàn toàn trên production domain
- ✅ Không còn bất kỳ reference nào đến localhost
- ✅ Tất cả assets load với HTTPS
- ✅ Database images paths đã được update
- ✅ Performance tốt với cache busting
- ✅ Security headers đầy đủ
- ✅ Error logging hoạt động

---

## 📞 Support

Nếu gặp vấn đề trong quá trình deploy:
1. Kiểm tra error logs
2. Xem lại checklist trên
3. Test từng component riêng lẻ
4. Kiểm tra file permissions

**Good luck với deployment! 🚀**
