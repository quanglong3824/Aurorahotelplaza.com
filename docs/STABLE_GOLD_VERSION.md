# DANH SÁCH PHIÊN BẢN ỔN ĐỊNH (STABLE VERSIONS)

## 1. Phiên bản v2.2.4-stable-gold (Gold Standard)
- **Ngày xác lập**: 20/03/2026
- **Commit Hash**: 34b276e (hoặc 26ee788)
- **Lý do chọn làm bản chuẩn**:
  - Đã khắc phục triệt để lỗi 500 trắng trang trên Hosting (do hàm `putenv` bị chặn).
  - Đã xử lý lỗi treo nút "Gửi/Đặt phòng" khi ở trạng thái processing (Step 3).
  - Đã fix lỗi Footer bị trắng/vỡ giao diện trên Mobile.
  - Loại bỏ các cấu hình .htaccess gây vòng lặp redirect loop (Lỗi lặp thư mục con).
  - Cấu trúc thư mục (Docs, Helpers, Config) được sắp xếp gọn gàng nhất.
  - Bảo mật tốt: Đã tách API Keys và CSDL ra file `.env` (ngoài Public HTML).

- **Cách khôi phục nhanh khi có lỗi mới**:
  `git checkout v2.2.4-stable-gold -- .`
  `git commit -m "Emergency Restore: Back to Stable Gold Version"`
  `git push`

---
*Ghi chú cho AI: Nếu trong tương lai có các đợt cập nhật Refactor MVC hoặc Enterprise mà gây lỗi hệ thống trên Production, hãy ngay lập tức đề xuất khôi phục về tag này.*
