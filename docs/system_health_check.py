import urllib.request
import urllib.parse
import time
import ssl
import sys
from html.parser import HTMLParser

# ========================================================
# CONFIGURATION - AURORA GUARDIAN v4.0 (ENTERPRISE TEST)
# ========================================================
BASE_URL = "https://aurorahotelplaza.com/2025"
MAX_PAGES = 40
CHECK_EVERY_ASSET = True  # Kiểm tra toàn bộ ảnh/css/js trên mỗi trang
DETECT_PHP_ERRORS = True  # Tìm kiếm Warning/Fatal Error ẩn trong HTML

# Bypass SSL
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

class AdvancedParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.assets = []
        self.scripts = []
        self.styles = []
        self.meta = {}
        self.body_text = ""

    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == 'a' and 'href' in d: self.links.append(d['href'])
        elif tag == 'img' and 'src' in d: self.assets.append(d['src'])
        elif tag == 'script' and 'src' in d: self.scripts.append(d['src'])
        elif tag == 'link' and 'href' in d:
            if d.get('rel') == 'stylesheet': self.styles.append(d['href'])
            else: self.assets.append(d['href'])
        elif tag == 'meta':
            name = d.get('name') or d.get('property')
            if name: self.meta[name] = d.get('content')

    def handle_data(self, data):
        self.body_text += data + " "

class AuroraGuardian:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.domain = urllib.parse.urlparse(base_url).netloc
        self.visited = set()
        self.queue = [self.base_url + "/"]
        self.assets_checked = set()
        self.issues = {
            "critical": [], # 500, PHP Errors, SQL Leaks
            "security": [], # .env, http links
            "broken": [],   # 404 links/assets
            "logic": [],    # Booking validation
            "seo": []       # Missing meta
        }

    def log(self, msg, status="INFO"):
        c = {"INFO": "\033[94m", "OK": "\033[92m", "FAIL": "\033[91m", "WARN": "\033[93m", "CRIT": "\033[41m\033[37m", "HEADER": "\033[95m"}
        print(f"{c.get(status, '')}[{status}] {msg}\033[0m")

    def safe_request(self, url, method="GET", data=None, is_asset=False):
        try:
            req = urllib.request.Request(url, method=method)
            req.add_header('User-Agent', 'Aurora-Guardian-Enterprise/4.0')
            if data:
                req.data = urllib.parse.urlencode(data).encode()
            
            with urllib.request.urlopen(req, timeout=10, context=ctx) as r:
                return r.getcode(), r.read().decode('utf-8', errors='ignore'), r.info()
        except urllib.error.HTTPError as e:
            return e.code, "", None
        except Exception as e:
            return 0, str(e), None

    def check_php_leaks(self, body, url):
        """Phát hiện lỗi PHP ẩn trong mã nguồn"""
        patterns = ["Fatal error", "Parse error", "Warning:", "Notice:", "Stack trace:", "on line", "Uncaught Error"]
        for p in patterns:
            if p in body:
                self.log(f"PHÁT HIỆN LỖI PHP TẠI: {url} ({p})", "CRIT")
                self.issues["critical"].append(f"PHP Leak ({p}) at {url}")
                return True
        return False

    def test_sql_injection_canary(self):
        """Thử nghiệm các ký tự gây lỗi SQL"""
        self.log("Đang kiểm tra SQL Injection Canary (An toàn)...", "HEADER")
        test_url = f"{self.base_url}/room-details/deluxe.php?id=1'"
        code, body, _ = self.safe_request(test_url)
        sql_errors = ["SQLSTATE", "mysql_fetch", "PDOException", "Syntax error or access violation"]
        for err in sql_errors:
            if err in body:
                self.log(f"CẢNH BÁO: Phát hiện lỗi SQL khi truyền ký tự lạ!", "CRIT")
                self.issues["critical"].append("SQL Error Leak detected")

    def test_booking_logic(self):
        """Kiểm tra chuyên sâu các kịch bản đặt phòng"""
        self.log("Đang kiểm tra Logic Đặt phòng (Validation)...", "HEADER")
        api_url = f"{self.base_url}/booking/api/create_booking.php"
        
        # Test Case: Đặt phòng > 30 ngày (Phải bị chặn bởi Logic Backend)
        data = {
            'room_type_id': 1,
            'guest_name': 'QA Tester',
            'guest_email': 'qa@example.com',
            'guest_phone': '0901234567',
            'check_in_date': '2026-05-01', 
            'check_out_date': '2026-06-15', # 45 ngày
            'num_adults': 1,
            'num_children': 0,
            'calculated_nights': 45
        }
        
        try:
            code, body, _ = self.safe_request(api_url, method="POST", data=data)
            if code == 200 and ("vượt quá 30 đêm" in body.lower() or "error" in body.lower()):
                self.log("Validation > 30 ngày: CHẶN ĐÚNG", "OK")
            else:
                self.log("Validation > 30 ngày: LỖI (Hệ thống không chặn)", "FAIL")
                self.issues["logic"].append("Booking Duration Logic not enforced on Backend")
        except Exception as e:
            self.log(f"Lỗi khi gọi API Booking: {str(e)}", "FAIL")

    def validate_assets(self, assets, page_url):
        """Kiểm tra từng hình ảnh/css/js"""
        for a in list(set(assets)):
            full_a = urllib.parse.urljoin(page_url, a)
            if full_a in self.assets_checked: continue
            self.assets_checked.add(full_a)

            # Check Mixed Content
            if full_a.startswith("http://"):
                self.issues["security"].append(f"Mixed Content: {full_a} found in {page_url}")

            code, _, _ = self.safe_request(full_a, method="HEAD", is_asset=True)
            if code != 200:
                self.log(f"Asset hỏng: {full_a} (Status {code})", "FAIL")
                self.issues["broken"].append(f"Broken Asset: {full_a} (In {page_url})")

    def run(self):
        self.log("=== KHỞI CHẠY AURORA GUARDIAN v4.0 - ENTERPRISE SCANNED ===", "HEADER")
        
        # 1. Security & Logic Pre-check
        self.test_sql_injection_canary()
        self.test_booking_logic()
        
        # 2. Main Crawl Loop
        count = 0
        while self.queue and count < MAX_PAGES:
            url = self.queue.pop(0)
            if url in self.visited: continue
            self.visited.add(url)
            count += 1

            self.log(f"Đang soi: {url}", "INFO")
            code, body, info = self.safe_request(url)

            if code != 200:
                self.log(f"Lỗi truy cập {code}: {url}", "FAIL")
                self.issues["broken"].append(f"Broken Link: {url} (Status {code})")
                continue

            # Check PHP Errors
            self.check_php_leaks(body, url)

            # Parse Page
            parser = AdvancedParser()
            try:
                parser.feed(body)
                
                # Check SEO
                if 'description' not in parser.meta:
                    self.issues["seo"].append(f"Thiếu Meta Description: {url}")

                # Check Assets on this page
                if CHECK_EVERY_ASSET:
                    all_assets = parser.assets + parser.scripts + parser.styles
                    self.validate_assets(all_assets, url)

                # Find internal links
                for link in parser.links:
                    full = urllib.parse.urljoin(url, link).split('#')[0].rstrip('/')
                    if self.domain in full and full.startswith(self.base_url):
                        if full not in self.visited and not any(full.endswith(x) for x in ['.jpg', '.png', '.pdf']):
                            self.queue.append(full)
            except: pass

        self.final_report()

    def final_report(self):
        self.log("\n" + "="*60, "HEADER")
        self.log("BÁO CÁO GIÁM SÁT HỆ THỐNG - AURORA GUARDIAN", "HEADER")
        self.log("="*60, "HEADER")
        
        sections = [
            ("LỖI NGHIÊM TRỌNG (PHP/SQL)", self.issues["critical"], "CRIT"),
            ("BẢO MẬT & MIXED CONTENT", self.issues["security"], "WARN"),
            ("LIÊN KẾT & ẢNH HỎNG (404)", self.issues["broken"], "FAIL"),
            ("SEO & VALIDATION", self.issues["seo"], "INFO")
        ]

        for title, data, status in sections:
            self.log(f"\n[{title}]", status)
            if not data:
                print("  -> Tuyệt vời! Không phát hiện lỗi.")
            else:
                for item in data[:15]: print(f"  - {item}")
                if len(data) > 15: print(f"  ... và {len(data)-15} lỗi khác.")

        print(f"\nTổng quát: Quét {len(self.visited)} trang, {len(self.assets_checked)} tài sản tĩnh.")
        self.log("KẾT THÚC KIỂM THỬ.", "HEADER")

if __name__ == "__main__":
    tester = AuroraGuardian(BASE_URL)
    tester.run()
