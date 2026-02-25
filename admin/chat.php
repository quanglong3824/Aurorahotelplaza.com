<?php
session_start();
$page_title = 'Tin nhắn';
$page_subtitle = 'Hỗ trợ & chat trực tuyến với khách hàng';
$current_page = 'chat';

require_once __DIR__ . '/includes/admin-header.php';

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'] ?? 'Nhân viên';
?>

<!-- Override padding từ admin-header <main p-8> -->
<style>
    /* ── Layout override ─────────────────────────────── */
    main {
        padding: 0 !important;
    }

    /* ── Chat Shell ──────────────────────────────────── */
    #chatShell {
        display: flex;
        height: calc(100vh - 81px);
        /* 81px = sticky header cao */
        overflow: hidden;
    }

    /* ── Cột trái: Conversation List ─────────────────── */
    #chatSidebar {
        width: 320px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e2e8f0;
        background: #fff;
        transition: width .25s ease;
    }

    .dark #chatSidebar {
        background: #0f172a;
        border-color: #1e293b;
    }

    /* ── Cột giữa: Chat Window ───────────────────────── */
    #chatMain {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #f8fafc;
    }

    .dark #chatMain {
        background: #0f172a;
    }

    /* ── Cột phải: Customer Info ─────────────────────── */
    #chatInfo {
        width: 272px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #e2e8f0;
        background: #fff;
        overflow-y: auto;
        transition: width .25s, opacity .25s;
    }

    .dark #chatInfo {
        background: #0f172a;
        border-color: #1e293b;
    }

    #chatInfo.collapsed {
        width: 0;
        opacity: 0;
        overflow: hidden;
    }

    /* ── Scrollbar đẹp ───────────────────────────────── */
    .chat-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .chat-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9px;
    }

    .dark .chat-scroll::-webkit-scrollbar-thumb {
        background: #334155;
    }

    /* ── Conv item active ────────────────────────────── */
    .conv-item.active {
        background: linear-gradient(135deg, rgba(212, 175, 55, .12), rgba(184, 148, 31, .08));
        border-left: 3px solid #d4af37;
    }

    .conv-item {
        border-left: 3px solid transparent;
    }

    /* ── Tabs ────────────────────────────────────────── */
    .chat-tab {
        flex: 1;
        padding: 8px 4px;
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        border-bottom: 2px solid transparent;
        transition: all .2s;
        text-align: center;
        cursor: pointer;
    }

    .chat-tab.active {
        color: #d4af37;
        border-bottom-color: #d4af37;
    }

    /* ── Tin nhắn bubble ─────────────────────────────── */
    #chatMessages {
        flex: 1;
        overflow-y: auto;
        padding: 16px 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    /* ── Input area ──────────────────────────────────── */
    #chatInputArea {
        border-top: 1px solid #e2e8f0;
        background: #fff;
        padding: 12px 16px;
        flex-shrink: 0;
    }

    .dark #chatInputArea {
        background: #0f172a;
        border-color: #1e293b;
    }

    /* ── Quick reply popup ───────────────────────────── */
    #quickReplyPopup {
        position: absolute;
        bottom: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 -8px 24px rgba(0, 0, 0, .1);
        z-index: 50;
        max-height: 240px;
        overflow-y: auto;
    }

    .dark #quickReplyPopup {
        background: #1e293b;
        border-color: #334155;
    }

    /* ── Typing dots ─────────────────────────────────── */
    @keyframes typingBounce {

        0%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-6px);
        }
    }

    .typing-dot {
        width: 7px;
        height: 7px;
        background: #94a3b8;
        border-radius: 50%;
        animation: typingBounce 1.2s ease-in-out infinite;
    }

    .typing-dot:nth-child(2) {
        animation-delay: .15s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: .30s;
    }

    /* ── Empty state ──────────────────────────────────── */
    #chatEmptyState {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
    }

    /* ── Placeholder khi chưa chọn conv ─────────────── */
    #chatPlaceholder {
        flex: 1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #94a3b8;
    }

    #chatPlaceholder:not(.hidden) {
        display: flex;
    }

    /* ── Urgency pulse ───────────────────────────────── */
    @keyframes urgentPulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .4;
        }
    }

    .urgent-dot {
        animation: urgentPulse 1.2s ease-in-out infinite;
    }

    /* ── Sound toggle ────────────────────────────────── */
    #soundToggle.muted .material-symbols-outlined {
        color: #94a3b8;
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════
     CHAT SHELL
══════════════════════════════════════════════════════════════════════ -->
<div id="chatShell">

    <!-- ── CỘT TRÁI: Conversation List ─────────────────────────────────── -->
    <aside id="chatSidebar">

        <!-- Search + Filters -->
        <div class="p-3 border-b border-gray-100 dark:border-slate-800 flex-shrink-0">
            <!-- Search -->
            <div class="relative mb-3">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                             text-gray-400 text-[18px]">search</span>
                <input id="convSearch" type="text" placeholder="Tìm tên, SĐT, booking..." class="w-full pl-9 pr-3 py-2 rounded-xl text-sm bg-gray-100
                              dark:bg-slate-800 border border-transparent
                              focus:outline-none focus:border-amber-400
                              dark:text-gray-200 transition-colors">
            </div>

            <!-- Stats bar -->
            <div id="chatStatsBar" class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 px-1">
                <span>Đang tải...</span>
            </div>

            <!-- Tabs -->
            <div class="flex mt-3 border-b border-gray-100 dark:border-slate-800">
                <button class="chat-tab active" data-tab="active" onclick="ChatManager.switchTab('active', this)">
                    Đang mở
                </button>
                <button class="chat-tab" data-tab="mine" onclick="ChatManager.switchTab('mine', this)">
                    Của tôi
                </button>
                <button class="chat-tab" data-tab="closed" onclick="ChatManager.switchTab('closed', this)">
                    Đã đóng
                </button>
                <button class="chat-tab" data-tab="all" onclick="ChatManager.switchTab('all', this)">
                    Tất cả
                </button>
            </div>
        </div>

        <!-- Conversations -->
        <div id="chatConvList" class="flex-1 overflow-y-auto chat-scroll">
            <!-- Render bởi ChatManager.renderConversationList() -->
            <div class="p-8 text-center text-gray-400">
                <div class="w-8 h-8 border-2 border-amber-400 border-t-transparent
                            rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm">Đang tải...</p>
            </div>
        </div>

        <!-- New unassigned alert -->
        <div id="unassignedAlert" class="hidden flex-shrink-0 mx-3 mb-3">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                        rounded-xl p-3 flex items-center gap-2">
                <span class="urgent-dot w-2.5 h-2.5 bg-red-500 rounded-full flex-shrink-0"></span>
                <p class="text-xs text-red-700 dark:text-red-300 font-medium" id="unassignedCount">
                    Có khách đang chờ
                </p>
            </div>
        </div>

    </aside>

    <!-- ── CỘT GIỮA: Chat Window ─────────────────────────────────────────── -->
    <section id="chatMain">

        <!-- Placeholder khi chưa chọn conv -->
        <div id="chatPlaceholder">
            <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-amber-900/30
                        flex items-center justify-center mb-2">
                <span class="material-symbols-outlined text-4xl text-amber-500">forum</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">Chọn cuộc hội thoại</h3>
            <p class="text-sm text-gray-400">Chọn một khách hàng bên trái để bắt đầu</p>
        </div>

        <!-- Active chat window (ẩn khi chưa chọn) -->
        <div id="chatWindowWrapper" class="hidden flex-col flex-1 min-h-0 h-full">

            <!-- Chat Header -->
            <div id="chatHeader" class="px-5 py-3.5 bg-white dark:bg-slate-900
                         border-b border-gray-200 dark:border-slate-800
                         flex items-center gap-3 flex-shrink-0 shadow-sm">

                <!-- Avatar -->
                <div class="relative flex-shrink-0">
                    <div id="hdrAvatar" class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-600
                                 flex items-center justify-center text-white font-bold text-base">
                        ?
                    </div>
                    <span id="hdrStatusDot" class="absolute bottom-0 right-0 w-3 h-3 bg-gray-400 rounded-full
                                 ring-2 ring-white dark:ring-slate-900"></span>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 id="hdrName" class="font-bold text-gray-900 dark:text-white text-sm truncate">—</h3>
                        <span id="hdrStatusLabel" class="text-xs px-2 py-0.5 rounded-full bg-gray-100
                                     dark:bg-slate-800 text-gray-500 dark:text-gray-400">
                            —
                        </span>
                    </div>
                    <p id="hdrSub" class="text-xs text-gray-400 truncate mt-0.5">—</p>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-1.5 flex-shrink-0">

                    <!-- Claim button (hiện khi chưa assign) -->
                    <button id="btnClaim" onclick="ChatManager.claimConversation(ChatManager.activeConvId)" class="hidden items-center gap-1 px-3 py-1.5 text-xs font-bold
                                   bg-amber-500 hover:bg-amber-600 text-white rounded-lg
                                   transition-colors">
                        <span class="material-symbols-outlined text-sm">hand_gesture</span>
                        Nhận xử lý
                    </button>

                    <!-- Assign (admin/receptionist) -->
                    <?php if (in_array($user_role, ['admin', 'receptionist'])): ?>
                        <div class="relative" id="assignDropdownWrapper">
                            <button onclick="toggleAssignDropdown()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800
                                       transition-colors text-gray-500 dark:text-gray-400" title="Gán cho nhân viên">
                                <span class="material-symbols-outlined text-[18px]">person_add</span>
                            </button>
                            <div id="assignDropdown" class="hidden absolute right-0 top-full mt-1 w-56 bg-white
                                    dark:bg-slate-800 rounded-xl shadow-2xl border
                                    border-gray-200 dark:border-slate-700 z-50 py-1">
                                <p class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                    Chọn nhân viên
                                </p>
                                <div id="staffList">
                                    <div class="px-4 py-3 text-xs text-gray-400">Đang tải...</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Info panel toggle -->
                    <button id="btnInfoToggle" onclick="ChatManager.toggleInfoPanel()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800
                                   transition-colors text-gray-500 dark:text-gray-400" title="Thông tin khách">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                    </button>

                    <!-- Sound -->
                    <button id="soundToggle" onclick="ChatManager.toggleSound()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800
                                   transition-colors" title="Bật/tắt âm thanh">
                        <span class="material-symbols-outlined text-[18px] text-amber-500">volume_up</span>
                    </button>

                    <!-- More actions -->
                    <div class="relative" id="moreActionsWrapper">
                        <button onclick="toggleMoreActions()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800
                                       transition-colors text-gray-500 dark:text-gray-400">
                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                        </button>
                        <div id="moreActionsDropdown" class="hidden absolute right-0 top-full mt-1 w-48 bg-white
                                    dark:bg-slate-800 rounded-xl shadow-2xl border
                                    border-gray-200 dark:border-slate-700 z-50 py-1">
                            <button onclick="ChatManager.closeConversation(ChatManager.activeConvId)" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm
                                           text-gray-700 dark:text-gray-300 hover:bg-gray-50
                                           dark:hover:bg-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-sm text-gray-400">check_circle</span>
                                Đóng hội thoại
                            </button>
                            <?php if (in_array($user_role, ['admin', 'receptionist'])): ?>
                                <button onclick="ChatManager.lockConversation(ChatManager.activeConvId)" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm
                                           text-gray-700 dark:text-gray-300 hover:bg-gray-50
                                           dark:hover:bg-slate-700 transition-colors">
                                    <span class="material-symbols-outlined text-sm text-gray-400">lock</span>
                                    Khoá hội thoại
                                </button>
                            <?php endif; ?>
                            <button onclick="ChatManager.loadMessages(ChatManager.activeConvId)" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm
                                           text-gray-700 dark:text-gray-300 hover:bg-gray-50
                                           dark:hover:bg-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-sm text-gray-400">refresh</span>
                                Làm mới
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatMessages" class="chat-scroll flex-1 overflow-y-auto px-2 py-4">
                <!-- Tin nhắn được render bởi JS -->
            </div>

            <!-- Typing Indicator -->
            <div id="chatTypingArea" class="px-4 h-8 flex-shrink-0 flex items-center"></div>

            <!-- ── INPUT AREA ─────────────────────────────────────────────── -->
            <div id="chatInputArea" class="flex-shrink-0">

                <!-- Internal note toggle -->
                <div class="flex items-center gap-3 mb-2">
                    <label class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" id="internalNoteToggle" class="w-3.5 h-3.5 accent-amber-500">
                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-amber-500">lock</span>
                            Ghi chú nội bộ
                        </span>
                    </label>
                    <span id="internalLabel" class="hidden text-xs text-amber-600 bg-amber-50 dark:bg-amber-900/20
                                 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-700">
                        Chỉ nhân viên thấy
                    </span>
                </div>

                <!-- Quick reply popup (absolute trên input) -->
                <div class="relative">
                    <div id="quickReplyPopup" class="hidden"></div>

                    <!-- Input row -->
                    <div id="inputWrapper" class="flex items-end gap-2 bg-gray-50 dark:bg-slate-800
                                 border border-gray-200 dark:border-slate-700
                                 rounded-xl px-3 py-2 transition-all
                                 focus-within:border-amber-400 focus-within:ring-1
                                 focus-within:ring-amber-400/30">

                        <textarea id="chatInput" rows="1" placeholder="Nhập tin nhắn hoặc gõ / để dùng mẫu nhanh..."
                            class="flex-1 bg-transparent resize-none outline-none
                                         text-sm text-gray-800 dark:text-gray-200
                                         placeholder-gray-400 dark:placeholder-gray-500
                                         max-h-32 overflow-y-auto leading-5" style="min-height:24px"></textarea>

                        <div class="flex items-center gap-1 flex-shrink-0 pb-0.5">
                            <!-- Gửi -->
                            <button id="chatSendBtn" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                           bg-gradient-to-r from-amber-400 to-amber-600
                                           hover:from-amber-500 hover:to-amber-700
                                           text-white text-sm font-bold
                                           transition-all shadow-sm hover:shadow-md
                                           disabled:opacity-50 disabled:cursor-not-allowed
                                           active:scale-95">
                                <span class="material-symbols-outlined text-sm">send</span>
                                Gửi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hint -->
                <p class="text-[11px] text-gray-400 mt-1.5 px-1">
                    Enter để gửi · Shift+Enter xuống dòng · / để chọn mẫu nhanh
                </p>
            </div>

        </div><!-- /chatWindowWrapper -->

    </section>

    <!-- ── CỘT PHẢI: Customer Info Panel ────────────────────────────────── -->
    <aside id="chatInfo" class="chat-scroll">
        <div class="p-4">

            <!-- Avatar + tên -->
            <div class="text-center pb-4 border-b border-gray-100 dark:border-slate-800">
                <div id="infoAvatar" class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 to-amber-600
                             flex items-center justify-center text-white font-bold text-xl mx-auto mb-2">
                    ?
                </div>
                <h4 id="infoName" class="font-bold text-gray-900 dark:text-white text-sm">—</h4>
                <p id="infoRole" class="text-xs text-gray-400 mt-0.5">—</p>
                <div id="infoTier" class="hidden mt-2">
                    <span class="text-xs px-2 py-1 bg-amber-100 text-amber-700
                                 dark:bg-amber-900/30 dark:text-amber-400 rounded-full font-bold"></span>
                </div>
            </div>

            <!-- Contact info -->
            <div class="py-4 border-b border-gray-100 dark:border-slate-800 space-y-2">
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Liên hệ</h5>
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-[16px] text-gray-400">phone</span>
                    <span id="infoPhone" class="text-gray-600 dark:text-gray-300">—</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-[16px] text-gray-400">mail</span>
                    <span id="infoEmail" class="text-gray-600 dark:text-gray-300 truncate">—</span>
                </div>
            </div>

            <!-- Booking hiện tại -->
            <div class="py-4 border-b border-gray-100 dark:border-slate-800">
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                    Đặt phòng liên quan
                </h5>
                <div id="infoBooking" class="text-xs text-gray-400">Không có</div>
            </div>

            <!-- Loyalty -->
            <div class="py-4 border-b border-gray-100 dark:border-slate-800">
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Hạng thành viên</h5>
                <div id="infoLoyalty" class="text-xs text-gray-400">—</div>
            </div>

            <!-- Staff xử lý -->
            <div class="py-4">
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Nhân viên phụ trách</h5>
                <div id="infoStaff" class="text-xs text-gray-400">Chưa gán</div>
            </div>

        </div>
    </aside>

</div><!-- /chatShell -->

<!-- ═══════════════════════════════
     SCRIPTS
═══════════════════════════════ -->
<script src="assets/js/chat-admin.js"></script>
<script>
    // ── Extend ChatManager với các logic bổ sung cho page này ───────────────────

    // Switch tab
    ChatManager.switchTab = function (tab, el) {
        document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');

        const params = {};
        if (tab === 'mine') { params.mine = 1; params.status = 'active'; }
        if (tab === 'closed') { params.status = 'closed'; }
        if (tab === 'all') { params.status = 'all'; }
        // 'active' = default

        ChatManager.loadConversations(params);
    };

    // Toggle info panel
    ChatManager.toggleInfoPanel = function () {
        const panel = document.getElementById('chatInfo');
        panel.classList.toggle('collapsed');
        const btn = document.getElementById('btnInfoToggle');
        btn.querySelector('span').textContent =
            panel.classList.contains('collapsed') ? 'info_outline' : 'info';
    };

    // Lock conversation
    ChatManager.lockConversation = function (convId) {
        fetch((window.siteBase || '') + '/admin/api/manage-conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'lock', conversation_id: convId })
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) ChatManager.showToast('Đã khoá hội thoại', 'success');
                document.getElementById('moreActionsDropdown')?.classList.add('hidden');
            });
    };

    // Cập nhật header khi chọn conv
    ChatManager.updateChatHeader = function (conv) {
        const name = conv.customer_name || 'Khách';
        const init = name[0]?.toUpperCase() || '?';
        const phone = conv.customer_phone || '';
        const booking = conv.booking_code ? `BK: ${conv.booking_code}` : '';

        document.getElementById('hdrAvatar').textContent = init;
        document.getElementById('hdrName').textContent = name;
        document.getElementById('hdrSub').textContent = [phone, booking].filter(Boolean).join(' · ') || conv.subject;
        document.getElementById('hdrStatusLabel').textContent =
            conv.status === 'open' ? 'Chờ xử lý' : conv.status === 'assigned' ? 'Đang xử lý' : 'Đã đóng';
        document.getElementById('hdrStatusLabel').className =
            `text-xs px-2 py-0.5 rounded-full ${conv.status === 'open' ? 'bg-red-100 text-red-600' :
                conv.status === 'assigned' ? 'bg-green-100 text-green-600' :
                    'bg-gray-100 text-gray-500'
            }`;

        // Claim button
        const canClaim = !conv.staff_id && conv.status === 'open';
        document.getElementById('btnClaim').classList.toggle('hidden', !canClaim);
        document.getElementById('btnClaim').classList.toggle('flex', canClaim);

        // Status dot colour
        const dot = document.getElementById('hdrStatusDot');
        dot.className = `absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900 ${conv.status === 'assigned' ? 'bg-green-500' :
            conv.status === 'open' ? 'bg-red-500 urgent-dot' : 'bg-gray-400'
            }`;

        // Cập nhật Input Form dựa vào Status
        const isClosed = conv.status === 'closed';
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('chatSendBtn');
        const internalToggle = document.getElementById('internalNoteToggle');
 if (isClosed) {
            if (input) { input.disabled = true; input.placeholder = "Cuộc trò chuyện đã bị đóng."; }
            if (sendBtn) sendBtn.disabled = true;
            if (internalToggle) internalToggle.disabled = true;
        } else {
            if (input) { input.disabled = false; input.placeholder = "Nhập tin nhắn hoặc gõ / để dùng mẫu nhanh..."; }
            if (sendBtn) sendBtn.disabled = false;
            if (internalToggle) internalToggle.disabled = false;
        }

        // Info panel
        ChatManager.updateInfoPanel(conv);
    };

    // Cập nhật info panel
    ChatManager.updateInfoPanel = function (conv) {
        document.getElementById('infoAvatar').textContent = (conv.customer_name || '?')[0].toUpperCase();
        document.getElementById('infoName').textContent = conv.customer_name || '—';
        document.getElementById('infoPhone').textContent = conv.customer_phone || '—';
        document.getElementById('infoEmail').textContent = conv.customer_email || '—';

        // Booking
        const bk = document.getElementById('infoBooking');
        if (conv.booking_code) {
            bk.innerHTML = `
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800
                        rounded-lg p-2.5 space-y-1">
                <div class="flex items-center gap-1 font-bold text-blue-700 dark:text-blue-400">
                    <span class="material-symbols-outlined text-[14px]">confirmation_number</span>
                    ${conv.booking_code}
                </div>
                ${conv.room_type ? `<div class="text-gray-500">${conv.room_type}</div>` : ''}
            </div>`;
        } else {
            bk.innerHTML = '<span class="text-gray-400">Không có booking liên quan</span>';
        }

        // Staff
        const staffEl = document.getElementById('infoStaff');
        if (conv.staff_name) {
            staffEl.innerHTML = `
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30
                            flex items-center justify-center text-amber-600 text-xs font-bold">
                    ${conv.staff_name[0]}
                </div>
                <span class="text-gray-700 dark:text-gray-300">${conv.staff_name}</span>
            </div>`;
        } else {
            staffEl.innerHTML = '<span class="text-gray-400">Chưa có nhân viên phụ trách</span>';
        }
    };

    // Override openConversation để cập nhật header
    const _origOpen = ChatManager.openConversation.bind(ChatManager);
    ChatManager.openConversation = function (convId) {
        // Lấy data conv hiện tại từ DOM
        const convEl = document.querySelector(`[data-conv="${convId}"]`);
        if (convEl) {
            // Parse data từ conv item để update header ngay (không đợi API)
            const name = convEl.querySelector('.font-semibold')?.textContent?.trim();
            const phone = '';
            ChatManager.updateChatHeader({
                customer_name: name,
                customer_phone: phone,
                status: convEl.dataset.status || 'open',
                staff_id: convEl.dataset.staffId || null,
                booking_code: convEl.dataset.booking || null,
            });
        }

        _origOpen(convId);
    };

    // Override updateStats
    ChatManager.updateStats = function (stats) {
        if (!stats) return;
        const el = document.getElementById('chatStatsBar');
        if (el) {
            el.innerHTML = `
            <div class="flex items-center gap-2 text-[11px]">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    <span class="text-gray-500">${stats.unassigned || 0} chờ</span>
                </span>
                <span class="text-gray-300">·</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-gray-500">${stats.total_assigned || 0} đang xử lý</span>
                </span>
                <span class="text-gray-300">·</span>
                <span class="font-bold text-amber-500">${stats.total_unread || 0} chưa đọc</span>
            </div>`;
        }

        // Cập nhật unassigned alert
        const alert = document.getElementById('unassignedAlert');
        const count = parseInt(stats.unassigned) || 0;
        if (count > 0) {
            alert?.classList.remove('hidden');
            document.getElementById('unassignedCount').textContent = `${count} khách đang chờ xử lý`;
        } else {
            alert?.classList.add('hidden');
        }
    };

    // Override showUnassignedAlert (không dùng toast nữa, dùng panel)
    ChatManager.showUnassignedAlert = function (count) { /* handled by updateStats */ };

    // Toggle sound
    ChatManager.toggleSound = function () {
        ChatManager.soundEnabled = !ChatManager.soundEnabled;
        const btn = document.getElementById('soundToggle');
        btn.querySelector('span').textContent = ChatManager.soundEnabled ? 'volume_up' : 'volume_off';
        btn.querySelector('span').className = `material-symbols-outlined text-[18px] ${ChatManager.soundEnabled ? 'text-amber-500' : 'text-gray-400'
            }`;
    };

    // ── Assign dropdown ────────────────────────────────────────────────────────────
    function toggleAssignDropdown() {
        const dd = document.getElementById('assignDropdown');
        const isHidden = dd.classList.contains('hidden');
        dd.classList.toggle('hidden');
        if (isHidden) loadStaffList();
    }

    function loadStaffList() {
        fetch((window.siteBase || '') + '/admin/api/get-staff-list.php')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('staffList');
                if (!data.success || !data.data?.length) {
                    list.innerHTML = '<div class="px-4 py-3 text-xs text-gray-400">Không có nhân viên online</div>';
                    return;
                }
                list.innerHTML = data.data.map(s => `
                <button onclick="assignToStaff(${s.user_id})"
                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm
                               text-gray-700 dark:text-gray-300 hover:bg-amber-50
                               dark:hover:bg-amber-900/20 transition-colors text-left">
                    <div class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30
                                flex items-center justify-center text-amber-600 text-xs font-bold">
                        ${s.full_name[0]}
                    </div>
                    <div>
                        <div class="font-medium text-xs">${s.full_name}</div>
                        <div class="text-[11px] text-gray-400">${s.load} đang xử lý</div>
                    </div>
                </button>`).join('');
            })
            .catch(() => { });
    }

    function assignToStaff(staffId) {
        fetch((window.siteBase || '') + '/admin/api/manage-conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'assign',
                conversation_id: ChatManager.activeConvId,
                staff_id: staffId
            })
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) ChatManager.showToast('Đã gán nhân viên thành công', 'success');
                document.getElementById('assignDropdown')?.classList.add('hidden');
            });
    }

    // ── More actions dropdown ──────────────────────────────────────────────────────
    function toggleMoreActions() {
        document.getElementById('moreActionsDropdown')?.classList.toggle('hidden');
    }

    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', e => {
        if (!e.target.closest('#assignDropdownWrapper'))
            document.getElementById('assignDropdown')?.classList.add('hidden');
        if (!e.target.closest('#moreActionsWrapper'))
            document.getElementById('moreActionsDropdown')?.classList.add('hidden');
    });

    // ── Internal note toggle ───────────────────────────────────────────────────────
    document.getElementById('internalNoteToggle')?.addEventListener('change', function () {
        const label = document.getElementById('internalLabel');
        const wrapper = document.getElementById('inputWrapper');
        const input = document.getElementById('chatInput');

        if (this.checked) {
            label?.classList.remove('hidden');
            wrapper?.classList.add('border-amber-400', 'bg-amber-50/50', 'dark:bg-amber-900/10');
            input.placeholder = 'Ghi chú nội bộ (chỉ nhân viên thấy)...';
        } else {
            label?.classList.add('hidden');
            wrapper?.classList.remove('border-amber-400', 'bg-amber-50/50', 'dark:bg-amber-900/10');
            input.placeholder = 'Nhập tin nhắn hoặc gõ / để dùng mẫu nhanh...';
        }
    });

    // ── Textarea auto-resize ───────────────────────────────────────────────────────
    document.getElementById('chatInput')?.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 128) + 'px';
    });

    // ── Keyboard shortcut: Escape đóng popup ──────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ChatManager.hideQuickReplyPopup();
            document.getElementById('assignDropdown')?.classList.add('hidden');
            document.getElementById('moreActionsDropdown')?.classList.add('hidden');
        }
    });

    // ── Thêm chat nav link vào sidebar (nếu chưa có) ──────────────────────────────
    // (Thêm vào admin-header.php sau này, cho giờ highlight current page bằng JS)
    document.querySelectorAll('.sidebar-link').forEach(l => {
        if (l.href?.includes('chat.php')) l.classList.add('active');
    });
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>