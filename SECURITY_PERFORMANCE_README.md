# 🔒 Security & Performance Implementation Guide

## Tổng quan

Website Aurora Hotel Plaza đã được tối ưu toàn diện về **bảo mật**, **performance** và **SEO**. Tài liệu này hướng dẫn cách sử dụng các tính năng đã được triển khai.

---

## 📦 Files đã được tạo

### 1. Security Files
- ✅ `.htaccess` - Cấu hình bảo mật Apache (đã nâng cấp)
- ✅ `helpers/security.php` - Security Helper class
- ✅ `security-check.php` - Tool kiểm tra bảo mật (chỉ dùng trong dev)

### 2. Performance Files
- ✅ `assets/js/performance.js` - Performance optimization script

### 3. SEO Files
- ✅ `helpers/seo.php` - SEO Helper class
- ✅ `sitemap.xml` - XML sitemap
- ✅ `robots.txt` - Robots directives (đã có sẵn)

### 4. Documentation
- ✅ `docs/SECURITY_GUIDE.md` - Hướng dẫn bảo mật chi tiết
- ✅ `docs/PERFORMANCE_SEO_GUIDE.md` - Hướng dẫn performance & SEO

---

## 🚀 Quick Start

### 1. Kiểm tra bảo mật & performance

Truy cập (chỉ trong localhost):
```
http://localhost/security-check.php
```

**⚠️ QUAN TRỌNG:** Xóa file này trước khi deploy production!

### 2. Sử dụng Security Helper

```php
<?php
require_once 'helpers/security.php';

// CSRF Protection
$token = Security::generateCSRFToken();
?>

<form method="POST" action="process.php">
    <?php echo Security::getCSRFInput(); ?>
    <input type="text" name="name">
    <button type="submit">Submit</button>
</form>

<?php
// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    // Sanitize input
    $name = Security::sanitizeString($_POST['name']);
    $email = Security::sanitizeEmail($_POST['email']);
    
    // Rate limiting
    $ip = Security::getClientIP();
    if (!Security::checkRateLimit($ip, 5, 300)) {
        die('Too many requests');
    }
    
    // Process form...
}
?>
```

### 3. Sử dụng SEO Helper

```php
<?php
require_once 'helpers/seo.php';
?>
<!DOCTYPE html>
<html>
<head>
    <?php
    // Generate meta tags
    echo SEO::generateMetaTags([
        'title' => 'Phòng Deluxe - Aurora Hotel Plaza',
        'description' => 'Phòng Deluxe sang trọng với đầy đủ tiện nghi...',
        'keywords' => 'phòng deluxe, khách sạn biên hòa',
        'image' => '/assets/img/rooms/deluxe.jpg',
        'type' => 'product'
    ]);
    
    // Generate structured data
    echo SEO::generateHotelStructuredData();
    ?>
</head>
<body>
    <?php
    // Breadcrumb
    $breadcrumbs = [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Phòng', 'url' => '/rooms.php'],
        ['name' => 'Phòng Deluxe', 'url' => '/room-details/deluxe.php']
    ];
    
    echo SEO::generateBreadcrumbHTML($breadcrumbs);
    echo SEO::generateBreadcrumbStructuredData($breadcrumbs);
    ?>
</body>
</html>
```

### 4. Sử dụng Performance Script

Thêm vào HTML:
```html
<!-- Thêm vào cuối body -->
<script src="/assets/js/performance.js"></script>
```

Lazy loading images:
```html
<!-- Native lazy loading -->
<img src="image.jpg" loading="lazy" alt="Description">

<!-- Custom lazy loading -->
<img data-src="image.jpg" class="lazyload" alt="Description">

<!-- Lazy background -->
<div data-bg="background.jpg" class="hero"></div>
```

---

## 🛡️ Bảo vệ chống 4 phương thức tấn công

### 1. SQL Injection ✅

**Đã triển khai:**
- Prepared statements (bắt buộc sử dụng)
- Input validation qua Security Helper
- .htaccess rules chặn SQL injection patterns

**Cách sử dụng:**
```php
// ✅ ĐÚNG
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", Security::sanitizeEmail($_POST['email']));
$stmt->execute();

// ❌ SAI - KHÔNG BAO GIỜ LÀM NHƯ NÀY
$query = "SELECT * FROM users WHERE email = '{$_POST['email']}'";
```

### 2. XSS (Cross-Site Scripting) ✅

**Đã triển khai:**
- Output encoding tự động
- Content Security Policy headers
- Input sanitization
- .htaccess rules chặn XSS patterns

**Cách sử dụng:**
```php
// ✅ ĐÚNG - Luôn escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Hoặc dùng Security Helper
echo Security::sanitizeString($user_input);

// ❌ SAI
echo $_POST['comment'];
```

### 3. CSRF (Cross-Site Request Forgery) ✅

**Đã triển khai:**
- CSRF token generation & validation
- SameSite cookie attribute
- Form protection

**Cách sử dụng:**
```php
// Trong form
<form method="POST">
    <?php echo Security::getCSRFInput(); ?>
    <!-- form fields -->
</form>

// Khi xử lý form
if (!Security::validateCSRFToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### 4. DDoS (Distributed Denial of Service) ✅

**Đã triển khai:**
- Rate limiting per IP
- Bad bot blocking
- mod_evasive configuration (nếu có)
- Connection limits

**Cách sử dụng:**
```php
// Rate limiting
$ip = Security::getClientIP();
if (!Security::checkRateLimit($ip, 5, 300)) {
    http_response_code(429);
    die('Too many requests. Please try again later.');
}
```

---

## ⚡ Performance Optimization

### 1. Image Optimization

```html
<!-- Lazy loading -->
<img src="image.jpg" loading="lazy" alt="Description">

<!-- Responsive images -->
<img srcset="image-400.jpg 400w, image-800.jpg 800w" 
     sizes="(max-width: 600px) 400px, 800px"
     src="image-800.jpg" 
     loading="lazy" 
     alt="Description">

<!-- WebP with fallback -->
<picture>
    <source srcset="image.webp" type="image/webp">
    <img src="image.jpg" alt="Description" loading="lazy">
</picture>
```

### 2. CSS Optimization

```html
<!-- Critical CSS inline -->
<style>
    /* Critical above-the-fold CSS */
</style>

<!-- Defer non-critical CSS -->
<link rel="preload" href="styles.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="styles.css"></noscript>
```

### 3. JavaScript Optimization

```html
<!-- Defer JavaScript -->
<script src="script.js" defer></script>

<!-- Async for independent scripts -->
<script src="analytics.js" async></script>
```

### 4. Resource Hints

```html
<!-- DNS Prefetch -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">

<!-- Preconnect -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>

<!-- Preload critical resources -->
<link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
```

---

## 🔍 SEO Best Practices

### 1. Meta Tags

Mỗi trang nên có:
- Unique title (50-60 ký tự)
- Meta description (150-160 ký tự)
- Open Graph tags
- Twitter Card tags
- Canonical URL

### 2. Structured Data

Sử dụng SEO Helper để tạo:
- Hotel schema
- Room schema
- Breadcrumb schema
- Review schema

### 3. Sitemap

File `sitemap.xml` đã được tạo. Cần:
1. Cập nhật khi thêm trang mới
2. Submit lên Google Search Console
3. Thêm vào robots.txt (đã có)

---

## ✅ Pre-deployment Checklist

### Security
- [ ] Tất cả forms có CSRF protection
- [ ] Tất cả inputs được validate & sanitize
- [ ] Tất cả outputs được encode
- [ ] Database queries dùng prepared statements
- [ ] File permissions đúng (755/644)
- [ ] Sensitive files trong .gitignore
- [ ] Error display = Off
- [ ] HTTPS enabled
- [ ] Security headers configured
- [ ] **XÓA file `security-check.php`**

### Performance
- [ ] Images được optimize (WebP, lazy loading)
- [ ] CSS/JS được minify
- [ ] Browser caching enabled
- [ ] Gzip compression enabled
- [ ] OPcache enabled
- [ ] Performance script loaded

### SEO
- [ ] Mỗi trang có unique meta tags
- [ ] Structured data implemented
- [ ] Sitemap.xml updated
- [ ] Robots.txt configured
- [ ] Canonical URLs set
- [ ] Image alt tags added
- [ ] Mobile-friendly tested

---

## 🧪 Testing

### 1. Security Testing

```bash
# Test CSRF protection
curl -X POST http://localhost/process.php -d "name=test"
# Should fail without CSRF token

# Test rate limiting
for i in {1..10}; do curl http://localhost/api/endpoint; done
# Should block after 5 requests

# Test SQL injection
curl "http://localhost/page.php?id=1' OR '1'='1"
# Should be blocked by .htaccess
```

### 2. Performance Testing

```bash
# Page load time
curl -o /dev/null -s -w 'Total: %{time_total}s\n' http://localhost/

# Check gzip
curl -H "Accept-Encoding: gzip" -I http://localhost/

# Check caching headers
curl -I http://localhost/assets/img/logo.png
```

### 3. SEO Testing

- Google PageSpeed Insights: https://pagespeed.web.dev/
- Schema Validator: https://validator.schema.org/
- Mobile-Friendly Test: https://search.google.com/test/mobile-friendly

---

## 📊 Monitoring

### Security Logs

```bash
# View security logs
tail -f logs/security.log

# Search for failed logins
grep "LOGIN_FAILED" logs/security.log

# Find suspicious IPs
grep "SUSPICIOUS" logs/security.log | awk '{print $4}' | sort | uniq -c
```

### Performance Monitoring

```javascript
// In browser console
performance.getEntriesByType('navigation')[0]
```

---

## 🚨 Incident Response

### Nếu phát hiện tấn công:

1. **Block IP ngay lập tức**
   ```bash
   echo "deny from SUSPICIOUS_IP" >> .htaccess
   ```

2. **Check logs**
   ```bash
   tail -100 logs/security.log
   tail -100 logs/error.log
   ```

3. **Backup database**
   ```bash
   mysqldump -u root -p database_name > backup.sql
   ```

4. **Thay đổi passwords**
   - Database password
   - Admin passwords
   - API keys

5. **Liên hệ team security**
   - Email: security@aurorahotelplaza.com
   - Phone: +84-251-3836-888

---

## 📚 Additional Resources

### Documentation
- `docs/SECURITY_GUIDE.md` - Chi tiết về bảo mật
- `docs/PERFORMANCE_SEO_GUIDE.md` - Chi tiết về performance & SEO

### External Resources
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Google Web Vitals](https://web.dev/vitals/)
- [Schema.org](https://schema.org/)

---

## 💡 Tips & Best Practices

### Security
1. **Luôn validate input** - Never trust user input
2. **Luôn escape output** - Prevent XSS
3. **Sử dụng prepared statements** - Prevent SQL injection
4. **Implement CSRF protection** - On all forms
5. **Keep software updated** - PHP, MySQL, libraries

### Performance
1. **Optimize images** - Use WebP, lazy loading
2. **Minimize HTTP requests** - Combine files
3. **Enable caching** - Browser & server-side
4. **Defer non-critical resources** - CSS, JS
5. **Monitor Core Web Vitals** - LCP, FID, CLS

### SEO
1. **Unique content** - No duplicate content
2. **Mobile-friendly** - Responsive design
3. **Fast loading** - < 3 seconds
4. **Structured data** - Help search engines
5. **Quality backlinks** - Build authority

---

## ⚠️ Important Notes

1. **XÓA `security-check.php` trước khi deploy production!**
2. Thay đổi database credentials trong production
3. Enable HTTPS và force redirect
4. Set up regular backups
5. Monitor logs thường xuyên
6. Update dependencies định kỳ
7. Test thoroughly trước khi deploy

---

**Last Updated:** November 19, 2025  
**Version:** 1.0.0  
**Status:** ✅ Ready for Production (after removing security-check.php)

---

## 📞 Support

Nếu cần hỗ trợ:
- Email: support@aurorahotelplaza.com
- Security: security@aurorahotelplaza.com
- Phone: +84-251-3836-888
