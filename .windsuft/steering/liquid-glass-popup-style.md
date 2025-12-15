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

### Liquid Glass Effect
```css
.qr-popup-glass {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.25) 0%,
        rgba(255, 255, 255, 0.1) 50%,
        rgba(255, 255, 255, 0.05) 100%
    );
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset,
        0 32px 64px -12px rgba(0, 0, 0, 0.4);
}
```

### Animation Shimmer
```css
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
- **Border**: `rgba(255, 255, 255, 0.3)`
- **Background overlay**: `rgba(0, 0, 0, 0.5)`

## Dark Mode
Style tự động hỗ trợ dark mode với class `.dark`:
```css
.dark .qr-popup-glass {
    background: linear-gradient(
        135deg,
        rgba(30, 30, 30, 0.8) 0%,
        rgba(20, 20, 20, 0.9) 100%
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

## Nguyên tắc thiết kế

1. **Luôn có backdrop blur** - Tạo cảm giác nổi và tách biệt với nền
2. **Gradient trong suốt** - Không dùng màu solid, luôn có độ trong suốt
3. **Border mờ** - `rgba(255, 255, 255, 0.3)` để tạo viền nhẹ
4. **Animation mượt** - Sử dụng cubic-bezier cho transition
5. **Màu vàng Aurora** - `#d4af37` cho accent color
6. **Dark mode ready** - Luôn test với `.dark` class
