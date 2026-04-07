# CÁCH 3: KHÔNG CẦN COMPOSER - ĐÃ CÓ SẴN VENDORT

## ✓ ĐÃ TẠO SẴN THƯ MỤC VENDOR/

Bạn không cần tải gì thêm! Thư mục `vendor/` đã được tạo sẵn trong project với đầy đủ:
- `vendor/autoload.php` - File tự động load classes
- `vendor/google-gemini-php/client/src/Client.php` - Gemini Client
- `vendor/google-gemini-php/client/src/GenerativeModel.php` - Model handler
- `vendor/google-gemini-php/client/src/Response.php` - Response handler

---

## BƯỚC 1: UPLOAD THƯ MỤC VENDOR/ LÊN HOST

### Cách 1: Dùng FileZilla (Khuyến nghị)

1. **Tải FileZilla**: https://filezilla-project.org/

2. **Kết nối FTP**:
   - Mở FileZilla
   - Nhập thông tin FTP do host cung cấp:
     - Host: `ftp.aurorahotelplaza.com` (hoặc IP)
     - Username: (do host cung cấp)
     - Password: (do host cung cấp)
     - Port: 21 hoặc 22
   - Click "Quickconnect"

3. **Upload thư mục vendor/**:
   - Bên TRÁI (máy bạn): Tìm đến `d:\Source AURORA\Aurorahotelplaza.com\Aurorahotelplaza.com\vendor\`
   - Bên PHẢI (host): Vào `public_html/` hoặc `www/`
   - Kéo toàn bộ thư mục `vendor/` từ TRÁI sang PHẢI
   - Đợi upload xong (có thể mất 5-15 phút)

### Cách 2: Nén thành ZIP rồi upload qua cPanel

1. **Nén thư mục vendor/ thành ZIP**:
   - Click phải vào folder `vendor/`
   - Chọn "Send to" → "Compressed (zipped) folder"
   - Hoặc dùng WinRAR/7-Zip để nén

2. **Upload qua cPanel**:
   - Đăng nhập cPanel
   - Vào File Manager
   - Vào `public_html/`
   - Click "Upload"
   - Chọn file `vendor.zip`
   - Đợi upload xong

3. **Giải nén**:
   - Click phải vào `vendor.zip`
   - Chọn "Extract"
   - Click "Extract Files"
   - Xóa file `vendor.zip` sau khi giải nén

---

## BƯỚC 2: TẠO FILE .ENV

1. **Vào cPanel → File Manager**

2. **Vào thư mục `config/`**

3. **Tạo file `.env`**:
   - Click "+ File"
   - Tên: `.env` (có dấu chấm đầu!)
   - Click "Create File"

4. **Sửa file `.env`**, dán nội dung:
```env
# Database
DB_HOST=localhost
DB_NAME=ten_database
DB_USER=user_db
DB_PASS=mat_khau_db

# Gemini API Key (QUAN TRỌNG!)
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Model
AI_MODEL=gemini-2.0-flash
```

5. **Thay thông tin**:
   - `ten_database` → Tên database thật
   - `user_db` → User database
   - `mat_khau_db` → Password database
   - `AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX` → API Key từ Google

6. **Lưu file**

---

## BƯỚC 3: LẤY GEMINI API KEY

1. **Truy cập**: https://aistudio.google.com/app/apikey

2. **Đăng nhập** Gmail

3. **Click "Create API Key in new project"**

4. **Copy API Key** (dạng `AIzaSy...`)

5. **Dán vào file `.env`** ở Bước 2

---

## BƯỚC 4: KIỂM TRA

1. **Truy cập**: `https://aurorahotelplaza.com/test_gemini.php`

2. **Kết quả**:
   - Nếu thấy "✓ THÀNH CÔNG!" và AI trả lời → ĐÃ XONG!
   - Nếu lỗi → Đọc thông báo để sửa

---

## CÁC LỖI THƯỜNG GẶP

### "vendor/autoload.php not found"
→ Chưa upload thư mục `vendor/`
→ Upload lại đầy đủ

### "Class 'Gemini\Client' not found"
→ File vendor autoload không load được class
→ Kiểm tra file `vendor/google-gemini-php/client/src/Client.php` có tồn tại

### "CHƯA CẤU HÌNH API KEY"
→ File `.env` chưa đúng hoặc chưa có key
→ Kiểm tra lại file `config/.env`

### "429 Rate limit exceeded"
→ API Key bị giới hạn
→ Chờ vài phút hoặc tạo key mới

### "403 Permission denied"
→ API Key không hợp lệ
→ Kiểm tra lại key, đảm bảo copy đủ

---

## SAU KHI TEST XONG

**XÓA FILE TEST** để bảo mật:
```bash
# Trong cPanel File Manager
Delete: test_gemini.php
```

---

## HỖ TRỢ

Email: quanglong.3824@gmail.com