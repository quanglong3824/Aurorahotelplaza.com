import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// --- CUSTOM METRICS ---
export const errorRate = new Rate('errors');
export const bookingLatency = new Trend('latency_booking_api');
export const authLatency = new Trend('latency_auth_api');

// --- CONFIGURATION ---
export const options = {
    scenarios: {
        // 1. Duyệt nội dung (Tải nhẹ, số lượng lớn)
        content_browsers: {
            executor: 'constant-vus',
            vus: 10,
            duration: '3m',
            exec: 'browsingFlow',
        },
        // 2. Đặt phòng dồn dập (Tải nặng, quan trọng nhất)
        heavy_bookers: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 20 },
                { duration: '2m', target: 50 },
                { duration: '1m', target: 0 },
            ],
            exec: 'bookingFlow',
        },
        // 3. Quản trị viên kiểm tra hệ thống
        admin_activity: {
            executor: 'constant-vus',
            vus: 5,
            duration: '3m',
            exec: 'adminFlow',
        },
        // 4. Các form phụ (Liên hệ, Comment, Newsletter)
        form_fillers: {
            executor: 'shared-iterations',
            vus: 5,
            iterations: 50,
            exec: 'miscellaneousForms',
        }
    },
    thresholds: {
        'errors': ['rate<0.01'], // Chấp nhận tối đa 1% lỗi
        'http_req_duration': ['p(95)<1500'], // 95% request dưới 1.5s
        'latency_booking_api': ['p(99)<3000'], // API đặt phòng ko được quá 3s ngay cả khi tải nặng
    },
};

const BASE_URL = 'https://aurorahotelplaza.com/2025';

// Helper để lấy CSRF Token từ một trang bất kỳ
function getCsrfToken(url) {
    let res = http.get(url);
    let match = res.body.match(/name="csrf_token" value="([^"]+)"/);
    return match ? match[1] : null;
}

// --- 1. LUỒNG DUYỆT NỘI DUNG ---
export function browsingFlow() {
    group('Duyệt Gallery & Blog', function () {
        http.get(`${BASE_URL}/gallery.php`);
        http.get(`${BASE_URL}/blog.php`);
        // Xem ngẫu nhiên 1 bài blog
        http.get(`${BASE_URL}/blog-detail.php?slug=uu-dai-dat-phong-mua-he-2024`);
        sleep(2);
    });

    group('Xem chi tiết Phòng/Căn hộ', function () {
        const rooms = [
            '/room-details/deluxe-king.php',
            '/room-details/premium-suite.php',
            '/apartment-details/modern-studio.php',
            '/apartment-details/classical-family.php'
        ];
        http.get(`${BASE_URL}${rooms[Math.floor(Math.random() * rooms.length)]}`);
        sleep(3);
    });
}

// --- 2. LUỒNG ĐẶT PHÒNG (CORE LOGIC) ---
export function bookingFlow() {
    group('Đặt phòng Full Case', function () {
        // Bước 1: Kiểm tra giá (Search)
        let res = http.get(`${BASE_URL}/booking/index.php`);
        const token = getCsrfToken(`${BASE_URL}/booking/index.php`);

        // Bước 2: Gửi yêu cầu đặt phòng (POST)
        const payload = {
            room_type_id: '1',
            check_in: '2026-05-20',
            check_out: '2026-05-25',
            adults: '2',
            children: '1',
            extra_beds: '1',
            stay_type: 'standard',
            guest_name: 'Tester ' + __VU,
            guest_phone: '0909' + Math.floor(Math.random() * 1000000),
            guest_email: 'test' + __VU + '@example.com',
            csrf_token: token,
            payment_method: 'cash'
        };

        const startTime = Date.now();
        res = http.post(`${BASE_URL}/booking/api/create_booking.php`, payload);
        bookingLatency.add(Date.now() - startTime);

        check(res, {
            'Booking Success': (r) => r.status === 200 && r.json().success === true,
        }) || errorRate.add(1);

        sleep(5);
    });
}

// --- 3. LUỒNG QUẢN TRỊ (STRESS TEST CƠ SỞ DỮ LIỆU) ---
export function adminFlow() {
    group('Admin Dashboard & Reports', function () {
        // Mô phỏng Admin check Dashboard liên tục
        http.get(`${BASE_URL}/admin/index.php`);
        http.get(`${BASE_URL}/admin/bookings.php`);
        http.get(`${BASE_URL}/admin/reports.php`);
        
        // Check AI Stats (Nặng)
        http.get(`${BASE_URL}/admin/ai-stats.php`);
        sleep(4);
    });
}

// --- 4. CÁC FORM PHỤ (TRƯỜNG HỢP ÍT ĐIỂM NÓNG) ---
export function miscellaneousForms() {
    group('Form Liên hệ', function () {
        const token = getCsrfToken(`${BASE_URL}/contact.php`);
        let res = http.post(`${BASE_URL}/api/contact.php`, {
            name: 'Customer Service Test',
            email: 'feedback@test.com',
            subject: 'Inquiry',
            message: 'Testing high density form submission',
            csrf_token: token
        });
        check(res, { 'Contact Sent': (r) => r.status === 200 });
    });

    group('Gửi bình luận Blog', function () {
        // Chỉ gửi nếu đã có token (giả lập user đăng nhập)
        const token = getCsrfToken(`${BASE_URL}/blog-detail.php?slug=uu-dai-dat-phong-mua-he-2024`);
        let res = http.post(`${BASE_URL}/api/blog-interaction.php`, {
            action: 'comment',
            post_id: '1',
            content: 'Tuyệt vời! Tôi sẽ ghé thăm sớm.',
            csrf_token: token
        });
        check(res, { 'Comment Handled': (r) => r.status < 500 });
    });

    group('Theo dõi đơn hàng (Track Booking)', function () {
        let res = http.post(`${BASE_URL}/booking/api/track.php`, JSON.stringify({
            query: 'AURORA' + Math.floor(Math.random() * 1000),
            mode: 'latest'
        }), { headers: { 'Content-Type': 'application/json' } });
        check(res, { 'Tracking API responds': (r) => r.status === 200 });
    });
}

// --- LUỒNG AUTH & SECURITY (XÁC THỰC) ---
export function authFlow() {
    group('Đăng ký & Đăng nhập', function () {
        const token = getCsrfToken(`${BASE_URL}/auth/register.php`);
        
        // Thử đăng ký user trùng (Kiểm tra xử lý ngoại lệ)
        let res = http.post(`${BASE_URL}/auth/register.php`, {
            full_name: 'Existing User',
            email: 'admin@aurorahotelplaza.com',
            password: 'Password123!',
            csrf_token: token
        });
        
        check(res, {
            'Auth system stable': (r) => r.status < 500
        });
    });
}
