# Aurora Hotel Plaza - Project Architecture & Developer Guide

Tài liệu này cung cấp cái nhìn toàn diện về cấu trúc mã nguồn và kiến trúc hệ thống của dự án Aurora Hotel Plaza để các Agent hoặc Developer có thể nắm bắt và sửa đổi code một cách chính xác.

## 1. Tổng quan kiến trúc (Architecture)
Dự án sử dụng mô hình **Hybrid MVC / Procedural**:
- **Frontend:** Các trang như `rooms.php`, `apartments.php` đóng vai trò là Entry Scripts, gọi đến `Controllers` để xử lý logic và sau đó include `Views` để hiển thị.
- **Backend (Admin):** Nằm trong thư mục `admin/`, tuân thủ chặt chẽ hơn cấu trúc Controller - View.
- **Core Logic:** Tập trung tại các thư mục `helpers/`, `models/`, và `src/Core/`.

## 2. Cấu trúc thư mục quan trọng
- `/admin`: Toàn bộ hệ thống quản trị (Dashboard, Bookings, Rooms, Logs...).
- `/api`: Các điểm cuối (endpoints) cho AJAX, Chat, và xử lý liên hệ.
- `/config`: Cấu hình hệ thống (Database, Router, Environment, Mailer).
- `/controllers`: Logic xử lý yêu cầu cho Frontend.
- `/helpers`: Các hàm tiện ích (Language, Auth, Image, Pricing, SEO, Security).
- `/includes`: Các thành phần giao diện dùng chung (Header, Footer, Chat widget).
- `/lang`: Tệp tin dịch thuật (vi.php, en.php).
- `/models`: Các lớp đối tượng dữ liệu (Booking, RoomType...).
- `/views`: Các tệp tin giao diện (HTML/PHP) tách biệt khỏi logic xử lý.
- `/apartment-details` & `/room-details`: Các trang chi tiết cho từng loại phòng/căn hộ.

## 3. Các thành phần then chốt (Core Components)

### A. Kết nối Database (`config/database.php`)
- Sử dụng **PDO** để kết nối.
- Cấu hình lấy từ `.env` (thông qua `load_env.php`).
- Hàm quan trọng: `getDB()` - Trả về đối tượng kết nối PDO.

### B. Hệ thống Routing (`config/router.php`)
- Quản lý đường dẫn ứng dụng, giúp URL thân thiện hơn.
- Tránh lỗi 404 bằng cách định nghĩa các route hợp lệ.

### C. Đa ngôn ngữ (`helpers/language.php`)
- Hỗ trợ Tiếng Việt (vi) và Tiếng Anh (en).
- `__($key)`: Dịch chuỗi tĩnh từ file lang.
- `_f($row, $field)`: Lấy dữ liệu database theo ngôn ngữ (ví dụ: `name_en` nếu đang ở chế độ Tiếng Anh).
- `initLanguage()`: Khởi tạo ngôn ngữ từ Session/Cookie.

### D. Tiện ích chung (`helpers/functions.php`)
- `sanitize($data)`: Làm sạch dữ liệu đầu vào.
- `formatDate($date)`: Định dạng ngày tháng (mặc định m/d/Y).
- `formatCurrency($amount)`: Định dạng tiền tệ VND.

## 4. Quy trình xử lý một trang (Request Flow)
1. **Khởi tạo:** Page script (VD: `rooms.php`) gọi `config/load_env.php` và `helpers/functions.php`.
2. **Xử lý Logic:** Page script khởi tạo `Controller` tương ứng (VD: `FrontRoomsController`).
3. **Lấy dữ liệu:** Controller tương tác với Database thông qua `getDB()` hoặc các `Models`.
4. **Hiển thị:** Page script include tệp tin View tương ứng trong `/views` (VD: `views/front-rooms.view.php`).

## 5. Lưu ý khi sửa đổi Code (Developer Notes)
- **Cập nhật Giao diện:** Luôn tìm file tương ứng trong thư mục `views/` thay vì sửa trực tiếp ở file page ngoài cùng.
- **Thêm tính năng mới:** Tạo Controller mới trong `controllers/` và View mới trong `views/`.
- **Database:** Nếu thêm field mới vào bảng, hãy nhớ thêm field tương ứng có hậu tố `_en` để hỗ trợ đa ngôn ngữ.
- **Security:** Luôn sử dụng `prepare()` statement của PDO để chống SQL Injection.
- **Git:** Commit bằng tiếng Việt và tự động push (theo mandate của dự án).

---
*Tài liệu này được tạo tự động bởi Gemini CLI Agent để hỗ trợ duy trì kiến thức dự án.*
