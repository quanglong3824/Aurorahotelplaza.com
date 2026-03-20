import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  /* Chạy test song song cho nhanh */
  fullyParallel: false,
  /* Tránh test lặp lại vô tận nếu lỗi */
  retries: 0,
  /* Reporter hiển thị kết quả HTML cực đẹp (Pass/Fail/Trace) */
  reporter: 'html',
  
  use: {
    /* Base URL: Đổi thành URL localhost hoặc domain thật của dự án */
    baseURL: 'http://localhost/Github/AURORA%20HOTEL%20PLAZA/DOANH%20NGHIE%CC%A3%CC%82P/Aurorahotelplaza.com',
    /* Bật tính năng Trace (Ghi lại hình ảnh, network, click) nếu test lỗi */
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ],
});
