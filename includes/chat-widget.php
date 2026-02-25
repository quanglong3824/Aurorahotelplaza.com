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
 *   - Nếu đã đăng nhập → widget đầy đủ với chat realtime
 *   - Nếu chưa đăng nhập → panel mời đăng nhập
 *   - Không render gì nếu đang ở trang /admin/
 */

// Không hiện widget trong trang admin
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (strpos($current_path, '/admin/') !== false)
    return;

$is_logged = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_init = mb_strtoupper(mb_substr($user_name, 0, 1)) ?: '?';

// base_path đã được set ở footer.php
$bp = $base_path ?? '';
?>

<!-- ══════════════════════════════════════════════════════════════
     CHAT WIDGET — CSS & JS (chỉ load 2 file này)
══════════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="<?php echo $bp; ?>assets/css/chat-widget.css?v=1.0.0">

<!-- ══════════════════════════════════════════════════════════════
     FLOATING BUTTON
══════════════════════════════════════════════════════════════ -->
<button id="cwBtn" aria-label="Mở chat hỗ trợ" data-logged-in="<?php echo $is_logged ? '1' : '0'; ?>">

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
<div id="cwPanel" role="dialog" aria-label="Chat hỗ trợ Aurora Hotel Plaza">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div id="cwHeader">
        <div class="cw-avatar">
            <span class="material-symbols-outlined" style="font-size:20px;color:#fff">
                support_agent
            </span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="cw-title">Aurora Hotel Plaza</div>
            <div class="cw-subtitle">
                <span class="cw-online-dot"></span>
                Hỗ trợ trực tuyến
            </div>
        </div>
        <button id="cwCloseBtn" class="cw-close-btn" aria-label="Đóng chat">
            <span class="material-symbols-outlined" style="font-size:18px">close</span>
        </button>
    </div>

    <!-- ── Nội dung thay đổi theo trạng thái đăng nhập ────────── -->

    <?php if ($is_logged): ?>
        <!-- ── ĐÃ ĐĂNG NHẬP: Chat area ──────────────────────── -->

        <!-- Messages container -->
        <div id="cwMessages" style="flex:1;overflow-y:auto;">
            <!-- Render bởi ChatWidget.renderMessages() -->
            <div data-empty style="text-align:center;padding:32px 16px;color:#94a3b8">
                <div style="font-size:36px;margin-bottom:8px"></div>
                <p style="font-size:13px;line-height:1.6">
                    Xin chào <strong>
                        <?php echo htmlspecialchars($user_name ?: 'bạn'); ?>
                    </strong>!<br>
                    Chúng tôi sẵn sàng hỗ trợ.
                </p>
            </div>
        </div>

        <!-- Typing indicator -->
        <div id="cwTyping"></div>

        <!-- New message toast -->
        <div id="cwNewMsgToast" class="cw-new-msg-toast">
            ↓ Tin nhắn mới
        </div>

        <!-- Offline bar -->
        <div id="cwOfflineBar">
            <span class="material-symbols-outlined" style="font-size:14px">wifi_off</span>
            Ngoài giờ làm việc — Chúng tôi sẽ phản hồi sớm nhất
        </div>

        <!-- Input area -->
        <div id="cwInputArea">
            <div id="cwInputRow">
                <textarea id="cwInput" rows="1" placeholder="Nhập tin nhắn của bạn..."
                    aria-label="Nhập tin nhắn"></textarea>
                <button id="cwSendBtn" aria-label="Gửi tin nhắn">
                    <span class="material-symbols-outlined" style="font-size:18px">send</span>
                </button>
            </div>
            <div id="cwInputHint">Nhấn Enter để gửi · Shift+Enter xuống dòng</div>
        </div>

    <?php else: ?>
        <!-- ── CHƯA ĐĂNG NHẬP: mời đăng nhập ───────────────── -->
        <div id="cwLoginPrompt">
            <div style="width:56px;height:56px;border-radius:50%;
                        background:linear-gradient(135deg,#d4af37,#b8941f);
                        display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:28px;color:#fff">chat</span>
            </div>
            <p>
                Đăng nhập để nhắn tin trực tiếp với nhân viên hỗ trợ của Aurora Hotel Plaza.
            </p>
            <a href="<?php echo $bp; ?>auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                class="cw-login-btn">
                <span class="material-symbols-outlined" style="font-size:18px">login</span>
                Đăng nhập để chat
            </a>
            <a href="tel:+842513918888" style="display:flex;align-items:center;gap:6px;font-size:13px;
                      color:#d4af37;text-decoration:none;font-weight:600;margin-top:4px">
                <span class="material-symbols-outlined" style="font-size:16px">call</span>
                Gọi hotline: (+84-251) 391.8888
            </a>
        </div>
    <?php endif; ?>

</div><!-- /cwPanel -->


<!-- ══════════════════════════════════════════════════════════
     JS — defer để không block render
══════════════════════════════════════════════════════════ -->
<script src="<?php echo $bp; ?>assets/js/chat-widget.js?v=1.0.0" defer></script>

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