<?php
/**
 * includes/chat-widget.php
 * ─────────────────────────
 * HTML cho floating chat widget — phía KHÁCH HÀNG.
 * Include vào includes/footer.php (trước </body>).
 *
 * - CSS: assets/css/chat-widget.css
 * - JS:  assets/js/chat-widget.js
 * - PHP: file này (chỉ HTML/PHP logic)
 *
 * Hiển thị:
 *   - Khách vãng lai: Chat như khách với AI trợ lý
 *   - Khách đã đăng nhập: Chat đầy đủ với staff
 *   - Không render gì nếu đang ở trang /admin/
 */

// Không hiện widget trong trang admin
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (strpos($current_path, '/admin/') !== false)
    return;

// Luôn start session để quản lý guest chat
session_start();

// Tạo guest_id nếu chưa có (cho phép khách vãng lai chat)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    // Tạo guest ID duy nhất từ IP + timestamp
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $timestamp = time();
    $_SESSION['chat_guest_id'] = 'guest_' . md5($ip . $timestamp);
    $_SESSION['guest_created_at'] = $timestamp;
    
    // Đặt cookie để duy trì guest session qua các lần truy cập
    if (!isset($_COOKIE['chat_guest_id'])) {
        setcookie('chat_guest_id', $_SESSION['chat_guest_id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
}

// Xác định trạng thái chat
$is_logged = isset($_SESSION['user_id']);
$guest_id = $_SESSION['chat_guest_id'] ?? null;
$user_name = $is_logged 
    ? ($_SESSION['user_name'] ?? __('chat.guest')) 
    : ('Khách ' . substr($_SESSION['chat_guest_id'], -6));
$user_init = mb_strtoupper(mb_substr($user_name, 0, 1)) ?: '?';

// base_path đã được set ở footer.php
$bp = $base_path ?? '';

// BASE_URL cho JS
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/environment.php';
}
$cw_base = rtrim(BASE_URL, '/');
?>

<!-- ══════════════════════════════════════════════════════════════
     CHAT WIDGET — CSS & JS (chỉ load 2 file này)
══════════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="<?php echo $bp; ?>assets/css/chat-widget.css?v=<?php echo time(); ?>">

<!-- ══════════════════════════════════════════════════════════════
     FLOATING BUTTON
══════════════════════════════════════════════════════════════ -->
<button id="cwBtn" aria-label="<?php _e('chat.open_chat'); ?>" data-logged-in="<?php echo $is_logged ? '1' : '0'; ?>">

    <!-- Icon chat (khi đóng) -->
    <span class="cw-icon-chat material-symbols-outlined" style="font-size:26px">chat_bubble</span>

    <!-- Icon đóng (khi mở) -->
    <span class="cw-icon-close material-symbols-outlined" style="font-size:24px">close</span>

    <!-- Unread badge -->
    <span id="cwUnreadBadge">0</span>
</button>


<!-- ══════════════════════════════════════════════════════════════
     CHAT PANEL
══════════════════════════════════════════════════════════════ -->
<div id="cwPanel" role="dialog" aria-label="<?php echo addslashes(__('chat.open_chat')); ?> Aurora Hotel Plaza">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div id="cwHeader">
        <div class="cw-avatar">
            <span class="material-symbols-outlined" style="font-size:20px;color:#fff">
                support_agent
            </span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="cw-title">Aurora Hotel Plaza</div>
            <div class="cw-subtitle" id="cwStaffStatus">
                <span class="cw-online-dot" id="cwOnlineDot"></span>
                <span id="cwStatusText"><?php _e('chat.checking'); ?></span>
            </div>
        </div>
        <button id="cwResetAiBtn" class="cw-close-btn" aria-label="<?php _e('chat.refresh_ai'); ?>"
            title="<?php _e('chat.clear_history'); ?>" style="margin-right:4px;">
            <span class="material-symbols-outlined" style="font-size:18px">refresh</span>
        </button>
        <button id="cwCloseBtn" class="cw-close-btn" aria-label="<?php _e('chat.close_chat'); ?>">
            <span class="material-symbols-outlined" style="font-size:18px">close</span>
        </button>
    </div>

    <!-- ── Nội dung thay đổi theo trạng thái đăng nhập ────────── -->

    <?php if ($is_logged): ?>
        <!-- ── ĐÃ ĐĂNG NHẬP: Chat area ──────────────────────── -->
        <?php include __DIR__ . '/chat-widget-logged-in.php'; ?>
    <?php else: ?>
        <!-- ── KHÁCH VÃNG LAI: Chat area với AI ─────────────── -->
        <?php include __DIR__ . '/chat-widget-guest.php'; ?>
    <?php endif; ?>

</div><!-- /cwPanel -->


<!-- ══════════════════════════════════════════
     JS — siteBase inject trước, defer sau
══════════════════════════════════════════ -->
<script>window.siteBase = '<?php echo $cw_base; ?>';</script>
<script src="<?php echo $bp; ?>assets/js/chat-widget.js?v=<?php echo time(); ?>" defer></script>

<?php if ($is_logged): ?>
    <script>
        // Pass PHP session data vào ChatWidget (không dùng biến global dài dòng)
        document.addEventListener('DOMContentLoaded', function () {
            // Load lịch sử chat nếu có conv sẵn
            if (typeof ChatWidget !== 'undefined') {
                ChatWidget.checkExistingConversation();
            }
        });
    </script>
<?php endif; ?>