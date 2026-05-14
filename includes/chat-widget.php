<?php
/**
 * includes/chat-widget.php
 * ─────────────────────────
 * Floating phone call button — phía KHÁCH HÀNG.
 * Include vào includes/footer.php (trước </body>).
 *
 * Thay thế chat AI bằng nút gọi điện thoại tổng đài.
 *
 * - CSS: assets/css/phone-btn.css
 * - Languages: lang/vi.php, lang/en.php  (chat.phone_btn_*)
 */

// Không hiện widget trong trang admin
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (strpos($current_path, '/admin/') !== false)
    return;

// Load language helper if not loaded
if (!function_exists('__')) {
    require_once __DIR__ . '/../helpers/language.php';
    initLanguage();
}
?>

<!-- ══════════════════════════════════════════════════════════════
     FLOATING PHONE CALL BUTTON
══════════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="<?php echo asset('css/phone-btn.css'); ?>?v=<?php echo time(); ?>">

<a id="cwPhoneBtn" href="tel:+842513918888" aria-label="<?php _e('chat.phone_btn_call_now'); ?> Aurora Hotel Plaza">
    <span class="cw-phone-icon material-symbols-outlined">call</span>
</a>
<div id="cwPhoneTooltip">
    <?php _e('chat.phone_btn_call_now'); ?>: (0251) 391 8888<br>
    <span style="font-size:11px;font-weight:400;opacity:.8"><?php _e('chat.phone_btn_hotline'); ?></span>
</div>