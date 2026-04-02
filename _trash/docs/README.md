# Aurora Hotel Plaza - The Ultimate Project Documentation & Feature Bloat Report

**Phiên bản:** `v2.1.1` (Tích hợp Livechat Realtime & Database Manager)
**Công nghệ:** Vanilla PHP, MySQL, Tailwind CSS (Liquid Glassmorphism), Javascript (SSE/AJAX).

---

## Tổng Quan Thống Kê (Feature Statistics)

Dự án hiện đang sở hữu **46 Mô-đun/Tính năng chính**, phân bổ bám sát 2 luồng giải pháp chính:

- **Giao diện Khách hàng (Client Portal):** 14 Tính năng.
- **Hệ thống Quản trị (Admin Panel):** 32 Tính năng.

---

## I. Giao Diện Khách Hàng (Customer Site - 14 Tính năng)

Được thiết kế theo trải nghiệm Liquid Glassmorphism, tập trung vào thị giác cao cấp và khả năng chuyển đổi (Conversion-rate).

### 🟢 Hoạt động Ổn định (Production-Ready)

1. **Trang chủ (Home & Landing):** Giao diện nổi bật, slider và trải nghiệm động.
2. **Hệ thống Đặt phòng lưu trú (Booking Flow):** Chọn ngày, tìm phòng khả dụng, giỏ hàng dịch vụ.
3. **Danh mục Khách sạn & Căn hộ riêng biệt (`rooms.php`, `apartments.php`):** Phân tách 2 mô hình kinh doanh.
4. **Chi tiết Không gian lưu trú:** Hình ảnh thư viện, đánh giá, tiện nghi chi tiết, mô tả.
5. **Dịch vụ & Tiện ích (Services Module):** Bảng danh sách Spa, Gym, Đưa đón và các gói dịch vụ phụ (Service Packages).
6. **Thanh toán trực tuyến (Payment System):** Luồng khởi tạo phiên giao dịch điện tử.
7. **Bảng tin & Blog (News / Articles):** Tin tức khách sạn, thư viện kinh nghiệm du lịch.
8. **Hệ thống Xác thực (Auth) & Phân quyền:** Đăng nhập, đăng ký, bảo mật bằng Token CSRF nhiều lớp.
9. **Cổng Thông tin Khách hàng (Profile / Dashboard):** Quản lý hồ sơ cá nhân và thay đổi mật khẩu.
10. **Quản lý Lịch sử Đặt phòng (Booking History):** Xem trạng thái phòng, tải hoá đơn (Invoice / PDF Layout).
11. **Chat trực tuyến (Livechat Widget):** Cửa sổ chat thả nổi (Floating UI), SSE thời gian thực, lưu trữ session khách không cần F5.
12. **Liên hệ Trực tuyến (Contact):** Gửi phản hồi, gửi thắc mắc sự cố.
13. **Chính sách \u0026 Bảo mật:** Đảm bảo pháp lý (Terms, Privacy, Cancellation Policies).

### 🔴 Chờ Phát Triển Hoặc Đánh Giá Lại (Unstable / Bloat)

14. **Sơ đồ xem phòng Khách Hàng (`room-map-user.php`):** _(Bloat)_ - Đang chờ thiết kế lại trải nghiệm tránh nhầm lẫn giữa Xem Sơ Đồ Khách Sạn và luồng đặt phòng chọn ngày tiêu chuẩn. Trải nghiệm người dùng đang bị rối.

---

## II. Hệ Thống Quản Trị Khách Sạn (Admin PMS Panel - 32 Tính năng)

Được chia làm 4 Nhóm chuyên trách, vận hành như một phần mềm Quản lý Khách sạn thực thụ (PMS - Property Management System) cho bộ phận Sale / Reception / Manager.

### 1. Nhóm Lễ Tân & Bán Hàng (Reception & Transactions - 9 Tính năng)

- 🟢 **Bảng Điều Khiển (Dashboard):** Tổng hợp số liệu, biểu đồ doanh thu, thông kê dữ liệu phòng realtime.
- 🟢 **Quản lý Đặt Phòng & Lịch sử (Bookings):** Quy trình từ chờ xác nhận đến nhận/trả phòng (Check-in / Check-out).
- 🟢 **Đơn Đặt Dịch Vụ Phụ (Service Bookings):** Đơn yêu cầu dùng Spa, dùng bữa tại Tầng, v.v.
- 🟢 **Lịch Đặt Phòng (Calendar & Timeline):** Hiển thị dạng sơ đồ Gantt trực quan, tránh đụng ngày.
- 🟢 **Quản lý Yêu Cầu Căn Hộ Dài Hạn (Apartment Inquiries):** Form booking thiết kế riêng cho khách thuê tháng.
- 🔴 **Phân hệ Sơ Đồ Phòng Layout Tòa Nhà (Room Map):** _(Bloat)_ - Đang cải tiến cách hiển thị trực quan kéo/thả. Giao diện đang chưa tận dụng hết sức mạnh.
- 🔴 **Quản Lý Duy tu/Bảo trì Khu Vực Tầng (Floor Maintenance):** Bảng CSDL đã có nhưng Logic giao diện kéo thả sửa điện/nước phòng đang tạm hoãn.
- 🔴 **Trạm Thanh Toán Mở Rộng / Mã QR View (View QRCode):** Phình công thức tạo mã.
- 🔴 **Hệ Thống Hoàn Tiền (Refunds):** _(Bloat)_ - Đang trong giai đoạn mockup UI, logic cổng API Cổng gạch nợ Ngân hàng chưa hoàn thiện, đòi hỏi đối soát kế toán cao.

### 2. Nhóm Sản Phẩm & Chính Sách Giá (Product & Pricing - 6 Tính năng)

- 🟢 **Kho Danh sách Phòng (Rooms):** Quản lý trạng thái trống/bảo trì/đang dọn dẹp cấp tốc.
- 🟢 **Phân loại Hạng Phòng (Room Types):** Quản lý loại giường, diện tích, sức chứa Max/Min, Amenities.
- 🟢 **Quản lý Dịch vụ Phụ & Trọn Gói (Service & Packages Định Vị).**
- 🟢 **Bảng Giá Tiêu Chuẩn (Pricing Baseline):** Chốt giá cứng cho ngày thường và cuối tuần cơ bản.
- 🟢 **Khuyến Mãi & Voucher Đồng Hành (Promotions / Coupons).**
- 🔴 **Giá Chi Tiết (Pricing Detailed / Seasonal):** _(Bloat)_ - Hệ số tính tiền (Multiplier) chạy theo Mùa Cao Điểm, Giờ Chót (Last Minute) đang quá rối công thức nội suy vòng lặp, dễ làm Sai số tài chính.

### 3. Nhóm Tương Tác Cấp Cao & Marketing (CRM & Communications - 8 Tính năng)

- 🟢 **Siêu Trạm Cứu Chữa Tin Nhắn Khách Hàng (Chat Administrator):** Điều phối SSE đa luồng. Chuyển gán cho Cấp dưới, Claim đoạn hội thoại, Box nội bộ Ghi chú màu xám (Tính năng siêu việt nhất hệ thống, No-Reload).
- 🟢 **Cài đặt Tự động hoá Chat (Chatbot Settings):** Cấu hình Online/Offline/Trả lời Mẫu nhanh tự động (Quick Replies).
- 🟢 **Hồ sơ Khách Hàng Vãng Lai (Customers CRM):** Toàn bộ Database chân dung Khách hàng.
- 🟢 **Bộ máy Lên lịch Bài Viết & Tin Tức Mạng (Blog CMS).**
- 🟢 **Trung tâm Phản hồi (Review / Blog Comments).**
- 🟢 **Kênh Ảnh & Banner Chiến Dịch Quảng Cáo.**
- 🟢 **FAQs / Hỏi Đáp Tổng Hợp Trực Tuyến.**
- 🔴 **Điểm thưởng & Thành Viên Vàng/Bạc Khách Quen (Loyalty / Tiers):** _(Bloat)_ - Logic đổi Điểm sang Mã Giảm Giá khi Check-out chưa được bóc tách dứt điểm khỏi Payment Gateway, khiến Backend Database phải Join bảng quá nhiều.

### 4. Nhóm Cốt Lõi Hệ Thống Lõi (Core System / DevOps - 9 Tính năng)

- 🟢 **Người Dùng Nhân Sự Quản Trị \u0026 Quyền Hạn (Users \u0026 Roles):** Quản lý Sale, Lễ tân, Quản trị viên (Phân quyền bảo mật cao).
- 🟢 **Hệ Thông Báo Alert Trung Tâm (Notifications):** API báo chuông khi có khách vừa Đặt một phòng Mới trên Web.
- 🟢 **Tracking Sessions/Log Tình trạng Trực Tuyến Bộ Phận Chat (Staff Heartbeat):** Nhận diện nhân viên túc trực không cần Load.
- 🟢 **Nhật ký Hoạt Động (Audit Logs):** Ghi chú vĩnh viễn Dấu Chân người đã "Xóa", "Tạo" gì trong CSDL, bao gồm cả IP. Tranh cãi bằng cớ.
- 🟢 **Cấu Hình Websie Cốt Lõi (System OverridesSettings):** Header Titles, Logo, Mã Số Thuế.
- 🟢 **Tích Hợp Báo Cáo Kế Toán Chi Tiết Nâng Cao (Reports Analytics Dữ liệu Lọc Tháng/Năm).**
- 🟢 **Dọn Dẹp Database Rác 2 Cấp Độ (System DB Cleanup / Ready-For-Release):** Nâng cấp quét sạch hơn 30 Bảng Logs Chat, Token, Session để mang CSDL mới tinh vào Vận Hành Thực (Production).
- 🟢 **Xuất Lưu Dữ liệu Vật Lý Lên Đĩa (Backup SQL - Raw Dump).**

---

## III. Tại Sao Lại Tồn Tại Tình Trạng "Feature Bloat" (Tính năng bị Quá tải)?

Dự án Aurora Hotel Plaza mang định hướng là một nền tảng lai, tham khảo tiêu chuẩn kép giữa:

1. **OTA (Online Travel Agent):** Tập trung vào khách vãng lai (như hệ thống Booking.com).
2. **PMS (Property Management Software):** Phần mềm lưới nội bộ vận hành KS (Lễ Tân/Buồng phòng).

Sự mở rộng nhanh (Scale-up) trong việc gom tất cả công cụ nội bộ này vào một luồng trong Database 50 Bảng đã khiến:

- **Hệ sinh thái Thành Viên (Loyalty / Tiers)**: Bị cản trở đường đi vì chưa chốt ranh giới cấp điểm (Tính tỷ lệ quy đổi khi khách Review ra sao? Khi Refund thì lấy lại điểm ra sao?).
- **Refund System / Seasonal Pricing**: Chưa đủ Resource Test Case để dò soát thuật toán số âm / số dương / giờ vào. Đang có dấu hiệu (Over-Engineering).
- **Bản Đồ Buồng Phòng Trực Quan (Room Map UI)**: Bị vướng lặp lại tính năng công dụng của Lịch Booking Gantt. Do đó, người dùng (kể cả Sale) bối rối không biết nên dùng tính năng xem đồ hoạ nào.

**🚀 GIẢI PHÁP ĐỊNH HƯỚNG QUẢN TRỊ RỦI RO (Cho Release v3.0):**
Áp dụng phương pháp **Mảnh Ghép Rời Rạc (Decoupling)** bằng cách:

- Ngưng mở rộng code logic cho Tính năng Cổng Chuyển Đổi Fintech / Loyalty, tạm giữ nguyên trạng thái Module.
- **Tập trung sức mạnh bán hàng vào Livechat Admin Chat Station (Realtime), Lịch Timeline Gantt và Form Giao Nhận Booking Glassmorphism** làm 3 vũ khí (Selling-Points) mạnh mẽ nhất chứng minh chất lượng hoàn thiện UI/UX của Aurora Hotel!

> _Báo cáo cam kết phản ánh chi tiết toàn bộ trạng thái kiến trúc phần mềm tính đến phiên bản v2.1.1. Thực hiện: Team Phát triển dự án Aurora._
