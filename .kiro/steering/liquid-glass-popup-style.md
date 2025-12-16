# Liquid Glass UI Style Guide

## Overview
Style guide cho UI với hiệu ứng "liquid glass" - một thiết kế hiện đại với backdrop blur, gradient trong suốt và animation shimmer. Style này là **tiêu chuẩn thiết kế UI** cho toàn bộ dự án Aurora Hotel Plaza.

## Áp dụng cho
- **Popup/Modal**: QR code, xác nhận, thông báo, form dialog
- **Card nổi bật**: Thông tin quan trọng, featured content
- **Overlay**: Lightbox, image gallery, video player
- **Dropdown/Menu**: Navigation dropdown, context menu
- **Toast/Notification**: Alert messages, success/error notifications
- **Sidebar**: Mobile navigation, filter panel
- **Form container**: Login, register, booking form overlay

## File CSS
`profile/assets/css/qr-popup.css`

## Cấu trúc HTML

```html
<!-- Overlay -->
<div id="popupOverlay" class="qr-popup-overlay" onclick="closePopup(event)">
    <div class="qr-popup-container" onclick="event.stopPropagation()">
        <div class="qr-popup-glass">
            <!-- Close button -->
            <button class="qr-popup-close" onclick="closePopup()">
                <span class="material-symbols-outlined">close</span>
            </button>
            
            <!-- Header -->
            <div class="qr-popup-header">
                <h3>Tiêu đề</h3>
                <p>Mô tả ngắn</p>
            </div>
            
            <!-- Content -->
            <div class="qr-popup-code">
                <!-- Nội dung chính -->
            </div>
            
            <!-- Actions -->
            <div class="qr-popup-actions">
                <button class="qr-popup-btn qr-popup-btn-primary">
                    <span class="material-symbols-outlined">icon</span>
                    Nút chính
                </button>
                <button class="qr-popup-btn qr-popup-btn-secondary">
                    <span class="material-symbols-outlined">icon</span>
                    Nút phụ
                </button>
            </div>
            
            <!-- Info box (optional) -->
            <div class="qr-popup-info">
                <p>💡 Thông tin bổ sung</p>
            </div>
        </div>
    </div>
</div>
```

## JavaScript Functions

```javascript
function openPopup() {
    const overlay = document.getElementById('popupOverlay');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePopup(event) {
    if (event && event.target !== event.currentTarget) return;
    const overlay = document.getElementById('popupOverlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePopup();
    }
});
```

## Các class CSS chính

| Class | Mô tả |
|-------|-------|
| `.qr-popup-overlay` | Overlay nền mờ với backdrop-filter blur |
| `.qr-popup-container` | Container với animation scale khi mở |
| `.qr-popup-glass` | Hiệu ứng liquid glass chính |
| `.qr-popup-close` | Nút đóng góc phải trên |
| `.qr-popup-header` | Header với tiêu đề và mô tả |
| `.qr-popup-code` | Vùng nội dung chính (nền trắng) |
| `.qr-popup-actions` | Vùng chứa các nút action |
| `.qr-popup-btn-primary` | Nút chính (gradient vàng) |
| `.qr-popup-btn-secondary` | Nút phụ (trong suốt) |
| `.qr-popup-info` | Box thông tin bổ sung |

## Đặc điểm kỹ thuật

### Liquid Glass Effect (Cập nhật mới - trong suốt hơn)
```css
.qr-popup-glass {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.12) 0%,
        rgba(255, 255, 255, 0.06) 50%,
        rgba(255, 255, 255, 0.03) 100%
    );
    backdrop-filter: blur(16px) saturate(120%);
    -webkit-backdrop-filter: blur(16px) saturate(120%);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.12),
        0 0 0 1px rgba(255, 255, 255, 0.08) inset,
        0 32px 64px -12px rgba(0, 0, 0, 0.25);
}
```

### Highlight Effect (::before)
```css
.qr-popup-glass::before {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.08) 0%,
        rgba(255, 255, 255, 0) 100%
    );
}
```

### Animation Shimmer (::after)
```css
.qr-popup-glass::after {
    background: radial-gradient(
        circle at 30% 20%,
        rgba(212, 175, 55, 0.08) 0%,
        transparent 50%
    );
    animation: liquidShimmer 8s ease-in-out infinite;
}

@keyframes liquidShimmer {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    25% { transform: translate(5%, 5%) rotate(2deg); }
    50% { transform: translate(0, 10%) rotate(0deg); }
    75% { transform: translate(-5%, 5%) rotate(-2deg); }
}
```

### Transition mở/đóng
- **Overlay**: `transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`
- **Container**: `transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)` (bounce effect)

## Màu sắc
- **Primary (vàng Aurora)**: `#d4af37` → `#b8941f`
- **Text trắng**: `rgba(255, 255, 255, 0.8)` đến `white`
- **Border**: `rgba(255, 255, 255, 0.18)`
- **Background overlay**: `rgba(0, 0, 0, 0.5)`

## Dark Mode
Style tự động hỗ trợ dark mode với class `.dark`:
```css
.dark .qr-popup-glass {
    background: linear-gradient(
        135deg,
        rgba(30, 30, 30, 0.5) 0%,
        rgba(20, 20, 20, 0.6) 100%
    );
    border-color: rgba(255, 255, 255, 0.1);
}
```

## Ví dụ sử dụng hiện tại
- QR Code popup: `profile/view-qrcode.php`

## Các UI component có thể áp dụng

### 1. Confirmation Dialog
```html
<div class="qr-popup-overlay active">
    <div class="qr-popup-container">
        <div class="qr-popup-glass">
            <div class="qr-popup-header">
                <h3>Xác nhận hủy đặt phòng?</h3>
                <p>Hành động này không thể hoàn tác</p>
            </div>
            <div class="qr-popup-actions" style="flex-direction: row; gap: 12px;">
                <button class="qr-popup-btn qr-popup-btn-secondary" style="flex: 1;">Hủy</button>
                <button class="qr-popup-btn qr-popup-btn-primary" style="flex: 1;">Xác nhận</button>
            </div>
        </div>
    </div>
</div>
```

### 2. Image Lightbox
```html
<div class="qr-popup-overlay">
    <div class="qr-popup-container" style="max-width: 90vw;">
        <div class="qr-popup-glass" style="padding: 16px;">
            <button class="qr-popup-close">×</button>
            <img src="image.jpg" style="width: 100%; border-radius: 12px;">
        </div>
    </div>
</div>
```

### 3. Toast Notification (nhỏ gọn)
```html
<div class="qr-popup-glass" style="padding: 16px 24px; position: fixed; bottom: 24px; right: 24px; max-width: 320px;">
    <p style="color: white; display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined">check_circle</span>
        Đã lưu thành công!
    </p>
</div>
```

### 4. Dropdown Menu
```html
<div class="qr-popup-glass" style="padding: 8px; position: absolute; min-width: 200px;">
    <a href="#" style="display: block; padding: 12px 16px; color: white; border-radius: 8px;">
        Menu item 1
    </a>
    <a href="#" style="display: block; padding: 12px 16px; color: white; border-radius: 8px;">
        Menu item 2
    </a>
</div>
```

### 5. Mobile Sidebar
```html
<div class="qr-popup-overlay">
    <div class="qr-popup-glass" style="position: fixed; left: 0; top: 0; bottom: 0; width: 280px; border-radius: 0 24px 24px 0;">
        <div class="qr-popup-header">
            <h3>Menu</h3>
        </div>
        <!-- Navigation items -->
    </div>
</div>
```

### 6. Form Dialog
```html
<div class="qr-popup-overlay">
    <div class="qr-popup-container" style="max-width: 480px;">
        <div class="qr-popup-glass">
            <button class="qr-popup-close">×</button>
            <div class="qr-popup-header">
                <h3>Đăng nhập</h3>
            </div>
            <div class="qr-popup-code">
                <form>
                    <input type="email" placeholder="Email" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #ddd;">
                    <input type="password" placeholder="Mật khẩu" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;">
                </form>
            </div>
            <div class="qr-popup-actions">
                <button class="qr-popup-btn qr-popup-btn-primary">Đăng nhập</button>
            </div>
        </div>
    </div>
</div>
```

## Quy tắc thiết kế Liquid Glass

### Nguyên tắc cốt lõi

| # | Nguyên tắc | Mô tả | Giá trị |
|---|------------|-------|---------|
| 1 | **Trong suốt là ưu tiên** | Luôn để người dùng thấy được nội dung phía sau | Opacity max `0.12` |
| 2 | **Blur vừa phải** | Đủ để tạo hiệu ứng glass nhưng không che mất nền | `blur(16px)` |
| 3 | **Gradient 3 điểm** | Từ sáng → trung → tối theo hướng 135deg | `0.12 → 0.06 → 0.03` |
| 4 | **Border siêu nhẹ** | Tạo viền phân cách mà không gây chú ý | `rgba(255,255,255,0.18)` |
| 5 | **Shadow nhiều lớp** | Tạo chiều sâu với 3 lớp shadow | Xem CSS bên dưới |
| 6 | **Animation tinh tế** | Shimmer nhẹ nhàng, không gây rối mắt | `8s ease-in-out` |

### Công thức opacity

```
┌─────────────────────────────────────────────────────────────┐
│  LIQUID GLASS OPACITY FORMULA                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Background Gradient:  0.12 → 0.06 → 0.03                   │
│  Highlight (::before): 0.08 → 0                             │
│  Shimmer (::after):    0.08 (vàng Aurora)                   │
│  Border:               0.18                                 │
│  Shadow:               0.12, 0.08, 0.25                     │
│                                                             │
│  Dark Mode:            0.5 → 0.6 (đậm hơn để đọc được)      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Quy tắc màu sắc

| Thành phần | Light Mode | Dark Mode |
|------------|------------|-----------|
| Background | `rgba(255,255,255, 0.03-0.12)` | `rgba(20-30,20-30,20-30, 0.5-0.6)` |
| Border | `rgba(255,255,255, 0.18)` | `rgba(255,255,255, 0.1)` |
| Text | `white` hoặc `rgba(255,255,255, 0.8-0.9)` | Giữ nguyên |
| Accent | `#d4af37` (vàng Aurora) | Giữ nguyên |
| Shadow | `rgba(0,0,0, 0.12-0.25)` | Giữ nguyên |

### Quy tắc animation

```css
/* Transition mở/đóng - bounce effect */
transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);

/* Shimmer - chậm và tinh tế */
animation: liquidShimmer 8s ease-in-out infinite;

/* Hover effects - nhanh và nhẹ */
transition: all 0.3s ease;
```

### Quy tắc responsive

| Breakpoint | Điều chỉnh |
|------------|------------|
| Desktop | `max-width: 420px`, `padding: 32px` |
| Tablet | `max-width: 90%`, `padding: 24px` |
| Mobile | `width: 95%`, `padding: 20px`, `border-radius: 20px` |

### Checklist khi tạo UI mới với Liquid Glass

- [ ] Sử dụng class `.qr-popup-glass` hoặc copy CSS
- [ ] Đảm bảo có `backdrop-filter` VÀ `-webkit-backdrop-filter`
- [ ] Gradient 3 điểm với opacity thấp (max 0.12)
- [ ] Border với opacity 0.18
- [ ] Box-shadow 3 lớp
- [ ] Test trên nền có hình ảnh để đảm bảo trong suốt
- [ ] Test dark mode với class `.dark`
- [ ] Test animation không gây lag
- [ ] Đảm bảo text vẫn đọc được rõ ràng

### Không nên làm

❌ Dùng opacity cao hơn 0.2 cho background  
❌ Blur quá 20px (gây lag trên mobile)  
❌ Animation nhanh hơn 4s (gây rối mắt)  
❌ Dùng màu solid thay vì gradient  
❌ Quên `-webkit-backdrop-filter` (Safari)  
❌ Shadow quá đậm (opacity > 0.3)
