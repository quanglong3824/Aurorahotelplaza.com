# Aurora Hotel Plaza - Tổng Hợp Tài Liệu Dự Án (Báo Cáo Cấp Trên)

**Phiên bản hiện tại:** `v2.1.1` (Minor upgrade với nâng cấp Livechat Realtime & Hệ thống Dọn dẹp)
**Công nghệ sử dụng:** PHP Thuần (Vanilla PHP), MySQL, Tailwind CSS (Liquid Glassmorphism UI), Javascript Vanilla (SSE API Realtime).

Tài liệu này tổng hợp toàn bộ các module và tính năng đã được xây dựng trong hệ thống quản trị và giao diện khách sạn Aurora Hotel Plaza, bao gồm cả những tính năng đã hoàn thiện và những tính năng hiện đang nằm trong trạng thái chờ hoặc chưa ổn định do chiến lược phát triển mở rộng (Feature Bloat).

---

## I. Giao Diện Khách Hàng (Customer Booking & Public Site)

Đây là các tính năng phục vụ trực tiếp đối tượng khách hàng truy cập website.

### 🟡 1. Tính năng Đã Hoàn Thiện & Hoạt Động Tốt

- **Trang chủ (Trải nghiệm UI/UX mới):** Cấu trúc Glassmorphism hiện đại, tối ưu hoá thiết kế hình ảnh và không gian.
- **Hệ thống xem Phòng ngủ & Căn hộ:** Hiển thị chi tiết tiện ích, số khách, hình ảnh slider và cơ cấu giá.
- **Quy trình Đặt phòng trực tuyến (Booking Flow):** Chọn ngày tháng, chọn phòng, chọn dịch vụ nâng cao.
- **Thanh toán:** Hệ thống thanh toán Booking.
- **Livechat Hỗ Trợ (Realtime SSE):** Box nhắn tin theo thời gian thực kết nối khách với quản trị viên, nhớ session và lịch sử, thông báo người dùng kể cả khi Load lại trang.
- **Đăng ký / Đăng nhập (Auth & Session):** Lưu trữ session khách, đăng nhập bảo vệ nhiều lớp.
- **Trang Cá Nhân Trang Khách (Profile):** Quản lý thông tin cá nhân và lịch sử Đặt phòng (Xem trạng thái, Invoice).
- **Trang Tĩnh Nội Dung:** Đầy đủ các trang Blogs, Thư viện ảnh, Liên hệ, Chăm sóc khách hàng.

### 🔴 2. Tính năng Có thể Chưa hoạt động hoặc Phình (Feature Bloat)

- **Sơ đồ xem phòng Khách Hàng (`room-map-user.php`):** Tính năng mở rộng cho phép khách chọn chính xác mã phòng trên Sơ đồ. _Lý do phình:_ Đang bị trùng lặp logic với hệ thống tự nhận phòng tự động của Admin, cần phải phân vùng rõ khách vãng lai và khách nội bộ.

---

## II. Hệ Thống Quản Trị Hệ Thống (Admin Panel)

Admin Panel (`/admin/dashboard.php`) là bảng điều khiển đầy đủ mô-đun dành cho Nhân viên (Sale, Lễ tân) và Admin cấp cao.

### 🟡 1. Tính năng Trọng Tâm - Đã Hoạt Động Ổn Định

#### A. Nhóm TƯƠNG TÁC (Customer Interaction Module)

- **Chat Support System Realtime:** Tính năng lớn giúp Admin quản lý đồng thời nhiều lượt Chat từ khách bằng EventSource (SSE). Hỗ trợ Claim, Chuyển gán cho Cấp dưới, Tạo ghi chú bảo mật.
- **Cài đặt Chat (Chat Settings):** Tuỳ biến câu trả lời nhanh (Quick replies), Lời chào lúc Offline/Online, Auto-reply giờ hành chính.

#### B. Nhóm SẢN PHẨM & DỊCH VỤ (Product & Service Module)

- **Quản lý danh mục Phòng & Phân loại:** Liệt kê các Block và các Loại phòng.
- **Khởi tạo Dịch vụ Phụ:** Các gói Spa, Dining hoặc đưa rước sân bay. Đơn dịch vụ (Service Bookings).

#### C. Nhóm MARKETTING & NỘI DUNG (Content Module)

- **Blog & FAQs:** Hệ thống viết bài chuẩn SEO, Hỏi đáp cho trang Public.
- **Banners & Gallery:** Quản lý kho ảnh trình chiếu slider, Banners trang chủ.
- **Khuyến mãi (Promotions):** Khởi tạo mã giảm giá.

#### D. Nhóm HỆ THỐNG / CỐT LÕI (Core Modules)

- **Dọn dẹp hệ thống Database:** (_Vừa update v2.1.1_), Giúp Admin chủ động Flush toàn bộ các Bảng giao dịch "rác" để chuẩn bị Release Production chuẩn.
- **Thông báo & Audit Logs:** Xem lịch sử người dùng và Admin, thông báo Realtime trên quả chuông.
- **User Management & Role Permissions:** Quản trị các thành viên Đăng ký và Thiết lập nhân viên trực thuộc (Lễ tân, Sale, Quản lý).
- **Sao lưu Dữ liệu:** Backup Database định kỳ (SQL Dump).

---

### 🔴 2. Tính năng "Phình" (Feature Bloat) - Đang Thử Nghiệm / Cần Đánh Giá Lại

Do hệ thống liên tục được nâng cấp với tham vọng cao, một số tính năng đang trong pha **"In-Development" hoặc "Unstable Bloat"**, có thể không hoạt động như mong đợi:

1. **Chương trình Thành Viên (Loyalty / Points_Transactions) (`admin/loyalty.php`)**
   - _Tình trạng:_ Thường xuyên bị trục trặc logic tính điểm thưởng vs Booking Refund.
   - _Lý do:_ Hệ sinh thái Điểm tưởng khá rắc rối và chưa được tối ưu, bảng Database bị phình to gây quá tải lúc thực thi tính toán hạng thẻ Vàng / Bạc... Tạm thời cần định hình lại luồng Điểm.

2. **Hệ thống Hoàn Tiền - Refund Đặt Phòng (`admin/refunds.php`)**
   - _Tình trạng:_ Đang nằm ở dạng Mockup chức năng, chỉ đổi trạng thái nhưng cổng API Bank thật chưa được nối khớp.
   - _Lý do:_ Mở rộng quá nhanh từ Module Booking sang Module Fintech. Đang chờ giải pháp duyệt chi chính quy hơn.

3. **Yêu cầu căn hộ Dài Hạn (`apartment-inquiries.php`)**
   - _Tình trạng:_ Giao diện và API đã có, cấu trúc Form đã tồn tại nhưng có thể chưa phân loại tách biệt so với Booking lưu trú ngày ngắn (Hotel).
   - _Lý do:_ Phát sinh định hướng mới cho dự án "Cho thuê văn phòng/Căn hộ".

4. **Bảng Giá Theo Thuê Nhượng Lịch (Chi tiết Giá/Seasonal Pricing / Room-Pricing) (`pricing-detailed.php`)**
   - _Tình trạng:_ Mảng logic cài đặt giá ngày lễ, Giá Last-Minute (giờ chót) đã viết xong Layout tuy nhiên công thức tự động nội suy còn cồng kềnh.
   - _Lý do:_ Mở rộng theo phong cách OTA (như Booking.com) khiến cho hệ logic Tính Tiền Quá Rối.

---

## III. Định Hướng Phát Triển Vòng Đời Tiếp Theo (Next Period)

Bản Báo cáo này để nhận diện tình trạng "Over-Engineering" (phát triển quá mức so với thực tiễn) ở vài khía cạnh, đặc biệt với các file tính tiền và tích điểm.

Trong giai đoạn tới, chúng ta sẽ áp dụng chiến lược **Tinh Gọn (Lean & Decoupling)**:

1. Tái cấu trúc (Refactor) lại nhóm tính năng Điểm thưởng & Dịch vụ trả trước.
2. Hoãn nâng cấp hệ thống Thanh toán Refund cho đến khi chốt xong UI Khách hàng.
3. Tập trung biến tính năng **Livechat Realtime** và **Nhận diện trạng thái sơ đồ phòng** làm chức năng trọng điểm đinh cao (selling point) của bản Release 3.0.

> _Tài liệu cập nhật ngày 27/02/2026 - Người thực hiện: Team Phát triển._
