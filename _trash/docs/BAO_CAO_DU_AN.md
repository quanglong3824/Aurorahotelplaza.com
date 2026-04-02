# BÁO CÁO TỔNG QUAN TÌNH HÌNH DỰ ÁN AURORA HOTEL PLAZA

**Kính gửi:** Ban Giám đốc / Cấp trên  
**Dự án:** Trình quản lý Đặt phòng và Chăm sóc Khách hàng Tự động - Aurora Hotel Plaza  

---

## 1. SƠ LƯỢC CÁC TÍNH NĂNG CHÍNH CỦA HỆ THỐNG
Hệ thống được phát triển dưới dạng nền tảng đa điểm chạm, bao gồm các chức năng cốt lõi phục vụ khách hàng và vận hành nội bộ.

### 1.1 Tính năng Dành cho Khách (Frontend)
- **Truy vấn & Trình bày thông tin:** Khách hàng có thể dễ dàng xem chi tiết danh sách phòng (Rooms & Apartments), sơ đồ phòng, hình ảnh (Gallery) và dịch vụ đi kèm (Services).
- **Quy trình Đặt phòng (Booking System):** Cho phép đặt phòng, tích hợp chính sách hủy (Cancellation Policy) và hệ thống thanh toán (Payment).
- **Hệ thống Tài khoản (Profile):** Khách hàng có thể đăng nhập, xem lịch sử đặt phòng trực quan, hỗ trợ quản lý hồ sơ cá nhân hoàn thiện.
- **Trợ lý Ảo AI (Aurora AI):** Tích hợp ngay trên website để hỗ trợ tự động trả lời câu hỏi, kiểm tra tình trạng phòng trống và ghi nhận yêu cầu liên hệ trực tiếp từ khách. Khách có thể tra cứu nhanh mã booking của mình.

### 1.2 Chức năng Quản lý Phía Sau (Admin / Backend)
- **Quản lý Đặt phòng & Dịch vụ:** Xem và xử lý các đơn đặt phòng, theo dõi trạng thái thanh toán và check-in/check-out.
- **Quản lý Khách hàng & Phản hồi:** Quản lý data khách, xử lý các form liên hệ (Contact) và khiếu nại.
- **Quản lý AI & API:** Quản lý các keys kết nối AI (qua module `api_key_manager.php`), theo dõi lịch sử chat của khách với bot (qua bảng `chat_messages` và logs). Cấu hình hệ thống linh hoạt không cần can thiệp code.

---

## 2. NHỮNG ĐIỂM ĐÃ PHÁT TRIỂN & CẢI TIẾN THÀNH CÔNG
- **Kiến trúc dữ liệu rõ ràng:** Tách bạch Models, Helpers, Views giúp dễ bảo trì.
- **Tối ưu Trải nghiệm (UI/UX):** Đã kết hợp giao diện hiện đại (Tailwind/CSS thuần tối giản), hỗ trợ hiển thị đẹp mắt và thân thiện trên nhiều thiết bị kể cả Dark Mode (chế độ tối sang trọng).
- **Tích hợp Tự động hóa qua SQL:** AI có khả năng truy xuất trực tiếp vào cơ sở dữ liệu (`[TOOL_SQL]`) để lấy thông tin thực tế trả lời cho khách (số phòng trống, tình trạng booking, giá cả) thay vì chỉ trả lời rập khuôn.

---

## 3. TÌNH TRẠNG HIỆN TẠI, SAI SÓT & HẠN CHẾ (CẦN LƯU Ý)
Trong quá trình vận hành trực tiếp, hệ thống đã bắt đầu phát hiện một số điểm thắt cổ chai về mặt kỹ thuật, đặc biệt là ở hạng mục **Trí tuệ Nhân tạo (AI)**:

1. **Sai sót trong xử lý ngữ nghĩa và Lỗi hệ thống:** Hệ thống thi thoảng gặp tình trạng lỗi cú pháp nội bộ (500 errors) ở các trang do xung đột mã nguồn và lỗi mạng gián đoạn khi bắt API kết nối.
2. **Khả năng nhận diện của Trợ lý AI còn rất hạn chế:**
   - **Độ chính xác hiện nay của AI mới đạt khoảng 30%.** Ở nhiều trường hợp ngữ cảnh phức tạp, tư vấn sai dịch vụ hoặc không phản hồi đúng yêu cầu tra cứu của khách hàng.
   - **Nguyên nhân cốt lõi:** Hiện tại do giới hạn tài nguyên và để duy trì hệ thống chạy mượt, đội ngũ kỹ thuật đang **bắt buộc phải sử dụng nguồn API mô hình AI của Trung Quốc (Qwen V1)**. Mô hình này tuy miễn phí hoặc giá rất rẻ nhưng khả năng suy luận logic nghiệp vụ khách sạn cao cấp, hiểu ý định tiếng Việt và tự động xuất câu lệnh SQL thường xuyên bị sai lệch. 
   - Quá trình kết nối với server nội địa Trung Quốc nhiều khi có độ trễ cao, ảnh hưởng trải nghiệm chat của khách hàng VIP.

### Đề Xuất Khắc Phục:
Để khách sạn thực sự nâng tầm trải nghiệm đạt chuẩn "5 Sao" theo định hướng của Ban Giám đốc, giúp giảm tải công việc cho bộ phận Lễ tân và chốt sale tự động hiệu quả, bộ phận Kỹ thuật **rất mong muốn được Ban giám đốc xem xét, phê duyệt bổ sung quỹ ngân sách/kinh phí công nghệ**. 
Nguồn kinh phí này để:
- Chuyển đổi từ mô hình AI giá rẻ hiện tại sang các mô hình AI tiên tiến, trả phí, có tư duy vượt trội hơn (ví dụ: cấp quyền sử dụng các bản Enterprise của OpenAI GPT-4o hoặc Google Gemini Advanced siêu cấp). 
- Huấn luyện nội bộ chuyên sâu giúp đưa tỷ lệ chính xác từ 30% lên hơn 90% trong thời gian tới.

---
*Báo cáo được trích xuất tự động từ phiên tra cứu đánh giá cấu trúc hệ thống lúc này.*
