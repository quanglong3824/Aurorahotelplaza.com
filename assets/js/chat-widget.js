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
    staffOnline: false,
    _staffCheckInterval: null,

    // ── URL helper — dùng window.siteBase inject bởi PHP ─────────────────
    _url(path) {
        const base = (window.siteBase || '').replace(/\/$/, '');
        return base + '/' + path.replace(/^\//, '');
    },

    // ── Init ──────────────────────────────────────────────────────────────
    init() {
        // Chỉ init nếu user đã đăng nhập (PHP render data-logged-in)
        const btn = document.getElementById('cwBtn');
        if (!btn) return;

        this.bindEvents();
        
        // Kiểm tra nhân viên online ngay lập tức
        this.checkStaffOnline();
        // Kiểm tra lại mỗi 30 giây
        this._staffCheckInterval = setInterval(() => this.checkStaffOnline(), 30000);

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
        fetch(this._url('api/chat/get-conversations.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data?.length > 0) {
                    const conv = data.data[0]; // Conv mới nhất
                    this.convId = conv.conversation_id;

                    // Update UI if closed
                    this.updateConvStatus(conv.status);

                    // Cập nhật unread badge
                    const unread = parseInt(conv.unread_customer) || 0;
                    if (unread > 0) this.setUnread(unread);

            // Load messages nếu panel đang mở
                    if (this.isOpen) this.loadMessages();
                }
            })
            .catch(() => {});
    },

    updateConvStatus(status) {
        const inputRow = document.getElementById('cwInputRow');
        const hint = document.getElementById('cwInputHint');
        const cwInputArea = document.getElementById('cwInputArea');
        if (!inputRow || !hint || !cwInputArea) return;

        let restartBtn = document.getElementById('cwRestartBtn');
        if (restartBtn) restartBtn.remove();
        
        if (status === 'closed') {
            inputRow.style.display = 'none';
            hint.style.display = 'none';
            
            const btn = document.createElement('button');
            btn.id = 'cwRestartBtn';
            btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">play_arrow</span> Bắt đầu trò chuyện mới`;
            btn.style.cssText = `
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, var(--cw-gold) 0%, var(--cw-gold-dark) 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin-top: 10px;
                transition: transform 0.2s;
            `;
            btn.onmouseover = () => btn.style.transform = 'scale(1.02)';
            btn.onmouseout = () => btn.style.transform = 'scale(1)';
            btn.onmousedown = () => btn.style.transform = 'scale(0.95)';
            btn.onmouseup = () => btn.style.transform = 'scale(1.02)';
            btn.onclick = () => {
                this.reopenConversation();
            };
            cwInputArea.appendChild(btn);
        } else {
            inputRow.style.display = 'flex';
            hint.style.display = 'block';
        }
    },

    reopenConversation() {
        if (!this.convId) return;
        const restartBtn = document.getElementById('cwRestartBtn');
        if (restartBtn) restartBtn.style.opacity = '0.5';

        fetch(this._url('api/chat/reopen-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.updateConvStatus('open');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
                if (restartBtn) restartBtn.style.opacity = '1';
            }
        });
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

        return fetch(this._url('api/chat/create-conversation.php'), {
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
        const messages    = document.getElementById('cwMessages');
        const inputArea   = document.getElementById('cwInputArea');
        
        if (loginPrompt) loginPrompt.style.display = 'none';
        if (messages) messages.style.display = 'block';
        if (inputArea) inputArea.style.display = 'block';

        // Focus input
        setTimeout(() => document.getElementById('cwInput')?.focus(), 100);
    },

    // ── Load messages ─────────────────────────────────────────────────────
    loadMessages() {
        if (!this.convId) return;

        fetch(this._url(`api/chat/get-messages.php?conversation_id=${this.convId}&limit=30`))
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
                this._url(`api/chat/stream.php?type=conv&id=${this.convId}&last_id=${this.lastMsgId}`)
            );

            this.sseConn.addEventListener('message', (e) => {
                const msg = JSON.parse(e.data);
                if (+msg.message_id > this.lastMsgId) {
                    this.lastMsgId = +msg.message_id;
                    if (document.querySelector(`[data-msg-id="${msg.message_id}"]`)) return;
                    this.appendMessage(msg);
                }
            });

            this.sseConn.addEventListener('typing', (e) => {
                const data = JSON.parse(e.data);
                // Chỉ hiển thị typing của staff
                const hasStaffTyping = data.users?.some(u => u.user_type === 'staff');
                hasStaffTyping ? this.showTyping() : this.hideTyping();
            });

            this.sseConn.addEventListener('status_change', (e) => {
                const data = JSON.parse(e.data);
                this.updateConvStatus(data.status);
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

        const tempId = 'pending_' + Date.now();

        // Optimistic UI
        this.appendMessage({
            message_id:  tempId,
            sender_type: 'customer',
            message:     msg,
            created_at:  new Date().toISOString(),
            pending:     true
        });
        input.value = '';
        input.style.height = 'auto';

        fetch(this._url('api/chat/send-message.php'), {
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
                document.querySelector(`[data-msg-id="${tempId}"]`)?.remove();
            } else {
                const tmpEl = document.querySelector(`[data-msg-id="${tempId}"]`);
                if (document.querySelector(`[data-msg-id="${data.message_id}"]`)) {
                    tmpEl?.remove();
                } else if (tmpEl) {
                    tmpEl.setAttribute('data-msg-id', data.message_id);
                    const bubble = tmpEl.querySelector('.cw-bubble');
                    if (bubble) bubble.style.opacity = '1';
                    const timeEl = tmpEl.querySelector('.cw-bubble-time');
                    if (timeEl) timeEl.innerHTML = timeEl.innerHTML.replace('⏳', '✓');
                }
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
        fetch(this._url('api/chat/typing.php'), {
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
        const isBot    = msg.sender_type === 'bot';

        if (isSystem) {
            return `<div class="cw-system-msg" data-msg-id="${msg.message_id}">${this.esc(msg.message)}</div>`;
        }

        const time  = msg.created_at
            ? new Date(msg.created_at).toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'})
            : '';
            
        let init = '';
        if (isBot) {
            init = 'AI';
        } else if (!isUser) {
            init = 'NV';
        }

        // Parse booking UI if it's a bot message
        let contentHtml = this.esc(msg.message);
        let extraUiHtml = '';
        
        if (isBot) {
            const bookRegex = /\[BOOK_NOW_BTN:\s*slug=([^,\]]+),\s*name=([^,\]]+),\s*cin=([^,\]]+),\s*cout=([^\]]+)\]/i;
            const match = contentHtml.match(bookRegex);
            
            if (match) {
                const slug = match[1].trim();
                const name = match[2].trim();
                const cin = match[3].trim();
                const cout = match[4].trim();
                
                contentHtml = contentHtml.replace(match[0], '').trim();
                
                extraUiHtml = `
                    <div style="margin-top:12px; padding:12px; background:#fefce8; border:1px solid #fef08a; border-radius:10px;">
                        <div style="font-weight:bold; color:#854d0e; margin-bottom:8px; font-size:13px; display:flex; align-items:center; gap:4px;">
                           🎫 Xác nhận Đặt phòng Tự động
                        </div>
                        <div style="font-size:12px; color:#a16207; margin-bottom:4px;"><b>Loại phòng:</b> ${name}</div>
                        <div style="font-size:12px; color:#a16207; margin-bottom:12px;"><b>Ngày ở:</b> ${cin} - ${cout}</div>
                        <a href="/booking/index.php?room_type=${encodeURIComponent(slug)}&checkin=${encodeURIComponent(cin)}&checkout=${encodeURIComponent(cout)}&offline=1" target="_blank" 
                           style="display:block; text-align:center; padding:10px; background:linear-gradient(135deg, #eab308, #ca8a04); color:#fff; border-radius:6px; text-decoration:none; font-weight:bold; font-size:12px; box-shadow:0 2px 5px rgba(234, 179, 8, 0.3); transition:all 0.2s;">
                           NHẬN MÃ ĐẶT PHÒNG / QR CODE
                        </a>
                        <div style="font-size:10px; color:#c2410c; text-align:center; margin-top:8px; font-style:italic;">Hệ thống sẽ chuyển hướng để bạn lưu lại mã đặt phòng. Vui lòng đưa mã này tại Lễ tân khi Check-in!</div>
                    </div>
                `;
            }
        }

        if (isUser) {
            return `
                <div class="cw-bubble-row user" data-msg-id="${msg.message_id}">
                    <div>
                        <div class="cw-bubble" style="${msg.pending ? 'opacity:.7' : ''}">
                            ${contentHtml}
                        </div>
                        <div class="cw-bubble-time">${time} ${msg.pending ? '⏳' : '✓'}</div>
                    </div>
                </div>`;
        }

        return `
            <div class="cw-bubble-row staff" data-msg-id="${msg.message_id}">
                <div class="cw-staff-avatar-micro" ${isBot ? 'style="font-size:12px; font-weight:bold; color:#fff; background:linear-gradient(135deg, #4f46e5, #3b82f6);"' : ''}>${init}</div>
                <div>
                    ${isBot ? '<div style="font-size:11px; color:#4f46e5; font-weight:bold; margin-bottom:2px">Aurora AI</div>' : ''}
                    <div class="cw-bubble">${contentHtml}${extraUiHtml}</div>
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

    // ── Staff Online Status ──────────────────────────────────────────────
    checkStaffOnline() {
        fetch(this._url('api/chat/check-staff-online.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.staffOnline = data.is_online;
                    this.updateStaffStatusUI(data);
                }
            })
            .catch(() => {});
    },

    updateStaffStatusUI(data) {
        const dot = document.getElementById('cwOnlineDot');
        const text = document.getElementById('cwStatusText');
        const offlineBar = document.getElementById('cwOfflineBar');
        
        if (dot) {
            if (data.is_online) {
                dot.style.background = '#22c55e';
                dot.style.boxShadow = '0 0 6px rgba(34, 197, 94, 0.6)';
                dot.style.animation = 'none';
            } else {
                dot.style.background = '#94a3b8';
                dot.style.boxShadow = 'none';
                dot.style.animation = 'cwOfflinePulse 2s ease-in-out infinite';
            }
        }
        
        if (text) {
            text.textContent = data.status_text || 'Hỗ trợ trực tuyến';
        }
        
        // Hiện/ẩn offline bar
        if (offlineBar) {
            offlineBar.style.display = data.is_online ? 'none' : 'flex';
        }
    },

    // ── Event bindings ────────────────────────────────────────────────────
    bindEvents() {
        // Toggle button
        document.getElementById('cwBtn')
            ?.addEventListener('click', () => this.toggle());

        // Close button in panel header
        document.getElementById('cwCloseBtn')
            ?.addEventListener('click', () => this.close());

        // Reset AI button in panel header
        const resetBtn = document.getElementById('cwResetAiBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (!this.convId) return;
                if (!confirm('Bạn muốn xoá cuộc trò chuyện hiện tại để làm mới trí nhớ của AI?')) return;
                
                fetch(this._url('api/chat/reset-ai.php'), {
                    method: 'POST',
                    headers:{ 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversation_id: this.convId })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        const cwMessages = document.getElementById('cwMessages');
                        if (cwMessages) cwMessages.innerHTML = '';
                        this.lastMsgId = 0;
                        this.loadMessages(); // Will load empty text and welcome message
                    } else {
                        alert('Có lỗi xảy ra khi làm mới phiên AI.');
                    }
                });
            });
        }

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
