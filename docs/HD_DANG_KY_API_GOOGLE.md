# HƯỚNG DẪN ĐĂNG KÝ VÀ THIẾT LẬP GOOGLE GEMINI API (BẢN TRẢ PHÍ - PAY AS YOU GO)

Để loại bỏ giới hạn 20 request/ngày (Lỗi 429 Quota Exceeded) do dùng gói miễn phí, bạn cần thêm thẻ thanh toán vào Google Cloud. Trình tự này rất nhanh và an toàn (Google Cloud là nền tảng đáng tin cậy top 1 thế giới).

## BƯỚC 1: TRUY CẬP TRANG QUẢN LÝ THANH TOÁN AI STUDIO
1. Mở trình duyệt ẩn danh (để tránh lỗi nhiều tài khoản Google).
2. Đăng nhập vào tài khoản Gmail bạn muốn dùng làm Quản lý cho Khách sạn Aurora.
3. Truy cập vào đường link: **[Google AI Studio - Billing](https://aistudio.google.com/app/billing)**

## BƯỚC 2: THIẾT LẬP TÀI KHOẢN THANH TOÁN (BILLING ACCOUNT)
1. Tại trang Billing, hệ thống Google AI Studio sẽ yêu cầu bạn liên kết với một "Dự án Google Cloud" (Google Cloud Project) có bật Billing.
2. Bạn ấn vào nút **Set up billing** (Thiết lập thanh toán).
3. Hệ thống sẽ chuyển hướng bạn sang trang **Google Cloud Platform (GCP)**. 
4. Tại đây, làm theo các bước:
   - Quốc gia: Chọn **Vietnam**.
   - Điền thông tin Doanh nghiệp/Cá nhân (Tên, địa chỉ, mã bưu điện 700000 nếu ở HCM).
   - Nhập thông tin **Thẻ Visa hoặc MasterCard**.
   *Lưu ý: Google sẽ trừ thử khoảng 1 USD (25.000 VNĐ) để xác minh thẻ và sẽ lập tức **hoàn trả lại** ngay sau 5 phút.*

## BƯỚC 3: BẬT THANH TOÁN CHO DỰ ÁN AI CỦA BẠN
1. Sau khi Add thẻ thành công, bạn quay lại màn hình **[Google AI Studio - API Keys](https://aistudio.google.com/app/apikey)**.
2. Bạn bấm nút **Create API Key**.
3. Khung Popup hiện ra, nó sẽ hỏi bạn muốn tạo Key ở Project nào. Vui lòng chọn cái Project mà bạn vừa mới add thẻ (hoặc cứ tạo New Project ở tài khoản có Billing đó).
4. 🎉 Chọn **Create API key in existing project** -> Xong! Key mã mới xuất hiện bắt đầu bằng chữ `AIzaSy...`

*(Khi API Key nằm trong một Project đã nối Thẻ Billing, thì nó đã hóa thành bản Pay-As-You-Go. Lỗi 429 sẽ vĩnh viễn biến mất).*

## BƯỚC 4: 🛠 BẢO VỆ CHI PHÍ (QUAN TRỌNG NHẤT KHÔNG ĐƯỢC QUÊN)
Google cung cấp chức năng **Budget & Alerts** đ
## BƯỚC 3: BẬT THANH TOÁN CHêu, tránh rủi ro (VD set tối đa $5/tháng để nếu bị spam bot, Google sẽ tự động dừng).

1. Truy cập trang: [Google Cloud Billing Budgets](https://conso2. Bạn bấm nút **Create API Key**.
3. Khung Popup hiện ra, nó sẽ hỏi bạn muốn tạo Key ở Projuy AI Khach San Aurora`.
43. Khung Popup hiện ra, n?mục tiêu4. 🎉 Chọn **Create API key in existing projeKéo xuống phần Actions -> Bạn tick chọn gửi Email cảnh báo khi chạm 50%, 90% và 100% dung lượng.
*(Tuyệt vời! Bây giờ hệ thống Cảnh báo tự động đã được lập, khi tiêu tới 2.5$ là Mail điện thoại của bạn sẽ reng reng báo ngay).*

## BƯỚC 5: ĐƯA API KEY VỀ WEBSITE VÀ HƯỞNG THỤ
1. Copy API Key từ Bước 3.
2. Đăng nhập vào Host CPanel của trang web `aurorahotelplaza.com`.
3. Mở File Manager, tìm đến thư mục `config/api_keys.php` (như đã nói ở Bước trước, bạn tạo file này lên Host từ file mẫu).
4. Dán cái API Key vào: `define('GEMINI_API_KEY', 'API_KEY_MỚI_CỦA_BẠN');`
5. Lưu lại. F5 Web -> Chat xả láng với Aurora AI 24/7.
