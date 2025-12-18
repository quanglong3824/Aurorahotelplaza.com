# Aurora Hotel Plaza - Coding Standards & Style Guide

## Project Overview
Aurora Hotel Plaza là website đặt phòng khách sạn được xây dựng bằng PHP thuần (vanilla PHP) kết hợp với Tailwind CSS. Project hỗ trợ cả giao diện người dùng (frontend) và quản trị (admin panel).

## Tech Stack
- **Backend**: PHP 7.4+ (vanilla, không framework)
- **Database**: MySQL với PDO
- **Frontend**: Tailwind CSS (CDN), vanilla JavaScript
- **Icons**: Material Symbols Outlined
- **Fonts**: Plus Jakarta Sans (body), Montserrat (headings)

## Project Structure
```
/
├── admin/              # Admin panel
│   ├── api/            # Admin API endpoints
│   ├── assets/         # Admin-specific assets
│   └── includes/       # Admin header/footer
├── assets/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── img/            # Images
│   └── fonts/          # Custom fonts
├── auth/               # Authentication pages
├── booking/            # Booking flow
├── config/             # Configuration files
├── helpers/            # Helper functions
├── includes/           # Shared components (header, footer)
├── models/             # Data models
├── profile/            # User profile pages
├── room-details/       # Room detail pages
└── apartment-details/  # Apartment detail pages
```

## Color Palette
```css
/* Primary Colors */
--primary: #cc9a2c;
--primary-light: #d4af37;
--primary-dark: #b8941f;

/* Accent (Gold) */
--accent: #d4af37;

/* Background */
--background-light: #ffffff;
--background-dark: #111827;

/* Surface */
--surface-light: #f9fafb;
--surface-dark: #1f2937;

/* Text */
--text-primary-light: #1f2937;
--text-primary-dark: #f3f4f6;
--text-secondary-light: #6b7280;
--text-secondary-dark: #9ca3af;
```

## PHP Coding Standards

### File Structure
```php
<?php
// 1. Start session if needed
session_start();

// 2. Load configurations
require_once 'config/database.php';
require_once 'config/environment.php';
require_once 'helpers/image-helper.php';

// 3. Business logic / Data fetching
$data = [];
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM table WHERE status = 'active'");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Page error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<!-- 4. HTML output -->
```

### Database Queries
- Luôn sử dụng PDO với prepared statements
- Sử dụng `getDB()` function để lấy database connection
- Wrap queries trong try-catch block
- Log errors với `error_log()`

```php
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = :category
        ORDER BY sort_order ASC
    ");
    $stmt->execute([':category' => $category]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Query error: " . $e->getMessage());
    $results = [];
}
```

### Helper Functions
- Đặt trong thư mục `helpers/`
- Mỗi file helper có một mục đích cụ thể
- Sử dụng docblocks để document functions

```php
/**
 * Format currency VND
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}
```

## HTML/Tailwind Standards

### Page Layout (Frontend)
```php
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Page Title - Aurora Hotel Plaza</title>
    
    <!-- Tailwind CSS -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet"/>
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex h-full grow flex-col">
            <!-- Page content -->
        </main>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

### Admin Page Layout
```php
<?php
session_start();
require_once '../config/database.php';

$page_title = 'Page Title';
$page_subtitle = 'Page description';

// Data fetching logic...

include 'includes/admin-header.php';
?>

<!-- Page content with Tailwind classes -->

<?php include 'includes/admin-footer.php'; ?>
```

### Common Tailwind Classes
```html
<!-- Container -->
<div class="mx-auto max-w-7xl px-4">

<!-- Section -->
<section class="w-full py-16 sm:py-24">

<!-- Card -->
<div class="rounded-xl bg-surface-light dark:bg-surface-dark shadow-md p-6">

<!-- Button Primary -->
<button class="bg-accent text-white rounded-lg px-6 py-3 font-bold hover:opacity-90 transition-opacity">

<!-- Button Secondary -->
<button class="border-2 border-accent text-accent rounded-lg px-6 py-3 font-bold hover:bg-accent/10 transition-colors">

<!-- Grid -->
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

<!-- Form Input -->
<input type="text" class="form-input rounded-lg border border-gray-300 px-4 py-2 focus:border-accent focus:ring-accent">
```

### Icons Usage
```html
<!-- Material Symbols Outlined -->
<span class="material-symbols-outlined">icon_name</span>

<!-- Common icons -->
hotel, person, calendar_month, payments, check_circle, 
edit, delete, add, search, filter_alt, arrow_forward
```

## Admin Panel Standards

### Stat Cards
```html
<div class="stat-card">
    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Label</p>
    <p class="text-2xl font-bold">Value</p>
</div>
```

### Data Tables
```html
<div class="card">
    <div class="card-header">
        <h3 class="font-semibold">Table Title</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Column</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Data</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### Badges
```html
<span class="badge badge-success">Active</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-danger">Cancelled</span>
<span class="badge badge-info">Info</span>
```

### Action Buttons
```html
<div class="action-buttons">
    <button class="action-btn" title="Edit">
        <span class="material-symbols-outlined text-sm">edit</span>
    </button>
    <button class="action-btn text-red-600" title="Delete">
        <span class="material-symbols-outlined text-sm">delete</span>
    </button>
</div>
```

## JavaScript Standards

### API Calls
```javascript
fetch('api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'param1=' + value1 + '&param2=' + value2
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        showToast('Success message', 'success');
        // Handle success
    } else {
        showToast(data.message || 'Error occurred', 'error');
    }
})
.catch(error => {
    console.error('Error:', error);
    showToast('Error occurred', 'error');
});
```

### Toast Notifications
```javascript
showToast('Message', 'success'); // success, error, warning, info
```

## API Response Format
```php
// Success
echo json_encode([
    'success' => true,
    'message' => 'Operation successful',
    'data' => $result
]);

// Error
echo json_encode([
    'success' => false,
    'message' => 'Error description'
]);
```

## Naming Conventions
- **Files**: lowercase with hyphens (e.g., `room-types.php`, `image-helper.php`)
- **Database tables**: lowercase with underscores (e.g., `room_types`, `booking_services`)
- **PHP variables**: snake_case (e.g., `$room_types`, `$base_price`)
- **PHP functions**: camelCase (e.g., `getDB()`, `formatCurrency()`)
- **CSS classes**: Tailwind utility classes + BEM for custom (e.g., `.room-card`, `.btn-booking`)
- **JavaScript functions**: camelCase (e.g., `updateRoomStatus()`, `loadNotifications()`)

## Dark Mode Support
- Sử dụng class `dark:` prefix cho dark mode styles
- Toggle dark mode bằng class `dark` trên `<html>` element
- Luôn cung cấp cả light và dark variants cho colors

## Vietnamese Language
- Tất cả UI text hiển thị bằng tiếng Việt
- Comments trong code có thể bằng tiếng Anh hoặc tiếng Việt
- Date format: `d/m/Y` (e.g., 25/12/2025)
- Currency format: `number_format($amount, 0, ',', '.')` + `đ` hoặc `VNĐ`

## Security Best Practices
- Luôn escape output với `htmlspecialchars()`
- Sử dụng prepared statements cho database queries
- Validate và sanitize tất cả user input
- Check session và role trước khi cho phép truy cập admin pages
- Không hiển thị error details cho end users

## File Includes
- Frontend pages: `include 'includes/header.php'` và `include 'includes/footer.php'`
- Admin pages: `include 'includes/admin-header.php'` và `include 'includes/admin-footer.php'`
- Config files: `require_once 'config/database.php'`
- Helpers: `require_once 'helpers/function-name.php'`
