import asyncio
import sys
from playwright.async_api import async_playwright

# Cấu hình BASE_URL của bạn tại đây
BASE_URL = "https://aurorahotelplaza.com"

# Danh sách các đường dẫn cần kiểm tra
ROUTES = [
    # Static Routes
    "/",
    "/phong-khach-san",
    "/can-ho",
    "/dich-vu",
    "/thu-vien-anh",
    "/tin-tuc",
    "/lien-he",
    "/gioi-thieu",
    "/kham-pha",
    "/ban-do-phong",
    "/chinh-sach-huy",
    "/chinh-sach-bao-mat",
    "/dieu-khoan",
    "/dang-nhap",
    "/dang-ky",
    "/quen-mat-khau",
    "/dat-phong",
    
    # Dynamic Routes (Phòng)
    "/phong/deluxe",
    "/phong/premium-deluxe",
    "/phong/vip-suite",
    
    # Dynamic Routes (Căn hộ)
    "/can-ho/studio-apartment",
    "/can-ho/modern-studio",
    "/can-ho/classical-premium",
    
    # Dynamic Routes (Dịch vụ)
    "/dich-vu/wedding-service",
    "/dich-vu/conference-service",
    "/dich-vu/aurora-restaurant",
    
    # Profile Routes (Có thể trả về 302 nếu chưa đăng nhập, nhưng vẫn hợp lệ)
    "/ho-so",
    "/ho-so/dat-phong",
    "/ho-so/chinh-sua"
]

async def run_test():
    async with async_playwright() as p:
        print(f"\n🚀 Đang khởi động trình kiểm tra đường dẫn cho: {BASE_URL}")
        print(f"{'Đường dẫn':<40} | {'Status':<10} | {'Kết quả'}")
        print("-" * 70)
        
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()
        
        passed = 0
        failed = 0
        
        for route in ROUTES:
            url = f"{BASE_URL.rstrip('/')}{route}"
            try:
                # Kiểm tra phản hồi mạng
                response = await page.goto(url, wait_until="domcontentloaded", timeout=10000)
                status = response.status
                
                # 200 là OK, 302 là Redirect (thường gặp ở trang Profile khi chưa login)
                if status in [200, 302]:
                    result = "✅ [OK]"
                    passed += 1
                else:
                    result = "❌ [FAIL]"
                    failed += 1
                
                print(f"{route:<40} | {status:<10} | {result}")
            except Exception as e:
                print(f"{route:<40} | ERROR      | ❌ {str(e)[:25]}...")
                failed += 1
        
        await browser.close()
        
        print("-" * 70)
        print(f"📊 TỔNG KẾT: Thành công: {passed} | Thất bại: {failed}")
        if failed == 0:
            print("🎉 Tuyệt vời! Tất cả các đường dẫn đều hoạt động tốt.")
        else:
            print("⚠️ Cần kiểm tra lại một số đường dẫn bị lỗi.")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        BASE_URL = sys.argv[1]
    asyncio.run(run_test())
