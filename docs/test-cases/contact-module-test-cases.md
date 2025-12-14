# Test Cases - Module Liên hệ (Contact)

## Thông tin chung
- **Module**: Contact (Liên hệ)
- **Ngày tạo**: 14/12/2025
- **Phiên bản**: 1.0

---

## A. FRONTEND - Trang Liên hệ (`contact.php`)

### A1. UI Tests

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| UI-01 | Hiển thị trang liên hệ | Truy cập `/contact.php` | Trang load đầy đủ: header, form, thông tin liên hệ, map, footer | |
| UI-02 | Responsive mobile | Mở trang trên màn hình < 768px | Layout chuyển sang 1 cột, form và info xếp dọc | |
| UI-03 | Responsive tablet | Mở trang trên màn hình 768px - 1024px | Layout hiển thị phù hợp, không bị vỡ | |
| UI-04 | Dark mode | Bật dark mode | Màu sắc chuyển đổi đúng, text đọc được rõ ràng | |
| UI-05 | Form fields hiển thị | Kiểm tra form | Có đủ: Họ tên, Email, SĐT, Chủ đề, Tin nhắn | |
| UI-06 | Required fields | Kiểm tra label | Các trường bắt buộc có dấu (*) màu đỏ | |
| UI-07 | Placeholder text | Kiểm tra input | Mỗi input có placeholder hướng dẫn | |
| UI-08 | Google Map | Kiểm tra section map | Map hiển thị đúng vị trí khách sạn | |
| UI-09 | Thông tin liên hệ | Kiểm tra info section | Hiển thị: địa chỉ, SĐT, email, giờ làm việc | |
| UI-10 | Social links | Kiểm tra social icons | Có đủ Facebook, Instagram, YouTube | |

### A2. UX Tests - Form Submission

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| UX-01 | Submit form thành công | Điền đầy đủ thông tin hợp lệ → Submit | Toast success, modal hiển thị mã liên hệ | |
| UX-02 | Submit form trống | Không điền gì → Submit | Toast error: "Vui lòng điền đầy đủ thông tin" | |
| UX-03 | Email không hợp lệ | Nhập email sai format → Submit | Toast error: "Email không hợp lệ" | |
| UX-04 | SĐT không hợp lệ | Nhập SĐT < 10 số → Submit | Toast error: "Số điện thoại không hợp lệ" | |
| UX-05 | Tin nhắn quá ngắn | Nhập message < 10 ký tự → Submit | Toast error: "Nội dung tin nhắn quá ngắn" | |
| UX-06 | Loading state | Click submit | Button disabled, hiện spinner "Đang gửi..." | |
| UX-07 | Success modal | Gửi thành công | Modal hiện mã CT + submission_id | |
| UX-08 | Form reset sau submit | Gửi thành công | Chỉ reset message và subject, giữ lại name/email/phone | |

### A3. UX Tests - User đã đăng nhập

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| UX-09 | Auto-fill thông tin | Đăng nhập → Vào trang liên hệ | Name, email, phone tự động điền từ session | |
| UX-10 | Readonly fields | Kiểm tra các field đã auto-fill | Các field có data từ session bị readonly | |
| UX-11 | Notice đăng nhập | Kiểm tra form | Hiển thị thông báo "Bạn đang đăng nhập với..." | |
| UX-12 | Liên kết user_id | Gửi form khi đã đăng nhập | Database lưu user_id của user | |

---

## B. ADMIN - Quản lý liên hệ (`admin/contacts.php`)

### B1. UI Tests

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| ADM-UI-01 | Truy cập trang | Login admin → Vào Liên hệ | Trang hiển thị đầy đủ: stats, filters, table | |
| ADM-UI-02 | Stat cards | Kiểm tra 4 stat cards | Hiển thị số lượng: Mới, Đang xử lý, Đã giải quyết, Đã đóng | |
| ADM-UI-03 | Filter section | Kiểm tra filters | Có: Search, dropdown Trạng thái, dropdown Chủ đề | |
| ADM-UI-04 | Data table | Kiểm tra bảng | Có cột: ID, Khách hàng, Chủ đề, Nội dung, Trạng thái, Ngày gửi, Thao tác | |
| ADM-UI-05 | Badge trạng thái | Kiểm tra badges | Màu đúng: Mới (blue), Đang xử lý (yellow), Đã giải quyết (green), Đã đóng (gray) | |
| ADM-UI-06 | Action buttons | Kiểm tra cột Thao tác | Có 3 nút: Xem, Sửa trạng thái, Xóa | |
| ADM-UI-07 | Highlight row mới | Kiểm tra row có status=new | Row có background màu xanh nhạt | |
| ADM-UI-08 | Pagination | Có > 20 records | Hiển thị pagination với số trang | |
| ADM-UI-09 | Empty state | Không có data | Hiển thị "Không có liên hệ nào" | |
| ADM-UI-10 | Sidebar active | Kiểm tra sidebar | Menu "Liên hệ" được highlight | |

### B2. UX Tests - Filters

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| ADM-UX-01 | Filter theo trạng thái | Chọn "Mới" → Lọc | Chỉ hiển thị liên hệ có status=new | |
| ADM-UX-02 | Filter theo chủ đề | Chọn "Đặt phòng" → Lọc | Chỉ hiển thị liên hệ có subject="Đặt phòng" | |
| ADM-UX-03 | Search by name | Nhập tên → Lọc | Hiển thị liên hệ có name chứa keyword | |
| ADM-UX-04 | Search by email | Nhập email → Lọc | Hiển thị liên hệ có email chứa keyword | |
| ADM-UX-05 | Search by phone | Nhập SĐT → Lọc | Hiển thị liên hệ có phone chứa keyword | |
| ADM-UX-06 | Kết hợp filters | Chọn status + search | Kết quả thỏa mãn cả 2 điều kiện | |
| ADM-UX-07 | Xóa bộ lọc | Click "Xóa bộ lọc" | Reset về trạng thái ban đầu, hiển thị tất cả | |
| ADM-UX-08 | Giữ filter khi phân trang | Filter → Chuyển trang | Filter vẫn được giữ nguyên | |

### B3. UX Tests - View Detail Modal

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| ADM-UX-09 | Mở modal xem | Click icon "Xem" | Modal hiện với đầy đủ thông tin liên hệ | |
| ADM-UX-10 | Thông tin đầy đủ | Kiểm tra modal content | Có: Họ tên, Email, SĐT, Chủ đề, Nội dung, Ngày gửi, IP | |
| ADM-UX-11 | Đóng modal | Click X hoặc overlay | Modal đóng | |

### B4. UX Tests - Update Status Modal

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| ADM-UX-12 | Mở modal cập nhật | Click icon "Sửa" | Modal hiện với dropdown trạng thái | |
| ADM-UX-13 | Cập nhật thành công | Chọn trạng thái mới → Submit | Toast success, trang reload, badge đổi màu | |
| ADM-UX-14 | Thêm ghi chú | Nhập ghi chú → Submit | Ghi chú được lưu vào activity log | |
| ADM-UX-15 | Đóng modal | Click Hủy hoặc X | Modal đóng, không thay đổi gì | |

### B5. UX Tests - Delete

| ID | Test Case | Bước thực hiện | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------------|------------------|-----------|
| ADM-UX-16 | Confirm xóa | Click icon "Xóa" | Hiện confirm dialog | |
| ADM-UX-17 | Hủy xóa | Click Cancel trên confirm | Không xóa, dialog đóng | |
| ADM-UX-18 | Xóa thành công (admin) | Admin confirm xóa | Toast success, row biến mất | |
| ADM-UX-19 | Xóa bị từ chối (staff) | Staff confirm xóa | Toast error: "Chỉ admin mới có quyền xóa" | |

---

## C. API Tests (`api/contact.php` & `admin/api/contacts.php`)

### C1. Frontend API

| ID | Test Case | Request | Kết quả mong đợi | Pass/Fail |
|----|-----------|---------|------------------|-----------|
| API-01 | POST thành công | POST với data hợp lệ | `{success: true, submission_id: X}` | |
| API-02 | GET method | GET request | `{success: false, message: "Method not allowed"}` | |
| API-03 | Missing name | POST thiếu name | `{success: false, message: "Vui lòng nhập họ và tên"}` | |
| API-04 | Invalid email | POST email sai | `{success: false, message: "Email không hợp lệ"}` | |
| API-05 | Invalid phone | POST phone sai | `{success: false, message: "Số điện thoại không hợp lệ"}` | |
| API-06 | Message too short | POST message < 10 chars | `{success: false, message: "Nội dung tin nhắn quá ngắn"}` | |

### C2. Admin API

| ID | Test Case | Request | Kết quả mong đợi | Pass/Fail |
|----|-----------|---------|------------------|-----------|
| API-07 | Get contact | GET ?action=get&id=1 | `{success: true, contact: {...}}` | |
| API-08 | Get invalid ID | GET ?action=get&id=999 | `{success: false, message: "Không tìm thấy"}` | |
| API-09 | Update status | POST action=update_status | `{success: true}` | |
| API-10 | Delete (admin) | POST action=delete (as admin) | `{success: true}` | |
| API-11 | Delete (staff) | POST action=delete (as staff) | `{success: false, message: "Chỉ admin..."}` | |
| API-12 | Unauthorized | Request không login | `{success: false, message: "Không có quyền"}` | |

---

## D. Database Tests

| ID | Test Case | Kiểm tra | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------|------------------|-----------|
| DB-01 | Insert record | Gửi form liên hệ | Record mới trong contact_submissions | |
| DB-02 | Default status | Kiểm tra record mới | status = 'new' | |
| DB-03 | Timestamp | Kiểm tra created_at | Thời gian chính xác | |
| DB-04 | User_id (logged in) | Gửi form khi đã login | user_id được lưu | |
| DB-05 | User_id (guest) | Gửi form khi chưa login | user_id = NULL | |
| DB-06 | IP address | Kiểm tra ip_address | IP của client được lưu | |
| DB-07 | Update status | Cập nhật trạng thái | status và updated_at thay đổi | |
| DB-08 | Delete record | Xóa liên hệ | Record bị xóa khỏi DB | |

---

## E. Email Tests

| ID | Test Case | Kiểm tra | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------|------------------|-----------|
| EMAIL-01 | Email xác nhận khách | Gửi form → Check email khách | Nhận email xác nhận với mã liên hệ | |
| EMAIL-02 | Email thông báo hotel | Gửi form → Check email hotel | info@aurorahotelplaza.com nhận thông báo | |
| EMAIL-03 | Nội dung email khách | Kiểm tra email | Có: tên, nội dung, mã liên hệ, thông tin liên hệ hotel | |
| EMAIL-04 | Nội dung email hotel | Kiểm tra email | Có: thông tin khách, nội dung, IP, thời gian | |

---

## F. Security Tests

| ID | Test Case | Kiểm tra | Kết quả mong đợi | Pass/Fail |
|----|-----------|----------|------------------|-----------|
| SEC-01 | XSS trong name | Nhập `<script>alert(1)</script>` | Được escape, không execute | |
| SEC-02 | XSS trong message | Nhập HTML/JS code | Được escape khi hiển thị | |
| SEC-03 | SQL Injection | Nhập `'; DROP TABLE--` | Query không bị ảnh hưởng | |
| SEC-04 | Admin access control | Truy cập admin/contacts.php không login | Redirect về login | |
| SEC-05 | API access control | Gọi admin API không login | Return 401/403 | |

---

## Ghi chú
- **Pass**: ✅
- **Fail**: ❌
- **Blocked**: ⏸️
- **Not Tested**: ⬜

## Người thực hiện
- Tester: _______________
- Ngày test: _______________
- Môi trường: Local / Staging / Production
