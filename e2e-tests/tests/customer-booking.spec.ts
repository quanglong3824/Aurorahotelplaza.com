import { test, expect } from '@playwright/test';

// Biến toàn cục để lưu mã Booking Code dùng chung giữa Khách và Admin
let sharedBookingCode = '';

test.describe('Nghiệp vụ Đặt phòng Khách hàng', () => {

  test('UNHAPPY PATH 1: Cố gắng đặt phòng khi để trống form (Kiểm tra validate)', async ({ page }) => {
    await page.goto('/booking/index.php');

    // Mặc định đang ở Step 1 (Chọn phòng). Cố bấm "Tiếp tục"
    // Nếu chưa chọn phòng/ngày, hệ thống không được cho qua.
    await page.click('button:has-text("Tiếp tục")');
    
    // Kiểm tra xem vẫn còn ở step1 hay không
    const step1 = page.locator('#step1');
    await expect(step1).toHaveClass(/active/);
  });

  test('UNHAPPY PATH 2: Ngày Check-out bé hơn hoặc bằng ngày Check-in', async ({ page }) => {
    await page.goto('/booking/index.php');

    // Điền ngày checkin là mai
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    // Ngày checkout là hôm qua (Vô lý)
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = yesterday.toISOString().split('T')[0];

    await page.fill('#check_in_date', tomorrowStr);
    await page.fill('#check_out_date', yesterdayStr);

    // Cố gắng tiếp tục
    await page.click('button:has-text("Tiếp tục")');

    // Chắc chắn phải có thông báo lỗi (Alert/Toast/Toastr)
    // Hệ thống Aurora thường highlight đỏ hoặc popup Swal
    const errorToast = page.locator('.toast-error, .swal2-error, [data-error]');
    // Chúng ta kì vọng không chuyển sang Step 2
    await expect(page.locator('#step1')).toHaveClass(/active/);
  });

  test('HAPPY PATH: Đặt phòng chuẩn và lấy mã Booking', async ({ page }) => {
    await page.goto('/booking/index.php');

    // -------- STEP 1: Chọn ngày & Phòng --------
    // Điền ngày hợp lệ: 3 ngày tính từ hôm nay
    const checkin = new Date();
    checkin.setDate(checkin.getDate() + 2);
    const checkout = new Date(checkin);
    checkout.setDate(checkout.getDate() + 2);

    await page.fill('#check_in_date', checkin.toISOString().split('T')[0]);
    await page.fill('#check_out_date', checkout.toISOString().split('T')[0]);
    
    // Chọn loại phòng đầu tiên trong danh sách
    const firstRoomRadio = page.locator('input[name="room_type"]').first();
    await firstRoomRadio.check({ force: true });

    // Bấm tiếp tục sang Step 2
    await page.click('button:has-text("Tiếp tục")');
    await expect(page.locator('#step2')).toHaveClass(/active/);

    // -------- STEP 2: Thông tin khách hàng --------
    await page.fill('#full_name', 'Playwright Tester');
    await page.fill('#email', 'tester@playwright.dev');
    await page.fill('#phone', '0987654321');
    
    // Bấm tiếp tục sang Step 3
    await page.click('button:has-text("Tiếp tục")');
    // Ở bước này có thể hệ thống gọi API Validate-booking (Anti spam)
    await page.waitForLoadState('networkidle'); 
    await expect(page.locator('#step3')).toHaveClass(/active/);

    // -------- STEP 3: Xác nhận và Thanh toán --------
    // Check đồng ý điều khoản
    await page.check('#agree_terms', { force: true });
    
    // Nhấn Nút Đặt Phòng Chính Thức (Modal hoặc Submit)
    const submitBtn = page.locator('button[type="submit"], #confirmBookingModalBtn');
    await submitBtn.click();

    // Chờ redirect sang trang xác nhận
    await page.waitForURL(/confirmation\.php\?booking_code=/);

    // Xác nhận đã có text "Thành công" hoặc "Cảm ơn"
    await expect(page.locator('body')).toContainText(/Thành công|Success|Cảm ơn/i);

    // Lấy mã Booking ra để chuyển cho Admin test
    const currentUrl = new URL(page.url());
    sharedBookingCode = currentUrl.searchParams.get('booking_code') || '';
    
    expect(sharedBookingCode).not.toBeNull();
    console.log(`[Khách hàng] Đã đặt thành công. Mã Booking: ${sharedBookingCode}`);
  });
});

export { sharedBookingCode };
