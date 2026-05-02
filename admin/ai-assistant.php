<?php
$page_title = 'Trợ lý Admin AI (Super AI)';
$page_subtitle = 'Trợ lý ảo hỗ trợ trực tiếp quản trị CSDL, phân tích và thực thi lệnh tự động.';
require_once 'includes/admin-header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    /* Chỉnh sửa layout Markdown chuyên nghiệp */
    .ai-text-content { 
        font-size: 14px; 
        line-height: 1.7; 
        color: #334155;
    }
    .dark .ai-text-content {
        color: #cbd5e1;
    }
    .ai-text-content p { margin-bottom: 1em; }
    .ai-text-content p:last-child { margin-bottom: 0; }
    .ai-text-content ul { list-style-type: disc; margin-left: 1.5em; margin-bottom: 1em; }
    .ai-text-content ol { list-style-type: decimal; margin-left: 1.5em; margin-bottom: 1em; }
    .ai-text-content li { margin-bottom: 0.4em; }
    .ai-text-content strong, .ai-text-content b { font-weight: 800; color: #1e293b; }
    .dark .ai-text-content strong, .dark .ai-text-content b { color: #f8fafc; }
    
    /* Code blocks */
    .ai-text-content code { 
        background-color: rgba(99, 102, 241, 0.1); 
        color: #4f46e5; 
        padding: 2px 6px; 
        border-radius: 6px; 
        font-family: 'JetBrains Mono', 'Fira Code', monospace; 
        font-size: 0.9em; 
    }
    .dark .ai-text-content code { 
        background-color: rgba(99, 102, 241, 0.2); 
        color: #a5b4fc; 
    }
    .ai-text-content pre { 
        background-color: #0f172a; 
        color: #f8fafc; 
        padding: 16px; 
        border-radius: 12px; 
        overflow-x: auto; 
        margin-bottom: 1.2em;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .ai-text-content pre code { background-color: transparent; color: inherit; padding: 0; }

    /* Tables */
    .ai-text-content table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0;
        margin-bottom: 1.2em; 
        font-size: 13px; 
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .dark .ai-text-content table { border-color: #334155; }
    .ai-text-content th, .ai-text-content td { 
        padding: 12px 14px; 
        border-bottom: 1px solid #e2e8f0; 
        text-align: left; 
    }
    .dark .ai-text-content th, .dark .ai-text-content td { border-color: #334155; }
    .ai-text-content th { 
        background-color: #f8fafc; 
        font-weight: 700; 
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 11px;
    }
    .dark .ai-text-content th { background-color: #1e293b; color: #94a3b8; }
    .ai-text-content tr:last-child td { border-bottom: none; }
    .ai-text-content tr:hover td { background-color: rgba(241, 245, 249, 0.5); }
    .dark .ai-text-content tr:hover td { background-color: rgba(30, 41, 59, 0.5); }

    /* Custom Scrollbar cho Terminal */
    #aiTerminal::-webkit-scrollbar { width: 4px; }
    #aiTerminal::-webkit-scrollbar-track { background: transparent; }
    #aiTerminal::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Khu vực Chat -->
    <div class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 flex flex-col h-[750px]">

        <!-- Header Chat -->
        <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white dark:from-slate-800 dark:to-slate-800 rounded-t-2xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg transform -rotate-3">
                    <span class="material-symbols-outlined text-white text-2xl">bolt</span>
                </div>
                <div>
                    <h3 class="font-black text-gray-900 dark:text-white flex items-center gap-2 tracking-tight">
                        SUPER AI ADMIN
                        <span class="px-2 py-0.5 rounded-lg bg-indigo-500 text-white text-[10px] font-black uppercase tracking-widest">Opencode v5</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-tighter">Hệ thống gỡ lỗi & Thống kê thời gian thực</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="clearChat()" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all group" title="Xóa lịch sử đồng bộ">
                    <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">delete_sweep</span>
                </button>
            </div>
        </div>

        <!-- Khung Tin nhắn -->
        <div id="aiChatWindow" class="flex-1 overflow-y-auto p-6 space-y-8 bg-slate-50 dark:bg-slate-900/50">
            <!-- Banner hệ thống -->
            <div class="system-banner text-center py-4 px-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800/30 mb-4">
                <p class="text-xs text-indigo-700 dark:text-indigo-300 font-medium italic">"Chào sếp! Em đã sẵn sàng truy cập Database và Source Code để hỗ trợ sếp gỡ lỗi và quản trị khách sạn."</p>
            </div>
        </div>

        <!-- Thanh Input -->
        <div class="p-4 border-t border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-b-2xl">
            <form id="aiForm" class="relative group">
                <input type="text" id="aiInput" 
                    class="w-full bg-slate-100 dark:bg-slate-900 border-2 border-transparent focus:border-indigo-500 dark:focus:border-indigo-500 text-gray-900 dark:text-white text-sm rounded-2xl p-4 pr-32 transition-all outline-none"
                    placeholder="Sếp cần em tra cứu dữ liệu, vẽ báo cáo hay sửa lỗi gì không ạ?"
                    autocomplete="off">
                <div class="absolute right-2 top-2 bottom-2 flex items-center gap-2">
                    <button type="submit" id="aiBtnSend" 
                        class="h-full px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition-all shadow-md shadow-indigo-500/20 active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
                        <span>GỬI LỆNH</span>
                        <span class="material-symbols-outlined text-sm">send</span>
                    </button>
                </div>
            </form>
            <div class="mt-3 flex flex-wrap gap-2">
                <button onclick="fillPrompt('Báo cáo doanh thu hôm nay')" class="text-[10px] font-bold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 transition-all border border-slate-200 dark:border-slate-600">📊 Doanh thu</button>
                <button onclick="fillPrompt('Check lỗi hệ thống mới nhất và đề xuất cách sửa')" class="text-[10px] font-bold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 transition-all border border-slate-200 dark:border-slate-600">⚠️ Check Logs & Fix</button>
                <button onclick="fillPrompt('Danh sách 10 khách hàng đặt phòng nhiều nhất')" class="text-[10px] font-bold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 hover:text-emerald-600 transition-all border border-slate-200 dark:border-slate-600">👥 Khách hàng VIP</button>
                <button onclick="fillPrompt('Dọn dẹp bộ nhớ đệm hệ thống')" class="text-[10px] font-bold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/30 hover:text-amber-600 transition-all border border-slate-200 dark:border-slate-600">🧹 Xóa Cache</button>
            </div>
        </div>
    </div>

    <!-- Cột Phải: System Terminal -->
    <div class="space-y-6">
        <div class="bg-slate-900 rounded-2xl p-4 shadow-xl border border-slate-800 h-[500px] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                    </div>
                    <span class="ml-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Admin AI Terminal</span>
                </div>
                <span class="text-[10px] font-mono text-emerald-500/50 pulse">● CONNECTED</span>
            </div>
            <div id="aiTerminal" class="flex-1 overflow-y-auto font-mono text-[11px] leading-relaxed space-y-1 pr-2">
                <div class="text-slate-500">[SYSTEM] Initialization... OK</div>
                <div class="text-slate-500">[DATABASE] Connecting to schema 'aurora'... OK</div>
                <div class="text-indigo-400">[READY] Waiting for user commands.</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white shadow-lg shadow-indigo-500/20 relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 group-hover:rotate-12 transition-transform duration-700">
                <span class="material-symbols-outlined text-[120px]">psychology</span>
            </div>
            <h4 class="text-sm font-black uppercase tracking-widest mb-2">Trạng thái mô hình</h4>
            <p class="text-2xl font-black mb-4">Opencode 5.0</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="opacity-70">Năng lực:</span>
                    <span class="font-bold">Full CRUD + File Access</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="opacity-70">Đồng bộ Cloud:</span>
                    <span class="font-bold text-emerald-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">cloud_done</span> Hoạt động
                    </span>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-white/10">
                <p class="text-[10px] leading-relaxed opacity-60">Lưu ý: Mọi thao tác thực thi CSDL đều được ghi lại vào Activity Logs để kiểm toán.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('aiForm');
    const input = document.getElementById('aiInput');
    const windowChat = document.getElementById('aiChatWindow');
    const btn = document.getElementById('aiBtnSend');
    const terminal = document.getElementById('aiTerminal');

    // ── Lịch sử trò chuyện (Đồng bộ Cloud & localStorage) ──────────────────────────────────
    const HISTORY_KEY = 'aurora_admin_ai_history';

    function saveHistory(user, content) {
        const arr = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
        arr.push({ user, content });
        if (arr.length > 200) arr.splice(0, arr.length - 200);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(arr));
        
        fetch('api/sync-ai-history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ history: arr })
        }).catch(() => {});
    }

    function restoreHistory() {
        fetch('api/sync-ai-history.php')
            .then(res => res.json())
            .then(data => {
                let arr = [];
                if (data.success && data.history && data.history.length > 0) {
                    arr = data.history;
                    localStorage.setItem(HISTORY_KEY, JSON.stringify(arr));
                } else {
                    arr = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                }
                
                if (arr.length === 0) return;
                appendTerminal(`Loaded ${arr.length} messages from synchronized history.`, 'INFO');
                arr.forEach(({ user, content }) => {
                    renderMessage(user, content, true);
                });
            })
            .catch(() => {
                const arr = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                if (arr.length === 0) return;
                appendTerminal(`Loaded ${arr.length} messages from local history (offline mode).`, 'INFO');
                arr.forEach(({ user, content }) => {
                    renderMessage(user, content, true);
                });
            });
    }

    function fillPrompt(text) {
        input.value = text;
        input.focus();
    }

    function appendTerminal(msg, type = 'LOG') {
        const div = document.createElement('div');
        let colorClass = 'text-slate-500';
        if (type === 'ERROR') colorClass = 'text-red-400';
        if (type === 'SUCCESS') colorClass = 'text-emerald-400';
        if (type === 'CMD') colorClass = 'text-amber-400';
        if (type === 'INFO') colorClass = 'text-indigo-400';

        const time = new Date().toLocaleTimeString('vi-VN', { hour12: false });
        div.innerHTML = `<span class="opacity-30">[${time}]</span> <span class="${colorClass}">[${type}] ${msg}</span>`;
        terminal.appendChild(div);
        terminal.scrollTo({ top: terminal.scrollHeight });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function generateCallCode(data) {
        // Encode JSON to base64 for cleaner HTML passing
        return btoa(unescape(encodeURIComponent(JSON.stringify(data))));
    }

    function executeAIAction(btnElement, base64Data) {
        const decoded = JSON.parse(decodeURIComponent(escape(atob(base64Data))));
        const box = btnElement.closest('.action-box');
        btnElement.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span> Đang chạy lệnh...';
        btnElement.disabled = true;

        appendTerminal('Executing AI-generated JSON payload...', 'CMD');

        fetch('api/execute-ai-action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(decoded)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    appendTerminal(`Action executed perfectly. Affected rows: ${res.affected_rows || 0}`, 'SUCCESS');
                    let extraMsg = res.system_message ? `<div class="text-xs mt-2 font-mono bg-white dark:bg-slate-900 p-2 rounded text-green-700 dark:text-green-400 text-left">${res.system_message}</div>` : '';
                    box.innerHTML = `
                    <div class="text-center p-3 text-green-700 bg-green-50 rounded-xl border border-green-200">
                        <div class="font-bold mb-1 flex justify-center items-center gap-1"><span class="material-symbols-outlined">check_circle</span> ĐÃ PHÊ DUYỆT & THỰC THI!</div>
                        <div class="text-xs">Lệnh đã được hệ thống ghi nhận và xử lý.</div>
                        ${extraMsg}
                    </div>
                `;
                } else {
                    appendTerminal(`Action Execution Error: ${res.message}`, 'ERROR');
                    btnElement.innerHTML = 'Thử lại';
                    btnElement.disabled = false;
                    alert('Lỗi: ' + res.message);
                }
            }).catch(e => {
                appendTerminal(`Network Error`, 'ERROR');
                btnElement.disabled = false;
                btnElement.innerHTML = 'Thử lại';
            });
    }

    function rejectAIAction(btnElement) {
        const box = btnElement.closest('.action-box');
        appendTerminal('Admin destroyed AI Proposal.', 'INFO');
        box.innerHTML = `
            <div class="text-center p-3 text-red-700 bg-red-50 rounded-xl border border-red-200">
                <div class="font-bold mb-1 flex justify-center items-center gap-1"><span class="material-symbols-outlined">delete_forever</span> BỊ TỪ CHỐI & HỦY DIỆT!</div>
                <div class="text-xs">Lệnh nháp đã bị xóa khói bộ nhớ.</div>
            </div>
        `;
    }

    function renderMessage(user, content, isRestore = false, msgId = null, skipSave = false) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-4';
        if (msgId) div.id = msgId;

        if (user === 'admin') {
            div.innerHTML = `
                <div class="flex-1 flex justify-end">
                    <div class="bg-indigo-600 text-white p-4 rounded-2xl rounded-tr-none shadow-md text-sm max-w-[85%] inline-block">
                        ${escapeHtml(content)}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 mt-1 shadow-inner overflow-hidden border-2 border-white dark:border-slate-800">
                    <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 font-bold text-sm">account_circle</span>
                </div>
            `;
        } else {
            const regex = /\[ACTION:\s*(\{[\s\S]*?\})\s*\]/gi;
            const matches = [...content.matchAll(regex)];
            let actionBoxesHtml = '';
            
            let markdownContent = content.replace(/\[ACTION:\s*(\{[\s\S]*?\})\s*\]/gi, '').trim();
            let displayHtml = (typeof marked !== 'undefined') ? marked.parse(markdownContent) : escapeHtml(markdownContent).replace(/\n/g, '<br>');

            if (matches.length > 0) {
                matches.forEach(match => {
                    try {
                        const actionData = JSON.parse(match[1]);
                        let actionPreviewHtml = '';
                        if (actionData.action === 'RAPID_CRUD') {
                            actionPreviewHtml = `<div class="bg-gray-900 shadow-inner p-3 overflow-x-auto text-xs font-mono text-green-400 border border-gray-700 rounded-lg">
                                <span class="text-gray-500 block mb-2 select-none">-- RAW SQL PREVIEW --</span>
                                ${actionData.data.query}
                            </div>`;
                        } else if (actionData.action === 'SYSTEM_CMD') {
                            actionPreviewHtml = `<div class="bg-red-900 shadow-inner p-3 overflow-x-auto text-xs font-mono text-yellow-400 border border-red-700 rounded-lg">
                                <span class="text-red-300 block mb-2 select-none">-- SYSTEM COMMAND --</span>
                                EXECUTE: ${actionData.data.command}
                            </div>`;
                        } else {
                            actionPreviewHtml = `<div class="bg-slate-100 dark:bg-slate-900 p-2 text-xs font-mono break-all text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900 rounded">${JSON.stringify(actionData.data)}</div>`;
                        }

                        let uniqueId = 'btn_' + Math.random().toString(36).substr(2, 9);
                        let isAutoExecute = (actionData.level === 'C');
                        let btnHtml = '';
                        let tagHtml = '';
                        let autoHtml = '';

                        if (isAutoExecute) {
                            tagHtml = `<span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded ml-2 font-bold mb-1">Cấp C (Cơ Bản)</span>`;
                            autoHtml = `<div class="text-sm text-green-600 font-semibold mb-2"><span class="material-symbols-outlined text-[14px] animate-spin align-middle mr-1">refresh</span> Đang Tự Động Thực Thi...</div>`;
                            btnHtml = `<button id="${uniqueId}" class="hidden" style="display:none;"></button>`;
                        } else {
                            let levelName = actionData.level === 'S' ? 'Cấp S (Nguy Hiểm)' : 'Cấp A (Cảnh Báo)';
                            let levelColor = actionData.level === 'S' ? 'red' : 'amber';
                            tagHtml = `<span class="bg-${levelColor}-100 text-${levelColor}-700 text-[10px] px-2 py-0.5 rounded ml-2 font-bold mb-1">${levelName}</span>`;
                            btnHtml = `<div class="mt-3 flex space-x-2">
                                <button onclick='executeAIAction(this, "${generateCallCode(actionData)}")' class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition flex items-center shadow-sm">
                                    <span class="material-symbols-outlined text-[14px] mr-1">done_all</span> PHÊ DUYỆT LỆNH
                                </button>
                                <button onclick='rejectAIAction(this)' class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-sm hover:bg-slate-200 transition flex items-center border border-slate-300">
                                    <span class="material-symbols-outlined text-[14px] mr-1">close</span> HỦY LỆNH
                                </button>
                            </div>`;
                        }

                        let actionHtml = `
                            <div class="action-box mt-4 p-4 border-2 border-indigo-100 dark:border-indigo-900 bg-indigo-50/30 dark:bg-indigo-900/10 rounded-xl">
                                <h5 class="font-bold text-indigo-800 dark:text-indigo-300 text-xs mb-2 flex items-center gap-1 uppercase tracking-tighter"><span class="material-symbols-outlined text-sm">settings_suggest</span> Ý định thực thi: [${actionData.action}]${tagHtml}</h5>
                                ${actionPreviewHtml}
                                ${autoHtml}
                                ${btnHtml}
                            </div>
                        `;

                        actionBoxesHtml += actionHtml;

                        if (isAutoExecute && !isRestore && !skipSave) {
                            setTimeout(() => {
                                const executeButton = div.querySelector(`#${uniqueId}`);
                                if (executeButton) executeAIAction(executeButton, generateCallCode(actionData));
                            }, 500);
                        }
                    } catch (e) { console.error("Action error", e); }
                });
            }

            div.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg flex-shrink-0 mt-1">
                    <span class="material-symbols-outlined text-white text-sm">bolt</span>
                </div>
                <div class="flex-1 w-full overflow-hidden">
                    <div class="bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 p-5 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 dark:border-slate-700 text-sm leading-relaxed inline-block max-w-full">
                        <div class="ai-text-content">${displayHtml}</div>
                        ${isRestore ? restoreActionBoxesHtml(actionBoxesHtml) : actionBoxesHtml}
                    </div>
                </div>
            `;
        }

        windowChat.appendChild(div);
        windowChat.scrollTo({ top: windowChat.scrollHeight, behavior: 'smooth' });

        if (!isRestore && !skipSave) saveHistory(user, content);
    }

    form.onsubmit = (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;

        renderMessage('admin', msg);
        input.value = '';
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">refresh</span>';

        appendTerminal(`Sending request to Opencode Super AI...`, 'INFO');

        const aiMessageId = 'msg_' + Math.random().toString(36).substr(2, 9);
        renderMessage('ai', '', false, aiMessageId);
        
        fetch('api/chat-admin-ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        })
        .then(response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let fullText = '';
            let sseBuffer = '';

            function pushData() {
                reader.read().then(({ done, value }) => {
                    if (done) {
                        btn.disabled = false;
                        btn.innerHTML = '<span>GỬI LỆNH</span><span class="material-symbols-outlined text-sm">send</span>';
                        saveHistory('ai', fullText);
                        
                        const finalDiv = windowChat.querySelector(`#${aiMessageId}`);
                        if(finalDiv && fullText.includes('[ACTION:')) {
                            finalDiv.remove();
                            renderMessage('ai', fullText, false, null, true);
                        }
                        return;
                    }
                    
                    const chunk = decoder.decode(value, { stream: true });
                    sseBuffer += chunk;
                    let lines = sseBuffer.split('\n');
                    sseBuffer = lines.pop(); 
                    
                    lines.forEach(line => {
                        line = line.trim();
                        if (line.startsWith('data: ')) {
                            const dataStr = line.substring(6).trim();
                            if (dataStr === '[DONE]') return;
                            try {
                                const data = JSON.parse(dataStr);
                                if (data.error) {
                                    appendTerminal(`[ERROR] AI Core: ${data.error}`, 'ERROR');
                                    fullText += "\n**Lỗi:** " + data.error;
                                } else if (data.text) {
                                    fullText += data.text;
                                }
                            } catch (e) {}
                        }
                    });
                    
                    const targetDiv = windowChat.querySelector(`#${aiMessageId} .ai-text-content`);
                    if (targetDiv) {
                        // Lọc bỏ action tag khi đang stream
                        const cleanText = fullText.replace(/\[ACTION:\s*(\{[\s\S]*?\})\s*\]/gi, '');
                        targetDiv.innerHTML = (typeof marked !== 'undefined') ? marked.parse(cleanText) : escapeHtml(cleanText).replace(/\n/g, '<br>');
                        windowChat.scrollTo({ top: windowChat.scrollHeight, behavior: 'smooth' });
                    }
                    
                    pushData();
                }).catch(err => {
                    appendTerminal(`Stream Error: ${err.message}`, 'ERROR');
                    btn.disabled = false;
                    btn.innerHTML = '<span>GỬI LỆNH</span><span class="material-symbols-outlined text-sm">send</span>';
                });
            }
            pushData();
        })
        .catch(er => {
            appendTerminal(`Connection Failed: ${er.message}`, 'ERROR');
            btn.disabled = false;
            btn.innerHTML = '<span>GỬI LỆNH</span><span class="material-symbols-outlined text-sm">send</span>';
        });
    }

    function restoreActionBoxesHtml(html) {
        if (!html) return '';
        const dummy = document.createElement('div');
        dummy.innerHTML = html;
        dummy.querySelectorAll('.action-box').forEach(box => {
            box.innerHTML = `
                <div class="text-center p-2 text-slate-500 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase tracking-tight">
                    <span class="material-symbols-outlined text-xs align-middle mr-1">history</span> Lệnh từ phiên trước
                </div>`;
        });
        return dummy.innerHTML;
    }

    function clearChat() {
        if(!confirm('Sếp có chắc chắn muốn xóa toàn bộ lịch sử chat đồng bộ không?')) return;
        const childs = windowChat.querySelectorAll(':scope > div:not(.system-banner)');
        childs.forEach(c => c.remove());
        localStorage.removeItem(HISTORY_KEY);
        
        fetch('api/sync-ai-history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
        }).catch(() => {});
        
        appendTerminal(`All chat history wiped from Cloud & Local.`, 'SUCCESS');
    }

    restoreHistory();
</script>

<?php require_once 'includes/admin-footer.php'; ?>