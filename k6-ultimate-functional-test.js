import http from 'k6/http';
import { check, group, sleep } from 'k6';

// Cấu hình kịch bản: Chạy 1 User ảo (VU) nhưng chạy rất lâu để đi hết mọi ngóc ngách
export const options = {
    vus: 1,
    iterations: 1, // Chỉ chạy 1 vòng lặp hoàn chỉnh
    thresholds: {
        // Đảm bảo không có bất kỳ link nào bị chết hoặc API nào validate sai
        'checks': ['rate==1.0'], 
    },
};

const BASE_URL = 'https://aurorahotelplaza.com/2025';

// Helper: Lấy CSRF Token
function getCsrfToken(url) {
    let res = http.get(url);
    let match = res.body.match(/name="csrf_token" value="([^"]+)"/);
    return match ? match[1] : null;
}

// Helper: Tính ngày tương lai
function addDays(dateStr, days) {
    let d = new Date(dateStr);
    d.setDate(d.getDate() + days);
    return d.toISOString().split('T')[0];
}

export default function () {
    const today = new Date().toISOString().split('T')[0];

    // =====================================================================
    // MODULE 1: THE CRAWLER (QUÉT 100% ĐƯỜNG DẪN)
    // =====================================================================
    group('1. Web Crawler - Quét 100% Links', function () {
        console.log('Bắt đầu cào dữ liệu từ trang chủ...');
        let res = http.get(`${BASE_URL}/index.php`);
        check(res, { 'Trang chủ tải thành công': (r) => r.status === 200 });

        // Cào tất cả các thẻ <a> chứa href
        let linkRegex = /<a\s+(?:[^>]*?\s+)?href=(["'])(.*?)\1/g;
        let match;
        let links = new Set(); // Dùng Set để lọc trùng lặp

        while ((match = linkRegex.exec(res.body)) !== null) {
            let url = match[2];
            // Bỏ qua các link ngoài, email, đt, hoặc neo (#)
            if (!url.startsWith('http') && !url.startsWith('mailto:') && !url.startsWith('tel:') && !url.startsWith('#') && !url.includes('.css') && !url.includes('.js')) {
                // Chuẩn hóa URL tương đối
                if (url.startsWith('./')) url = url.substring(2);
                if (url.startsWith('/')) url = url.substring(1);
                links.add(url);
            }
        }

        console.log(`Tìm thấy ${links.size} đường dẫn nội bộ. Bắt đầu kiểm tra từng link...`);

        // Truy cập từng link một
        links.forEach(link => {
            let fullUrl = `${BASE_URL}/${link}`;
            let linkRes = http.get(fullUrl);
            
            // Chấp nhận 200 (OK), 301/302 (Redirect). Không chấp nhận 404, 500.
            let success = check(linkRes, {
                [`Link hoạt động [${link}]`]: (r) => r.status < 400,
                [`Không lỗi PHP [${link}]`]: (r) => !r.body.includes('Fatal error') && !r.body.includes('PDOException')
            });

            if (!success) {
                console.error(`🚨 PHÁT HIỆN LỖI TẠI LINK: ${fullUrl} (Status: ${linkRes.status})`);
            }
        });
        sleep(1);
    });

    // =====================================================================
    // MODULE 2: DEEP DATA VALIDATION (BẪY DỮ LIỆU ĐẶT PHÒNG)
    // =====================================================================
    group('2. Data Validation - Ép lỗi Logic', function () {
        const bookingApi = `${BASE_URL}/booking/api/create_booking.php`;
        const token = getCsrfToken(`${BASE_URL}/booking/index.php`);
        const headers = { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' };

        // Test 2.1: Lỗi "7759 Ngày" (Đặt phòng quá 30 ngày)
        let payloadExtremeDays = {
            room_type_id: '1',
            check_in: today,
            check_out: addDays(today, 7759), // +21 năm
            adults: '2',
            guest_name: 'Extreme Tester',
            guest_phone: '0909123456',
            csrf_token: token
        };
        let resExtreme = http.post(bookingApi, payloadExtremeDays, { headers });
        check(resExtreme, {
            'Chặn đặt phòng 7759 ngày (Xử lý hợp lệ)': (r) => {
                // Hệ thống tốt phải trả về 400 Bad Request, hoặc 200 nhưng json.success = false
                try {
                    let json = r.json();
                    return json.success === false || r.status === 400;
                } catch(e) { return false; }
            }
        });

        // Test 2.2: Check-out trước Check-in (Xuyên không)
        let payloadTimeTravel = {
            room_type_id: '1',
            check_in: addDays(today, 5),
            check_out: today, 
            adults: '2',
            guest_name: 'Time Traveler',
            guest_phone: '0909123456',
            csrf_token: token
        };
        let resTimeTravel = http.post(bookingApi, payloadTimeTravel, { headers });
        check(resTimeTravel, {
            'Chặn Check-out trước Check-in': (r) => {
                try {
                    return r.json().success === false || r.status === 400;
                } catch(e) { return false; }
            }
        });

        // Test 2.3: Số lượng người âm hoặc phi lý
        let payloadGhosts = {
            room_type_id: '1',
            check_in: today,
            check_out: addDays(today, 1),
            adults: '-5', // Số âm
            children: '100', // Phi lý
            guest_name: 'Ghost Tester',
            guest_phone: '0909123456',
            csrf_token: token
        };
        let resGhosts = http.post(bookingApi, payloadGhosts, { headers });
        check(resGhosts, {
            'Chặn số lượng người phi lý': (r) => {
                try { return r.json().success === false || r.status === 400; } catch(e) { return false; }
            }
        });
        sleep(1);
    });

    // =====================================================================
    // MODULE 3: FUNCTIONAL LINKAGE (LIÊN KẾT TÍNH NĂNG)
    // =====================================================================
    group('3. Functional Linkage - Đặt phòng & Theo dõi', function () {
        const bookingApi = `${BASE_URL}/booking/api/create_booking.php`;
        const trackApi = `${BASE_URL}/booking/api/track.php`;
        const token = getCsrfToken(`${BASE_URL}/booking/index.php`);
        const headers = { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' };

        // Bước 1: Tạo một booking hoàn toàn hợp lệ
        let validPayload = {
            room_type_id: '1',
            check_in: addDays(today, 10),
            check_out: addDays(today, 12), // 2 đêm
            adults: '2',
            children: '0',
            stay_type: 'standard',
            guest_name: 'Linkage Tester',
            guest_phone: '0988777666',
            guest_email: 'linkage@aurorahotelplaza.com',
            payment_method: 'cash',
            csrf_token: token
        };

        console.log('Tạo Booking hợp lệ để test liên kết...');
        let resBooking = http.post(bookingApi, validPayload, { headers });
        let bookingCode = '';

        check(resBooking, {
            'Tạo Booking hợp lệ thành công': (r) => {
                try {
                    let json = r.json();
                    if (json.success && json.booking_code) {
                        bookingCode = json.booking_code; // Trích xuất mã để test bước sau
                        return true;
                    }
                    return false;
                } catch(e) { return false; }
            }
        });

        sleep(2); // Đợi DB ghi nhận

        // Bước 2: Dùng mã Booking Code vừa sinh ra để Tracking
        if (bookingCode) {
            console.log(`Đang Track Booking Code vừa tạo: ${bookingCode}`);
            let resTrack = http.post(trackApi, JSON.stringify({
                query: bookingCode,
                mode: 'latest'
            }), { headers: { 'Content-Type': 'application/json' } });

            check(resTrack, {
                'Tính năng Tracking tìm thấy đúng mã Booking': (r) => {
                    try {
                        let json = r.json();
                        // Phải thành công và danh sách trả về phải chứa bookingCode
                        return json.success === true && 
                               json.bookings.length > 0 && 
                               json.bookings[0].booking_code === bookingCode;
                    } catch(e) { return false; }
                }
            });
        } else {
            console.error('Không lấy được Booking Code từ bước 1 để test Tracking!');
        }
    });
}
