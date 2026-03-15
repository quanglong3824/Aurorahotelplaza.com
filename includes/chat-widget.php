<?php
/**
 * includes/chat-widget.php
 * Refactored to follow MVC pattern.
 * Logic moved to FrontSharedController.
 */
require_once __DIR__ . '/../controllers/FrontSharedController.php';
$chatData = FrontSharedController::getChatWidgetData($base_path ?? '');

if (!$chatData['show_widget']) {
    return;
}

$is_logged = $chatData['is_logged'];
$user_name = $chatData['user_name'];
$user_init = $chatData['user_init'];
$cw_base = $chatData['cw_base'];
$bp = $chatData['base_path'];
?>

<!-- Chat Widget Styles -->
<link rel="stylesheet" href="<?php echo $bp; ?>assets/css/chat-widget.css?v=<?php echo time(); ?>">

<!-- Floating Button -->
<button id="cwBtn" aria-label="<?php _e('chat.open_chat'); ?>" data-logged-in="<?php echo $is_logged ? '1' : '0'; ?>">
    <!-- Icon chat (khi đóng) -->
    <span class="cw-icon-chat material-symbols-outlined" style="font-size:26px">chat_bubble</span>
    <!-- Icon đóng (khi mở) -->
    <span class="cw-icon-close material-symbols-outlined" style="font-size:24px">close</span>
    <!-- Unread badge -->
    <span id="cwUnreadBadge">0</span>
</button>

<!-- Chat Panel -->
<div id="cwPanel" role="dialog" aria-label="<?php echo addslashes(__('chat.open_chat')); ?> Aurora Hotel Plaza">
    <!-- Header -->
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
            title="<?php _e('chat.clear_history'); ?>">
            <span class="material-symbols-outlined" style="font-size:18px">refresh</span>
        </button>
    </div>

    <!-- Content based on login status -->
    <?php if ($is_logged): ?>
        <?php include __DIR__ . '/chat-widget-logged-in.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/chat-widget-guest.php'; ?>
    <?php endif; ?>
</div>

<!-- Chat Widget Scripts -->
<script>window.siteBase = '<?php echo $cw_base; ?>';</script>
<script src="<?php echo $bp; ?>assets/js/chat-widget.js?v=<?php echo time(); ?>" defer></script>
<script src="<?php echo $bp; ?>assets/js/common/chat-widget-init.js?v=<?php echo time(); ?>" defer></script>
