# HƯỚNG DẪN CÀI ĐẶT VENDOR KHÔNG CẦN COMPOSER
## (Cách đơn giản nhất - Không cần biết kỹ thuật)

---

## BƯỚC 1: TẢI FILE VENDOR TẢI VỀ

### Option A: Tải từ GitHub (Khuyến nghị)

1. Truy cập link này: https://github.com/longlequang/aurora-vendor-backup/releases
   (Hoặc link dự phòng bên dưới)

2. Nếu không có link trên, tải từ source này:
   - Link 1: https://github.com/google-gemini-php/client/archive/refs/heads/main.zip
   - Link 2: https://packagist.org/packages/google-gemini-php/client

### Option B: Nhờ người khác tạo vendor rồi gửi cho bạn

1. Nhờ developer chạy `composer install` trên máy họ
2. Họ sẽ gửi cho bạn folder `vendor/` đã nén thành `.zip`
3. Bạn giải nén và upload lên host

---

## BƯỚC 2: UPLOAD LÊN HOSTING

### Cách 1: Dùng cPanel File Manager (Dễ nhất)

1. **Đăng nhập cPanel**
   - Vào: `https://your-domain.com/cpanel`
   - Hoặc: `https://hosting-provider.com/cpanel`
   - Nhập username/password do host cung cấp

2. **Mở File Manager**
   - Click icon "File Manager"
   - Vào thư mục website (thường là `public_html` hoặc `www`)

3. **Upload file vendor.zip**
   - Click nút "Upload" trên thanh công cụ
   - Chọn file `vendor.zip` bạn đã tải
   - Đợi upload xong (100%)

4. **Giải nén file**
   - Click phải vào `vendor.zip`
   - Chọn "Extract"
   - Click "Extract Files"
   - Đợi giải nén xong

5. **Xóa file zip** (để tiết kiệm dung lượng)
   - Click phải vào `vendor.zip`
   - Chọn "Delete"

### Cách 2: Dùng FileZilla (FTP Client)

1. **Tải và cài FileZilla**
   - Link: https://filezilla-project.org/
   - Cài đặt như phần mềm bình thường

2. **Kết nối FTP**
   - Mở FileZilla
   - Nhập thông tin:
     - Host: `ftp.your-domain.com` hoặc IP host
     - Username: do host cung cấp
     - Password: do host cung cấp
     - Port: 21 (hoặc 22 cho SFTP)
   - Click "Quickconnect"

3. **Upload thư mục vendor/**
   - Bên trái (Local): Tìm đến folder `vendor/` trên máy bạn
   - Bên phải (Remote): Vào `public_html/` trên host
   - Kéo thả folder `vendor/` từ trái sang phải
   - Đợi upload xong (có thể mất 5-10 phút)

---

## BƯỚC 3: TẠO FILE .ENV

1. **Vào cPanel → File Manager**
2. **Vào thư mục `config/`**
3. **Tạo file mới tên `.env`**
   - Click "+ File" trên thanh công cụ
   - Tên file: `.env` (có dấu chấm ở đầu!)
   
4. **Sửa file .env, dán nội dung này vào:**
```env
# Database
DB_HOST=localhost
DB_NAME=ten_database_cua_ban
DB_USER=user_database_cua_ban
DB_PASS=mat_khau_database

# Gemini API Key (QUAN TRỌNG!)
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Model AI
AI_MODEL=gemini-2.0-flash
```

5. **Thay thế thông tin:**
   - `ten_database_cua_ban` → Tên database thật
   - `user_database_cua_ban` → User database
   - `mat_khau_database` → Password database
   - `AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX` → API Key bạn lấy từ Google

6. **Lưu file** (Ctrl+S hoặc nút Save)

---

## BƯỚC 4: LẤY GEMINI API KEY

1. **Truy cập**: https://aistudio.google.com/app/apikey

2. **Đăng nhập** bằng Google Account (Gmail)

3. **Click "Create API Key in new project"**
   - Hoặc "Get API Key" nếu đã có project

4. **Copy API Key** (dạng `AIzaSyXXXXXXXXXXXXXXXX...`)

5. **Dán vào file .env** ở bước 3

---

## BƯỚC 5: KIỂM TRA

1. **Truy cập**: `https://your-domain.com/test_gemini.php`

2. **Kết quả mong đợi:**
   - ✓ File vendor/autoload.php tồn tại
   - ✓ Đã load autoload thành công
   - ✓ Lấy API Key thành công
   - ✓ THÀNH CÔNG! (AI trả lời)

3. **Nếu thấy lỗi:**
   - Đọc thông báo lỗi để biết cần sửa gì
   - Thường gặp nhất: Sai API Key hoặc chưa upload đủ vendor

---

## CÁC LỖI THƯỜNG GẶP

### Lỗi: "vendor/autoload.php not found"
→ Chưa upload folder `vendor/` lên host
→ Upload lại folder `vendor/` đầy đủ

### Lỗi: "Class 'Gemini' not found"
→ Folder vendor không có thư viện google-gemini-php
→ Tải vendor zip đầy đủ hơn

### Lỗi: "CHƯA CẤU HÌNH API KEY"
→ File .env chưa có GEMINI_API_KEY
→ Kiểm tra lại file .env trong `config/`

### Lỗi: "429 Too Many Requests"
→ API Key bị giới hạn
→ Chờ vài phút hoặc tạo API Key mới

### Lỗi: "403 Permission Denied"
→ API Key không hợp lệ
→ Kiểm tra lại key hoặc tạo key mới

---

## HỖ TRỢ

Nếu vẫn không được, liên hệ:
- Email: quanglong.3824@gmail.com
- Hoặc nhờ developer có kinh nghiệm với Composer

---

## FILE CẦN CÓ TRONG VENDOR/

Sau khi upload, folder `vendor/` phải có các folder con:
```
vendor/
├── autoload.php
├── composer/
├── google-gemini-php/
│   └── client/
│       ├── src/
│       └── ...
├── guzzlehttp/
├── psr/
└── ...
```

Nếu thiếu, upload lại file vendor.zip đầy đủ hơn.