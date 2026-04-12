# HƯỚNG DẪN CÀI ĐẶT COMPOSER DEPENDENCIES

## Vấn đề hiện tại
Dự án cần thư viện `google-gemini-php/client` để AI hoạt động, nhưng thư mục `vendor/` chưa tồn tại hoặc chưa có thư viện này.

---

## CÁCH 1: Cài đặt trên Hosting (Khuyến nghị)

### Bước 1: SSH vào hosting
```bash
ssh username@your-domain.com
# Hoặc qua cPanel → Terminal
```

### Bước 2: Di chuyển vào thư mục website
```bash
cd /home/username/public_html
# Hoặc đường dẫn thực tế của website bạn
```

### Bước 3: Cài Composer (nếu chưa có)
```bash
# Kiểm tra composer
composer --version

# Nếu chưa có, cài đặt:
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### Bước 4: Cài đặt dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Bước 5: Kiểm tra kết quả
```bash
ls -la vendor/
ls vendor/google-gemini-php/
```

---

## CÁCH 2: Cài đặt Local rồi upload lên host

### Bước 1: Cài Composer trên máy local
1. Tải installer: https://getcomposer.org/download/
2. Chạy installer
3. Thêm vào PATH

### Bước 2: Cài đặt dependencies
```bash
cd d:\Source AURORA\Aurorahotelplaza.com\Aurorahotelplaza.com
composer install --no-dev --optimize-autoloader
```

### Bước 3: Upload thư mục vendor/ lên host
Dùng FTP Client (FileZilla, WinSCP) hoặc cPanel File Manager:
- Upload toàn bộ thư mục `vendor/` lên host
- Đường dẫn: `/public_html/vendor/`

---

## CÁCH 3: Tải file ZIP vendor (nếu không dùng Composer)

### Tạo file test_api.php để kiểm tra
```php
<?php
// File: test_api.php
require_once 'vendor/autoload.php';

try {
    // Test Gemini API
    $apiKey = getenv('GEMINI_API_KEY') ?: 'YOUR_API_KEY_HERE';
    $client = Gemini::client($apiKey);
    $response = $client->generativeModel('gemini-2.0-flash')->generateContent('Hello');
    echo "SUCCESS: " . $response->text();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
```

---

## KIỂM TRA SAU KHI CÀI ĐẶT

### 1. Kiểm tra thư mục vendor
```bash
ls vendor/google-gemini-php/client/src/
```

### 2. Test API
Tạo file `test_gemini.php` ở root:
```php
<?php
require_once 'vendor/autoload.php';
require_once 'config/load_env.php';

$apiKey = env('GEMINI_API_KEY');
if (empty($apiKey)) {
    die("CHƯA CẤU HÌNH API KEY!");
}

try {
    $client = Gemini::client($apiKey);
    $response = $client->generativeModel('gemini-2.0-flash')->generateContent('Xin chào');
    echo "AI PHẢN HỒI: " . $response->text();
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage();
}
```

Truy cập: `https://your-domain.com/test_gemini.php`

---

## CẤU HÌNH API KEY

Tạo file `config/.env`:
```env
# Database
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_db_user
DB_PASS=your_db_password

# Gemini API Key
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Model
AI_MODEL=gemini-2.0-flash
```

### Lấy API Key:
1. Truy cập: https://aistudio.google.com/app/apikey
2. Đăng nhập Google Account
3. Click "Create API Key"
4. Copy và dán vào file `.env`

---

## XỬ LÝ SỰ CỐ

### Lỗi: "Class 'Gemini' not found"
→ Thư mục vendor/ chưa được cài đặt đúng
→ Chạy lại: `composer install --no-dev`

### Lỗi: "429 Too Many Requests"
→ API Key bị giới hạn quota
→ Dùng nhiều key: `GEMINI_API_KEYS=key1,key2,key3`

### Lỗi: "Database connection failed"
→ Kiểm tra thông tin DB trong file `.env`

---

## HỖ TRỢ

Nếu vẫn gặp vấn đề, kiểm tra:
1. Error log: `tail -f /path/to/error.log`
2. PHP version: `php -v` (cần 8.0+)
3. Permissions: `chmod 755 vendor/`