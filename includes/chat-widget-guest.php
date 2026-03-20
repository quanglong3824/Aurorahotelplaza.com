<!-- Messages container -->
<div id="cwMessages" style="flex:1;overflow-y:auto;">
    <!-- Render bởi ChatWidget.renderMessages() -->
    <div data-empty style="text-align:center;padding:32px 16px;color:#94a3b8">
        <div style="font-size:36px;margin-bottom:8px"></div>
        <p style="font-size:13px;line-height:1.6">
            <?php _e('chat.welcome', ['name' => '<strong>' . htmlspecialchars($user_name) . '</strong>']); ?><br>
            <?php _e('chat.ready_to_help'); ?>
        </p>
    </div>
</div>

<!-- Typing indicator -->
<div id="cwTyping"></div>

<!-- New message toast -->
<div id="cwNewMsgToast" class="cw-new-msg-toast">
    <?php _e('chat.new_message'); ?>
</div>

<!-- Offline bar -->
<div id="cwOfflineBar">
    <span class="material-symbols-outlined" style="font-size:14px">wifi_off</span>
    <?php _e('chat.offline_msg'); ?>
</div>

<!-- Input area -->
<div id="cwInputArea">
    <div id="cwInputRow">
        <textarea id="cwInput" rows="1" placeholder="<?php _e('chat.placeholder'); ?>"
            aria-label="<?php _e('chat.placeholder'); ?>"></textarea>
        <button id="cwSendBtn" aria-label="<?php _e('chat.send'); ?>">
            <span class="material-symbols-outlined" style="font-size:18px">send</span>
        </button>
    </div>
    <div id="cwInputHint"><?php _e('chat.hint'); ?></div>
</div>
