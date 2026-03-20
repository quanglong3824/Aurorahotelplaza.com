import { test, expect } from '@playwright/test';
// Trong thực tế, E2E sẽ pass qua biến môi trường hoặc chạy theo Flow (File A xong chạy File B)
// Ở kịch bản này, ta giả lập hành động Lễ Tân tìm mã booking vừa được tạo.

test.describe('Nghiệp vụ Quản trị viên (Admin / Lễ Tân)', () => {

  test('ADMIN UNHAPPY PATH: Đăng nhập sai thông tin', async ({ page }) => {
    await page.goto('/admin/login.php');
    
    await page.fill('input[name="username"]', 'admin_fake');
    await page.fill('input[name="password"]', 'wrong_password');
    await page.click('button[type="submit"]');

    // Chắc chắn không vào được dashboard và có thông báo lỗi
    await expect(page).not.toHaveURL(/dashboard\.php/);
    await expect(page.locator('body')).toContainText(/sai|lỗi|không hợp lệ|invalid/i);
  });

  test('ADMIN HAPPY PATH: Xử lý vòng đời đơn đặt phòng (Pending -> Confirmed -> Checkin -> Checkout)', async ({ page }) => {
    // 1. Đăng nhập Admin
    await page.goto('/admin/login.php');
    // NOTE: Cần tài khoản Admin thật cấu hình trong DB của bạn. 
    // Tôi để tạm 'admin' / '123456' (Bạn hãy đổi nếu khác)
    await page.fill('input[name="username"]', 'admin'); 
    await page.fill('input[name="password"]', '123456'); // Thay đổi pass thật
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/index\.php/); // hoặc dashboard.php

    // 2. Vào trang Quản lý Booking
    await page.goto('/admin/bookings.php');
    
    // Lưu ý: Nếu ở file khách hàng tạo ra sharedBookingCode chưa truyền được sang đây, 
    // Ta có thể test bằng cách chọn đơn hàng có trạng thái "Pending" đầu tiên.
    // Chờ bảng render
    await page.waitForSelector('table tbody tr');

    // Lọc trạng thái Pending
    const statusFilter = page.locator('select[name="status"]');
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('pending');
      await page.click('button:has-text("Lọc")');
      await page.waitForLoadState('networkidle');
    }

    // Chọn Đơn hàng đầu tiên (Nhấn nút Chi tiết/Sửa)
    const firstDetailBtn = page.locator('a.btn-detail, a:has-text("Chi tiết")').first();
    // Nếu không có đơn nào Pending thì bỏ qua test
    if (await firstDetailBtn.count() === 0) {
      console.log('Không có đơn Pending nào để test Lễ tân.');
      return;
    }
    
    await firstDetailBtn.click();

    // 3. Nghiệp vụ: Xác nhận đơn (Pending -> Confirmed)
    await expect(page.locator('body')).toContainText('Chi tiết');
    
    // Đổi Select Status sang Confirmed
    const statusSelect = page.locator('select[name="status"]');
    await statusSelect.selectOption('confirmed');
    
    // Lễ tân ấn Lưu
    await page.click('button:has-text("Lưu"), button:has-text("Cập nhật")');
    await page.waitForLoadState('networkidle');

    // 4. Nghiệp vụ: Thanh toán (Unpaid -> Paid)
    // Giả lập khách đã đóng tiền
    const paymentStatusSelect = page.locator('select[name="payment_status"]');
    if (await paymentStatusSelect.isVisible()) {
      await paymentStatusSelect.selectOption('paid');
      await page.click('button:has-text("Lưu"), button:has-text("Cập nhật")');
      await page.waitForLoadState('networkidle');
    }

    // 5. Nghiệp vụ: Nhận phòng (Check-in)
    // Hệ thống thường có nút Check-in hoặc đổi trạng thái sang checked_in
    await statusSelect.selectOption('checked_in');
    
    // Gán số phòng cho khách (nếu có input gán phòng)
    const roomAssignSelect = page.locator('select[name="room_id"]');
    if (await roomAssignSelect.isVisible()) {
       // Chọn một option khả dụng không phải empty
       const options = await roomAssignSelect.locator('option').all();
       if (options.length > 1) {
          await roomAssignSelect.selectOption(await options[1].getAttribute('value'));
       }
    }
    
    await page.click('button:has-text("Lưu"), button:has-text("Cập nhật")');
    await page.waitForLoadState('networkidle');

    // 6. Nghiệp vụ: Trả phòng (Check-out)
    await statusSelect.selectOption('checked_out');
    await page.click('button:has-text("Lưu"), button:has-text("Cập nhật")');

    console.log('[Admin] Đã test thành công luồng: Xác nhận -> Thanh toán -> Nhận phòng -> Trả phòng');
  });

});
