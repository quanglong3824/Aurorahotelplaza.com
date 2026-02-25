/**
 * chat-widget.js — Aurora Hotel Plaza
 * JavaScript riêng cho chat widget phía khách hàng
 * ─────────────────────────────────────────────────
 * Tách hoàn toàn khỏi ChatManager (admin).
 * Không phụ thuộc thư viện bên ngoài.
 */

const ChatWidget = {

    // ── Config ────────────────────────────────────────────────────────────
    convId:      null,
    lastMsgId:   0,
    sseConn:     null,
    typingTimer: null,
    isOpen:      false,
    isAtBottom:  true,
    unread:      0,

    // ── Init ──────────────────────────────────────────────────────────────
    init() {
        // Chỉ init nếu user đã đăng nhập (PHP render data-logged-in)
        const btn = document.getElementById('cwBtn');
        if (!btn) return;

        this.bindEvents();

        // Nếu đã đăng nhập, load conversations ngay
        if (btn.dataset.loggedIn === '1') {
            this.checkExistingConversation();
        }
    },

    // ── Toggle panel ──────────────────────────────────────────────────────
    toggle() {
        this.isOpen ? this.close() : this.open();
    },

    open() {
        this.isOpen = true;
        document.getElementById('cwPanel').classList.add('open');
        document.getElementById('cwBtn').classList.add('open');

        if (this.convId) {
            this.startSSE();
            this.markRead();
            this.scrollToBottom(true);
        }

        // Ẩn badge khi mở
        this.clearUnread();
    },

    close() {
        this.isOpen = false;
        document.getElementById('cwPanel').classList.remove('open');
        document.getElementById('cwBtn').classList.remove('open');

        // Đóng SSE khi thu widget (tiết kiệm connection)
        this.stopSSE();
    },

    // ── Check / Create conversation ───────────────────────────────────────
    checkExistingConversation() {
        fetch('/api/chat/get-conversations.php')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data?.length > 0) {
                    const conv = data.data[0]; // Conv mới nhất
                    this.convId = conv.conversation_id;

                    // Cập nhật unread badge
                    const unread = parseInt(conv.unread_customer) || 0;
                    if (unread > 0) this.setUnread(unread);

                    // Load messages nếu panel đang mở
                    if (this.isOpen) this.loadMessages();
                }
            })
            .catch(() => {});
    },

    createOrGetConversation(subject = 'Hỗ trợ khách hàng', bookingId = null) {
        const loadingEl = document.getElementById('cwMessages');
        if (loadingEl) {
            loadingEl.innerHTML = `
                <div class="flex items-center justify-center h-full gap-2 text-gray-400">
                    <div class="w-5 h-5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                    <span style="font-size:13px">Đang kết nối...</span>
                </div>`;
        }

        return fetch('/api/chat/create-conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, booking_id: bookingId, source: 'website' })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            this.convId = data.conversation_id;
            this.showChatArea();
            this.loadMessages();
            this.startSSE();
            return data.conversation_id;
        })
        .catch(err => {
            if (loadingEl) {
                loadingEl.innerHTML = `<div style="text-align:center;padding:24px;color:#ef4444;font-size:13px">
                    Không thể kết nối. Vui lòng thử lại.
                </div>`;
            }
        });
    },

    showChatArea() {
        const loginPrompt = document.getElementById('cwLoginPrompt');
        const chatArea    = document.getElementById('cwChatArea');
        loginPrompt?.classList.add('hidden');
        chatArea?.classList.remove('hidden');
        chatArea?.classList.add('flex');

        // Focus input
        setTimeout(() => document.getElementById('cwInput')?.focus(), 100);
    },

    // ── Load messages ─────────────────────────────────────────────────────
    loadMessages() {
        if (!this.convId) return;

        fetch(`/api/chat/get-messages.php?conversation_id=${this.convId}&limit=30`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.renderMessages(data.messages);
                if (data.messages.length > 0) {
                    this.lastMsgId = Math.max(...data.messages.map(m => +m.message_id));
                }
            })
            .catch(() => {});
    },

    // ── SSE ───────────────────────────────────────────────────────────────
    startSSE() {
        if (!this.convId || this.sseConn) return;

        const connect = () => {
            if (!this.isOpen) return; // Không kết nối nếu widget đóng
            this.sseConn = new EventSource(
                `/api/chat/stream.php?type=conv&id=${this.convId}&last_id=${this.lastMsgId}`
            );

            this.sseConn.addEventListener('message', (e) => {
                const msg = JSON.parse(e.data);
                if (+msg.message_id > this.lastMsgId) {
                    this.lastMsgId = +msg.message_id;
                    this.appendMessage(msg);
                }
            });

            this.sseConn.addEventListener('typing', (e) => {
                const data = JSON.parse(e.data);
                // Chỉ hiển thị typing của staff
                const hasStaffTyping = data.users?.some(u => u.user_type === 'staff');
                hasStaffTyping ? this.showTyping() : this.hideTyping();
            });

            this.sseConn.onerror = () => {
                this.sseConn?.close();
                this.sseConn = null;
                if (this.isOpen) {
                    setTimeout(() => connect(), 4000);
                }
            };
        };

        connect();
    },

    stopSSE() {
        this.sseConn?.close();
        this.sseConn = null;
    },

    // ── Send message ──────────────────────────────────────────────────────
    sendMessage() {
        if (!this.convId) {
            // Lần đầu gửi → tạo conversation rồi gửi
            this.createOrGetConversation().then(() => {
                this._doSend();
            });
            return;
        }
        this._doSend();
    },

    _doSend() {
        const input  = document.getElementById('cwInput');
        const sendBtn = document.getElementById('cwSendBtn');
        const msg    = input?.value.trim();
        if (!msg || !this.convId) return;

        sendBtn.disabled = true;

        // Optimistic UI
        this.appendMessage({
            message_id:  Date.now(),
            sender_type: 'customer',
            message:     msg,
            created_at:  new Date().toISOString(),
            pending:     true
        });
        input.value = '';
        input.style.height = 'auto';

        fetch('/api/chat/send-message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: this.convId,
                message: msg,
                message_type: 'text',
                is_internal: false
            })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                // Rollback
                const tmpEl = document.querySelector(`[data-pending="${msg.substring(0,20)}"]`);
                tmpEl?.remove();
            }
        })
        .catch(() => {})
        .finally(() => {
            sendBtn.disabled = false;
            input.focus();
        });
    },

    // ── Typing update ─────────────────────────────────────────────────────
    onInput() {
        const val = document.getElementById('cwInput')?.value || '';

        // Tạo conv nếu chưa có khi user bắt đầu gõ
        if (!this.convId && val.length === 1) {
            this.createOrGetConversation();
        }

        clearTimeout(this.typingTimer);
        this._sendTyping(true);

        this.typingTimer = setTimeout(() => {
            this._sendTyping(false);
        }, 2000);
    },

    _sendTyping(isTyping) {
        if (!this.convId) return;
        fetch('/api/chat/typing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId, is_typing: isTyping })
        }).catch(() => {});
    },

    // ── Mark read ────────────────────────────────────────────────────────
    markRead() {
        if (!this.convId) return;
        // SSE stream tự xử lý mark-read phía server
        // Chỉ cần reset badge phía client
        this.clearUnread();
    },

    // ── Render ────────────────────────────────────────────────────────────
    renderMessages(msgs) {
        const container = document.getElementById('cwMessages');
        if (!container) return;

        if (!msgs || msgs.length === 0) {
            container.innerHTML = `
                <div style="text-align:center;padding:32px 16px;color:#94a3b8">
                    <div style="font-size:36px;margin-bottom:8px">💬</div>
                    <p style="font-size:13px;line-height:1.6">
                        Xin chào! Chúng tôi sẵn sàng hỗ trợ bạn.<br>
                        Hãy gửi tin nhắn để bắt đầu.
                    </p>
                </div>`;
            return;
        }

        let lastDate = '';
        container.innerHTML = msgs.map(msg => {
            const msgDate = msg.created_at ? new Date(msg.created_at).toLocaleDateString('vi-VN') : '';
            let divider = '';
            if (msgDate !== lastDate) {
                lastDate = msgDate;
                const label = msgDate === new Date().toLocaleDateString('vi-VN') ? 'Hôm nay' : msgDate;
                divider = `<div class="cw-day-divider">${label}</div>`;
            }
            return divider + this.renderBubble(msg);
        }).join('');

        this.scrollToBottom(true);
    },

    appendMessage(msg) {
        const container = document.getElementById('cwMessages');
        if (!container) return;

        // Xóa empty state nếu có
        const emptyEl = container.querySelector('[data-empty]');
        emptyEl?.remove();

        const wrapper = document.createElement('div');
        wrapper.innerHTML = this.renderBubble(msg);
        container.appendChild(wrapper.firstElementChild);

        const wasAtBottom = this.isAtBottom;
        this.scrollToBottom();

        // Nếu widget đóng hoặc user đang cuộn lên → tăng unread + toast
        if (!this.isOpen && msg.sender_type !== 'customer') {
            this.setUnread(this.unread + 1);
        } else if (this.isOpen && !wasAtBottom && msg.sender_type !== 'customer') {
            this.showNewMsgToast();
        }
    },

    renderBubble(msg) {
        const isUser   = msg.sender_type === 'customer';
        const isSystem = msg.sender_type === 'system';

        if (isSystem) {
            return `<div class="cw-system-msg">${this.esc(msg.message)}</div>`;
        }

        const time  = msg.created_at
            ? new Date(msg.created_at).toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'})
            : '';
        const init  = isUser ? '' : 'NV';

        if (isUser) {
            return `
                <div class="cw-bubble-row user" ${msg.pending ? 'data-pending="'+this.esc(msg.message.substring(0,20))+'"' : ''}>
                    <div>
                        <div class="cw-bubble" style="${msg.pending ? 'opacity:.7' : ''}">
                            ${this.esc(msg.message)}
                        </div>
                        <div class="cw-bubble-time">${time} ${msg.pending ? '⏳' : '✓'}</div>
                    </div>
                </div>`;
        }

        return `
            <div class="cw-bubble-row staff">
                <div class="cw-staff-avatar-micro">${this.esc(init)}</div>
                <div>
                    <div class="cw-bubble">${this.esc(msg.message)}</div>
                    <div class="cw-bubble-time">${time}</div>
                </div>
            </div>`;
    },

    // ── Typing indicator ──────────────────────────────────────────────────
    showTyping() {
        const el = document.getElementById('cwTyping');
        if (!el) return;
        el.innerHTML = `
            <div class="cw-typing-dot"></div>
            <div class="cw-typing-dot"></div>
            <div class="cw-typing-dot"></div>
            <span style="font-size:11px;color:#94a3b8;margin-left:4px">Nhân viên đang gõ...</span>`;

        clearTimeout(this._typingClear);
        this._typingClear = setTimeout(() => this.hideTyping(), 5000);
    },

    hideTyping() {
        const el = document.getElementById('cwTyping');
        if (el) el.innerHTML = '';
    },

    // ── Scrolling ─────────────────────────────────────────────────────────
    scrollToBottom(instant = false) {
        const c = document.getElementById('cwMessages');
        if (!c) return;
        if (instant) {
            c.scrollTop = c.scrollHeight;
        } else {
            c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
        }
        this.isAtBottom = true;
        this.hideNewMsgToast();
    },

    onScroll() {
        const c = document.getElementById('cwMessages');
        if (!c) return;
        this.isAtBottom = c.scrollHeight - c.clientHeight - c.scrollTop < 60;
        if (this.isAtBottom) this.hideNewMsgToast();
    },

    // ── Unread badge ──────────────────────────────────────────────────────
    setUnread(count) {
        this.unread = count;
        const badge = document.getElementById('cwUnreadBadge');
        if (!badge) return;
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.toggle('show', count > 0);
        document.getElementById('cwBtn')?.classList.toggle('has-unread', count > 0);
    },

    clearUnread() {
        this.setUnread(0);
    },

    // ── New message toast (khi không ở cuối) ──────────────────────────────
    showNewMsgToast() {
        const toast = document.getElementById('cwNewMsgToast');
        toast?.classList.add('show');
    },

    hideNewMsgToast() {
        const toast = document.getElementById('cwNewMsgToast');
        toast?.classList.remove('show');
    },

    // ── Tab visibility ────────────────────────────────────────────────────
    onVisibilityChange() {
        if (!this.isOpen) return;
        if (document.hidden) {
            this.stopSSE();
        } else {
            this.startSSE();
        }
    },

    // ── Helpers ───────────────────────────────────────────────────────────
    esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/\n/g,'<br>');
    },

    // ── Event bindings ────────────────────────────────────────────────────
    bindEvents() {
        // Toggle button
        document.getElementById('cwBtn')
            ?.addEventListener('click', () => this.toggle());

        // Close button in panel header
        document.getElementById('cwCloseBtn')
            ?.addEventListener('click', () => this.close());

        // Input: typing + auto-resize
        const input = document.getElementById('cwInput');
        input?.addEventListener('input', () => {
            this.onInput();
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        });

        // Send: Enter (không Shift)
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Send button
        document.getElementById('cwSendBtn')
            ?.addEventListener('click', () => this.sendMessage());

        // Scroll detection
        document.getElementById('cwMessages')
            ?.addEventListener('scroll', () => this.onScroll(), { passive: true });

        // New msg toast scroll to bottom
        document.getElementById('cwNewMsgToast')
            ?.addEventListener('click', () => this.scrollToBottom());

        // Start conversation button (khi đăng nhập)
        document.getElementById('cwStartBtn')
            ?.addEventListener('click', () => {
                const subject = document.getElementById('cwSubjectInput')?.value.trim()
                              || 'Hỗ trợ khách hàng';
                this.createOrGetConversation(subject);
            });

        // Tab visibility
        document.addEventListener('visibilitychange',
            () => this.onVisibilityChange());

        // ESC đóng panel
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) this.close();
        });
    }
};

// ── Auto-init sau khi DOM sẵn sàng ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => ChatWidget.init());
