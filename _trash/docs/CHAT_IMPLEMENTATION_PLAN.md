# 💬 CHAT REAL-TIME — IMPLEMENTATION PLAN

**Dự án:** Aurora Hotel Plaza  
**Phiên bản:** 1.0.0  
**Ngày tạo:** 2026-02-25  
**Trạng thái CSDL:** ✅ Đã migration thành công lên production  
**Công nghệ:** SSE (Server-Sent Events) + AJAX POST + PHP + MySQL

---

## 📑 MỤC LỤC

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Cấu trúc file](#2-cấu-trúc-file)
3. [Design UI — Phía Admin (all roles)](#3-design-ui--phía-admin)
4. [Design UI — Phía User (Customer)](#4-design-ui--phía-user)
5. [Phân quyền theo role](#5-phân-quyền-theo-role)
6. [API Endpoints](#6-api-endpoints)
7. [Lộ trình triển khai (Tasks)](#7-lộ-trình-triển-khai)
8. [Quy tắc code](#8-quy-tắc-code)

---

## 1. TỔNG QUAN KIẾN TRÚC

```
┌─────────────────────────────────────────────────────────────────┐
│  CUSTOMER SIDE                    ADMIN/STAFF SIDE              │
│                                                                 │
│  [Chat Widget - floating]  ←──→  [admin/chat.php - full page]  │
│  [chat/index.php]          ←──→  [Sidebar badge + counter]     │
└───────────────────────────┬─────────────────────────────────────┘
                            │ HTTP
              ┌─────────────▼──────────────┐
              │     PHP API Layer          │
              │  api/chat/send.php         │
              │  api/chat/stream.php (SSE) │
              │  api/chat/conversations.php│
              └─────────────┬──────────────┘
                            │ PDO
              ┌─────────────▼──────────────┐
              │         MySQL              │
              │  chat_conversations        │
              │  chat_messages             │
              │  chat_typing               │
              │  chat_quick_replies        │
              │  chat_settings             │
              └────────────────────────────┘
```

**Luồng hoạt động:**

```
Gửi tin:   Client → POST /api/chat/send.php → MySQL INSERT → return success
Nhận tin:  Client → GET  /api/chat/stream.php (giữ kết nối SSE) → MySQL SELECT mỗi 2s → push data
Typing:    Client → POST /api/chat/typing.php → MySQL UPSERT → SSE push typing event
```

---

## 2. CẤU TRÚC FILE

```
Aurorahotelplaza.com/
│
├── chat/                              ← Module chat phía USER
│   ├── index.php                      ← Trang chat dành cho customer (đăng nhập)
│   └── assets/
│       ├── chat-user.css              ← CSS riêng cho chat user
│       └── chat-user.js               ← JS: SSE client, gửi tin, typing
│
├── api/
│   └── chat/
│       ├── stream.php                 ← SSE endpoint (giữ kết nối, push tin mới)
│       ├── send-message.php           ← POST: gửi tin nhắn
│       ├── get-conversations.php      ← GET: danh sách hội thoại
│       ├── get-messages.php           ← GET: lịch sử tin nhắn
│       ├── create-conversation.php    ← POST: tạo cuộc hội thoại mới
│       ├── mark-read.php              ← POST: đánh dấu đã đọc
│       ├── typing.php                 ← POST: cập nhật trạng thái typing
│       └── close-conversation.php    ← POST: đóng/khoá hội thoại
│
└── admin/
    ├── chat.php                       ← Trang chat quản lý (admin/receptionist/sale)
    └── api/
        ├── assign-conversation.php    ← POST: gán conv cho staff
        ├── lock-conversation.php      ← POST: khoá (chỉ receptionist trở lên)
        ├── get-quick-replies.php      ← GET: danh sách câu trả lời nhanh
        ├── save-quick-reply.php       ← POST: thêm/sửa quick reply (admin only)
        └── chat-settings.php         ← POST: cập nhật cài đặt chat (admin only)
```

---

## 3. DESIGN UI — PHÍA ADMIN

### 3.1 Layout tổng thể (`admin/chat.php`)

```
┌─────────────────────────────────────────────────────────────────┐
│ SIDEBAR (64px)  │           MAIN CONTENT                        │
│  [Aurora logo]  │ ┌──────────────────────────────────────────┐  │
│  [nav items]    │ │  HEADER: "Tin nhắn" | badge unread | btn │  │
│  ✉ Chat [12]   │ ├──────────┬───────────────────────────────┤  │
│  (gold active)  │ │          │                               │  │
│                 │ │ CONVERSATION│  CHAT WINDOW              │  │
│                 │ │  LIST    │                               │  │
│                 │ │          │  [Customer name + booking]   │  │
│                 │ │ [search] │  [Status badge] [Assign btn] │  │
│                 │ │          │  ─────────────────────────── │  │
│                 │ │ [conv 1] │  [Message bubbles]           │  │
│                 │ │ [conv 2] │  [Typing indicator...]       │  │
│                 │ │ [conv 3] │  ─────────────────────────── │  │
│                 │ │  ...     │  [Internal note toggle]      │  │
│                 │ │          │  [Quick reply /shortcut]     │  │
│                 │ │          │  [Input box] [Send btn]      │  │
│                 │ └──────────┴───────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Panel Conversation List (Cột trái — 320px)

**Visual:**

- Background: `white` / `dark:slate-900`
- Border-right: `1px solid #e2e8f0`
- Chiều cao: `calc(100vh - header)`, scroll nội bộ

**Thành phần:**

```
┌──────────────────────────────┐
│ 🔍 [Tìm theo tên / SĐT...]  │  ← input search debounce 300ms
├──────────────────────────────┤
│ [Tab: Tất cả] [Mở] [Chờ tôi]│  ← filter tabs
├──────────────────────────────┤
│ ● Nguyễn Văn A               │  ← dot màu = status
│   "Hỏi về check-in sớm..."  │  ← preview 50 chars
│   BK2026... · 5 phút trước  │  ← booking code + thời gian
│                         [3] │  ← unread badge (gold)
├──────────────────────────────┤
│   Trần Thị B                 │  ← đã đọc = text nhạt hơn
│   "Cảm ơn bạn!"             │
│   14:02 hôm nay              │
├──────────────────────────────┤
│   ...                        │
└──────────────────────────────┘
```

**Màu status dot:**

- `open` + chưa assign: 🔴 đỏ nhấp nháy (cần xử lý)
- `assigned` (đang phụ trách): 🟢 xanh
- `closed`: ⚫ xám

**Item được chọn:**

- Background: `linear-gradient(135deg, #d4af37/15, #b8941f/15)`
- Border-left: `3px solid #d4af37`

### 3.3 Panel Chat Window (Cột phải)

**Header của chat window:**

```
┌────────────────────────────────────────────────────────────┐
│ [Avatar] Nguyễn Văn A          [● Online]  [···] menu     │
│          Customer · BK202601018EC952                       │
│          📞 0901234567  ✉ vana@gmail.com                   │
└────────────────────────────────────────────────────────────┘
          [Assign] [Close] [Lock - chỉ receptionist+]
```

**Khu vực tin nhắn:**

```
┌────────────────────────────────────────────────────────────┐
│                                                            │
│   ┌─────────────────────┐                                  │
│   │ Xin chào, tôi muốn │← bubble CUSTOMER (trái, grey)    │
│   │ hỏi về phòng VIP   │                                  │
│   └─────────────────────┘  14:01                          │
│                                                            │
│                      ┌──────────────────────────────────┐  │
│                      │ Xin chào anh/chị! Em sẽ hỗ trợ  │  │ ← bubble STAFF (phải, gold)
│                      │ ngay ạ. Phòng VIP hiện còn...   │  │
│                      └──────────────────────────────────┘  │
│                                              14:02 · Lan ✓ │
│                                                            │
│   ┌─────────────────────────┐                             │
│   │ 📝 [Ghi chú nội bộ]     │ ← internal note (vàng nhạt,│
│   │ Khách VIP, cần ưu tiên  │   chỉ staff thấy)          │
│   └─────────────────────────┘                             │
│                                                            │
│   Lan đang gõ...  ●●●                                     │← typing indicator
└────────────────────────────────────────────────────────────┘
```

**Bubble styles:**
| Loại | Background | Text | Border-radius |
|---|---|---|---|
| Customer | `#f1f5f9` (light) / `#1e293b` (dark) | default | `18px 18px 18px 4px` |
| Staff | `linear-gradient(#d4af37, #b8941f)` | white | `18px 18px 4px 18px` |
| System | `#fef3c7` | `#92400e` | `8px` full |
| Internal Note | `#fffbeb` border `#fbbf24` | `#78350f` | `8px` full, dashed border |

**Khu vực nhập tin:**

```
┌────────────────────────────────────────────────────────────┐
│ [🔒 Nội bộ] toggle                                         │← toggle internal note
├────────────────────────────────────────────────────────────┤
│ /shortcut → quick reply suggestions hiển thị ở đây        │← dropdown gợi ý
├────────────────────────────────────────────────────────────┤
│ [📎] [Nhập tin nhắn hoặc gõ / để chọn mẫu...]   [Gửi →] │
└────────────────────────────────────────────────────────────┘
```

**Quick Reply Popup** (khi gõ `/`):

```
┌────────────────────────────┐
│ /xin-chao  Chào mừng      │ ← hover highlight gold
│ /gia-phong Hỏi giá phòng  │
│ /check-in  Giờ check-in   │
│ /huy-phong Chính sách hủy │
└────────────────────────────┘
```

### 3.4 Panel Thông tin khách (Cột phụ — tùy chọn thu/mở, 280px)

```
┌───────────────────────┐
│ 👤 THÔNG TIN KHÁCH    │
│ ─────────────────── │
│ Nguyễn Văn A          │
│ ✉ vana@email.com     │
│ 📞 0901234567         │
│ 🏅 Silver Member      │
│ 2,500 điểm           │
├───────────────────────┤
│ 📋 BOOKING HIỆN TẠI  │
│ BK2026...            │
│ Deluxe · 01/03-03/03 │
│ ✅ Confirmed          │
├───────────────────────┤
│ 📜 LỊCH SỬ (3 đơn)   │
│ [Xem tất cả]         │
└───────────────────────┘
```

### 3.5 Badge Chat trên Sidebar

Thêm vào `admin-header.php` trong nav:

```html
<!-- CHAT - hiện với mọi role -->
<div class="mt-6 mb-2">
  <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
    Tương tác
  </p>
</div>
<a href="chat.php" class="sidebar-link <?= active('chat') ?>">
  <span class="material-symbols-outlined">chat</span>
  <span>Tin nhắn</span>
  <!-- Badge unread - cập nhật qua JS polling 30s -->
  <span
    id="chatUnreadBadge"
    class="ml-auto bg-red-500 text-white text-xs font-bold
                 px-2 py-0.5 rounded-full hidden"
  >
    0
  </span>
</a>
```

**Floating Chat Alert** (popup góc phải admin khi có tin mới):

```
┌─────────────────────────────────┐
│ 💬 Tin nhắn mới                 │
│ Nguyễn Văn A: "Xin chào..."    │
│                    [Trả lời →] │
└─────────────────────────────────┘
```

Hiển thị 5 giây rồi tự ẩn, có sound notification (toggle on/off).

### 3.6 Màu sắc & Typography (Admin) — bám theo design có sẵn

```css
/* Dựa trên palette từ admin-header.php */
--chat-gold: #d4af37;
--chat-gold-dark: #b8941f;
--chat-online: #22c55e; /* green-500 */
--chat-offline: #94a3b8; /* slate-400 */
--chat-urgent: #ef4444; /* red-500 */
--chat-note-bg: #fffbeb; /* amber-50 */
--chat-note-border: #fbbf24; /* amber-400 */

/* Font: đã dùng Inter */
/* Icons: Material Symbols Outlined (đã có) */
```

---

## 4. DESIGN UI — PHÍA USER

### 4.1 Chat Widget (Floating Button — xuất hiện mọi trang)

Thêm vào `includes/footer.php`, chỉ render khi user đã đăng nhập:

```
                              ┌────────────────────────────────┐
                              │ 💬  Hỗ trợ trực tuyến         │← slide in từ phải
                              │ ─────────────────────────────  │
                              │  [Tin nhắn bubbles...]         │
                              │                                │
                              │  [Input] [→ Gửi]              │
                              └────────────────────────────────┘
                                                    ▲
[Icon chat + badge unread] ────────────────────────┘
(fixed bottom-right, gold gradient)
```

**Chat Button:**

```css
/* Button mở chat - góc dưới phải */
position: fixed;
bottom: 28px;
right: 28px;
width: 60px;
height: 60px;
border-radius: 50%;
background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
box-shadow: 0 8px 24px rgba(212, 175, 55, 0.45);
/* Pulse animation khi có tin nhắn mới */
animation: chatPulse 2s infinite;
```

**Chat Panel (popup):**

```
┌──────────────────────────────────────────┐
│ Aurora Hotel Plaza  [●Online] [✕ đóng]  │← header gradient gold
├──────────────────────────────────────────┤
│                                          │
│  ┌──────────────────┐                   │
│  │ Xin chào! Em có  │← bubble staff     │
│  │ thể hỗ trợ gì?   │  (trái, gold)     │
│  └──────────────────┘  14:01            │
│                                          │
│          ┌──────────────────────────┐    │
│          │ Cho hỏi giá phòng VIP  │← bubble user (phải, grey)
│          └──────────────────────────┘    │
│                              14:01       │
│                                          │
│  ●●●  Nhân viên đang gõ...              │← typing
├──────────────────────────────────────────┤
│ [📎] [Nhập tin nhắn...]        [→ Gửi] │
└──────────────────────────────────────────┘
```

**Kích thước popup:**

- Width: `380px` (desktop) / `100vw` (mobile)
- Height: `520px` (desktop) / `75vh` (mobile)
- Border-radius: `20px` (desktop) / `20px 20px 0 0` (mobile, slide up)

### 4.2 Trang Chat đầy đủ (`chat/index.php`)

Dành cho mobile hoặc người muốn xem lịch sử chat đầy đủ:

```
┌───────────────────────────────────────┐
│ ← Aurora Hotel · Hỗ trợ   [●Online]  │← header (gold)
├───────────────────────────────────────┤
│                                       │
│  [Lịch sử tin nhắn - full scroll]    │
│                                       │
│  ─── Hôm nay, 02/25/2026 ───         │
│                                       │
│  ┌──────────────────────┐             │
│  │ Chào anh! Em có thể │             │
│  │ hỗ trợ gì ạ?        │             │
│  └──────────────────────┘             │
│  Nhân viên hỗ trợ · 13:45            │
│                                       │
│                 ┌──────────────────┐  │
│                 │ Hỏi về phòng VIP │  │
│                 └──────────────────┘  │
│                           Bạn · 13:46 │
│                                       │
├───────────────────────────────────────┤
│ [📎] [Nhập tin nhắn...]    [→ Gửi]  │
└───────────────────────────────────────┘
```

### 4.3 Tích hợp vào Profile

Trong `profile/index.php` — thêm nút shortcut:

```
┌──────────────────────────────────┐
│ 💬 Liên hệ hỗ trợ               │
│ Có câu hỏi về đặt phòng?        │
│            [Nhắn tin ngay →]    │
└──────────────────────────────────┘
```

Trong `profile/booking-detail.php` — nút chat gắn với booking:

```
[💬 Chat về đơn này] → mở chat với subject = "Về đơn BK20260101..."
```

### 4.4 Thông báo unread cho User

Trong `includes/header.php` — thêm badge chat vào nav user:

```html
<a href="/chat/" class="relative">
  <span class="material-symbols-outlined">chat_bubble</span>
  <span
    id="userChatBadge"
    class="absolute -top-1 -right-1 w-5 h-5
                 bg-red-500 text-white text-xs rounded-full hidden"
  >
    0
  </span>
</a>
```

### 4.5 Màu sắc & Style (User side) — bám liquid-glass design

```css
/* Glassmorphism style - bám theo liquid-glass.css */
.chat-widget-panel {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(212, 175, 55, 0.3);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

/* Header chat */
.chat-header {
  background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
  padding: 16px 20px;
  border-radius: 20px 20px 0 0;
}

/* Bubble customer */
.bubble-customer {
  background: linear-gradient(135deg, #d4af37, #b8941f);
  color: white;
  border-radius: 18px 18px 4px 18px;
  max-width: 75%;
  align-self: flex-end;
}

/* Bubble staff */
.bubble-staff {
  background: rgba(241, 245, 249, 0.9);
  color: #1e293b;
  border-radius: 18px 18px 18px 4px;
  max-width: 75%;
  align-self: flex-start;
}
```

---

## 5. PHÂN QUYỀN THEO ROLE

| Tính năng               | Customer | Lễ tân | Sale | Admin |
| ----------------------- | :------: | :----: | :--: | :---: |
| Xem chat của mình       |    ✅    |   —    |  —   |   —   |
| Gửi tin nhắn            |    ✅    |   ✅   |  ✅  |  ✅   |
| Xem TẤT CẢ hội thoại    |    ❌    |   ✅   |  ✅  |  ✅   |
| Gán conv cho staff khác |    ❌    |   ✅   |  ❌  |  ✅   |
| Khoá conversation       |    ❌    |   ✅   |  ❌  |  ✅   |
| Đóng conversation       |    ❌    |   ✅   |  ✅  |  ✅   |
| Xoá tin nhắn            |    ❌    |   ❌   |  ❌  |  ✅   |
| Xem ghi chú nội bộ      |    ❌    |   ✅   |  ✅  |  ✅   |
| Viết ghi chú nội bộ     |    ❌    |   ✅   |  ✅  |  ✅   |
| Quản lý Quick Replies   |    ❌    |   ❌   |  ❌  |  ✅   |
| Cài đặt chat system     |    ❌    |   ❌   |  ❌  |  ✅   |

**Logic PHP kiểm tra quyền:**

```php
// Trong mỗi API endpoint
$role = $_SESSION['user_role'];

// Chỉ staff mới xem tất cả conversations
if (!in_array($role, ['admin', 'receptionist', 'sale'])) {
    // Customer chỉ xem conv của mình
    $where_customer = "AND c.customer_id = {$_SESSION['user_id']}";
}

// Chỉ receptionist+ mới lock
if ($action === 'lock' && !in_array($role, ['admin', 'receptionist'])) {
    http_response_code(403);
    exit;
}
```

---

## 6. API ENDPOINTS

### 6.1 `GET /api/chat/stream.php` — SSE Stream

**Params:** `?conversation_id=5&last_id=12`

**Response (text/event-stream):**

```
data: {"type":"message","id":13,"sender_type":"staff",
       "message":"Xin chào!","created_at":"2026-02-25 14:05:00"}

data: {"type":"typing","user_id":7,"user_type":"staff","is_typing":1}

: heartbeat
```

**Interval:** 2 giây (configurable qua `chat_settings`)  
**Timeout:** 60 giây (hoặc khi client disconnect)

---

### 6.2 `POST /api/chat/send-message.php`

**Body:**

```json
{
  "conversation_id": 5,
  "message": "Xin chào!",
  "message_type": "text",
  "is_internal": false
}
```

**Response:**

```json
{
  "success": true,
  "message_id": 45,
  "created_at": "2026-02-25 14:05:30"
}
```

---

### 6.3 `POST /api/chat/create-conversation.php`

**Body:**

```json
{
  "subject": "Hỏi về phòng VIP",
  "booking_id": 10,
  "source": "booking"
}
```

---

### 6.4 `GET /api/chat/get-conversations.php`

**Params:** `?status=open&search=Nguyen&page=1`

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "conversation_id": 5,
      "customer_name": "Nguyễn Văn A",
      "customer_phone": "0901...",
      "subject": "Hỏi giá phòng",
      "last_message_preview": "Xin chào, cho tôi...",
      "last_message_at": "2026-02-25 14:00:00",
      "unread_staff": 3,
      "status": "open",
      "booking_code": "BK20260101..."
    }
  ],
  "total": 12
}
```

---

### 6.5 `POST /api/chat/typing.php`

**Body:** `{"conversation_id": 5, "is_typing": true}`

---

### 6.6 Admin APIs

| Endpoint                             | Method | Mô tả              | Role          |
| ------------------------------------ | ------ | ------------------ | ------------- |
| `/admin/api/assign-conversation.php` | POST   | Gán conv cho staff | Receptionist+ |
| `/admin/api/lock-conversation.php`   | POST   | Khoá/mở khoá       | Receptionist+ |
| `/admin/api/get-quick-replies.php`   | GET    | Lấy quick replies  | All staff     |
| `/admin/api/save-quick-reply.php`    | POST   | CRUD quick reply   | Admin only    |
| `/admin/api/chat-settings.php`       | POST   | Cập nhật cài đặt   | Admin only    |

---

## 7. LỘ TRÌNH TRIỂN KHAI

### Phase 1 — Backend Core (Ưu tiên cao)

- [ ] **Task 1.1** — `api/chat/create-conversation.php`
  - Tạo hội thoại mới cho customer
  - Auto gửi tin chào tự động nếu `auto_reply_enabled = 1`
  - Thời gian: ~2h

- [ ] **Task 1.2** — `api/chat/send-message.php`
  - Insert vào `chat_messages`
  - Update `last_message_at`, `last_message_preview`, `unread_*` trong `chat_conversations`
  - Thời gian: ~2h

- [ ] **Task 1.3** — `api/chat/stream.php` (SSE)
  - Infinite loop, SELECT tin mới + typing, push data
  - Set proper headers: `Content-Type: text/event-stream`, `X-Accel-Buffering: no`
  - Handle `connection_aborted()`
  - Thời gian: ~3h

- [ ] **Task 1.4** — `api/chat/get-messages.php` + `get-conversations.php`
  - Lịch sử tin nhắn (phân trang)
  - Danh sách hội thoại (filter, search)
  - Thời gian: ~2h

- [ ] **Task 1.5** — `api/chat/mark-read.php` + `typing.php`
  - Đánh dấu đã đọc, reset `unread_*`
  - Cập nhật typing status
  - Thời gian: ~1h

**Tổng Phase 1: ~10h**

---

### Phase 2 — Admin UI (`admin/chat.php`)

- [ ] **Task 2.1** — Layout 3 cột (conversation list + chat window + info panel)
  - Responsive: collapse info panel trên tablet
  - Dùng Tailwind classes bám style hiện có
  - Thời gian: ~4h

- [ ] **Task 2.2** — Conversation List component
  - Render hội thoại, filter tabs (Tất cả / Mở / Của tôi)
  - Search realtime
  - Status dot + unread badge
  - Thời gian: ~3h

- [ ] **Task 2.3** — Chat Window component
  - Render bubble messages
  - SSE client connection
  - Typing indicator
  - Scroll to bottom on new message
  - Thời gian: ~4h

- [ ] **Task 2.4** — Input area
  - Quick reply popup khi gõ `/`
  - Internal note toggle
  - File attachment (cơ bản)
  - Thời gian: ~3h

- [ ] **Task 2.5** — Admin actions
  - Assign, Close, Lock buttons
  - Customer info panel (lấy từ API existing)
  - Thời gian: ~2h

- [ ] **Task 2.6** — Sidebar badge + floating alert
  - Badge tự cập nhật 30s
  - Sound notification (toggle)
  - Toast khi có tin mới
  - Thời gian: ~2h

**Tổng Phase 2: ~18h**

---

### Phase 3 — User UI

- [ ] **Task 3.1** — Chat widget button (floating)
  - CSS + JS widget
  - Thêm vào `includes/footer.php`
  - Chỉ render khi đã đăng nhập
  - Thời gian: ~2h

- [ ] **Task 3.2** — Chat popup panel
  - UI glassmorphism bám `liquid-glass.css`
  - SSE client cho user
  - Responsive (mobile slide-up)
  - Thời gian: ~5h

- [ ] **Task 3.3** — `chat/index.php` (trang đầy đủ)
  - Full chat page với lịch sử
  - Thời gian: ~3h

- [ ] **Task 3.4** — Tích hợp vào profile
  - Nút chat trong `profile/booking-detail.php`
  - Badge unread trong navbar
  - Thời gian: ~2h

**Tổng Phase 3: ~12h**

---

### Phase 4 — Admin Settings & Quick Replies

- [ ] **Task 4.1** — Trang quản lý Quick Replies (admin only)
  - CRUD trong trang `admin/chat.php` tab Settings
  - Thời gian: ~3h

- [ ] **Task 4.2** — Chat Settings panel
  - Bật/tắt chat, giờ làm việc, tin nhắn tự động
  - Thời gian: ~2h

- [ ] **Task 4.3** — Thêm sidebar link vào `admin-header.php`
  - Link + badge cho tất cả role
  - Thời gian: ~30 phút

**Tổng Phase 4: ~6h**

---

### Tổng thời gian ước tính: ~46 giờ

| Phase    | Nội dung                 | Thời gian |
| -------- | ------------------------ | --------- |
| Phase 1  | Backend API + SSE        | ~10h      |
| Phase 2  | Admin UI                 | ~18h      |
| Phase 3  | User UI (widget + page)  | ~12h      |
| Phase 4  | Settings & Quick Replies | ~6h       |
| **Tổng** |                          | **~46h**  |

---

## 8. QUY TẮC CODE

### 8.1 SSE — Quan trọng trên shared hosting

```php
// PHẢI có các header này
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');  // Bypass Nginx buffer

// PHẢI có ini_set
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
set_time_limit(60); // Giới hạn 60s, client sẽ reconnect

// PHẢI check timeout cPanel
// Hầu hết cPanel giới hạn script timeout 30-60s
// → Client JS tự reconnect khi SSE connection đóng
```

### 8.2 Client JS — SSE reconnect

```javascript
function initSSE(conversationId, lastId) {
  const source = new EventSource(
    `/api/chat/stream.php?conversation_id=${conversationId}&last_id=${lastId}`,
  );

  source.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.type === "message") appendMessage(data);
    if (data.type === "typing") showTyping(data);
    lastId = Math.max(lastId, data.id || 0);
  };

  source.onerror = () => {
    source.close();
    // Reconnect sau 3 giây
    setTimeout(() => initSSE(conversationId, lastId), 3000);
  };
}
```

### 8.3 Bảo mật

```php
// LUÔN kiểm tra trong mọi API
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Customer chỉ được đọc conv của MÌNH
$stmt = $db->prepare("
    SELECT * FROM chat_conversations
    WHERE conversation_id = ?
    AND customer_id = ?  -- LUÔN filter theo user_id
");

// Staff không cần filter customer_id
// Nhưng phải kiểm tra role trước
```

### 8.4 Performance

- **Index đã có:** `idx_conv_id_msg`, `idx_conv_created` → SSE query nhanh
- **`unread_*` counter:** Dùng `UPDATE ... SET unread = unread + 1` thay vì `COUNT(*)` mỗi lần
- **SSE interval:** 2s (cấu hình qua `chat_settings`)
- **Typing cleanup:** `DELETE FROM chat_typing WHERE updated_at < NOW() - INTERVAL 5 SECOND`

---

## 9. CHECKLIST TRƯỚC KHI DEPLOY

### Backend

- [ ] Tất cả API có kiểm tra session + role
- [ ] Prepared statements cho mọi query
- [ ] CSRF token cho POST requests
- [ ] SSE headers đúng
- [ ] `set_time_limit` phù hợp với hosting

### Frontend

- [ ] SSE auto-reconnect hoạt động
- [ ] Scroll to bottom khi có tin mới
- [ ] Badge unread cập nhật đúng
- [ ] Responsive hoạt động trên mobile
- [ ] Dark mode hoạt động (bám class `dark:`)

### UX

- [ ] Tin nhắn gửi thành công hiện ngay (optimistic UI)
- [ ] Typing indicator tắt sau 5s không có hoạt động
- [ ] Offline message hiển thị đúng ngoài giờ làm việc
- [ ] Widget không che content quan trọng

---

_Tài liệu này được tạo ngày 2026-02-25, cập nhật theo tiến độ triển khai._
