# 📸 Hướng Dẫn Cập Nhật Đường Dẫn Ảnh Trong Database

## ⚠️ Vấn Đề Gặp Phải

**Lỗi MySQL:**
```
#1054 - Unknown column 'image_url' in 'where clause'
```

**Nguyên nhân:** SQL script cũ sử dụng tên cột sai. Database thực tế có cấu trúc khác.

---

## 📊 Cấu Trúc Database Thực Tế

### **Bảng `room_types`**
```sql
- images (TEXT) - Chứa nhiều ảnh, phân cách bởi dấu phẩy
- thumbnail (VARCHAR) - Ảnh thumbnail chính
```

**Ví dụ dữ liệu hiện tại:**
```
images: "assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg,assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg"
thumbnail: "assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg"
```

### **Bảng `banners`**
```sql
- image_desktop (VARCHAR) - Ảnh cho desktop
- image_mobile (VARCHAR) - Ảnh cho mobile
```

---

## ✅ SQL Script Đã Sửa

File: `sql-update-image-paths.sql`

### **Cập nhật room_types:**
```sql
-- Cập nhật cột images
UPDATE room_types 
SET images = REPLACE(images, 'assets/', '/2025/assets/')
WHERE images NOT LIKE '%/2025/%' AND images IS NOT NULL;

-- Cập nhật cột thumbnail
UPDATE room_types 
SET thumbnail = REPLACE(thumbnail, 'assets/', '/2025/assets/')
WHERE thumbnail NOT LIKE '%/2025/%' AND thumbnail IS NOT NULL;
```

### **Cập nhật banners:**
```sql
UPDATE banners 
SET image_desktop = REPLACE(image_desktop, 'assets/', '/2025/assets/')
WHERE image_desktop NOT LIKE '%/2025/%' AND image_desktop IS NOT NULL;

UPDATE banners 
SET image_mobile = REPLACE(image_mobile, 'assets/', '/2025/assets/')
WHERE image_mobile NOT LIKE '%/2025/%' AND image_mobile IS NOT NULL;
```

---

## 🚀 Cách Chạy SQL Script

### **Bước 1: Backup Database**
```bash
# Trên cPanel -> phpMyAdmin -> Export
# Hoặc dùng command line:
mysqldump -u auroraho_longdev -p auroraho_aurorahotelplaza.com > backup_before_update.sql
```

### **Bước 2: Chạy SQL Update**
1. Mở **phpMyAdmin** trên cPanel
2. Chọn database: `auroraho_aurorahotelplaza.com`
3. Click tab **SQL**
4. Copy nội dung file `sql-update-image-paths.sql`
5. Paste vào SQL editor
6. Click **Go**

### **Bước 3: Kiểm Tra Kết Quả**
```sql
-- Xem 5 records đầu tiên
SELECT room_type_id, type_name, 
       LEFT(images, 100) as images_preview, 
       thumbnail 
FROM room_types 
LIMIT 5;

-- Kết quả mong đợi:
-- images: "/2025/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg,/2025/assets/img/deluxe/..."
-- thumbnail: "/2025/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg"
```

---

## 🔍 Kiểm Tra Chi Tiết

### **1. Đếm số records đã update:**
```sql
SELECT 
    'room_types' as table_name,
    COUNT(*) as total_records,
    SUM(CASE WHEN images LIKE '%/2025/%' THEN 1 ELSE 0 END) as updated_images,
    SUM(CASE WHEN thumbnail LIKE '%/2025/%' THEN 1 ELSE 0 END) as updated_thumbnails
FROM room_types;
```

### **2. Xem tất cả đường dẫn:**
```sql
SELECT type_name, images, thumbnail 
FROM room_types 
ORDER BY room_type_id;
```

### **3. Kiểm tra banners:**
```sql
SELECT title, image_desktop, image_mobile 
FROM banners;
```

---

## 📝 Kết Quả Sau Khi Update

### **Trước:**
```
images: "assets/img/deluxe/room-1.jpg"
thumbnail: "assets/img/deluxe/room-1.jpg"
```

### **Sau:**
```
images: "/2025/assets/img/deluxe/room-1.jpg"
thumbnail: "/2025/assets/img/deluxe/room-1.jpg"
```

### **Trong PHP Code:**
```php
// Lấy dữ liệu từ database
$thumbnail = $row['thumbnail']; // "/2025/assets/img/deluxe/room-1.jpg"

// Tạo full URL
$fullUrl = BASE_URL . $thumbnail;
// Kết quả: https://aurorahotelplaza.com/2025/assets/img/deluxe/room-1.jpg

// Hoặc đơn giản hơn:
echo '<img src="' . BASE_URL . $row['thumbnail'] . '">';
```

---

## 🔄 Rollback (Nếu Cần Hoàn Tác)

Nếu có vấn đề, chạy SQL này để hoàn tác:

```sql
-- Hoàn tác room_types
UPDATE room_types 
SET images = REPLACE(images, '/2025/assets/', 'assets/')
WHERE images LIKE '%/2025/%';

UPDATE room_types 
SET thumbnail = REPLACE(thumbnail, '/2025/assets/', 'assets/')
WHERE thumbnail LIKE '%/2025/%';

-- Hoàn tác banners
UPDATE banners 
SET image_desktop = REPLACE(image_desktop, '/2025/assets/', 'assets/')
WHERE image_desktop LIKE '%/2025/%';

UPDATE banners 
SET image_mobile = REPLACE(image_mobile, '/2025/assets/', 'assets/')
WHERE image_mobile LIKE '%/2025/%';
```

---

## 🎯 Lưu Ý Quan Trọng

### **1. Đường dẫn trong database:**
- ✅ **Đúng:** `/2025/assets/img/room.jpg` (có leading slash)
- ❌ **Sai:** `assets/img/room.jpg` (không có /2025/)
- ❌ **Sai:** `https://aurorahotelplaza.com/2025/assets/...` (không hardcode domain)

### **2. Trong PHP code:**
```php
// ✅ Đúng
echo BASE_URL . $row['thumbnail'];

// ❌ Sai - không hardcode
echo 'https://aurorahotelplaza.com/2025' . $row['thumbnail'];
```

### **3. Kiểm tra trên website:**
```bash
# Sau khi update database, test:
https://aurorahotelplaza.com/2025/rooms.php
https://aurorahotelplaza.com/2025/apartments.php

# Mở DevTools (F12) -> Network tab
# Kiểm tra các image requests có load đúng không
```

---

## 🐛 Troubleshooting

### **Lỗi: Images không hiển thị sau khi update**
```bash
# Kiểm tra:
1. Database đã update chưa? (chạy SELECT query)
2. BASE_URL có đúng không? (check url-check.php)
3. File ảnh có tồn tại không? (check /2025/assets/img/)
4. Permissions đúng chưa? (755 cho folders, 644 cho files)
```

### **Lỗi: Một số ảnh hiển thị, một số không**
```sql
-- Kiểm tra xem có ảnh nào chưa được update:
SELECT room_type_id, type_name, thumbnail 
FROM room_types 
WHERE thumbnail NOT LIKE '%/2025/%' 
  AND thumbnail IS NOT NULL;

-- Nếu có, update thủ công:
UPDATE room_types 
SET thumbnail = CONCAT('/2025/', thumbnail)
WHERE room_type_id = [ID];
```

### **Lỗi: Ảnh bị duplicate /2025/2025/**
```sql
-- Nếu chạy script 2 lần, có thể bị duplicate:
-- Sửa bằng cách:
UPDATE room_types 
SET images = REPLACE(images, '/2025/2025/', '/2025/')
WHERE images LIKE '%/2025/2025/%';

UPDATE room_types 
SET thumbnail = REPLACE(thumbnail, '/2025/2025/', '/2025/')
WHERE thumbnail LIKE '%/2025/2025/%';
```

---

## ✅ Checklist Sau Khi Update

- [ ] Backup database đã tạo
- [ ] SQL script đã chạy thành công
- [ ] Kiểm tra SELECT query - đường dẫn có `/2025/`
- [ ] Test website - images hiển thị đúng
- [ ] Check DevTools - không có 404 errors
- [ ] Test tất cả pages: rooms, apartments, admin, etc.

---

## 📞 Support

Nếu gặp vấn đề:
1. Kiểm tra error logs: `/public_html/2025/error_log`
2. Xem database structure: `DESCRIBE room_types;`
3. Test từng query riêng lẻ
4. Restore từ backup nếu cần

**Đã test và hoạt động với database structure thực tế!** ✅
