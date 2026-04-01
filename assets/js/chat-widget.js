/**
 * chat-widget.js — Aurora Hotel Plaza
 * JavaScript riêng cho chat widget phía khách hàng
 * ─────────────────────────────────────────────────
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
    _optimisticMsgs: new Set(), // Theo dõi các tin nhắn đang gửi để tránh trùng lặp

    // ── URL helper ────────────────────────────────────────────────────────
    _url(path) {
        const base = (window.siteBase || '').replace(/\/$/, '');
        return base + '/' + path.replace(/^\//, '');
    },

    // ── Init ──────────────────────────────────────────────────────────────
    init() {
        const btn = document.getElementById('cwBtn');
        if (!btn) return;

        this.bindEvents();
        this.checkStaffOnline();
        this._staffCheckInterval = setInterval(() => this.checkStaffOnline(), 30000);
        this.checkExistingConversation();
        this.initBookingBubble();
    },

    // ── Booking Bubble ────────────────────────────────────────────────────
    initBookingBubble() {
        const bubble = document.getElementById('cwBookingBubble');
        const closeBtn = document.getElementById('cwCloseBubble');
        const bookingBtn = document.getElementById('cwBookingBtn');
        if (!bubble || !closeBtn || !bookingBtn) return;

        bookingBtn.onclick = (e) => {
            e.stopPropagation();
            this.toggleBookingBubble();
        };

        closeBtn.onclick = (e) => {
            e.stopPropagation();
            this.hideBookingBubble();
            sessionStorage.setItem('cw_bubble_closed', '1');
        };

        this.loadSessionBookings();
    },

    loadSessionBookings() {
        fetch(this._url('api/chat/get-session-bookings.php'))
            .then(r => r.json())
            .then(data => {
                const bookingBtn = document.getElementById('cwBookingBtn');
                const bookingCount = document.getElementById('cwBookingCount');

                if (data.success && data.bookings?.length > 0) {
                    this.renderBookingBubble(data.bookings);
                    
                    // Hiện nút nổi
                    if (bookingBtn) {
                        bookingBtn.classList.add('show');
                        if (bookingCount) bookingCount.textContent = data.count || data.bookings.length;
                    }

                    // Tự động hiện bóng nổi lần đầu để gây chú ý
                    const isClosed = sessionStorage.getItem('cw_bubble_closed');
                    if (!isClosed) {
                        setTimeout(() => {
                            this.showBookingBubble();
                            bookingBtn.classList.add('cw-shake');
                            setTimeout(() => bookingBtn.classList.remove('cw-shake'), 1000);
                        }, 1500);
                    }
                } else {
                    if (bookingBtn) bookingBtn.classList.remove('show');
                    const list = document.getElementById('cwBookingList');
                    if (list) list.innerHTML = '<div class="cw-bb-empty">Không có dữ liệu đặt phòng gần đây.</div>';
                }
            }).catch(() => {});
    },

    renderBookingBubble(bookings) {
        const list = document.getElementById('cwBookingList');
        if (!list) return;

        list.innerHTML = bookings.map(b => `
            <div class="cw-bb-item" onclick="window.location.href='${this._url('booking/confirmation.php?booking_code=' + b.code)}'">
                <div class="cw-bb-item-content">
                    <div class="cw-bb-room">${b.room}</div>
                    <div class="cw-bb-meta">
                        <span class="cw-bb-status" style="background:${b.status_color}20; color:${b.status_color}">${b.status_label}</span>
                        <span style="font-size:10px;">${b.check_in}</span>
                    </div>
                    <div style="font-size:9px; color:#94a3b8; margin-top:4px; font-family:monospace;">Mã: ${b.code}</div>
                </div>
                <div class="cw-bb-qr">
                    <img src="${this._url('api/chat/get-booking-qr.php?code=' + b.code)}" alt="QR ${b.short_code}">
                </div>
            </div>
        `).join('');
    },

    toggleBookingBubble() {
        const bubble = document.getElementById('cwBookingBubble');
        if (bubble) {
            if (bubble.classList.contains('show')) {
                this.hideBookingBubble();
            } else {
                this.showBookingBubble();
            }
        }
    },

    showBookingBubble() {
        const bubble = document.getElementById('cwBookingBubble');
        if (bubble && !this.isOpen) {
            bubble.style.display = 'flex';
            setTimeout(() => bubble.classList.add('show'), 10);
        }
    },

    hideBookingBubble() {
        const bubble = document.getElementById('cwBookingBubble');
        if (bubble) {
            bubble.classList.remove('show');
            setTimeout(() => bubble.style.display = 'none', 300);
        }
    },

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.hideBookingBubble(); 
            this.open();
        }
    },

    open() {
        this.isOpen = true;
        document.getElementById('cwPanel')?.classList.add('open');
        document.getElementById('cwBtn')?.classList.add('open');
        if (this.convId) {
            this.startSSE();
            if (typeof ChatWidget.markRead === 'function') {
                ChatWidget.markRead();
            }
            this.scrollToBottom(true);
        }
        this.clearUnread();
    },

    close() {
        this.isOpen = false;
        document.getElementById('cwPanel').classList.remove('open');
        document.getElementById('cwBtn').classList.remove('open');
        this.stopSSE();
    },

    checkExistingConversation() {
        fetch(this._url('api/chat/get-conversations.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data?.length > 0) {
                    const conv = data.data[0];
                    this.convId = conv.conversation_id;
                    this.updateConvStatus(conv.status);
                    const unread = parseInt(conv.unread_customer) || 0;
                    if (unread > 0) this.setUnread(unread);
                    if (this.isOpen) this.loadMessages();
                } else if (this.isOpen) {
                    this.createConversation();
                }
            }).catch(() => {});
    },

    createConversation() {
        if (this.convId) return;
        const guestId = this.getGuestId();
        fetch(this._url('api/chat/create-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ guest_id: guestId })
        }).then(r => r.json()).then(data => {
            if (data.success && data.conversation_id) {
                this.convId = data.conversation_id;
                this.loadMessages();
            }
        });
    },

    getGuestId() {
        const name = 'chat_guest_id=';
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
        }
        return null;
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
            btn.className = 'cw-btn-restart';
            btn.onclick = () => this.reopenConversation();
            cwInputArea.appendChild(btn);
        } else {
            inputRow.style.display = 'flex';
            hint.style.display = 'block';
        }
    },

    reopenConversation() {
        if (!this.convId) return;
        fetch(this._url('api/chat/reopen-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId })
        }).then(r => r.json()).then(data => {
            if (data.success) this.updateConvStatus('open');
        });
    },

    createOrGetConversation(subject = 'Hỗ trợ khách hàng', bookingId = null) {
        return fetch(this._url('api/chat/create-conversation.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, booking_id: bookingId, source: 'website' })
        }).then(r => r.json()).then(data => {
            if (!data.success) throw new Error(data.message);
            this.convId = data.conversation_id;
            this.showChatArea();
            this.loadMessages();
            this.startSSE();
            return data.conversation_id;
        });
    },

    showChatArea() {
        const loginPrompt = document.getElementById('cwLoginPrompt');
        const messages    = document.getElementById('cwMessages');
        const inputArea   = document.getElementById('cwInputArea');
        if (loginPrompt) loginPrompt.style.display = 'none';
        if (messages) messages.style.display = 'block';
        if (inputArea) inputArea.style.display = 'block';
        setTimeout(() => document.getElementById('cwInput')?.focus(), 100);
    },

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
            });
    },

    startSSE() {
        if (!this.convId || this.sseConn) return;
        const connect = () => {
            if (!this.isOpen) return;
            this.sseConn = new EventSource(this._url(`api/chat/stream.php?type=conv&id=${this.convId}&last_id=${this.lastMsgId}`));
            this.sseConn.addEventListener('message', (e) => {
                const msg = JSON.parse(e.data);
                if (+msg.message_id > this.lastMsgId) {
                    this.lastMsgId = +msg.message_id;
                    
                    // Chống trùng lặp tin nhắn khách (Kiểm tra tin nhắn pending cùng nội dung)
                    if (msg.sender_type === 'customer') {
                        const pendingMsg = document.querySelector(`.cw-bubble-row.user[data-msg-id^="pending_"]`);
                        if (pendingMsg && pendingMsg.innerText.includes(msg.message)) {
                            pendingMsg.setAttribute('data-msg-id', msg.message_id);
                            pendingMsg.querySelector('.cw-bubble').style.opacity = '1';
                            const timeEl = pendingMsg.querySelector('.cw-bubble-time');
                            if (timeEl) timeEl.innerHTML = timeEl.innerHTML.replace('⏳', '✓');
                            return;
                        }
                    }

                    if (!document.querySelector(`[data-msg-id="${msg.message_id}"]`)) {
                        const activeStream = document.querySelector('[data-msg-id^="stream_"]');
                        if (msg.sender_type === 'bot' && activeStream) activeStream.remove();
                        this.appendMessage(msg);
                    }
                }
            });
            this.sseConn.addEventListener('typing', (e) => {
                const data = JSON.parse(e.data);
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
                if (this.isOpen) setTimeout(() => connect(), 4000);
            };
        };
        connect();
    },

    stopSSE() {
        this.sseConn?.close();
        this.sseConn = null;
    },

    sendMessage() {
        if (this.isSending) return;
        if (!this.convId) {
            this.isSending = true;
            this.createOrGetConversation().then(() => {
                this.isSending = false;
                this._doSend();
            }).catch(() => { this.isSending = false; });
            return;
        }
        this._doSend();
    },

    _doSend() {
        if (this.isSending) return;
        const input = document.getElementById('cwInput');
        const sendBtn = document.getElementById('cwSendBtn');
        const msg = input?.value.trim();
        if (!msg || !this.convId) return;

        this.isSending = true;
        sendBtn.disabled = true;
        const tempId = 'pending_' + Date.now();

        this.appendMessage({
            message_id: tempId,
            sender_type: 'customer',
            message: msg,
            created_at: new Date().toISOString(),
            pending: true
        });
        input.value = '';
        input.style.height = 'auto';

        fetch(this._url('api/chat/send-message.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId, message: msg, message_type: 'text' })
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                document.querySelector(`[data-msg-id="${tempId}"]`)?.remove();
            } else {
                const tmpEl = document.querySelector(`[data-msg-id="${tempId}"]`);
                if (tmpEl) {
                    tmpEl.setAttribute('data-msg-id', data.message_id);
                    const bubble = tmpEl.querySelector('.cw-bubble');
                    if (bubble) bubble.style.opacity = '1';
                    const timeEl = tmpEl.querySelector('.cw-bubble-time');
                    if (timeEl) timeEl.innerHTML = timeEl.innerHTML.replace('⏳', '✓');
                }
                this.triggerAiStream(data.message_id);
            }
        }).finally(() => {
            this.isSending = false;
            sendBtn.disabled = false;
            input.focus();
        });
    },

    triggerAiStream(userMsgId) {
        if (!this.convId) return;
        const streamId = 'stream_' + Date.now();
        const container = document.getElementById('cwMessages');
        if (!container) return;

        const wrapper = document.createElement('div');
        // Lúc đầu render bubble với nội dung là loading animation để tránh "box trắng nhỏ"
        wrapper.innerHTML = this.renderBubble({
            message_id: streamId,
            sender_type: 'bot',
            message: '<div class="cw-stream-loading"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>',
            created_at: new Date().toISOString()
        });
        container.appendChild(wrapper.firstElementChild);
        this.scrollToBottom();

        const bubbleEl = document.querySelector(`[data-msg-id="${streamId}"] .cw-bubble`);
        let fullText = "";
        let hasContent = false;
        const aiStreamSource = new EventSource(this._url(`api/chat/ai-stream.php?conversation_id=${this.convId}&user_message_id=${userMsgId}`));

        aiStreamSource.onmessage = (e) => {
            try {
                const data = JSON.parse(e.data);

                if (data.error) {
                    aiStreamSource.close();
                    bubbleEl.innerHTML = `<i>Lỗi: ${data.error}</i>`;
                    this.scrollToBottom();
                    return;
                }
                
                if (data.done) {
                    aiStreamSource.close();
                    if (data.message_id) {
                        const streamEl = document.querySelector(`[data-msg-id="${streamId}"]`);
                        if (streamEl) {
                            streamEl.setAttribute('data-msg-id', data.message_id);
                            const finalParsed = this.parseAiContent(fullText, data.message_id, true);
                            bubbleEl.innerHTML = finalParsed.html + finalParsed.extra;
                        }
                    }
                    return;
                }

                if (data.text) {
                    if (!hasContent) {
                        hasContent = true;
                        bubbleEl.innerHTML = ''; // Xóa loading animation khi có text đầu tiên
                    }
                    fullText += data.text;
                    const parsed = this.parseAiContent(fullText, streamId, true);
                    bubbleEl.innerHTML = parsed.html + parsed.extra;
                    this.scrollToBottom();
                }
                if (data.status === 'running_tool') {
                    this.showTyping(`Aurora đang tìm kiếm thông tin...`);
                } else if (data.status === 'switching') {
                    this.showTyping(data.message || `Đang chuyển đổi AI...`);
                } else {
                    this.hideTyping();
                }
            } catch (err) {}
        };
        aiStreamSource.onerror = () => {
            aiStreamSource.close();
            if (!fullText) bubbleEl.innerHTML = "<i>Dạ Aurora đang gặp chút sự cố kết nối, Anh/Chị vui lòng thử lại sau ít phút hoặc gọi Hotline nhé!</i>";
        };
    },

    renderMessages(msgs) {
        const container = document.getElementById('cwMessages');
        if (!container) return;
        if (!msgs || msgs.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:32px;color:#94a3b8">Xin chào! Chúng tôi sẵn sàng hỗ trợ bạn.</div>';
            return;
        }
        let lastDate = '';
        container.innerHTML = msgs.map(msg => {
            const msgDate = msg.created_at ? new Date(msg.created_at).toLocaleDateString('vi-VN') : '';
            let divider = '';
            if (msgDate !== lastDate) {
                lastDate = msgDate;
                divider = `<div class="cw-day-divider">${msgDate === new Date().toLocaleDateString('vi-VN') ? 'Hôm nay' : msgDate}</div>`;
            }
            return divider + this.renderBubble(msg);
        }).join('');
        this.scrollToBottom(true);
    },

    appendMessage(msg) {
        const container = document.getElementById('cwMessages');
        if (!container) return;
        const emptyEl = container.querySelector('[data-empty]');
        if (emptyEl) emptyEl.remove();
        const wrapper = document.createElement('div');
        wrapper.innerHTML = this.renderBubble(msg);
        container.appendChild(wrapper.firstElementChild);
        const wasAtBottom = this.isAtBottom;
        this.scrollToBottom();
        if (!this.isOpen && msg.sender_type !== 'customer') this.setUnread(this.unread + 1);
        else if (this.isOpen && !wasAtBottom && msg.sender_type !== 'customer') this.showNewMsgToast();
    },

    renderBubble(msg) {
        const isUser   = msg.sender_type === 'customer';
        const isSystem = msg.sender_type === 'system';
        const isBot    = msg.sender_type === 'bot' || msg.sender_role === 'bot';

        if (isSystem) return `<div class="cw-system-msg" data-msg-id="${msg.message_id}">${this.esc(msg.message)}</div>`;

        const time = msg.created_at ? new Date(msg.created_at).toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'}) : '';
        const init = isBot ? 'AI' : (isUser ? '' : 'NV');
        
        // Chỉ parse nội dung nếu không phải là loading animation (đã có thẻ HTML)
        let parsed = { html: msg.message, extra: '' };
        if (!msg.message.includes('cw-stream-loading')) {
            parsed = this.parseAiContent(msg.message, msg.message_id, isBot);
        }

        if (isUser) {
            return `
                <div class="cw-bubble-row user" data-msg-id="${msg.message_id}">
                    <div>
                        <div class="cw-bubble" style="${msg.pending ? 'opacity:.7' : ''}">${parsed.html}</div>
                        <div class="cw-bubble-time">${time} ${msg.pending ? '⏳' : '✓'}</div>
                    </div>
                </div>`;
        }

        return `
            <div class="cw-bubble-row staff" data-msg-id="${msg.message_id}">
                <div class="cw-staff-avatar-micro" ${isBot ? 'style="background:linear-gradient(135deg, #4f46e5, #3b82f6); color:#fff;"' : ''}>${init}</div>
                <div>
                    ${isBot ? '<div style="font-size:10px; color:#4f46e5; font-weight:bold; margin-bottom:2px; margin-left:4px;">Aurora AI</div>' : ''}
                    <div class="cw-bubble">${parsed.html}${parsed.extra}</div>
                    <div class="cw-bubble-time">${time}</div>
                </div>
            </div>`;
    },

    parseAiContent(text, msgId = 0, isBot = true) {
        if (!text) return { html: '', extra: '' };
        let html = this.esc(text);
        let extra = '';

        // Markdown images
        html = html.replace(/!\[([^\]]+)\]\(([^)]+)\)/g, (match, alt, url) => {
            return `<div class="cw-msg-image-container" style="margin-top:8px; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;">
                <img src="${url}" alt="${alt}" style="width:100%; height:auto; display:block; object-fit:cover; max-height:200px;">
                <div style="font-size:10px; color:#64748b; background:#f8fafc; padding:4px 8px; text-align:center;">${alt}</div>
            </div>`;
        });

        // Bold
        html = html.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

        if (isBot) {
            // Success booking button
            const successRegex = /\[BOOK_NOW_BTN_SUCCESS:\s*booking_code=([^,\]]+),\s*booking_id=([^\]]+)\]/i;
            const successMatch = html.match(successRegex);
            if (successMatch) {
                const booking_code = successMatch[1].trim();
                const booking_id = successMatch[2].trim();
                html = html.replace(successMatch[0], '').trim();
                extra += `<div class="cw-ai-card success" style="margin-top:12px; padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; text-align:center;">
                    <div style="color:#16a34a; font-weight:bold; margin-bottom:8px; font-size:14px;">✅ ĐẶT PHÒNG THÀNH CÔNG</div>
                    <div style="font-size:16px; font-weight:bold; color:#ca8a04; margin-bottom:12px; letter-spacing:1px;">Mã: ${booking_code}</div>
                    <a href="${this._url('profile/view-qrcode.php?id=' + booking_id)}" target="_blank" class="cw-btn-primary" style="display:inline-block; background:linear-gradient(135deg, #16a34a, #15803d); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:bold;">MỞ XEM QR CODE</a>
                </div>`;
            }

            // Confirm booking button
            const bookRegex = /\[BOOK_NOW_BTN:\s*slug=([^,\]]+),\s*name=([^,\]]+),\s*cin=([^,\]]+),\s*cout=([^\]]+)\]/i;
            const match = html.match(bookRegex);
            if (match) {
                const slug = match[1].trim();
                const name = match[2].trim();
                const cin = match[3].trim();
                const cout = match[4].trim();
                html = html.replace(match[0], '').trim();
                extra += `<div class="cw-ai-card confirm" style="margin-top:12px; padding:12px; background:#fffbeb; border:1px solid #fef08a; border-radius:10px;">
                    <div style="font-weight:bold; color:#854d0e; margin-bottom:8px; font-size:13px;">🎫 Xác nhận Đặt phòng</div>
                    <div style="font-size:12px; color:#a16207; margin-bottom:4px;"><b>Loại:</b> ${name}</div>
                    <div style="font-size:12px; color:#a16207; margin-bottom:12px;"><b>Ngày:</b> ${cin} - ${cout}</div>
                    <button onclick="ChatWidget.confirmAiBooking('${slug}', '${cin}', '${cout}', ${msgId}, this)" style="display:block; width:100%; border:none; cursor:pointer; padding:10px; background:linear-gradient(135deg, #eab308, #ca8a04); color:#fff; border-radius:6px; font-weight:bold; font-size:12px;">XÁC NHẬN ĐẶT PHÒNG</button>
                </div>`;
            }

            // View QR
            const qrRegex = /\[VIEW_QR_BTN:\s*code=([^,\]]+),\s*id=([^\]]+)\]/i;
            const qrMatch = html.match(qrRegex);
            if (qrMatch) {
                const qrcode = qrMatch[1].trim();
                const qrid = qrMatch[2].trim();
                const qrSrc = this._url(`api/chat/get-booking-qr.php?code=${encodeURIComponent(qrcode)}`);
                const dlUrl = this._url(`profile/api/download-qrcode.php?code=${encodeURIComponent(qrcode)}`);
                const viewUrl = this._url(`profile/view-qrcode.php?id=${qrid}`);
                html = html.replace(qrMatch[0], '').trim();
                extra += `<div class="cw-ai-card qr-inline" style="margin-top:10px; padding:14px 12px; background:linear-gradient(135deg,#eff6ff,#f0fdf4); border:1.5px solid #bfdbfe; border-radius:12px; text-align:center;">
                    <div style="font-size:11px; font-weight:700; color:#1e40af; letter-spacing:.05em; margin-bottom:6px;">🎫 MÃ ĐẶT PHÒNG</div>
                    <div style="font-size:18px; font-weight:900; color:#1e293b; letter-spacing:.08em; margin-bottom:10px;">${qrcode}</div>
                    <div style="display:inline-block; background:#fff; padding:8px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.12); margin-bottom:10px;">
                        <img src="${qrSrc}" alt="QR ${qrcode}" width="160" height="160" style="display:block; border-radius:6px;"/>
                    </div>
                    <div style="display:flex; gap:8px; justify-content:center;">
                        <a href="${dlUrl}" download="QR-${qrcode}.png" style="flex:1; background:#2563eb; color:#fff; padding:9px 6px; border-radius:8px; text-decoration:none; font-size:11px; font-weight:700;">Tải QR</a>
                        <a href="${viewUrl}" target="_blank" style="flex:1; background:#f1f5f9; color:#334155; padding:9px 6px; border-radius:8px; text-decoration:none; font-size:11px; font-weight:600; border:1px solid #e2e8f0;">Xem trang</a>
                    </div>
                </div>`;
            }

            // Links
            const linkRegex = /\[LINK_BTN:\s*name=([^,\]]+),\s*url=([^\]]+)\]/gi;
            let linkMatch;
            while ((linkMatch = linkRegex.exec(html)) !== null) {
                extra += `<a href="${linkMatch[2].trim()}" target="_blank" style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; margin-top:6px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; color:#2563eb; text-decoration:none; font-size:12px; font-weight:600;">
                    <span>${linkMatch[1].trim()}</span>
                    <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward</span>
                </a>`;
            }
            html = html.replace(linkRegex, '').trim();
        }
        return { html, extra };
    },

    showTyping(text = 'Nhân viên đang gõ...') {
        const el = document.getElementById('cwTyping');
        if (!el) return;
        el.innerHTML = `<div class="cw-typing-dot"></div><div class="cw-typing-dot"></div><div class="cw-typing-dot"></div><span style="font-size:11px;color:#94a3b8;margin-left:4px">${text}</span>`;
        clearTimeout(this._typingClear);
        this._typingClear = setTimeout(() => this.hideTyping(), 5000);
    },

    hideTyping() {
        const el = document.getElementById('cwTyping');
        if (el) el.innerHTML = '';
    },

    scrollToBottom(instant = false) {
        const c = document.getElementById('cwMessages');
        if (!c) return;
        if (instant) c.scrollTop = c.scrollHeight;
        else c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
        this.isAtBottom = true;
        this.hideNewMsgToast();
    },

    onScroll() {
        const c = document.getElementById('cwMessages');
        if (!c) return;
        this.isAtBottom = c.scrollHeight - c.clientHeight - c.scrollTop < 60;
        if (this.isAtBottom) this.hideNewMsgToast();
    },

    setUnread(count) {
        this.unread = count;
        const badge = document.getElementById('cwUnreadBadge');
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.toggle('show', count > 0);
        }
        document.getElementById('cwBtn')?.classList.toggle('has-unread', count > 0);
    },

    markRead() {
        if (!this.convId) return;
        fetch(this._url('api/chat/mark-read.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId })
        }).catch(() => {});
    },

    clearUnread() { this.setUnread(0); },

    showNewMsgToast() { document.getElementById('cwNewMsgToast')?.classList.add('show'); },
    hideNewMsgToast() { document.getElementById('cwNewMsgToast')?.classList.remove('show'); },

    onVisibilityChange() {
        if (!this.isOpen) return;
        if (document.hidden) this.stopSSE();
        else this.startSSE();
    },

    confirmAiBooking(slug, cin, cout, messageId, btnElement) {
        if (btnElement) {
            btnElement.innerHTML = 'Đang tiến hành đặt phòng...';
            btnElement.style.pointerEvents = 'none';
            btnElement.style.opacity = '0.7';
        }
        fetch(this._url('api/chat/confirm-booking.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slug, check_in: cin, check_out: cout, message_id: messageId })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                if (btnElement && btnElement.parentElement) {
                    btnElement.parentElement.innerHTML = `<div style="text-align:center; padding:10px 0;">
                        <div style="color:#16a34a; font-weight:bold; margin-bottom:8px; font-size:14px;">✅ ĐẶT PHÒNG THÀNH CÔNG</div>
                        <div style="font-size:16px; font-weight:bold; color:#ca8a04; margin-bottom:12px;">Mã: ${data.booking_code}</div>
                        <a href="${this._url('profile/view-qrcode.php?id=' + data.booking_id)}" target="_blank" style="display:inline-block; background:linear-gradient(135deg, #16a34a, #15803d); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:bold;">MỞ XEM QR CODE</a>
                    </div>`;
                } else window.open(this._url('profile/view-qrcode.php?id=' + data.booking_id), '_blank');
            } else {
                alert(data.message || 'Lỗi đặt phòng. Vui lòng thử lại!');
                if (btnElement) { btnElement.innerHTML = 'XÁC NHẬN ĐẶT PHÒNG'; btnElement.style.pointerEvents = 'auto'; btnElement.style.opacity = '1'; }
            }
        });
    },

    esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/\n/g,'<br>');
    },

    checkStaffOnline() {
        fetch(this._url('api/chat/check-staff-online.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.staffOnline = data.is_online;
                    this.updateStaffStatusUI(data);
                }
            });
    },

    updateStaffStatusUI(data) {
        const dot = document.getElementById('cwOnlineDot');
        const text = document.getElementById('cwStatusText');
        if (dot) dot.style.background = data.is_online ? '#22c55e' : '#94a3b8';
        if (text) text.textContent = data.status_text || 'Hỗ trợ trực tuyến';
    },

    bindEvents() {
        document.getElementById('cwBtn')?.addEventListener('click', () => this.toggle());
        document.getElementById('cwCloseBtn')?.addEventListener('click', () => this.close());
        document.getElementById('cwResetAiBtn')?.addEventListener('click', () => {
            if (!this.convId || !confirm('Bạn muốn xoá cuộc trò chuyện hiện tại?')) return;
            fetch(this._url('api/chat/reset-ai.php'), {
                method: 'POST',
                headers:{ 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: this.convId })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    document.getElementById('cwMessages').innerHTML = '';
                    this.lastMsgId = 0;
                    this.loadMessages();
                }
            });
        });
        const input = document.getElementById('cwInput');
        input?.addEventListener('input', () => {
            this.onInput();
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        });
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        document.getElementById('cwSendBtn')?.addEventListener('click', () => this.sendMessage());
        document.getElementById('cwMessages')?.addEventListener('scroll', () => this.onScroll(), { passive: true });
        document.getElementById('cwNewMsgToast')?.addEventListener('click', () => this.scrollToBottom());
        document.getElementById('cwStartBtn')?.addEventListener('click', () => this.createOrGetConversation());
        document.addEventListener('visibilitychange', () => this.onVisibilityChange());
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && this.isOpen) this.close(); });
    },

    onInput() {
        const val = document.getElementById('cwInput')?.value || '';
        if (!this.convId && val.length === 1) this.createOrGetConversation();
        clearTimeout(this.typingTimer);
        this._sendTyping(true);
        this.typingTimer = setTimeout(() => this._sendTyping(false), 2000);
    },

    _sendTyping(isTyping) {
        if (!this.convId) return;
        fetch(this._url('api/chat/typing.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: this.convId, is_typing: isTyping })
        }).catch(() => {});
    }
};

document.addEventListener('DOMContentLoaded', () => ChatWidget.init());
