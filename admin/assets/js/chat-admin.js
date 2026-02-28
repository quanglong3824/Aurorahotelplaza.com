/**
 * ChatManager — Admin Chat JavaScript
 * File: admin/assets/js/chat-admin.js
 *
 * Quản lý:
 *  - SSE Global Stream (danh sách conv)
 *  - SSE Conversation Stream (tin nhắn active)
 *  - Gửi tin nhắn
 *  - Typing indicator
 *  - Quick Replies (/shortcut)
 *  - Sound notification
 */

const ChatManager = {

    // ── State ────────────────────────────────────────────────────────────────
    globalSSE:       null,
    convSSE:         null,
    activeConvId:    null,
    lastMsgId:       0,
    typingTimer:     null,
    isTyping:        false,
    soundEnabled:    true,
    quickReplies:    [],

    // ── URL helper — dùng siteBase inject bởi PHP ────────────────────────────
    _url(path) {
        const base = (window.siteBase || '').replace(/\/$/, '');
        return base + '/' + path.replace(/^\//, '');
    },

    // ── DOM refs (set sau khi DOMContentLoaded) ──────────────────────────────
    els: {},

    // ════════════════════════════════════════════════════════════════════════
    // INIT
    // ════════════════════════════════════════════════════════════════════════
    init() {
        this.els = {
            convList:      document.getElementById('chatConvList'),
            chatWindow:    document.getElementById('chatWindow'),
            msgContainer:  document.getElementById('chatMessages'),
            inputBox:      document.getElementById('chatInput'),
            sendBtn:       document.getElementById('chatSendBtn'),
            typingArea:    document.getElementById('chatTypingArea'),
            badge:         document.getElementById('chatUnreadBadge'),
            sidebarBadge:  document.getElementById('chatSidebarBadge'),
            quickPopup:    document.getElementById('quickReplyPopup'),
            internalToggle:document.getElementById('internalNoteToggle'),
            convSearch:    document.getElementById('convSearch'),
        };

        this.startGlobalStream();
        this.loadConversations();
        this.loadQuickReplies();
        this.bindEvents();
    },

    // ════════════════════════════════════════════════════════════════════════
    // SSE — GLOBAL STREAM
    // ════════════════════════════════════════════════════════════════════════
    startGlobalStream() {
        if (this.globalSSE) return; // Không mở 2 lần

        const connect = () => {
            this.globalSSE = new EventSource(this._url('api/chat/stream.php?type=global'));

            this.globalSSE.addEventListener('list_update', (e) => {
                const data = JSON.parse(e.data);
                this.onGlobalUpdate(data);
            });

            this.globalSSE.onerror = () => {
                this.globalSSE?.close();
                this.globalSSE = null;
                // Reconnect sau 5s
                setTimeout(() => connect(), 5000);
            };
        };

        connect();
    },

    onGlobalUpdate(data) {
        // Cập nhật badge tổng unread
        this.updateUnreadBadge(data.total_unread || 0);

        // Cập nhật danh sách conv nếu có thay đổi
        if (data.conversations?.length > 0) {
            this.patchConversationList(data.conversations);
        }

        // Toast khi có conv mới chưa assign
        if (data.unassigned > 0) {
            this.showUnassignedAlert(data.unassigned);
        }
    },

    // ════════════════════════════════════════════════════════════════════════
    // SSE — CONVERSATION STREAM
    // ════════════════════════════════════════════════════════════════════════
    openConversation(convId) {
        // Đóng SSE conv cũ
        if (this.convSSE) {
            this.convSSE.close();
            this.convSSE = null;
        }

        this.activeConvId = convId;
        this.lastMsgId    = 0;
        
        // Hiện spinner Clear DOM cũ tránh lỗi overlap UI trong lúc chờ Request fetch
        if (this.els.msgContainer) {
            this.els.msgContainer.innerHTML = '<div class="flex items-center justify-center p-8 w-full"><div class="w-6 h-6 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div></div>';
        }

        // Hiện chat window, ẩn placeholder
        document.getElementById('chatPlaceholder')?.classList.add('hidden');
        document.getElementById('chatWindowWrapper')?.classList.remove('hidden');
        document.getElementById('chatWindowWrapper')?.classList.add('flex');

        // Lấy data conv hiện tại từ DOM để update Header liền
        const convEl = document.querySelector(`[data-conv="${convId}"]`);
        if (convEl) {
            const name = convEl.querySelector('.font-semibold')?.textContent?.trim();
            if (typeof this.updateChatHeader === 'function') {
                this.updateChatHeader({
                    customer_name: name,
                    status: convEl.dataset.status || 'open',
                    staff_id: convEl.dataset.staffId || null,
                    booking_code: convEl.dataset.booking || null,
                });
            }
        }

        // Load lịch sử trước
        this.loadMessages(convId).then(() => {
            // Sau đó mở SSE để nhận realtime
            this.startConvStream(convId);
        });

        // Highlight conv đang active
        document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
        document.querySelector(`[data-conv="${convId}"]`)?.classList.add('active');

        // Mark read
        this.markRead(convId);
    },

    startConvStream(convId) {
        const connect = () => {
            if (this.activeConvId !== convId) return; // User đã chuyển sang conv khác

            this.convSSE = new EventSource(
                this._url(`api/chat/stream.php?type=conv&id=${convId}&last_id=${this.lastMsgId}`)
            );

            this.convSSE.addEventListener('message', (e) => {
                const msg = JSON.parse(e.data);
                if (Number(msg.message_id) > this.lastMsgId) {
                    this.lastMsgId = Number(msg.message_id);
                    if (document.querySelector(`[data-msg="${msg.message_id}"]`)) return;
                    this.appendMessage(msg);
                }
            });

            this.convSSE.addEventListener('typing', (e) => {
                const data = JSON.parse(e.data);
                this.showTypingIndicator(data.users);
            });

            this.convSSE.onerror = () => {
                this.convSSE?.close();
                this.convSSE = null;
                setTimeout(() => {
                    if (this.activeConvId === convId) connect();
                }, 3000);
            };
        };

        connect();
    },

    // ════════════════════════════════════════════════════════════════════════
    // LOAD DATA
    // ════════════════════════════════════════════════════════════════════════
    loadConversations(params = {}) {
        const qs = new URLSearchParams({ status: 'active', ...params }).toString();
        return fetch(this._url(`api/chat/get-conversations.php?${qs}`))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.renderConversationList(data.data);
                    this.updateStats(data.stats);
                }
            })
            .catch(console.error);
    },

    loadMessages(convId) {
        return fetch(this._url(`api/chat/get-messages.php?conversation_id=${convId}&limit=30`))
            .then(r => r.json())
            .then(data => {
                // Sửa lỗi tin nhắn của người trước nhảy vào box của người sau (Race Condition)
                if (this.activeConvId != convId) return;

                if (data.success) {
                    this.renderMessages(data.messages);
                    if (data.messages.length > 0) {
                        this.lastMsgId = Math.max(...data.messages.map(m => Number(m.message_id)));
                    }
                }
            })
            .catch(console.error);
    },

    loadQuickReplies() {
        fetch(this._url('admin/api/get-quick-replies.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success) this.quickReplies = data.data;
            });
    },

    // ════════════════════════════════════════════════════════════════════════
    // SEND MESSAGE
    // ════════════════════════════════════════════════════════════════════════
    sendMessage() {
        const msg         = this.els.inputBox?.value.trim();
        const isInternal  = this.els.internalToggle?.checked || false;

        if (!msg || !this.activeConvId) return;

        this.els.sendBtn.disabled = true;

        const tempId = 'pending_' + Date.now();

        // Optimistic UI: hiện ngay lên màn hình
        const optimistic = {
            message_id:  tempId, // temp id
            sender_type: 'staff',
            message:     msg,
            is_internal: isInternal,
            sender_name: 'Bạn',
            created_at:  new Date().toISOString(),
            pending:     true
        };
        this.appendMessage(optimistic);

        // Clear input ngay
        this.els.inputBox.value = '';
        this.hideQuickReplyPopup();

        // Gửi lên server
        fetch(this._url('api/chat/send-message.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: this.activeConvId,
                message:         msg,
                message_type:    'text',
                is_internal:     isInternal
            })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                // Rollback optimistic message
                document.querySelector(`[data-msg="${tempId}"]`)?.remove();
                this.showError('Gửi tin nhắn thất bại, vui lòng thử lại');
            } else {
                const tmpEl = document.querySelector(`[data-msg="${tempId}"]`);
                if (document.querySelector(`[data-msg="${data.message_id}"]`)) {
                    tmpEl?.remove();
                } else if (tmpEl) {
                    tmpEl.setAttribute('data-msg', data.message_id);
                    const bubble = tmpEl.querySelector('.bg-gradient-to-br');
                    if (bubble) bubble.classList.remove('opacity-75');
                    const timeEl = tmpEl.querySelector('.flex.items-center.justify-end');
                    if (timeEl) timeEl.innerHTML = timeEl.innerHTML.replace('⏳', '✓').replace('text-gray-400', 'text-green-500');
                }
            }
        })
        .catch(() => {
            document.querySelector(`[data-msg="${tempId}"]`)?.remove();
            this.showError('Mất kết nối, vui lòng thử lại');
        })
        .finally(() => {
            this.els.sendBtn.disabled = false;
            this.els.inputBox.focus();
        });
    },

    // ════════════════════════════════════════════════════════════════════════
    // TYPING
    // ════════════════════════════════════════════════════════════════════════
    onInputChange() {
        if (!this.activeConvId) return;

        // Quick reply popup
        const val = this.els.inputBox?.value || '';
        if (val.startsWith('/')) {
            this.showQuickReplyPopup(val.substring(1));
        } else {
            this.hideQuickReplyPopup();
        }

        // Typing indicator
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTyping(true);
        }

        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.isTyping = false;
            this.sendTyping(false);
        }, 2000); // Ngừng gõ 2s → báo stop typing
    },

    sendTyping(isTyping) {
        if (!this.activeConvId) return;
        fetch(this._url('api/chat/typing.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: this.activeConvId,
                is_typing: isTyping
            })
        }).catch(() => {});
    },

    showTypingIndicator(users) {
        if (!this.els.typingArea) return;
        if (!users || users.length === 0) {
            this.els.typingArea.innerHTML = '';
            return;
        }
        const names = users.map(u => u.user_type === 'customer' ? 'Khách' : 'Nhân viên').join(', ');
        this.els.typingArea.innerHTML = `
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 px-4 py-2">
                <div class="flex gap-1">
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
                <span>${names} đang gõ...</span>
            </div>
        `;
        // Auto-clear sau 5s nếu SSE không push stop
        clearTimeout(this._typingClear);
        this._typingClear = setTimeout(() => {
            if (this.els.typingArea) this.els.typingArea.innerHTML = '';
        }, 5000);
    },

    // ════════════════════════════════════════════════════════════════════════
    // QUICK REPLIES
    // ════════════════════════════════════════════════════════════════════════
    showQuickReplyPopup(query) {
        const matches = this.quickReplies.filter(r =>
            !query ||
            r.shortcut?.toLowerCase().includes(query.toLowerCase()) ||
            r.title?.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 6);

        if (matches.length === 0) {
            this.hideQuickReplyPopup();
            return;
        }

        this.els.quickPopup.innerHTML = matches.map(r => `
            <button class="quick-reply-item w-full text-left px-4 py-3 hover:bg-amber-50
                           dark:hover:bg-amber-900/20 border-b border-gray-100 dark:border-slate-700
                           last:border-0 transition-colors"
                    onclick="ChatManager.applyQuickReply(${r.reply_id})">
                <span class="text-xs font-mono text-amber-600 font-bold">${r.shortcut || ''}</span>
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">${r.title}</span>
            </button>
        `).join('');

        this.els.quickPopup.classList.remove('hidden');
    },

    hideQuickReplyPopup() {
        this.els.quickPopup?.classList.add('hidden');
    },

    applyQuickReply(replyId) {
        const reply = this.quickReplies.find(r => r.reply_id == replyId);
        if (!reply || !this.els.inputBox) return;
        this.els.inputBox.value = reply.content;
        this.hideQuickReplyPopup();
        this.els.inputBox.focus();
    },

    // ════════════════════════════════════════════════════════════════════════
    // RENDER
    // ════════════════════════════════════════════════════════════════════════
    renderConversationList(convs) {
        if (!this.els.convList) return;
        if (convs.length === 0) {
            this.els.convList.innerHTML = `
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <span class="material-symbols-outlined text-4xl mb-2 block">chat_bubble_outline</span>
                    <p>Không có cuộc trò chuyện nào</p>
                </div>
            `;
            return;
        }

        this.els.convList.innerHTML = convs.map(c => this.renderConvItem(c)).join('');

        // Cập nhật header nếu đang mở một conv có trong danh sách
        if (this.activeConvId && typeof this.updateChatHeader === 'function') {
            const activeConv = convs.find(c => c.conversation_id == this.activeConvId);
            if (activeConv) {
                this.updateChatHeader(activeConv);
            }
        }
    },

    renderConvItem(c) {
        const isUrgent   = !c.staff_id && c.status === 'open';
        const dotColor   = isUrgent ? 'bg-red-500 animate-pulse'
                         : c.status === 'assigned' ? 'bg-green-500' : 'bg-gray-400';
        const previewTxt = c.last_message_preview
                         ? c.last_message_preview.substring(0, 50) + (c.last_message_preview.length > 50 ? '...' : '')
                         : 'Cuộc trò chuyện mới';
        const timeAgo    = this.timeAgo(c.last_message_at);
        const unread     = parseInt(c.unread_staff) || 0;
        const isActive   = this.activeConvId === parseInt(c.conversation_id);

        return `
            <div class="conv-item ${isActive ? 'active' : ''} cursor-pointer px-4 py-3
                        border-b border-gray-100 dark:border-slate-800
                        hover:bg-amber-50/50 dark:hover:bg-slate-800
                        transition-all duration-150 relative"
                 data-conv="${c.conversation_id}"
                 onclick="ChatManager.openConversation(${c.conversation_id})">

                <div class="flex items-start gap-3">
                    <!-- Avatar placeholder -->
                    <div class="relative flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-600
                                    flex items-center justify-center text-white font-bold text-sm">
                            ${(c.customer_name || '?')[0].toUpperCase()}
                        </div>
                        <span class="absolute bottom-0 right-0 w-3 h-3 ${dotColor} rounded-full
                                     ring-2 ring-white dark:ring-slate-900"></span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                ${this.escHtml(c.customer_name || 'Khách vãng lai')}
                            </span>
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2">${timeAgo}</span>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                            ${this.escHtml(previewTxt)}
                        </p>

                        <div class="flex items-center justify-between mt-1">
                            <div class="flex items-center gap-1">
                                ${c.booking_code ? `
                                    <span class="text-xs text-amber-600 font-mono">
                                        ${c.booking_code.substring(0, 10)}...
                                    </span>` : ''}
                                ${isUrgent ? `
                                    <span class="text-xs text-red-500 font-bold">⚡ Chờ xử lý</span>
                                ` : ''}
                            </div>
                            ${unread > 0 ? `
                                <span class="bg-red-500 text-white text-xs font-bold
                                             px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                                    ${unread > 99 ? '99+' : unread}
                                </span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderMessages(msgs) {
        if (!this.els.msgContainer) return;
        this.els.msgContainer.innerHTML = msgs.map(m => this.renderBubble(m)).join('');
        this.scrollToBottom();
    },

    appendMessage(msg) {
        if (!this.els.msgContainer) return;
        const el = document.createElement('div');
        el.innerHTML = this.renderBubble(msg);
        this.els.msgContainer.appendChild(el.firstElementChild);
        this.scrollToBottom();

        // Sound
        if (msg.sender_type !== 'staff' && this.soundEnabled) {
            this.playNotifSound();
        }
    },

    renderBubble(msg) {
        const isStaff    = msg.sender_type === 'staff';
        const isSystem   = msg.sender_type === 'system';
        const isInternal = msg.is_internal == 1;
        const time       = msg.created_at ? new Date(msg.created_at).toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'}) : '';

        if (isSystem) {
            return `
                <div class="flex justify-center my-3" data-msg="${msg.message_id || ''}">
                    <span class="text-xs text-gray-400 bg-gray-100 dark:bg-slate-800
                                 px-3 py-1 rounded-full">${this.escHtml(msg.message)}</span>
                </div>`;
        }

        if (isInternal) {
            return `
                <div class="flex justify-start my-2 px-4" data-msg="${msg.message_id || ''}">
                    <div class="max-w-[75%] bg-amber-50 dark:bg-amber-900/20
                                border border-dashed border-amber-400 rounded-lg px-3 py-2">
                        <div class="flex items-center gap-1 mb-1">
                            <span class="material-symbols-outlined text-amber-500 text-xs">lock</span>
                            <span class="text-xs font-bold text-amber-600">Ghi chú nội bộ</span>
                        </div>
                        <p class="text-sm text-amber-900 dark:text-amber-200">${this.escHtml(msg.message)}</p>
                        <span class="text-xs text-amber-400 mt-1 block">${time}</span>
                    </div>
                </div>`;
        }

        if (isStaff) {
            return `
                <div class="flex justify-end my-2 px-4" data-msg="${msg.message_id || ''}">
                    <div class="max-w-[75%]">
                        <div class="bg-gradient-to-br from-amber-400 to-amber-600 text-white
                                    rounded-[18px_18px_4px_18px] px-4 py-2.5 shadow-sm
                                    ${msg.pending ? 'opacity-75' : ''}">
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">${this.escHtml(msg.message)}</p>
                        </div>
                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span class="text-xs text-gray-400">${msg.sender_name || 'Nhân viên'} · ${time}</span>
                            ${msg.pending ? '<span class="text-xs text-gray-400">⏳</span>' : '<span class="text-xs text-green-500">✓</span>'}
                        </div>
                    </div>
                </div>`;
        }

        if (msg.sender_type === 'bot') {
            let cleanMsg = msg.message
                .replace(/\[BOOK_NOW_BTN_SUCCESS:[^\]]+\]/gi, '\n*📌 Khách hàng vừa nhận mã xác nhận đặt phòng qua AI.*')
                .replace(/\[BOOK_NOW_BTN:[^\]]+\]/gi, '\n*📌 AI đã đưa ra phiếu thông tin đặt phòng tự động.*');
                
            return `
                <div class="flex justify-start my-2 px-4" data-msg="${msg.message_id || ''}">
                    <div class="max-w-[75%]">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-200
                                    border border-indigo-100 dark:border-indigo-800
                                    rounded-[18px_18px_18px_4px] px-4 py-2.5 shadow-sm">
                            <div class="flex items-center gap-1 mb-1 text-indigo-500">
                                <span class="material-symbols-outlined text-[14px]">psychology</span>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Aurora AI</span>
                            </div>
                            <p class="text-[13.5px] leading-relaxed whitespace-pre-wrap ${msg.pending ? 'opacity-75' : ''}">${this.escHtml(cleanMsg)}</p>
                        </div>
                        <span class="text-xs text-gray-400 mt-1 block pl-1">Aurora AI · ${time}</span>
                    </div>
                </div>`;
        }

        // Customer
        return `
            <div class="flex justify-start my-2 px-4" data-msg="${msg.message_id || ''}">
                <div class="max-w-[75%]">
                    <div class="bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200
                                rounded-[18px_18px_18px_4px] px-4 py-2.5 shadow-sm">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${this.escHtml(msg.message)}</p>
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block pl-1">${msg.sender_name || 'Khách'} · ${time}</span>
                </div>
            </div>`;
    },

    patchConversationList(updatedConvs) {
        // Xoá cục "Đang tải" nếu nó còn tồn tại khi load lần đầu qua SSE
        if (this.els.convList) {
            const loader = this.els.convList.querySelector('.animate-spin');
            if (loader) loader.parentElement.remove();
        }

        updatedConvs.forEach(conv => {
            const el = this.els.convList?.querySelector(`[data-conv="${conv.conversation_id}"]`);
            if (el) {
                // Update existing item
                el.outerHTML = this.renderConvItem(conv);
            } else {
                // New conversation — thêm vào đầu
                const newEl = document.createElement('div');
                newEl.innerHTML = this.renderConvItem(conv);
                this.els.convList?.prepend(newEl.firstElementChild);
            }

            // Cập nhật header nếu đang mở conv này
            if (this.activeConvId == conv.conversation_id) {
                if (typeof this.updateChatHeader === 'function') {
                    this.updateChatHeader(conv);
                }
            }
        });
    },

    updateStats(stats) {
        if (!stats) return;
        const el = document.getElementById('chatStatsBar');
        if (el) {
            el.innerHTML = `
                <span class="text-sm text-gray-500">
                    <span class="font-bold text-red-500">${stats.unassigned || 0}</span> chờ xử lý
                    · <span class="font-bold text-green-500">${stats.total_assigned || 0}</span> đang xử lý
                    · <span class="font-bold text-blue-500">${stats.total_unread || 0}</span> chưa đọc
                </span>`;
        }
    },

    // ════════════════════════════════════════════════════════════════════════
    // ACTIONS (Assign, Close, Claim...)
    // ════════════════════════════════════════════════════════════════════════
    claimConversation(convId) {
        fetch(this._url('admin/api/manage-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'claim', conversation_id: convId })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.showToast('Đã nhận xử lý cuộc trò chuyện', 'success');
                this.loadConversations();
                if (this.activeConvId == convId) {
                    this.loadMessages(convId);
                }
            } else {
                this.showToast(d.message || 'Lỗi', 'error');
            }
        });
    },

    closeConversation(convId) {
        if (!confirm('Đóng cuộc trò chuyện này?')) return;
        fetch(this._url('admin/api/manage-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'close', conversation_id: convId })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.showToast('Đã đóng cuộc trò chuyện', 'success');
                
                // Khóa input ngay tại giao diện nếu đang mở chính frame vừa đóng
                if (this.activeConvId == convId) {
                    const input = document.getElementById('chatInput');
                    const btn = document.getElementById('chatSendBtn');
                    if (input) { input.disabled = true; input.placeholder = "Cuộc trò chuyện đã bị đóng."; }
                    if (btn) btn.disabled = true;
                    const hdrStatus = document.getElementById('hdrStatusLabel');
                    if (hdrStatus) {
                        hdrStatus.textContent = 'Đã đóng';
                        hdrStatus.className = 'text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500';
                    }
                    const hdrDot = document.getElementById('hdrStatusDot');
                    if (hdrDot) hdrDot.className = 'absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900 bg-gray-400';
                    document.getElementById('moreActionsDropdown')?.classList.add('hidden');
                }
                
                this.loadConversations();
            }
        });
    },

    markRead(convId) {
        // SSE stream.php tự xử lý mark-read, không cần gọi thêm API
    },

    // ════════════════════════════════════════════════════════════════════════
    // UI HELPERS
    // ════════════════════════════════════════════════════════════════════════
    scrollToBottom() {
        const c = this.els.msgContainer;
        if (c) c.scrollTop = c.scrollHeight;
    },

    updateUnreadBadge(count) {
        const badges = [this.els.badge, this.els.sidebarBadge];
        badges.forEach(b => {
            if (!b) return;
            if (count > 0) {
                b.textContent = count > 99 ? '99+' : count;
                b.classList.remove('hidden');
            } else {
                b.classList.add('hidden');
            }
        });
        // Cập nhật title tab
        document.title = count > 0
            ? `(${count}) Tin nhắn - Aurora Hotel Plaza`
            : 'Tin nhắn - Aurora Hotel Plaza';
    },

    showUnassignedAlert(count) {
        // Chỉ show 1 lần mỗi 30s
        if (this._lastUnassignedAlert && Date.now() - this._lastUnassignedAlert < 30000) return;
        this._lastUnassignedAlert = Date.now();
        this.showToast(`⚡ ${count} khách đang chờ được xử lý`, 'warning');
    },

    showToast(msg, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            warning: 'bg-amber-500',
            error:   'bg-red-500',
            info:    'bg-blue-500',
        };
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 z-[9999] ${colors[type]} text-white
                           px-4 py-3 rounded-xl shadow-xl text-sm font-medium
                           flex items-center gap-2 transform translate-x-full
                           transition-transform duration-300`;
        toast.innerHTML = `<span>${msg}</span>`;
        document.body.appendChild(toast);

        requestAnimationFrame(() => toast.classList.remove('translate-x-full'));
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    showError(msg) { this.showToast(msg, 'error'); },

    playNotifSound() {
        if (!this.soundEnabled) return;
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(660, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (_) {}
    },

    timeAgo(datetime) {
        if (!datetime) return '';
        const diff = Math.floor((Date.now() - new Date(datetime)) / 1000);
        if (diff < 60)    return 'Vừa xong';
        if (diff < 3600)  return Math.floor(diff / 60) + 'p';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return new Date(datetime).toLocaleDateString('vi-VN');
    },

    escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    },

    // ════════════════════════════════════════════════════════════════════════
    // BIND EVENTS
    // ════════════════════════════════════════════════════════════════════════
    bindEvents() {
        // Gửi tin — Enter (không Shift)
        this.els.inputBox?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Typing
        this.els.inputBox?.addEventListener('input', () => this.onInputChange());

        // Send button
        this.els.sendBtn?.addEventListener('click', () => this.sendMessage());

        // Search conversation
        let searchTimer;
        this.els.convSearch?.addEventListener('input', (e) => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                this.loadConversations({ search: e.target.value });
            }, 300);
        });

        // Đóng quick reply khi click ra ngoài
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#quickReplyPopup') && !e.target.closest('#chatInput')) {
                this.hideQuickReplyPopup();
            }
        });

        // Tab visibility — tắt SSE khi ẩn để tiết kiệm connection
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Đóng conv SSE khi không nhìn vào (giữ global để nhận badge)
                this.convSSE?.close();
                this.convSSE = null;
            } else if (this.activeConvId) {
                // Mở lại khi quay lại
                this.startConvStream(this.activeConvId);
            }
        });
    }
};

// ── Auto init khi DOM sẵn sàng ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('chatConvList')) {
        ChatManager.init();
    }
});
