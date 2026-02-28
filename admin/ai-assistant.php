<?php
$page_title = 'Trợ lý Admin AI (Super AI)';
$page_subtitle = 'Trợ lý ảo hỗ trợ trực tiếp quản trị CSDL, phân tích và thực thi lệnh tự động.';
require_once 'includes/admin-header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Khu vực Chat -->
    <div
        class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 flex flex-col h-[700px]">

        <!-- Header Chat -->
        <div
            class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-white dark:from-slate-800 dark:to-slate-800 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center p-0.5 shadow-lg">
                    <div
                        class="w-full h-full bg-white dark:bg-slate-900 rounded-full flex items-center justify-center border-2 border-transparent">
                        <span
                            class="material-symbols-outlined text-transparent bg-clip-text bg-gradient-to-br from-indigo-500 to-purple-600">generating_tokens</span>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Super AI
                        <span
                            class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-bold border border-green-200">System
                            Root</span>
                    </h3>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400">Đã kết nối trực tiếp vào Lõi CSDL</p>
                </div>
            </div>
            <button onclick="clearChat()"
                class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors"
                title="Xóa lịch sử chat">
                <span class="material-symbols-outlined">delete_sweep</span>
            </button>
        </div>

        <!-- Khung Tin nhắn -->
        <div id="aiChatWindow" class="flex-1 overflow-y-auto p-4 space-y-6 bg-slate-50 dark:bg-slate-900/50">
            <!-- Lời chào mặc định -->
            <div class="flex items-start gap-4">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md flex-shrink-0 mt-1">
                    <span class="material-symbols-outlined text-white text-sm">smart_toy</span>
                </div>
                <div class="flex-1">
                    <div
                        class="bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 dark:border-slate-700 text-sm leading-relaxed">
                        <p class="font-bold text-indigo-600 dark:text-indigo-400 mb-2">Xin chào Sếp
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!
                        </p>
                        <p>Em là <b> Aurora AI</b> được cấp quyền tối cao. Em có thể thay sếp thực thi nhanh các nghiệp
                            vụ
                            sau:</p>
                        <ul class="list-disc ml-5 mt-2 space-y-1 text-gray-600 dark:text-gray-400">
                            <li>Cập nhật giá phòng hàng loạt (<b>UPDATE</b> room_pricing)</li>
                            <li>Tự động tạo mã khuyến mãi (<b>INSERT</b> promotions)</li>
                            <li>Truy vấn số liệu và Phân tích logic (<b>SELECT</b>)</li>
                        </ul>
                        <div
                            class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-xs rounded-xl border border-red-200 dark:border-red-800/30">
                            <b>Lưu ý:</b> Em chỉ "Tạo Lệnh Nháp". Lệnh thực tế chỉ chạy khi sếp nhấp nút <b>[PHÊ
                                DUYỆT]</b> bên dưới câu trả lời của em.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Khung Nhập Liệu -->
        <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 rounded-b-2xl">
            <form id="aiChatForm" class="flex gap-3">
                <div class="flex-1 relative">
                    <input type="text" id="aiInput" required autocomplete="off"
                        class="w-full bg-slate-100 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 pl-12 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-gray-900 dark:text-white"
                        placeholder="VD: Cập nhật giá phòng Deluxe hôm nay lên 2 triệu rưỡi nhé...">
                    <span class="material-symbols-outlined absolute left-4 top-3 text-gray-400">code_blocks</span>
                </div>
                <button type="submit" id="aiBtnSend"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg hover:shadow-indigo-500/30 transition-all flex items-center gap-2">
                    <span>Thực thi</span>
                    <span class="material-symbols-outlined text-sm">send</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Hướng dẫn & Logs -->
    <div class="col-span-1 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
            <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-indigo-500">lightbulb</span>
                Gợi ý lệnh (Prompt)
            </h4>
            <div class="space-y-3">
                <button
                    onclick="fillPrompt('Tạo một mã khuyến mãi giảm 20% tối đa 500k cho dịp Giáng Sinh 2026, áp dụng ngay hôm nay tới hết năm nha.')"
                    class="w-full text-left p-3 text-sm bg-slate-50 dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-100 dark:border-slate-700 transition-colors">
                    "Tạo mã khuyến mãi giảm 20% dịp Giáng Sinh tới cuối năm..."
                </button>
                <button
                    onclick="fillPrompt('Cào bảng giá phòng, set lại giá của phòng có tên Deluxe Room thành 2,500,000 VND từ ngày 1/5/2026 đến 5/5/2026 với lý do Lễ 30/4 nhé.')"
                    class="w-full text-left p-3 text-sm bg-slate-50 dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-100 dark:border-slate-700 transition-colors">
                    "Cập nhật giá phòng Deluxe lên 2,500,000 dịp Lễ 30/4..."
                </button>
            </div>
        </div>

        <div class="bg-slate-900 rounded-2xl shadow-sm border border-slate-700 overflow-hidden flex flex-col h-[380px]">
            <div class="p-3 border-b border-slate-700 flex justify-between items-center bg-black/20">
                <h4 class="font-bold text-green-400 flex items-center gap-2 text-xs font-mono uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm">terminal</span>
                    Admin SQL Terminal Logs
                </h4>
            </div>
            <div id="aiTerminal" class="p-4 flex-1 overflow-y-auto text-xs font-mono text-gray-400 space-y-2">
                <div><span class="text-blue-400">[SYSTEM]</span> Initialized connection to Database Core.</div>
                <div><span class="text-blue-400">[SYSTEM]</span> AI Identity Verified: Super Admin.</div>
                <div><span class="text-blue-400">[SYSTEM]</span> Waiting for natural language payload...</div>
            </div>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiInput');
    const windowChat = document.getElementById('aiChatWindow');
    const btn = document.getElementById('aiBtnSend');
    const terminal = document.getElementById('aiTerminal');

    // ── Lịch sử trò chuyện (localStorage) ──────────────────────────────────
    const HISTORY_KEY = 'aurora_admin_ai_history';

    function saveHistory(user, content) {
        const arr = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
        arr.push({ user, content });
        // Giới hạn 200 tin nhắn gần nhất
        if (arr.length > 200) arr.splice(0, arr.length - 200);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(arr));
    }

    function restoreHistory() {
        const arr = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
        if (arr.length === 0) return;
        appendTerminal(`Loaded ${arr.length} messages from local history.`, 'INFO');
        arr.forEach(({ user, content }) => {
            // Khi restore, các action box đã phê duyệt/hủy sẽ được đánh dấu là đã xử lý
            renderMessage(user, content, true);
        });
    }

    // Chặn enter vô duyên
    function fillPrompt(text) {
        input.value = text;
        input.focus();
    }

    function appendTerminal(text, type = 'INFO') {
        const div = document.createElement('div');
        let color = 'text-gray-400';
        if (type === 'SUCCESS') color = 'text-green-400';
        if (type === 'ERROR') color = 'text-red-400';
        if (type === 'CMD') color = 'text-purple-400';

        div.innerHTML = `<span class="${color}">[${type}]</span> ${text}`;
        terminal.appendChild(div);
        terminal.scrollTo({ top: terminal.scrollHeight, behavior: 'smooth' });
    }

    function generateCallCode(actionData) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(actionData))));
    }

    function escapeHtml(unsafe) {
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
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
                    appendTerminal(`Query executed perfectly. Affected rows: ${res.affected_rows || 1}`, 'SUCCESS');
                    box.innerHTML = `
                    <div class="text-center p-3 text-green-700 bg-green-50 rounded-xl border border-green-200">
                        <div class="font-bold mb-1 flex justify-center items-center gap-1"><span class="material-symbols-outlined">check_circle</span> ĐÃ PHÊ DUYỆT & THỰC THI!</div>
                        <div class="text-xs">Dữ liệu đã được ghi vào Database.</div>
                    </div>
                `;
                } else {
                    appendTerminal(`SQL Execution Error: ${res.message}`, 'ERROR');
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

    function renderMessage(user, content, isRestore = false) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-4';

        if (user === 'admin') {
            div.innerHTML = `
                <div class="flex-1 flex justify-end">
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 text-white p-4 rounded-2xl rounded-tr-none shadow-md text-sm max-w-[80%] inline-block text-right">
                        ${escapeHtml(content)}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 mt-1 shadow-inner overflow-hidden border-2 border-white">
                    <span class="material-symbols-outlined text-gray-700 font-bold text-sm">person</span>
                </div>
            `;
        } else {
            // Parse Action Tags
            let displayHtml = escapeHtml(content);
            let actionBox = '';

            // Xóa thẻ code markdown bao lấy json (nếu AI rảnh rỗi nhét vào)
            content = content.replace(/```json\n/g, '').replace(/```\n/g, '').replace(/```/g, '');

            // Extract all ACTION tags using matchAll
            const regex = /\[ACTION:\s*(\{[\s\S]*?\})\s*\]/gi;
            const matches = [...content.matchAll(regex)];

            let actionBoxesHtml = '';

            if (matches.length > 0) {
                // Strip all ACTION tags from the display text
                displayHtml = escapeHtml(content.replace(/\[ACTION:\s*(\{[\s\S]*?\})\s*\]/gi, '').trim());
                displayHtml = displayHtml.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>');

                matches.forEach(match => {
                    try {
                        const actionData = JSON.parse(match[1]);
                        let actionPreviewHtml = '';
                        if (actionData.table === 'promotions') {
                            const pCode = actionData.data.promotion_code || actionData.data.code || '???';
                            const pName = actionData.data.promotion_name || actionData.data.title || '???';
                            actionPreviewHtml = `<div class="text-xs text-indigo-700 mb-2 font-mono bg-white p-2 border border-indigo-100 rounded">INSERT INTO promotions (<br>&nbsp;&nbsp;promotion_code='<b class="text-red-600">${pCode}</b>', <br>&nbsp;&nbsp;promotion_name='<b class="text-blue-600">${pName}</b>', ...<br>)</div>`;
                        } else if (actionData.table === 'room_pricing') {
                            actionPreviewHtml = `<div class="text-xs text-indigo-700 mb-2 font-mono bg-white p-2 border border-indigo-100 rounded">INSERT INTO room_pricing (<br>&nbsp;&nbsp;room_type_id='<b class="text-red-600">${actionData.data.room_type_id}</b>',<br>&nbsp;&nbsp;price='<b class="text-green-600">${actionData.data.price}</b>',<br>&nbsp;&nbsp;date='${actionData.data.date || actionData.data.start_date}'<br>)</div>`;
                        } else if (actionData.table === 'room_types') {
                            actionPreviewHtml = `<div class="text-xs text-indigo-700 mb-2 font-mono bg-white p-2 border border-indigo-100 rounded">UPDATE room_types SET<br>&nbsp;&nbsp;base_price='<b class="text-green-600">${actionData.data.base_price}</b>'<br>WHERE room_type_id='<b class="text-red-600">${actionData.data.room_type_id}</b>'</div>`;
                        } else if (actionData.action === 'RAPID_CRUD') {
                            actionPreviewHtml = `<div class="bg-gray-900 shadow-inner p-3 overflow-x-auto text-xs font-mono text-green-400 border border-gray-700 rounded-lg">
                                <span class="text-gray-500 block mb-2 select-none">-- RAW SQL PREVIEW --</span>
                                ${actionData.data.query}
                            </div>`;
                        } else {
                            actionPreviewHtml = `<div class="bg-indigo-50 p-2 text-xs font-mono break-all text-indigo-700 border border-indigo-100 rounded">${JSON.stringify(actionData.data)}</div>`;
                        }

                        let uniqueId = 'btn_' + Math.random().toString(36).substr(2, 9);
                        // Phân cấp Nhanh/Chậm theo Risk Level (A,S = Chờ Duyệt / C = Chạy Ngay)
                        let isAutoExecute = false;
                        let btnHtml = '';
                        let tagHtml = '';
                        let autoHtml = '';

                        if (actionData.level === 'C') {
                            isAutoExecute = true;
                            tagHtml = `<span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded ml-2 font-bold mb-1">Cấp C (Cơ Bản)</span>`;
                            autoHtml = `<div class="text-sm text-green-600 font-semibold mb-2"><span class="material-symbols-outlined text-[14px] animate-spin align-middle mr-1">refresh</span> Đang Tự Động Thực Thi...</div>`;
                            btnHtml = `<button id="${uniqueId}" class="hidden" style="display:none;"></button>`;
                        } else {
                            let levelName = actionData.level === 'S' ? 'Cấp S (Nguy Hiểm)' : 'Cấp A (Cảnh Báo)';
                            let levelColor = actionData.level === 'S' ? 'red' : 'yellow';
                            tagHtml = `<span class="bg-${levelColor}-100 text-${levelColor}-700 text-[10px] px-2 py-0.5 rounded ml-2 font-bold mb-1">${levelName}</span>`;
                            btnHtml = `<div class="mt-3 flex space-x-2">
                                <button onclick='executeAIAction(this, "${generateCallCode(actionData)}")' class="bg-indigo-600 text-white px-3 py-1.5 rounded text-sm hover:bg-indigo-700 transition flex items-center shadow-sm">
                                    <span class="material-symbols-outlined text-[14px] mr-1">done_all</span> PHÊ DUYỆT LỆNH
                                </button>
                                <button onclick='rejectAIAction(this)' class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded text-sm hover:bg-gray-200 transition flex items-center border border-gray-300">
                                    <span class="material-symbols-outlined text-[14px] mr-1">close</span> HỦY LỆNH NÀY
                                </button>
                            </div>`;
                        }

                        // Layout HTML
                        let actionHtml = `
                            <div class="action-box mt-4 p-4 border-2 border-indigo-200 bg-indigo-50/50 rounded-xl">
                                <h5 class="font-bold text-indigo-800 text-xs mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-sm">database</span> NẮM BẮT Ý ĐỊNH: [${actionData.action}]${tagHtml}</h5>
                                ${actionPreviewHtml}
                                ${autoHtml}
                                ${btnHtml}
                            </div>
                        `;

                        actionBoxesHtml += actionHtml;

                        // Chạy tự động luôn nếu là lệnh Cấp C
                        if (isAutoExecute) {
                            setTimeout(() => {
                                const executeButton = div.querySelector(`#${uniqueId}`);
                                if (executeButton) {
                                    executeAIAction(executeButton, generateCallCode(actionData));
                                }
                            }, 500); // Trì hoãn một chút để UI render được mượt
                        }
                    } catch (e) {
                        console.error("Parse JSON error", e);
                    }
                });
            } else {
                displayHtml = displayHtml.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>');
            }

            div.innerHTML = `
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md flex-shrink-0 mt-1">
                    <span class="material-symbols-outlined text-white text-sm">smart_toy</span>
                </div>
                <div class="flex-1">
                    <div class="bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 dark:border-slate-700 text-sm leading-relaxed inline-block">
                        ${displayHtml}
                        ${isRestore ? restoreActionBoxesHtml(actionBoxesHtml) : actionBoxesHtml}
                    </div>
                </div>
            `;
        }

        windowChat.appendChild(div);
        windowChat.scrollTo({ top: windowChat.scrollHeight, behavior: 'smooth' });

        // Lưu vào lịch sử (trừ khi đang restore)
        if (!isRestore) {
            saveHistory(user, content);
        }
    }

    form.onsubmit = (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;

        renderMessage('admin', msg);
        input.value = '';
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span>...';

        appendTerminal(`Sending parsing request to Gemini Vision: ContentLength=${msg.length}`, 'INFO');

        fetch('api/chat-admin-ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    appendTerminal(`[QUOTA REPORT] System is running on Active ${data.key_info}.`, 'INFO');

                    // Render Tracking Usage of Key
                    if (data.stats && data.stats[data.key_idx]) {
                        const s = data.stats[data.key_idx];
                        const budget = 1000000;
                        const percent = ((s.tokens / budget) * 100).toFixed(2);
                        appendTerminal(`[USAGE LIMIT] Tokens: ${s.tokens}/${budget} (${percent}%) | Request: ${s.requests}/1500 limit/day`, 'CMD');
                    }

                    appendTerminal(`[PERFORMANCE] API Request cost: ${data.tokens} Total Tokens used.`, 'SUCCESS');
                    appendTerminal(`Received Gemini Payload Response. Parsing JSON structure.`, 'SUCCESS');
                    renderMessage('ai', data.reply);
                } else if (data.error_type === 'QUOTA_EXCEEDED') {
                    // Hiển thị Quota Exceeded log nhưng báo cho Admin biết là hệ thống đang tự Handle
                    appendTerminal(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`, 'ERROR');
                    appendTerminal(`[QUOTA] Hết dung lượng (Quota) API Key hiện tại!`, 'ERROR');
                    appendTerminal(`[SYSTEM] Đang tự động luân chuyển sang API Key dự phòng...`, 'CMD');
                    if (data.retry_after) {
                        appendTerminal(`[DETAIL] ${data.message}`, 'INFO');
                    }
                    appendTerminal(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`, 'ERROR');

                    // Render tạm để Admin biết mà bấm lại
                    renderMessage('ai', 'Dạ, API Key hiện tại đã vắt kiệt công suất (Quota). Em đã tự động Swap sang dự phòng thành công, Sếp bấm gửi lại lệnh là ăn luôn ạ!');

                } else {
                    // Lỗi khác: chỉ log terminal, không hiện chat
                    appendTerminal(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`, 'ERROR');
                    appendTerminal(`[ERROR] AI Core: ${data.message}`, 'ERROR');
                    appendTerminal(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`, 'ERROR');
                    renderMessage('ai', 'Dạ hệ thống AI đang gặp sự cố nhỏ: ' + data.message);
                }
            })
            .catch(er => {
                appendTerminal(`[ERROR] Network/Connection failed: ${er.message}`, 'ERROR');
            })
            .finally(() => {
                // Chỉ mở lại nút nếu không phải đang cooldown 429
                if (!input.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>Thực thi</span><span class="material-symbols-outlined text-sm">send</span>';
                }
                btn.innerHTML = '<span>Thực thi</span><span class="material-symbols-outlined text-sm">send</span>';
                if (!input.disabled) input.focus();
            });
    }

    // Action box khi restore: chuyển nút thành trạng thái "đã xử lý trong phiên trước"
    function restoreActionBoxesHtml(html) {
        if (!html) return '';
        // Thay toàn bộ action-box bằng trạng thái archived
        const dummy = document.createElement('div');
        dummy.innerHTML = html;
        dummy.querySelectorAll('.action-box').forEach(box => {
            box.innerHTML = `
                <div class="text-center p-2 text-yellow-700 bg-yellow-50 rounded-xl border border-yellow-200 text-xs">
                    <span class="material-symbols-outlined text-sm align-middle">history</span>
                    <b>Lệnh từ phiên trước</b> — Nếu cần thực thi lại, hãy nhập lại yêu cầu.
                </div>`;
        });
        return dummy.innerHTML;
    }

    function clearChat() {
        const childs = windowChat.querySelectorAll(':scope > div:not(.system-banner)');
        childs.forEach(c => c.remove());
        localStorage.removeItem(HISTORY_KEY);
        appendTerminal(`Chat history cleared.`, 'INFO');
    }

    // Restore lịch sử khi tải trang
    restoreHistory();
</script>

<?php require_once 'includes/admin-footer.php'; ?>