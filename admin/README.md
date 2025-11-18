# Aurora Hotel Plaza - Admin Panel

## Tổng quan

Hệ thống quản trị toàn diện cho Aurora Hotel Plaza, cung cấp các công cụ quản lý đặt phòng, khách hàng, phòng, dịch vụ và báo cáo.

## Cấu trúc thư mục

```
admin/
├── api/                          # API endpoints
│   ├── update-booking-status.php
│   ├── update-room-status.php
│   └── ...
├── assets/
│   └── css/
│       └── admin.css            # Custom admin styles
├── includes/
│   ├── admin-header.php         # Header với sidebar navigation
│   └── admin-footer.php         # Footer với scripts
├── dashboard.php                # Trang chủ admin
├── bookings.php                 # Quản lý đặt phòng
├── booking-detail.php           # Chi tiết đặt phòng
├── room-types.php               # Quản lý loại phòng
├── rooms.php                    # Quản lý phòng
├── customers.php                # Quản lý khách hàng
├── reviews.php                  # Quản lý đánh giá
├── reports.php                  # Báo cáo & thống kê
├── settings.php                 # Cài đặt hệ thống
└── index.php                    # Redirect to dashboard
```

## Tính năng chính

### 1. Dashboard
- **Thống kê tổng quan**: Đặt phòng hôm nay, doanh thu, khách hàng, phòng trống
- **Quick actions**: Tạo đặt phòng mới, xem đơn chờ, báo cáo
- **Check-in/Check-out hôm nay**: Danh sách khách check-in và check-out
- **Đặt phòng gần đây**: 10 đơn đặt phòng mới nhất

### 2. Quản lý Đặt phòng (Bookings)
- **Danh sách đặt phòng**: Với bộ lọc và tìm kiếm
- **Chi tiết đặt phòng**: Thông tin đầy đủ về đơn
- **Quản lý trạng thái**:
  - Xác nhận đơn (pending → confirmed)
  - Check-in (confirmed → checked_in)
  - Check-out (checked_in → checked_out)
  - Hủy đơn (→ cancelled)
- **Phân phòng**: Gán phòng cụ thể cho đơn đặt
- **Lịch sử thay đổi**: Theo dõi tất cả thay đổi trạng thái
- **In và xuất**: In đơn, tải QR code

### 3. Quản lý Phòng (Rooms)

#### Room Types (Loại phòng)
- **Danh sách loại phòng**: Grid view với hình ảnh
- **Thông tin**: Tên, mô tả, giá, sức chứa, diện tích
- **Quản lý**: Thêm, sửa, xóa loại phòng
- **Trạng thái**: Active/Inactive

#### Rooms (Phòng cụ thể)
- **Danh sách phòng**: Với bộ lọc theo loại, tầng, trạng thái
- **Trạng thái phòng**:
  - Available (Trống)
  - Occupied (Đang sử dụng)
  - Maintenance (Bảo trì)
  - Cleaning (Đang dọn)
- **Cập nhật trạng thái**: Modal để thay đổi trạng thái và ghi chú
- **Theo dõi dọn phòng**: Thời gian dọn phòng cuối cùng

### 4. Quản lý Khách hàng (Customers)
- **Danh sách khách hàng**: Với thông tin chi tiết
- **Hạng thành viên**: Hiển thị tier và điểm tích lũy
- **Thống kê**: Tổng đơn, tổng chi tiêu
- **Quản lý trạng thái**: Active, Inactive, Banned
- **Xem lịch sử**: Đơn đặt phòng của khách

### 5. Quản lý Đánh giá (Reviews)
- **Danh sách đánh giá**: Với bộ lọc theo trạng thái và rating
- **Duyệt đánh giá**: Approve/Reject
- **Phản hồi**: Trả lời đánh giá từ khách
- **Chi tiết rating**: Sạch sẽ, dịch vụ, vị trí, giá trị
- **Xóa đánh giá**: Nếu vi phạm

### 6. Báo cáo & Thống kê (Reports)
- **Tổng quan doanh thu**: Tổng, trung bình, tỷ lệ hủy
- **Biểu đồ doanh thu**: Theo ngày
- **Hiệu suất loại phòng**: Doanh thu và số đơn theo loại
- **Top khách hàng**: 10 khách chi tiêu nhiều nhất
- **Phân bố trạng thái**: Biểu đồ trạng thái đơn
- **Phương thức thanh toán**: Thống kê theo phương thức
- **Tỷ lệ lấp đầy**: Occupancy rate
- **Xuất báo cáo**: In hoặc xuất Excel

### 7. Cài đặt (Settings)
- **Cài đặt chung**: Tên, email, phone, địa chỉ
- **Cài đặt đặt phòng**:
  - Đặt trước tối đa
  - Số đêm min/max
  - Chính sách hủy
  - Phí check-in sớm/check-out muộn
- **Cài đặt giá**: Thuế VAT, phí dịch vụ
- **Điểm thưởng**: Tỷ lệ tích điểm, thời hạn
- **Thông báo**: Email, SMS
- **Chế độ bảo trì**: Tắt website tạm thời

## Phân quyền

### Admin
- Toàn quyền truy cập tất cả chức năng
- Quản lý users, settings, reports
- Xem logs và activity

### Sale
- Quản lý bookings
- Xem customers
- Xem reports cơ bản
- Không thể thay đổi settings

### Receptionist (Lễ tân)
- Quản lý bookings
- Check-in/Check-out
- Cập nhật trạng thái phòng
- Xem thông tin khách hàng

## API Endpoints

### Bookings
- `POST /admin/api/update-booking-status.php` - Cập nhật trạng thái đơn
- `GET /admin/api/export-bookings.php` - Xuất Excel

### Rooms
- `POST /admin/api/update-room-status.php` - Cập nhật trạng thái phòng
- `POST /admin/api/delete-room.php` - Xóa phòng
- `POST /admin/api/delete-room-type.php` - Xóa loại phòng

### Customers
- `POST /admin/api/update-customer-status.php` - Khóa/mở khóa tài khoản
- `GET /admin/api/export-customers.php` - Xuất Excel

### Reviews
- `POST /admin/api/update-review-status.php` - Duyệt/từ chối đánh giá
- `POST /admin/api/respond-review.php` - Phản hồi đánh giá
- `POST /admin/api/delete-review.php` - Xóa đánh giá

## Giao diện

### Layout
- **Sidebar navigation**: Cố định bên trái (desktop), toggle (mobile)
- **Top header**: Tiêu đề trang, notifications, theme toggle
- **Responsive**: Hoạt động tốt trên mobile, tablet, desktop
- **Dark mode**: Hỗ trợ chế độ tối

### Components
- **Cards**: Container cho nội dung
- **Tables**: Data table với sort, filter
- **Forms**: Input, select, textarea với validation
- **Modals**: Popup cho actions
- **Badges**: Status indicators
- **Buttons**: Primary, secondary, success, danger, warning
- **Stats cards**: Hiển thị số liệu
- **Charts**: Biểu đồ đơn giản với CSS

### Styling
- **Tailwind CSS**: Utility-first framework
- **Custom CSS**: `/admin/assets/css/admin.css`
- **Icons**: Material Symbols Outlined
- **Colors**: Accent color từ theme chính

## JavaScript Functions

### Global Functions (admin-footer.php)
```javascript
showToast(message, type)        // Hiển thị thông báo
confirmDelete(message)          // Xác nhận xóa
formatCurrency(amount)          // Format tiền VNĐ
formatDate(dateString)          // Format ngày
formatDateTime(dateString)      // Format ngày giờ
```

### Page-specific Functions
- `updateBookingStatus()` - Cập nhật trạng thái đơn
- `updateRoomStatus()` - Cập nhật trạng thái phòng
- `approveReview()` - Duyệt đánh giá
- `respondReview()` - Phản hồi đánh giá

## Database Tables Sử dụng

### Core Tables
- `bookings` - Đơn đặt phòng
- `booking_history` - Lịch sử thay đổi
- `rooms` - Phòng
- `room_types` - Loại phòng
- `users` - Người dùng
- `reviews` - Đánh giá
- `payments` - Thanh toán
- `system_settings` - Cài đặt
- `activity_logs` - Nhật ký hoạt động

## Security

### Authentication
- Session-based authentication
- Role-based access control (RBAC)
- Redirect to login if not authenticated

### Authorization
- Check user role before allowing actions
- Admin-only pages protected
- API endpoints validate permissions

### Data Protection
- Prepared statements (PDO) chống SQL injection
- htmlspecialchars() chống XSS
- CSRF tokens (planned)
- Input validation

## Logging

### Activity Logs
Tất cả actions quan trọng được log vào `activity_logs`:
- User ID
- Action type
- Entity type & ID
- Description
- IP address
- Timestamp

### Error Logs
PHP errors được log vào server error log với `error_log()`

## Tính năng đang phát triển

### Modules chưa hoàn thành
- [ ] Services Management (Quản lý dịch vụ)
- [ ] Service Bookings (Đơn dịch vụ)
- [ ] Promotions (Khuyến mãi)
- [ ] Loyalty Program (Chương trình thành viên)
- [ ] Banners Management
- [ ] Gallery Management
- [ ] FAQs Management
- [ ] Blog Management
- [ ] Calendar View (Lịch đặt phòng)
- [ ] Room Assignment (Phân phòng tự động)

### Tính năng nâng cao
- [ ] Real-time notifications
- [ ] WebSocket cho updates
- [ ] Advanced charts (Chart.js)
- [ ] Bulk actions
- [ ] Export to PDF
- [ ] Email templates editor
- [ ] SMS integration
- [ ] Payment gateway management
- [ ] Multi-language support
- [ ] Advanced permissions

## Hướng dẫn sử dụng

### Đăng nhập
1. Truy cập `/admin/`
2. Đăng nhập với tài khoản có role: admin, sale, hoặc receptionist
3. Tự động redirect đến dashboard

### Quản lý đặt phòng
1. Vào **Bookings** từ sidebar
2. Sử dụng filter để tìm đơn
3. Click vào mã đơn để xem chi tiết
4. Sử dụng buttons để cập nhật trạng thái

### Cập nhật trạng thái phòng
1. Vào **Rooms** từ sidebar
2. Click icon sync trên phòng cần cập nhật
3. Chọn trạng thái mới và ghi chú
4. Submit

### Xem báo cáo
1. Vào **Reports** từ sidebar
2. Chọn khoảng thời gian
3. Click "Xem báo cáo"
4. Có thể in hoặc xuất Excel

### Cài đặt hệ thống
1. Vào **Settings** (chỉ admin)
2. Cập nhật các thông tin cần thiết
3. Click "Lưu cài đặt"

## Troubleshooting

### Lỗi database connection
- Kiểm tra config trong `/config/database.php`
- Đảm bảo MySQL đang chạy
- Kiểm tra credentials

### Không thể cập nhật trạng thái
- Kiểm tra permissions của user
- Xem console log để debug
- Kiểm tra API endpoint có hoạt động

### Layout bị vỡ
- Clear browser cache
- Kiểm tra Tailwind CSS đã load
- Xem console cho errors

## Support

Nếu có vấn đề hoặc câu hỏi, vui lòng liên hệ team phát triển.

---

**Version**: 1.0  
**Last Updated**: November 2025  
**Developer**: Aurora Hotel Development Team
