import urllib.request
import urllib.error
import ssl

# Cấu hình BASE_URL
BASE_URL = "https://aurorahotelplaza.com"

# Danh sách các đường dẫn cần kiểm tra
ROUTES = [
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
    "/phong/deluxe",
    "/phong/premium-deluxe",
    "/phong/vip-suite",
    "/can-ho/studio-apartment",
    "/can-ho/modern-studio",
    "/can-ho/classical-premium",
    "/dich-vu/wedding-service",
    "/dich-vu/conference-service",
    "/dich-vu/aurora-restaurant",
    "/ho-so",
    "/ho-so/dat-phong",
    "/ho-so/chinh-sua"
]

def run_test():
    print(f"\n🚀 Đang kiểm tra đường dẫn cho: {BASE_URL}")
    print(f"{'Đường dẫn':<40} | {'Status':<10} | {'Kết quả'}")
    print("-" * 70)
    
    # Bỏ qua kiểm tra chứng chỉ SSL nếu cần (cho local/dev)
    context = ssl._create_unverified_context()
    
    passed = 0
    failed = 0
    
    for route in ROUTES:
        url = f"{BASE_URL.rstrip('/')}{route}"
        try:
            # Gửi request (giả lập User-Agent trình duyệt)
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
            with urllib.request.urlopen(req, context=context, timeout=10) as response:
                status = response.getcode()
                result = "✅ [OK]"
                passed += 1
                print(f"{route:<40} | {status:<10} | {result}")
        except urllib.error.HTTPError as e:
            # 302/301 vẫn coi là OK nếu đó là redirect mong muốn (như ho-so -> dang-nhap)
            if e.code in [301, 302, 307, 308]:
                print(f"{route:<40} | {e.code:<10} | ✅ [REDIRECT]")
                passed += 1
            else:
                print(f"{route:<40} | {e.code:<10} | ❌ [FAIL]")
                failed += 1
        except Exception as e:
            print(f"{route:<40} | ERROR      | ❌ {str(e)[:25]}...")
            failed += 1
            
    print("-" * 70)
    print(f"📊 TỔNG KẾT: Thành công: {passed} | Thất bại: {failed}")

if __name__ == "__main__":
    run_test()
