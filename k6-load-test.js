import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metric để theo dõi tỉ lệ lỗi
export const errorRate = new Rate('errors');

// Cấu hình kịch bản Stress Test (Mô phỏng đợt Flash Sale / Đỉnh điểm du lịch)
export const options = {
    stages: [
        { duration: '30s', target: 20 },  // Giai đoạn 1: Khởi động nhẹ nhàng lên 20 users trong 30s
        { duration: '1m', target: 20 },   // Giai đoạn 2: Giữ vững 20 users trong 1 phút (Bình thường)
        { duration: '30s', target: 100 }, // Giai đoạn 3: Tăng đột biến lên 100 users (Sốc tải - Spike Test)
        { duration: '1m', target: 100 },  // Giai đoạn 4: Giữ vững 100 users trong 1 phút
        { duration: '30s', target: 0 },   // Giai đoạn 5: Giảm dần về 0
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // Yêu cầu nghiêm ngặt: 95% request phải phản hồi dưới 2 giây
        errors: ['rate<0.05'],             // Tỉ lệ lỗi (500, timeout) không được vượt quá 5%
    },
};

// URL mục tiêu trên môi trường thật
const BASE_URL = 'https://aurorahotelplaza.com/2025';

export default function () {
    // 1. Giả lập thiết bị đa dạng (Mobile & Desktop)
    const isMobile = Math.random() > 0.5;
    const userAgent = isMobile 
        ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
        : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    const params = {
        headers: {
            'User-Agent': userAgent,
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding': 'gzip, deflate, br',
            'Connection': 'keep-alive',
        },
    };

    // --- KỊCH BẢN 1: Duyệt các trang công khai (Home, Rooms, Services...) ---
    group('1. Public Browsing Flow', function () {
        const pages = [
            '/', '/index.php', '/rooms.php', '/apartments.php', 
            '/services.php', '/about.php', '/contact.php', 
            '/gallery.php', '/blog.php', '/privacy.php'
        ];

        // Một user trung bình sẽ click xem ngẫu nhiên 3 trang
        for (let i = 0; i < 3; i++) {
            const page = pages[Math.floor(Math.random() * pages.length)];
            const res = http.get(`${BASE_URL}${page}`, params);
            
            const success = check(res, {
                'Trang tải thành công (HTTP 200)': (r) => r.status === 200,
                'Không bị lỗi PHP Fatal': (r) => !r.body.includes('Fatal error') && !r.body.includes('PDOException'),
            });
            errorRate.add(!success);
            sleep(Math.random() * 2 + 1); // Tạm nghỉ 1-3s để đọc nội dung (Think time)
        }
    });

    // --- KỊCH BẢN 2: Vào xem chi tiết sâu (Detail Pages - Tốn DB Query) ---
    group('2. Detail Pages Flow', function () {
        const detailPaths = [
            '/apartment-details/studio-apartment.php',
            '/apartment-details/modern-premium.php',
            '/service-detail.php?slug=aurora-restaurant',
            '/service-detail.php?slug=pool-gym',
            '/blog-detail.php?slug=tin-tuc-khuyen-mai-moi' // Slug ngẫu nhiên
        ];

        const path = detailPaths[Math.floor(Math.random() * detailPaths.length)];
        const res = http.get(`${BASE_URL}${path}`, params);
        
        const success = check(res, {
            'Phản hồi bình thường (Không bị lỗi 500)': (r) => r.status < 500, // Chấp nhận cả 404 nếu slug ko tồn tại, miễn ko sập server
        });
        errorRate.add(!success);
        sleep(1);
    });

    // --- KỊCH BẢN 3: Luồng Đặt phòng (Booking Flow - Xử lý Logic OOP phức tạp) ---
    group('3. Booking Flow', function () {
        // Bước 1: Vào trang đặt phòng
        let res = http.get(`${BASE_URL}/booking/index.php`, params);
        check(res, { 'Trang Booking hiển thị tốt': (r) => r.status === 200 });
        sleep(2); // Dành 2s để "điền form"

        // Bước 2: Gửi API đặt phòng (POST)
        const bookingPayload = {
            check_in: '2026-12-01',
            check_out: '2026-12-05',
            adults: 2,
            children: 0,
            room_type_id: 1, 
            guest_name: 'K6 Stress Tester',
            guest_phone: '0909123456',
            guest_email: 'stress-test@k6.io',
            payment_method: 'cash'
        };

        res = http.post(`${BASE_URL}/booking/api/create_booking.php`, bookingPayload, {
            headers: Object.assign({}, params.headers, {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            })
        });

        const success = check(res, {
            'API Đặt phòng không bị sập (200/400)': (r) => r.status === 200 || r.status === 400 || r.status === 403,
            'API Trả về JSON hợp lệ': (r) => r.headers['Content-Type'] && r.headers['Content-Type'].includes('application/json')
        });
        errorRate.add(!success);
    });

    // --- KỊCH BẢN 4: Tấn công Đăng nhập & Bẻ khóa CSRF ---
    group('4. Authentication Flow (With CSRF Extraction)', function () {
        // Bước 1: Lấy trang đăng nhập
        let res = http.get(`${BASE_URL}/auth/login.php`, params);
        check(res, { 'Trang Login tải thành công': (r) => r.status === 200 });

        // Bước 2: Bóc tách CSRF Token bằng Regex (Giả lập hành vi hacker/user xịn)
        let csrfToken = 'invalid_mock_token';
        const csrfMatch = res.body.match(/name="csrf_token" value="([^"]+)"/);
        if (csrfMatch && csrfMatch.length > 1) {
            csrfToken = csrfMatch[1];
        }

        // Bước 3: Submit POST kèm token
        res = http.post(`${BASE_URL}/auth/login.php`, {
            email: 'hacker_' + Math.floor(Math.random() * 1000) + '@example.com',
            password: 'wrongpassword',
            csrf_token: csrfToken
        }, {
            headers: Object.assign({}, params.headers, {
                'Content-Type': 'application/x-www-form-urlencoded'
            })
        });

        const success = check(res, {
            'Xử lý Login trơn tru (Redirect hoặc Form lỗi, ko sập 500)': (r) => r.status === 200 || r.status === 302,
        });
        errorRate.add(!success);
        sleep(1);
    });

    // --- KỊCH BẢN 5: Tải tài nguyên tĩnh (Static Assets - Đo băng thông) ---
    group('5. Static Assets Loading', function () {
        const assets = [
            '/assets/css/tailwind-output.css',
            '/assets/css/style.css',
            '/assets/js/main.js',
            '/assets/img/src/logo/logo-dark-ui.png'
        ];

        // Tải đồng loạt cùng lúc
        const reqs = assets.map(asset => ({ method: 'GET', url: `${BASE_URL}${asset}`, params: params }));
        const responses = http.batch(reqs);

        responses.forEach(res => {
            const success = check(res, {
                'Tài nguyên tĩnh được tải (200) hoặc cached (304)': (r) => r.status === 200 || r.status === 304,
            });
            errorRate.add(!success);
        });
    });

    sleep(1); // Kết thúc 1 vòng đời user
}
