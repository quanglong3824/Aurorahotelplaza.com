# Kịch Bản Kiểm Thử AI Chốt Đơn (Sales Titan 7.0)

Dưới đây là các kịch bản "thực chiến" để sếp copy-paste vào khung chat nhằm kiểm tra độ thông minh và khả năng chốt đơn của AI.

---

## 🏎️ Case 1: Chốt đơn Thần tốc (The Flash)
*Mục tiêu: Kiểm tra xem AI có hiện [BOOKING_CARD] ngay lập tức khi nhận đủ thông tin không.*

- **Bước 1:** "Đặt giúp mình 1 phòng Modern tối nay đến sáng thứ 2 nhé, mình là Quang Long, sđt 0969875278, mail longdev.08@gmail.com"
- **Kỳ vọng:** AI tính toán ngày (tối nay là 2/5, thứ 2 là 4/5), nhận diện "Modern" là Modern Studio và **HIỆN NGAY THẺ XÁC NHẬN** kèm tổng tiền 2 đêm.

---

## 💰 Case 2: Khách hàng "Mặc cả" (The Haggler)
*Mục tiêu: Kiểm tra kỹ năng xử lý từ chối và up-sell.*

- **Bước 1:** "Chào em, mình cần phòng cho 2 người vào cuối tuần tới."
- **Bước 2 (Sau khi AI tư vấn):** "Phòng Aurora Studio đắt quá, có mã giảm giá hay phòng nào rẻ hơn không em? Anh chỉ muốn tầm 1.8tr thôi."
- **Bước 3:** "Ok lấy anh phòng Indochine đi. Anh tên Hùng, 0988888888, hung@test.com, ở từ 9/5 đến 11/5."
- **Kỳ vọng:** AI phải chuyển hướng sang phòng rẻ hơn (Indochine) và chốt đơn ngay khi nhận thông tin.

---

## 👨‍👩‍👧‍👦 Case 3: Khách hàng Gia đình (The Family)
*Mục tiêu: Kiểm tra kiến thức về loại phòng và chính sách trẻ em.*

- **Bước 1:** "Nhà mình có 2 vợ chồng và 2 bé (5 tuổi và 10 tuổi), em tư vấn phòng nào rộng rãi và cho anh biết chính sách trẻ em nhé."
- **Bước 2:** "Ok đặt cho anh phòng Family đó luôn. Anh là Minh, 0905123456, minh@family.vn, ở đêm 15/5."
- **Kỳ vọng:** AI gợi ý đúng hạng phòng Family, giải thích chính sách trẻ em và hiện thẻ xác nhận cho 1 đêm.

---

## 👻 Case 4: Khách hàng "Treo" - Thu thập Lead (The Ghost)
*Mục tiêu: Kiểm tra tính năng AI Learning (EXTRACT_LEAD).*

- **Bước 1:** "Anh tên Tuấn, đang tìm phòng trăng mật lãng mạn chút."
- **Bước 2 (Sau khi AI tư vấn):** "Để anh hỏi lại vợ đã nhé, sđt anh là 0977666555, có gì em gọi tư vấn cho anh."
- **Bước 3:** *Thoát chat hoặc không nhắn nữa.*
- **Kỳ vọng:** AI phải lẳng lặng xuất thẻ `[EXTRACT_LEAD]` với thông tin: Tên Tuấn, SĐT, sở thích trăng mật. Sếp vào Admin mục **"Khách hàng tiềm năng"** để kiểm tra kết quả.

---

## 🌍 Case 5: Khách quốc tế (The International)
*Mục tiêu: Kiểm tra đa ngôn ngữ và tính chuyên nghiệp.*

- **Bước 1:** "Hello, I'm looking for a room for my business trip tomorrow."
- **Bước 2:** "I like the Modern Premium. My name is Alex, +123456789, alex@business.com. I'll stay for 3 nights."
- **Kỳ vọng:** AI trả lời hoàn toàn bằng tiếng Anh chuyên nghiệp và hiện thẻ chốt đơn chuẩn format.

---

## ⚠️ Lưu ý khi Test:
1. **F5 Trang:** Mỗi khi bắt đầu một Case mới, sếp nên F5 hoặc xóa lịch sử để AI bắt đầu một ngữ cảnh sạch hoàn toàn.
2. **Nút Xác nhận:** Sau khi AI hiện thẻ, sếp bấm nút **"XÁC NHẬN & ĐẶT NGAY"** để kiểm tra xem Đơn hàng có bay về Admin và Email không.
3. **Múi giờ:** AI đã được cài múi giờ VN, sếp có thể dùng các từ như "tối nay", "sáng mai" thoải mái.
