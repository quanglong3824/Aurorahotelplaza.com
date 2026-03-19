# 🏨 Hướng Dẫn Sử Dụng Công Cụ Chẩn Đoán Đặt Phòng (Booking Diagnostic Tool)

> **Tài liệu này hướng dẫn chi tiết cách sử dụng công cụ chẩn đoán hệ thống đặt phòng của Aurora Hotel Plaza**

---

## 📋 Mục Lục

1. [Tổng Quan](#1-tổng-quan)
2. [Cách Sử Dụng](#2-cách-sử-dụng)
3. [Các Kiểm Tra Được Thực Hiện](#3-các-kiểm-tra-được-thực-hiện)
4. [Cách Đọc Kết Quả](#4-cách-đọc-kết-quả)
5. [Lỗi Thường Gặp & Giải Pháp](#5-lỗi-thường-gặp--giải-pháp)
6. [Mẹo & Thủ Thuật](#6-mẹo--thủ-thuật)
7. [Các File Liên Quan](#7-các-file-liên-quan)
8. [Thông Tin Hỗ Trợ](#8-thông-tin-hỗ-trợ)

---

## 1. Tổng Quan

### 🎯 Mục Đích

Công cụ chẩn đoán đặt phòng (**Booking Diagnostic Tool**) là một tiện ích mạnh mẽ giúp **kiểm tra toàn diện sức khỏe** của hệ thống đặt phòng Aurora Hotel Plaza. Công cụ này được thiết kế để:

- ✅ **Phát hiện sớm** các vấn đề về cơ sở dữ liệu
- ✅ **Xác minh** cấu trúc bảng và các cột cần thiết
- ✅ **Kiểm tra** kết nối database và quyền truy cập
- ✅ **Chạy thử** một booking mẫu (với rollback tự động)
- ✅ **Giám sát** môi trường PHP và các extension
- ✅ **Phân tích** hiệu suất hệ thống

### 📊 Thông Tin Kỹ Thuật

| Thuộc Tính | Giá Trị |
|------------|---------|
| **Phiên bản** | 1.0.0 |
| **Ngày phát triển** | 2026-03-19 |
| **Tác giả** | Aurora Development Team |
| **File chính** | `/booking/api/diagnostic-check.php` |
| **Định dạng output** | JSON |
| **Thời gian chạy trung bình** | 50-200ms |

### 🌟 Tính Năng Nổi Bật

```
┌─────────────────────────────────────────────────────────────┐
│  📌 CHẨN ĐOÁN TOÀN DIỆN HỆ THỐNG ĐẶT PHÒNG                 │
├─────────────────────────────────────────────────────────────┤
│  ✓ Kiểm tra kết nối Database                               │
│  ✓ Xác minh cấu trúc bảng (3 bảng chính)                   │
│  ✓ Kiểm tra 60+ cột dữ liệu                                │
│  ✓ Chạy booking test với transaction rollback              │
│  ✓ Phân tích môi trường PHP                                │
│  ✓ Kiểm tra session và file dependencies                   │
│  ✓ Đo lường hiệu suất (timing)                             │
│  ✓ Phát hiện error logs                                    │
│  ✓ Kiểm tra OOP classes                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Cách Sử Dụng

### 🔧 Có 2 Cách Để Chạy Công Cụ Chẩn Đoán

---

### **Cách 1: Sử Dụng Trình Duyệt (Console/Browser)** ⭐ *Khuyến Nghị*

#### Bước 1: Mở trình duyệt web

Mở bất kỳ trình duyệt nào (Chrome, Firefox, Safari, Edge...)

#### Bước 2: Truy cập URL diagnostic

```
http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

Hoặc nếu bạn đang sử dụng domain riêng:

```
https://yourdomain.com/booking/api/diagnostic-check.php
```

#### Bước 3: Xem kết quả

Kết quả sẽ hiển thị dưới dạng **JSON được định dạng đẹp** với đầy đủ thông tin chẩn đoán.

#### 📸 Ví dụ minh họa:

```
┌─────────────────────────────────────────────────────────┐
│  Trình duyệt: Chrome/Firefox/Safari                     │
│  URL: http://localhost/.../diagnostic-check.php         │
│  Kết quả: JSON hiển thị trực tiếp                       │
└─────────────────────────────────────────────────────────┘
```

#### 💡 Mẹo xem JSON đẹp hơn:

- **Chrome/Edge**: Cài extension "JSON Viewer"
- **Firefox**: Có sẵn JSON viewer tích hợp
- **Safari**: Sử dụng chế độ Developer

---

### **Cách 2: Sử Dụng API (Command Line/cURL)** 🖥️

#### Option A: Sử dụng cURL

```bash
# Cơ bản
curl http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php

# Với định dạng đẹp (nếu có jq)
curl -s http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php | jq

# Lưu kết quả vào file
curl -o diagnostic-result.json http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php

# Với headers chi tiết
curl -v -H "Accept: application/json" http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

#### Option B: Sử dụng PHP CLI

```bash
# Chạy trực tiếp từ command line
php /Applications/XAMPP/xamppfiles/htdocs/Github/AURORA\ HOTEL\ PLAZA/DOANH\ NGHIỆP/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

#### Option C: Sử dụng wget

```bash
# Tải về file
wget -O diagnostic-result.json http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php

# Hiển thị ra stdout
wget -qO- http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

#### Option D: Sử dụng PowerShell (Windows)

```powershell
# Cơ bản
Invoke-RestMethod -Uri "http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php" | ConvertTo-Json -Depth 10

# Lưu vào file
Invoke-RestMethod -Uri "http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php" | Out-File -FilePath diagnostic-result.json
```

#### Option E: Sử dụng Python

```python
import requests
import json

response = requests.get('http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php')
data = response.json()

# Hiển thị đẹp
print(json.dumps(data, indent=2, ensure_ascii=False))

# Lưu vào file
with open('diagnostic-result.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)
```

---

## 3. Các Kiểm Tra Được Thực Hiện

### 🔍 Tổng Quan Các Mục Kiểm Tra

Công cụ thực hiện **13 nhóm kiểm tra** chính:

```
┌────────────────────────────────────────────────────────────┐
│  STT  │  NHÓM KIỂM TRA                    │  SỐ LƯỢNG     │
├───────┼───────────────────────────────────┼───────────────┤
│   1   │  PHP Environment                   │  20+ thông số │
│   2   │  Database Connection               │  1 kiểm tra   │
│   3   │  Database Tables                   │  3 bảng       │
│   4   │  Bookings Table Columns            │  27+ cột      │
│   5   │  Booking Extra Guests Columns      │  9 cột        │
│   6   │  Room Types Table Columns          │  38+ cột      │
│   7   │  Sample Booking Test               │  1 test       │
│   8   │  Recent Bookings Data              │  5 metrics    │
│   9   │  Error Logs                        │  2 loại       │
│  10   │  OOP Classes Loading               │  5 classes    │
│  11   │  Session Validation                │  1 kiểm tra   │
│  12   │  File Dependencies                 │  10+ files    │
│  13   │  Permissions                       │  5 checks     │
└────────────────────────────────────────────────────────────┘
```

---

### 📌 Chi Tiết Từng Nhóm Kiểm Tra

#### 3.1. PHP Environment Checks (Môi trường PHP)

Kiểm tra các thông số quan trọng của PHP:

```json
{
  "php_version": "8.2.x",
  "php_sapi": "apache2handler",
  "server_software": "Apache/2.4.x",
  "memory_limit": "256M",
  "max_execution_time": "30",
  "extensions_loaded": ["pdo", "pdo_mysql", "json", "curl", "mbstring"...]
}
```

**Các extension bắt buộc:**
- `pdo` - PHP Data Objects
- `pdo_mysql` - MySQL driver cho PDO
- `json` - Xử lý JSON
- `session` - Quản lý session
- `mbstring` - Xử lý chuỗi multi-byte
- `curl` - HTTP requests

---

#### 3.2. Database Connection (Kết nối CSDL)

```json
{
  "connection": "success",
  "database_name": "aurorahotelplaza",
  "host": "localhost",
  "charset": "utf8mb4",
  "response_time_ms": 5.23
}
```

**Kiểm tra:**
- ✅ Kết nối MySQL/MariaDB
- ✅ Quyền truy cập database
- ✅ Thời gian phản hồi

---

#### 3.3. Database Tables Validation (Xác minh Bảng)

Kiểm tra sự tồn tại của **3 bảng chính**:

| Bảng | Mục Đích | Số Cột |
|------|----------|--------|
| `bookings` | Lưu thông tin đặt phòng | 27+ |
| `booking_extra_guests` | Lưu khách phụ thêm | 9 |
| `room_types` | Lưu loại phòng | 38+ |

---

#### 3.4. Bookings Table Columns (Cột Bảng Đặt Phòng)

Kiểm tra **27+ cột** trong bảng `bookings`:

```
✓ booking_id (PK)           ✓ check_in_date
✓ booking_code (UNIQUE)     ✓ check_out_date
✓ booking_type              ✓ num_adults
✓ user_id                   ✓ num_children
✓ guest_uuid                ✓ num_rooms
✓ room_type_id              ✓ total_nights
✓ room_id                   ✓ room_price
✓ check_in_time             ✓ total_amount
✓ check_out_time            ✓ guest_name
✓ num_guests                ✓ guest_email
✓ special_requests          ✓ guest_phone
✓ status                    ✓ payment_status
✓ created_at                ✓ updated_at
✓ occupancy_type            ✓ extra_guest_fee
✓ extra_bed_fee             ✓ extra_beds
✓ short_stay_hours          ✓ expected_checkin_time
✓ expected_checkout_time    ✓ price_type_used
✓ checked_in_at             ✓ checked_out_at
✓ checked_in_by             ✓ cancelled_at
✓ cancelled_by              ✓ cancellation_reason
```

---

#### 3.5. Booking Extra Guests Columns

Kiểm tra **9 cột** trong bảng `booking_extra_guests`:

```
✓ id (PK)              ✓ guest_name
✓ booking_id (FK)      ✓ height_cm
✓ guest_type           ✓ age
✓ fee                  ✓ includes_breakfast
✓ created_at
```

---

#### 3.6. Room Types Table Columns

Kiểm tra **38+ cột** trong bảng `room_types`:

```
✓ room_type_id (PK)        ✓ type_name, type_name_en
✓ slug                     ✓ category
✓ booking_type             ✓ description, description_en
✓ short_description        ✓ short_description_en
✓ max_occupancy            ✓ max_adults
✓ max_children             ✓ is_twin
✓ size_sqm                 ✓ bed_type
✓ amenities                ✓ images
✓ thumbnail                ✓ base_price
✓ weekend_price            ✓ holiday_price
✓ status                   ✓ sort_order
✓ created_at               ✓ updated_at
✓ price_published          ✓ price_single_occupancy
✓ price_double_occupancy   ✓ price_short_stay
✓ short_stay_description   ✓ view_type
✓ price_daily_single       ✓ price_daily_double
✓ price_weekly_single      ✓ price_weekly_double
✓ price_avg_weekly_single  ✓ price_avg_weekly_double
```

---

#### 3.7. Sample Booking Test (Kiểm Tra Đặt Phòng Mẫu)

🧪 **Chạy một booking test hoàn chỉnh:**

```
┌─────────────────────────────────────────────────────────┐
│  QUY TRÌNH TEST BOOKING                                │
├─────────────────────────────────────────────────────────┤
│  1. Tạo booking code duy nhất (DIAGxxxxxxxx)           │
│  2. Tạo email test duy nhất                            │
│  3. Bắt đầu transaction                                │
│  4. INSERT booking vào database                        │
│  5. VERIFY: Đọc lại booking vừa tạo                    │
│  6. ROLLBACK: Hoàn tác (xóa dữ liệu test)              │
│  7. VERIFY ROLLBACK: Đảm bảo dữ liệu đã được xóa       │
└─────────────────────────────────────────────────────────┘
```

**Đảm bảo:**
- ✅ Không để lại dữ liệu rác
- ✅ Transaction hoạt động đúng
- ✅ INSERT/SELECT hoạt động bình thường

---

#### 3.8. Recent Bookings Data (Dữ Liệu Đặt Phòng Gần Đây)

Thống kê nhanh:

```json
{
  "total_bookings": 1234,
  "recent_bookings_24h": 15,
  "recent_bookings_7d": 89,
  "pending_bookings": 12,
  "confirmed_bookings": 567,
  "status_distribution": {
    "pending": 12,
    "confirmed": 567,
    "checked_in": 234,
    "cancelled": 45
  },
  "last_booking": {
    "booking_code": "ABC12345",
    "status": "confirmed",
    "created_at": "2026-03-19 10:30:00"
  }
}
```

---

#### 3.9. Error Logs Check (Kiểm Tra Log Lỗi)

Quét error logs để tìm:
- ⚠️ PHP errors gần đây
- ⚠️ Booking-related errors
- ⚠️ Fatal warnings

---

#### 3.10. OOP Classes Loading (Kiểm tra Classes)

Xác minh khả năng load các class OOP:

| Class | File Path |
|-------|-----------|
| `Aurora\Core\DTOs\GuestDTO` | `/src/Core/DTOs/GuestDTO.php` |
| `Aurora\Core\Repositories\RoomRepository` | `/src/Core/Repositories/RoomRepository.php` |
| `Aurora\Core\Repositories\BookingRepository` | `/src/Core/Repositories/BookingRepository.php` |
| `Aurora\Core\Services\PricingService` | `/src/Core/Services/PricingService.php` |
| `Aurora\Core\Services\BookingService` | `/src/Core/Services/BookingService.php` |

---

## 4. Cách Đọc Kết Quả

### 📊 Cấu Trúc JSON Response

```json
{
  "success": true/false,
  "timestamp": "2026-03-19 11:43:00",
  "duration_ms": 85.42,
  "summary": {
    "total_checks": 15,
    "passed": 14,
    "failed": 1,
    "success_rate": 93.33,
    "errors_count": 1,
    "warnings_count": 2
  },
  "checks": [...],
  "errors": [...],
  "warnings": [...],
  "info": {...},
  "database": {...},
  "tables": {...},
  "columns": {...},
  "sample_booking_test": {...},
  "php_environment": {...}
}
```

---

### 🔑 Các Trường Quan Trọng Cần Xem

#### 4.1. `success` (Trạng Thái Tổng Quát)

```json
"success": true   // ✅ Tất cả kiểm tra đều PASS
"success": false  // ❌ Có ít nhất 1 kiểm tra FAIL
```

#### 4.2. `summary` (Tóm Tắt Nhanh)

```json
"summary": {
  "total_checks": 15,      // Tổng số kiểm tra
  "passed": 14,            // Số kiểm tra đạt
  "failed": 1,             // Số kiểm tra lỗi
  "success_rate": 93.33,   // Tỷ lệ thành công %
  "errors_count": 1,       // Số errors
  "warnings_count": 2      // Số warnings
}
```

**Đánh giá nhanh:**

| Success Rate | Tình Trạng | Hành Động |
|--------------|------------|-----------|
| 100% | 🟢 Hoàn hảo | Không cần làm gì |
| 90-99% | 🟡 Tốt | Xem warnings |
| 70-89% | 🟠 Cần lưu ý | Kiểm tra errors |
| < 70% | 🔴 Nghiêm trọng | Sửa ngay |

#### 4.3. `checks` (Chi Tiết Từng Kiểm Tra)

```json
"checks": [
  {
    "name": "Database Connection",
    "passed": true,
    "message": "Connected successfully to aurorahotelplaza",
    "details": {...},
    "timestamp": "2026-03-19 11:43:00"
  },
  {
    "name": "Bookings Table Columns",
    "passed": false,
    "message": "Missing columns: extra_bed_fee, short_stay_hours",
    "details": {
      "expected": 27,
      "existing": 25,
      "missing": ["extra_bed_fee", "short_stay_hours"]
    }
  }
]
```

#### 4.4. `errors` (Danh Sách Lỗi)

```json
"errors": [
  "Missing columns: extra_bed_fee, short_stay_hours",
  "Failed to load class: Aurora\\Core\\Services\\BookingService"
]
```

#### 4.5. `warnings` (Cảnh Báo)

```json
"warnings": [
  "PHP error log not readable",
  "No bookings in last 24 hours"
]
```

---

### 🎯 Phân Tích Kết Quả Mẫu

#### ✅ Kết Quả Tốt (All Passed)

```json
{
  "success": true,
  "summary": {
    "total_checks": 15,
    "passed": 15,
    "failed": 0,
    "success_rate": 100.0
  },
  "errors": [],
  "warnings": []
}
```

**Hành động:** ✅ Không cần làm gì, hệ thống hoạt động tốt!

---

#### ⚠️ Kết Quả Có Warnings

```json
{
  "success": true,
  "summary": {
    "total_checks": 15,
    "passed": 15,
    "failed": 0,
    "success_rate": 100.0,
    "warnings_count": 2
  },
  "warnings": [
    "PHP error log not configured",
    "No bookings in last 24 hours"
  ]
}
```

**Hành động:** ⚠️ Kiểm tra warnings, có thể bỏ qua nếu không nghiêm trọng.

---

#### ❌ Kết Quả Có Errors

```json
{
  "success": false,
  "summary": {
    "total_checks": 15,
    "passed": 12,
    "failed": 3,
    "success_rate": 80.0,
    "errors_count": 3
  },
  "errors": [
    "Missing columns: extra_bed_fee, short_stay_hours",
    "Database connection timeout",
    "Class not found: BookingService"
  ]
}
```

**Hành động:** ❌ **SỬA NGAY!** Xem phần [Lỗi Thường Gặp](#5-lỗi-thường-gặp--giải-pháp) bên dưới.

---

## 5. Lỗi Thường Gặp & Giải Pháp

### 🚨 Nhóm 1: Lỗi Database Connection

#### **Lỗi: "Database connection failed"**

**Nguyên nhân:**
- MySQL service không chạy
- Thông tin kết nối sai (host, username, password)
- Database không tồn tại
- Firewall chặn kết nối

**Giải pháp:**

```bash
# 1. Kiểm tra MySQL đang chạy
# macOS
brew services list | grep mysql

# Windows (XAMPP)
# Mở XAMPP Control Panel, kiểm tra MySQL status

# 2. Khởi động MySQL nếu cần
# macOS
brew services start mysql

# Windows XAMPP
# Click "Start" ở MySQL trong XAMPP Control Panel

# 3. Kiểm tra file cấu hình database
# File: /config/database.php
```

**Kiểm tra file `/config/database.php`:**

```php
<?php
$host = 'localhost';
$dbname = 'aurorahotelplaza';
$username = 'root';
$password = '';  // Hoặc mật khẩu của bạn
$charset = 'utf8mb4';
```

---

#### **Lỗi: "Access denied for user"**

**Nguyên nhân:** Username hoặc password sai

**Giải pháp:**

```sql
-- 1. Đăng nhập MySQL
mysql -u root -p

-- 2. Kiểm tra user tồn tại
SELECT User, Host FROM mysql.user;

-- 3. Cấp quyền nếu cần
GRANT ALL PRIVILEGES ON aurorahotelplaza.* TO 'root'@'localhost' IDENTIFIED BY '';
FLUSH PRIVILEGES;
```

---

### 🚨 Nhóm 2: Lỗi Thiếu Cột (Missing Columns)

#### **Lỗi: "Missing columns: extra_bed_fee, short_stay_hours..."**

**Nguyên nhân:** Database schema không đồng bộ với code

**Giải pháp:**

```sql
-- 1. Kiểm tra cột tồn tại
DESCRIBE bookings;

-- 2. Thêm cột thiếu
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS extra_bed_fee DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS extra_guest_fee DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS extra_beds INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS short_stay_hours INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS expected_checkin_time VARCHAR(10),
ADD COLUMN IF NOT EXISTS expected_checkout_time VARCHAR(10),
ADD COLUMN IF NOT EXISTS price_type_used VARCHAR(50),
ADD COLUMN IF NOT EXISTS occupancy_type VARCHAR(20),
ADD COLUMN IF NOT EXISTS checked_in_at DATETIME,
ADD COLUMN IF NOT EXISTS checked_out_at DATETIME,
ADD COLUMN IF NOT EXISTS checked_in_by INT,
ADD COLUMN IF NOT EXISTS cancelled_at DATETIME,
ADD COLUMN IF NOT EXISTS cancelled_by INT,
ADD COLUMN IF NOT EXISTS cancellation_reason TEXT;

-- 3. Verify
DESCRIBE bookings;
```

---

### 🚨 Nhóm 3: Lỗi PHP Extension

#### **Lỗi: "Missing required extension: pdo_mysql"**

**Nguyên nhân:** Extension chưa được bật trong php.ini

**Giải pháp:**

```ini
; 1. Mở file php.ini
; XAMPP macOS: /Applications/XAMPP/xamppfiles/etc/php.ini
; XAMPP Windows: C:\xampp\php\php.ini

; 2. Bỏ comment các dòng sau (xóa dấu ;)
extension=pdo
extension=pdo_mysql
extension=curl
extension=mbstring
extension=json

; 3. Restart Apache
# XAMPP Control Panel -> Stop Apache -> Start Apache
```

**Kiểm tra extensions đã load:**

```bash
# Command line
php -m | grep -E "pdo|curl|mbstring"

# Hoặc tạo file test.php
<?php phpinfo(); ?>
# Truy cập: http://localhost/test.php
```

---

### 🚨 Nhóm 4: Lỗi Sample Booking Test

#### **Lỗi: "Sample booking test failed: No room types available"**

**Nguyên nhân:** Bảng `room_types` không có dữ liệu

**Giải pháp:**

```sql
-- 1. Kiểm tra dữ liệu
SELECT COUNT(*) FROM room_types;

-- 2. Thêm room type test nếu cần
INSERT INTO room_types (
  type_name, type_name_en, slug, category, booking_type,
  max_occupancy, max_adults, max_children,
  base_price, status, created_at, updated_at
) VALUES (
  'Phòng Deluxe', 'Deluxe Room', 'deluxe-room', 'room', 'instant',
  3, 2, 1,
  1500000, 'active', NOW(), NOW()
);
```

---

#### **Lỗi: "Sample booking test failed: Insert statement failed"**

**Nguyên nhân:** 
- Foreign key constraint violation
- NOT NULL constraint violation
- Trigger error

**Giải pháp:**

```sql
-- 1. Kiểm tra foreign keys
SELECT 
  TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
  REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'aurorahotelplaza' 
AND TABLE_NAME = 'bookings';

-- 2. Kiểm tra error logs
# XAMPP macOS: /Applications/XAMPP/xamppfiles/logs/error_log
# XAMPP Windows: C:\xampp\apache\logs\error.log
```

---

### 🚨 Nhóm 5: Lỗi OOP Classes

#### **Lỗi: "Class not found: Aurora\\Core\\Services\\BookingService"**

**Nguyên nhân:** File class không tồn tại hoặc path sai

**Giải pháp:**

```bash
# 1. Kiểm tra file tồn tại
ls -la /src/Core/Services/BookingService.php

# 2. Kiểm tra namespace trong file
head -20 /src/Core/Services/BookingService.php

# Phải có:
# namespace Aurora\Core\Services;
# class BookingService { ... }

# 3. Kiểm tra autoloader
# Đảm bảo composer autoload hoặc manual require đúng path
```

---

### 🚨 Nhóm 6: Lỗi Permissions

#### **Lỗi: "Permission denied" khi đọc/write files**

**Giải pháp:**

```bash
# macOS/Linux
chmod 755 /Applications/XAMPP/xamppfiles/htdocs/Github/AURORA\ HOTEL\ PLAZA/DOANH\ NGHIỆP/Aurorahotelplaza.com/booking/api/
chmod 644 /Applications/XAMPP/xamppfiles/htdocs/Github/AURORA\ HOTEL\ PLAZA/DOANH\ NGHIỆP/Aurorahotelplaza.com/booking/api/diagnostic-check.php

# Windows (PowerShell)
icacls "C:\xampp\htdocs\Aurorahotelplaza.com\booking\api" /grant Everyone:(OI)(CI)F
```

---

## 6. Mẹo & Thủ Thuật

### 💡 Mẹo 1: Chạy Diagnostic Định Kỳ

Tạo cron job để chạy diagnostic tự động:

```bash
# macOS/Linux - Thêm vào crontab (crontab -e)
# Chạy mỗi 6 giờ
0 */6 * * * curl -s http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php >> /var/log/booking-diagnostic.log

# Windows - Task Scheduler
# Tạo task mới, trigger mỗi 6 giờ
# Action: curl -o C:\logs\diagnostic.json http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

---

### 💡 Mẹo 2: So Sánh Kết Quả Theo Thời Gian

```bash
# Lưu kết quả với timestamp
curl -s http://localhost/.../diagnostic-check.php | jq > diagnostic-$(date +%Y%m%d-%H%M%S).json

# So sánh 2 file
diff diagnostic-20260319-100000.json diagnostic-20260319-160000.json
```

---

### 💡 Mẹo 3: Tạo Dashboard Đơn Giản

Tạo file `diagnostic-dashboard.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Booking Diagnostic Dashboard</title>
    <meta http-equiv="refresh" content="60">
    <style>
        body { font-family: monospace; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🏨 Booking System Health</h1>
    <pre id="result">Loading...</pre>
    <script>
        fetch('/booking/api/diagnostic-check.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('result').textContent = 
                    JSON.stringify(data.summary, null, 2);
            });
    </script>
</body>
</html>
```

---

### 💡 Mẹo 4: Alert Khi Có Lỗi

Script bash gửi email khi có lỗi:

```bash
#!/bin/bash

RESULT=$(curl -s http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php)
SUCCESS=$(echo $RESULT | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    echo "Booking Diagnostic Failed!" | mail -s "🚨 Alert: Booking System Error" admin@aurorahotelplaza.com <<< "$RESULT"
fi
```

---

### 💡 Mẹo 5: Filter Kết Quả Với jq

```bash
# Chỉ xem summary
curl -s http://localhost/.../diagnostic-check.php | jq '.summary'

# Chỉ xem errors
curl -s http://localhost/.../diagnostic-check.php | jq '.errors'

# Chỉ xem failed checks
curl -s http://localhost/.../diagnostic-check.php | jq '.checks[] | select(.passed == false)'

# Đếm số errors
curl -s http://localhost/.../diagnostic-check.php | jq '.errors | length'

# Xem duration
curl -s http://localhost/.../diagnostic-check.php | jq '.duration_ms'
```

---

### 💡 Mẹo 6: Test Từ Remote

```bash
# Cho phép remote access (chỉ trong mạng nội bộ!)
# Thêm vào đầu file diagnostic-check.php:

$allowedIPs = ['127.0.0.1', '192.168.1.', '10.0.0.'];
$clientIP = $_SERVER['REMOTE_ADDR'];
$allowed = false;
foreach ($allowedIPs as $ip) {
    if (strpos($clientIP, $ip) === 0) {
        $allowed = true;
        break;
    }
}
if (!$allowed && php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}
```

---

### 💡 Mẹo 7: Performance Benchmark

```bash
# Chạy 10 lần và tính trung bình
for i in {1..10}; do
    curl -s -o /dev/null -w "%{time_total}\n" \
        http://localhost/Aurorahotelplaza.com/booking/api/diagnostic-check.php
done | awk '{sum+=$1} END {print "Average: " sum/NR " seconds"}'
```

---

## 7. Các File Liên Quan

### 📁 Cấu Trúc Thư Mục Booking

```
booking/
├── api/
│   ├── diagnostic-check.php      ⭐ File chính của diagnostic tool
│   ├── calculate_price.php       Tính toán giá phòng
│   ├── create_booking.php        Tạo booking mới
│   ├── validate-booking.php      Validate booking data
│   ├── confirm-booking-user.php  Xác nhận booking
│   ├── apply-promotion.php       Áp dụng khuyến mãi
│   ├── create_inquiry.php        Tạo inquiry
│   └── track.php                 Track booking status
├── assets/
│   ├── css/                      Stylesheets
│   └── js/                       JavaScript files
├── index.php                     Trang booking chính
└── confirmation.php              Trang xác nhận booking
```

---

### 📄 File Chính: diagnostic-check.php

**Đường dẫn đầy đủ:**

```
/Applications/XAMPP/xamppfiles/htdocs/Github/AURORA HOTEL PLAZA/DOANH NGHIỆP/Aurorahotelplaza.com/booking/api/diagnostic-check.php
```

**Thông tin file:**

| Thuộc tính | Giá trị |
|------------|---------|
| **Kích thước** | ~35KB |
| **Dòng code** | ~800+ lines |
| **Ngôn ngữ** | PHP 7.4+ |
| **Dependencies** | PDO, MySQL |
| **Output** | JSON |

---

### 🔗 Các File Liên Quan Khác

#### Database Configuration
```
/config/database.php
```

#### Controllers
```
/controllers/FrontBookingController.php
```

#### Models/Repositories
```
/src/Core/Repositories/BookingRepository.php
/src/Core/Repositories/RoomRepository.php
```

#### Services
```
/src/Core/Services/BookingService.php
/src/Core/Services/PricingService.php
```

#### DTOs
```
/src/Core/DTOs/GuestDTO.php
```

#### Helpers
```
/helpers/booking-validator.php
/helpers/language.php
```

#### Views
```
/views/front-booking.view.php
```

---

## 8. Thông Tin Hỗ Trợ

### 📞 Liên Hệ Hỗ Trợ

| Kênh | Thông Tin |
|------|-----------|
| **Email** | support@aurorahotelplaza.com |
| **Technical Team** | tech@aurorahotelplaza.com |
| **GitHub** | github.com/aurorahotelplaza |
| **Documentation** | wiki.aurorahotelplaza.com |

---

### 🐛 Báo Cáo Lỗi (Bug Report)

Khi báo lỗi, vui lòng cung cấp:

1. **Kết quả diagnostic đầy đủ** (JSON)
2. **Thời điểm xảy ra lỗi**
3. **Các bước reproduce**
4. **Môi trường chạy** (PHP version, MySQL version, OS)
5. **Error logs** liên quan

**Template báo lỗi:**

```markdown
## Bug Report: Booking Diagnostic

### Kết quả diagnostic
```json
{dán kết quả JSON ở đây}
```

### Thời gian xảy ra
YYYY-MM-DD HH:MM:SS

### Các bước reproduce
1. ...
2. ...
3. ...

### Môi trường
- PHP: 8.2.x
- MySQL: 8.0.x
- OS: macOS 14.x / Windows 11

### Error logs
```
[dán error logs ở đây]
```
```

---

### 📚 Tài Liệu Tham Khảo

- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [MySQL Reference](https://dev.mysql.com/doc/refman/8.0/en/)
- [JSON Format Specification](https://www.json.org/json-en.html)
- [cURL Documentation](https://curl.se/docs/)

---

### 🔄 Lịch Sử Phiên Bản

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-03-19 | Initial release |

---

## 📝 Ghi Chú Quan Trọng

> ⚠️ **LƯU Ý VỀ BẢO MẬT:**
> - Không public file diagnostic-check.php ra internet
> - Chỉ sử dụng trong môi trường development/staging
> - Nếu cần dùng trong production, thêm authentication
> - Xóa hoặc disable tool sau khi debug xong

> ⚠️ **LƯU Ý VỀ PERFORMANCE:**
> - Tool chạy sample booking test với rollback, không để lại dữ liệu
> - Thời gian chạy: 50-200ms tùy cấu hình server
> - Không nên chạy quá thường xuyên trong production (< 1 lần/giờ)

---

## ✅ Checklist Nhanh

Trước khi chạy diagnostic:

- [ ] MySQL service đang chạy
- [ ] Apache/PHP service đang chạy
- [ ] Database `aurorahotelplaza` tồn tại
- [ ] File permissions đúng
- [ ] PHP extensions được load (pdo, pdo_mysql, curl, mbstring)

Sau khi chạy diagnostic:

- [ ] `success` = true
- [ ] `success_rate` >= 90%
- [ ] Không có errors nghiêm trọng
- [ ] Sample booking test passed
- [ ] Tất cả columns tồn tại

---

**📌 Tài liệu này được cập nhật lần cuối: 2026-03-19**

**© 2026 Aurora Hotel Plaza. All rights reserved.**
