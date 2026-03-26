# Lộ trình Phát triển Hệ thống AI dựa trên 20 Kịch bản Vận hành - Aurora Hotel Plaza

Dựa trên bộ dữ liệu 20 kịch bản (scenarios) chuyên sâu về vận hành khách sạn, đây là lộ trình phát triển chi tiết để xây dựng hệ thống Trợ lý AI thông minh.

## Giai đoạn 1: Số hóa & Xây dựng Nền tảng (Quy mô 1-5)
1.  **Cấu trúc hóa dữ liệu (Data Structuring):** Chuyển đổi toàn bộ 20 kịch bản từ XLSX sang định dạng JSON/SQL để tích hợp trực tiếp vào Database của hệ thống Chat.
2.  **Xây dựng Prompt Engine theo Bộ phận:** Thiết lập các "System Prompt" riêng biệt cho từng bộ phận (Front Office, Housekeeping, F&B, Revenue) dựa trên mô tả nhiệm vụ.
3.  **Tích hợp Biến ngữ cảnh (Context Variables):** Lập trình để AI có thể nhận diện và xử lý các biến như `loyalty_tier`, `occupancy_level`, `guest_status` để đưa ra câu trả lời cá nhân hóa.
4.  **Hệ thống Phân loại Ý định (Intent Classification):** Sử dụng các "Câu hỏi mẫu" để huấn luyện AI nhận diện nhanh yêu cầu của khách thuộc kịch bản nào (ví dụ: đang than phiền hay muốn upgrade).
5.  **Triển khai Song ngữ (Multi-language Sync):** Đảm bảo AI phản hồi chuẩn xác cả tiếng Anh và tiếng Việt dựa trên các ví dụ thực tế đã có trong dữ liệu.

## Giai đoạn 2: Trình giả lập & Đào tạo Nhân sự (Quy mô 6-10)
6.  **AI Roleplay Simulator:** Xây dựng giao diện cho nhân viên tập sự thực hành xử lý các tình huống "Khó" (Hard examples) với AI đóng vai khách hàng.
7.  **Hệ thống Chấm điểm Tự động:** AI đánh giá phản hồi của nhân viên dựa trên tiêu chuẩn khách sạn và kịch bản mẫu, giúp giảm thời gian đào tạo thủ công.
8.  **Số hóa Quy trình vận hành tiêu chuẩn (SOP):** Liên kết kịch bản với các tệp hướng dẫn nghiệp vụ (SOP) để AI có thể trích dẫn nguồn khi nhân viên tra cứu.
9.  **Trợ lý Hiện trường (Real-time Staff Support):** Tích hợp AI vào ứng dụng nội bộ để nhân viên có thể hỏi nhanh các tình huống phát sinh và nhận gợi ý xử lý ngay lập tức.
10. **Xử lý Sự cố & Quy trình An toàn:** Ưu tiên module "Staff Incident" để AI hướng dẫn nhân viên các bước sơ cứu hoặc báo cáo sự cố khẩn cấp theo đúng protocol.

## Giai đoạn 3: Tối ưu hóa Doanh thu & Vận hành (Quy mô 11-15)
11. **Revenue Negotiation Bot:** Phát triển logic cho AI hỗ trợ nhân viên kinh doanh thương lượng giá (Rate Negotiation) dựa trên công suất phòng và giá đối thủ.
12. **Phối hợp liên bộ phận tự động (Cross-Dept Automation):** Thiết lập AI làm "trung tâm điều phối", tự động bắn thông báo giữa FO, HK và F&B khi có yêu cầu đặc biệt từ khách.
13. **Quản lý Thực đơn & Dị ứng (Dietary Bot):** AI hỗ trợ nhân viên F&B kiểm tra nhanh các thành phần dị ứng trong menu để tư vấn an toàn cho khách.
14. **Tối ưu hóa Lịch vệ sinh (HK Prioritization):** Sử dụng logic ưu tiên từ dữ liệu để AI sắp xếp thứ tự dọn phòng dựa trên tình trạng phòng và giờ khách đến.
15. **Xử lý Hủy phòng & Đền bù:** AI hỗ trợ tính toán mức đền bù hoặc phí hủy phòng cho các trường hợp đặc biệt để đảm bảo tính công bằng và doanh thu.

## Giai đoạn 4: Cá nhân hóa & Trải nghiệm khách hàng (Quy mô 16-20)
16. **Concierge Thông minh (Local Expert):** Mở rộng kịch bản "Local Recommendations" bằng cách kết nối AI với dữ liệu du lịch thực tế tại khu vực Biên Hòa.
17. **Dự báo nhu cầu khách VIP:** Dựa trên kịch bản "Special Room Setup", AI gợi ý các setup phòng dựa trên lịch sử sở thích của khách thường xuyên.
18. **Hệ thống xử lý Overbooking:** Xây dựng kịch bản tự động đề xuất phương án "Walk guest" (chuyển khách sang KS đối tác) kèm gói đền bù chuẩn hóa.
19. **Phân tích phản hồi & Cải thiện dữ liệu:** AI tự động thống kê các tình huống thực tế phát sinh ngoài 20 kịch bản này để bổ sung vào bộ dữ liệu huấn luyện.
20. **Dashboard Giám sát Sức khỏe Vận hành:** Hiển thị các chỉ số CSAT (mức độ hài lòng) dựa trên hiệu quả xử lý các tình huống mẫu trong thực tế.
