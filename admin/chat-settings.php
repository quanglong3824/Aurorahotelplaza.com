<?php
$page_title = 'Cài đặt Chat';
$page_subtitle = 'Cấu hình hệ thống chat & quản lý mẫu trả lời nhanh';
$current_page = 'chat-settings';   // ← fix highlight sidebar

require_once __DIR__ . '/includes/admin-header.php';  // đã require environment.php bên trong
require_once __DIR__ . '/../config/database.php';

$user_role = $_SESSION['user_role'];
if (!in_array($user_role, ['admin', 'receptionist'])) {
    echo '<div class="p-8 text-red-400">Không có quyền truy cập.</div>';
    require_once __DIR__ . '/includes/admin-footer.php';
    exit;
}

$db = getDB();

$migrationNeeded = false;
$qr = [];
$settingsRaw = [];

try {
    // Load quick replies
    $qr = $db->query("SELECT * FROM chat_quick_replies ORDER BY category, sort_order, title")->fetchAll();
    // Load chat settings
    $settingsRaw = $db->query("SELECT setting_key, setting_value FROM chat_settings")->fetchAll();
} catch (PDOException $e) {
    $migrationNeeded = true; // bảng chưa tồn tại
}

$settings = [];
foreach ($settingsRaw as $s)
    $settings[$s['setting_key']] = $s['setting_value'];

$defaults = [
    'auto_reply_enabled' => '1',
    'auto_reply_message' => 'Xin chào! Cảm ơn bạn đã liên hệ với Aurora Hotel Plaza. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.',
    'working_hours_start' => '08:00',
    'working_hours_end' => '22:00',
    'offline_message' => 'Chúng tôi hiện ngoài giờ làm việc. Vui lòng để lại tin nhắn, chúng tôi sẽ phản hồi sớm.',
    'max_conversations' => '10',
    'sse_interval_global' => '3',
    'sse_interval_conv' => '2',
    'sound_enabled' => '1',
    'chat_enabled' => '1',
];
$settings = array_merge($defaults, $settings);

// Group quick replies by category
$qrByCategory = [];
foreach ($qr as $r) {
    $qrByCategory[$r['category'] ?? 'Chung'][] = $r;
}
?>

<?php if ($migrationNeeded): ?>
    <div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:12px;
            padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;gap:16px">
        <span style="font-size:32px">⚠️</span>
        <div>
            <p style="font-weight:700;color:#92400e;font-size:15px;margin:0 0 6px">Chưa chạy database migration!</p>
            <p style="color:#78350f;font-size:13px;margin:0 0 12px">
                Bảng <code>chat_quick_replies</code> / <code>chat_settings</code> chưa tồn tại.
                Chạy migration trước khi dùng tính năng chat.
            </p>
            <a href="chat-install.php" style="background:#d4af37;color:#fff;padding:8px 18px;border-radius:8px;
                  text-decoration:none;font-weight:700;font-size:13px">
                🚀 Chạy Migration ngay
            </a>
        </div>
    </div>
<?php endif; ?>


<style>
    .settings-tab {
        padding: 10px 20px;
        border-bottom: 2px solid transparent;
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        cursor: pointer;
        transition: .2s;
        white-space: nowrap;
    }

    .settings-tab.active {
        color: #d4af37;
        border-bottom-color: #d4af37;
    }

    .settings-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
    }

    .dark .settings-card {
        background: #0f172a;
        border-color: #1e293b;
    }

    .qr-item {
        transition: .15s;
    }

    .qr-item:hover {
        background: #fefce8;
    }

    .dark .qr-item:hover {
        background: rgba(212, 175, 55, .06);
    }
</style>

<!-- ── Tabs ──────────────────────────────────────────────────── -->
<div class="flex gap-0 border-b border-gray-200 dark:border-slate-800 mb-6 -mt-2 overflow-x-auto">
    <button class="settings-tab active" onclick="switchTab('quick-replies', this)">Mẫu trả lời nhanh</button>
    <button class="settings-tab" onclick="switchTab('general', this)">Cài đặt chung</button>
    <button class="settings-tab" onclick="switchTab('timing', this)">Giờ làm việc & SSE</button>
</div>

<!-- ══════════════════════════════════════════════════════════
     TAB 1: QUICK REPLIES
══════════════════════════════════════════════════════════ -->
<div id="tab-quick-replies">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
            Mẫu trả lời nhanh
            <span class="ml-2 text-sm font-normal text-gray-400">(Gõ / trong chat để dùng)</span>
        </h3>
        <button onclick="openQRModal(null)" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-400 to-amber-600
                       text-white text-sm font-bold rounded-xl hover:shadow-lg transition-all active:scale-95">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm mẫu
        </button>
    </div>

    <!-- Category filter -->
    <div class="flex gap-2 mb-4 flex-wrap" id="qrCategoryFilter">
        <button onclick="filterQR('all', this)"
            class="px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 qr-filter-btn active">
            Tất cả
        </button>
        <?php foreach (array_keys($qrByCategory) as $cat): ?>
            <button onclick="filterQR('<?php echo htmlspecialchars($cat); ?>', this)" class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600
                       dark:bg-slate-800 dark:text-gray-400 hover:bg-amber-100 hover:text-amber-700
                       transition-colors qr-filter-btn">
                <?php echo htmlspecialchars($cat); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="settings-card overflow-hidden">
        <table class="w-full text-sm" id="qrTable">
            <thead class="bg-gray-50 dark:bg-slate-800/50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tiêu đề /
                        Shortcut</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Nội dung
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Danh
                        mục</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Trạng
                        thái</th>
                    <th class="w-24 px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="qrTableBody">
                <?php if (empty($qr)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-4xl block mb-2">chat_bubble_outline</span>
                            Chưa có mẫu nào. Nhấn "Thêm mẫu" để bắt đầu.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($qr as $r): ?>
                        <tr class="qr-item border-t border-gray-100 dark:border-slate-800"
                            data-category="<?php echo htmlspecialchars($r['category'] ?? 'Chung'); ?>">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($r['title']); ?>
                                </div>
                                <?php if ($r['shortcut']): ?>
                                    <code class="text-xs text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-1.5 py-0.5 rounded">
                                                    /<?php echo htmlspecialchars($r['shortcut']); ?>
                                                </code>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs">
                                <p class="truncate"><?php echo htmlspecialchars($r['content']); ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">
                                    <?php echo htmlspecialchars($r['category'] ?? 'Chung'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-bold <?php echo $r['is_active'] ? 'text-green-500' : 'text-gray-400'; ?>">
                                    <span
                                        class="w-2 h-2 rounded-full <?php echo $r['is_active'] ? 'bg-green-500' : 'bg-gray-400'; ?>"></span>
                                    <?php echo $r['is_active'] ? 'Bật' : 'Tắt'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <button onclick="openQRModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="p-1.5 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/20
                                           text-amber-600 transition-colors" title="Sửa">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <button onclick="deleteQR(<?php echo $r['reply_id']; ?>, this)" class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20
                                           text-red-500 transition-colors" title="Xóa">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     TAB 2: GENERAL SETTINGS
══════════════════════════════════════════════════════════ -->
<div id="tab-general" class="hidden">
    <form id="generalSettingsForm" onsubmit="saveSettings(event)">
        <div class="settings-card p-6 mb-4">
            <h4
                class="font-bold text-gray-900 dark:text-white mb-5 pb-3 border-b border-gray-100 dark:border-slate-800">
                Cài đặt chung
            </h4>
            <div class="space-y-5">
                <!-- Chat enabled -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Bật/tắt chat widget</p>
                        <p class="text-xs text-gray-400 mt-0.5">Hiển thị widget chat trên website cho khách</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="chat_enabled" class="sr-only peer" <?php echo $settings['chat_enabled'] === '1' ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                    dark:bg-gray-700 peer-checked:after:translate-x-full
                                    peer-checked:after:border-white after:content-[''] after:absolute
                                    after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                                    after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <!-- Auto reply -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Trả lời tự động</p>
                        <p class="text-xs text-gray-400 mt-0.5">Gửi tin chào khi khách mở hội thoại mới</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="autoReplyToggle" name="auto_reply_enabled" class="sr-only peer" <?php echo $settings['auto_reply_enabled'] === '1' ? 'checked' : ''; ?>
                            onchange="document.getElementById('autoReplyMsgBox').classList.toggle('hidden', !this.checked)">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                    dark:bg-gray-700 peer-checked:after:translate-x-full
                                    peer-checked:after:border-white after:content-[''] after:absolute
                                    after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                                    after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-amber-500"></div>
                    </label>
                </div>
                <div id="autoReplyMsgBox"
                    class="<?php echo $settings['auto_reply_enabled'] !== '1' ? 'hidden' : ''; ?>">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Tin nhắn chào tự động</label>
                    <textarea name="auto_reply_message" rows="3"
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                     bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                     text-sm focus:outline-none focus:border-amber-400 transition-colors"><?php echo htmlspecialchars($settings['auto_reply_message']); ?></textarea>
                </div>

                <!-- Offline message -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Tin nhắn ngoài giờ</label>
                    <textarea name="offline_message" rows="2"
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                     bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                     text-sm focus:outline-none focus:border-amber-400 transition-colors"><?php echo htmlspecialchars($settings['offline_message']); ?></textarea>
                </div>

                <!-- Max conversations per staff -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Số hội thoại tối đa / nhân viên</label>
                    <input type="number" name="max_conversations" min="1" max="50"
                        value="<?php echo (int) $settings['max_conversations']; ?>" class="w-24 px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-amber-400 to-amber-600
                           text-white font-bold rounded-xl hover:shadow-lg transition-all active:scale-95">
                <span class="material-symbols-outlined text-sm">save</span>
                Lưu cài đặt
            </button>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════
     TAB 3: TIMING & SSE
══════════════════════════════════════════════════════════ -->
<div id="tab-timing" class="hidden">
    <form id="timingSettingsForm" onsubmit="saveSettings(event)">
        <div class="settings-card p-6 mb-4">
            <h4
                class="font-bold text-gray-900 dark:text-white mb-5 pb-3 border-b border-gray-100 dark:border-slate-800">
                Giờ làm việc
            </h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Giờ bắt đầu</label>
                    <input type="time" name="working_hours_start"
                        value="<?php echo $settings['working_hours_start']; ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Giờ kết thúc</label>
                    <input type="time" name="working_hours_end" value="<?php echo $settings['working_hours_end']; ?>"
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400">
                </div>
            </div>
        </div>

        <div class="settings-card p-6 mb-4">
            <h4 class="font-bold text-gray-900 dark:text-white mb-2">
                SSE Polling Interval
                <span class="text-xs font-normal text-gray-400 ml-2">(khuyến nghị: global 3s, conv 2s trên shared
                    hosting)</span>
            </h4>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Global stream (giây)</label>
                    <input type="number" name="sse_interval_global" min="1" max="10"
                        value="<?php echo (int) $settings['sse_interval_global']; ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400">
                    <p class="text-xs text-gray-400 mt-1">Danh sách cuộc hội thoại admin</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Conv stream (giây)</label>
                    <input type="number" name="sse_interval_conv" min="1" max="10"
                        value="<?php echo (int) $settings['sse_interval_conv']; ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400">
                    <p class="text-xs text-gray-400 mt-1">Tin nhắn thời gian thực</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-amber-400 to-amber-600
                           text-white font-bold rounded-xl hover:shadow-lg transition-all active:scale-95">
                <span class="material-symbols-outlined text-sm">save</span>
                Lưu cài đặt
            </button>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL: Add/Edit Quick Reply
══════════════════════════════════════════════════════════ -->
<div id="qrModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="qrModalTitle" class="font-bold text-gray-900 dark:text-white">Thêm mẫu trả lời</h3>
            <button onclick="closeQRModal()"
                class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-gray-500">close</span>
            </button>
        </div>
        <form id="qrForm" onsubmit="saveQR(event)" class="p-6 space-y-4">
            <input type="hidden" id="qrId">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Tiêu đề <span
                        class="text-red-500">*</span></label>
                <input type="text" id="qrTitle" required placeholder="VD: Chào mừng khách" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700
                              bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200
                              text-sm focus:outline-none focus:border-amber-400 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Shortcut <span class="text-gray-400">(không
                        dấu, không space)</span></label>
                <div class="flex items-center gap-1">
                    <span class="text-amber-500 font-bold text-lg">/</span>
                    <input type="text" id="qrShortcut" placeholder="chao" pattern="[a-z0-9_-]*" class="flex-1 px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400 transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Nội dung <span
                        class="text-red-500">*</span></label>
                <textarea id="qrContent" rows="4" required placeholder="Nội dung tin nhắn mẫu..."
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700
                                 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200
                                 text-sm focus:outline-none focus:border-amber-400 transition-colors resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Danh mục</label>
                    <input type="text" id="qrCategory" placeholder="VD: Chào hỏi, Giá phòng..." class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Thứ tự</label>
                    <input type="number" id="qrSortOrder" value="0" min="0" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700
                                  bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200
                                  text-sm focus:outline-none focus:border-amber-400 transition-colors">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="qrActive" checked class="w-4 h-4 accent-amber-500">
                <label for="qrActive" class="text-sm text-gray-700 dark:text-gray-300">Kích hoạt</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeQRModal()" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-slate-700
                               text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-50
                               dark:hover:bg-slate-800 text-sm font-semibold transition-colors">
                    Huỷ
                </button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-amber-400 to-amber-600
                               text-white text-sm font-bold rounded-xl hover:shadow-lg transition-all">
                    Lưu mẫu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // siteBase được inject bởi admin-header.php qua window.siteBase
    const _apiBase = (window.siteBase || '').replace(/\/$/, '');

    // ── Tab switching ──────────────────────────────────────────────────────────────
    function switchTab(tab, el) {
        ['quick-replies', 'general', 'timing'].forEach(t => {
            document.getElementById('tab-' + t)?.classList.add('hidden');
        });
        document.querySelectorAll('.settings-tab').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab)?.classList.remove('hidden');
        el.classList.add('active');
    }

    // ── Category filter ────────────────────────────────────────────────────────────
    function filterQR(cat, el) {
        document.querySelectorAll('.qr-filter-btn').forEach(b => {
            b.className = b.className.replace('bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', '')
                .replace('active', '')
                + ' bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400';
        });
        el.classList.add('active', 'bg-amber-100', 'text-amber-700', 'dark:bg-amber-900/30', 'dark:text-amber-400');

        document.querySelectorAll('#qrTableBody tr[data-category]').forEach(row => {
            row.style.display = (cat === 'all' || row.dataset.category === cat) ? '' : 'none';
        });
    }

    // ── Quick reply modal ──────────────────────────────────────────────────────────
    function openQRModal(data) {
        const modal = document.getElementById('qrModal');
        document.getElementById('qrModalTitle').textContent = data ? 'Sửa mẫu trả lời' : 'Thêm mẫu trả lời';
        document.getElementById('qrId').value = data?.reply_id ?? '';
        document.getElementById('qrTitle').value = data?.title ?? '';
        document.getElementById('qrShortcut').value = data?.shortcut ?? '';
        document.getElementById('qrContent').value = data?.content ?? '';
        document.getElementById('qrCategory').value = data?.category ?? '';
        document.getElementById('qrSortOrder').value = data?.sort_order ?? 0;
        document.getElementById('qrActive').checked = data ? !!+data.is_active : true;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('qrTitle').focus();
    }

    function closeQRModal() {
        const modal = document.getElementById('qrModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('qrModal').addEventListener('click', function (e) {
        if (e.target === this) closeQRModal();
    });

    // ── Save quick reply ───────────────────────────────────────────────────────────
    async function saveQR(e) {
        e.preventDefault();
        const btn = e.submitter;
        btn.disabled = true;
        btn.textContent = 'Đang lưu...';

        const body = {
            reply_id: document.getElementById('qrId').value || null,
            title: document.getElementById('qrTitle').value,
            shortcut: document.getElementById('qrShortcut').value,
            content: document.getElementById('qrContent').value,
            category: document.getElementById('qrCategory').value || 'Chung',
            sort_order: +document.getElementById('qrSortOrder').value,
            is_active: document.getElementById('qrActive').checked ? 1 : 0,
        };

        const r = await fetch(_apiBase + '/admin/api/manage-quick-replies.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(r => r.json());

        btn.disabled = false;
        btn.textContent = 'Lưu mẫu';

        if (r.success) {
            showToast('Đã lưu mẫu trả lời', 'success');
            closeQRModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(r.message || 'Có lỗi xảy ra', 'error');
        }
    }

    // ── Delete quick reply ─────────────────────────────────────────────────────────
    async function deleteQR(id, btn) {
        if (!confirm('Xóa mẫu này?')) return;
        btn.disabled = true;

        const r = await fetch(_apiBase + '/admin/api/manage-quick-replies.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reply_id: id })
        }).then(r => r.json());

        if (r.success) {
            btn.closest('tr')?.remove();
            showToast('Đã xóa mẫu', 'success');
        } else {
            btn.disabled = false;
            showToast('Lỗi: ' + r.message, 'error');
        }
    }

    // ── Save settings ──────────────────────────────────────────────────────────────
    async function saveSettings(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {};
        formData.forEach((v, k) => data[k] = v);

        // Handle unchecked checkboxes
        ['chat_enabled', 'auto_reply_enabled'].forEach(k => {
            if (!(k in data)) data[k] = '0';
        });

        const r = await fetch(_apiBase + '/admin/api/chat-settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());

        if (r.success) showToast('Đã lưu cài đặt', 'success');
        else showToast('Lỗi lưu cài đặt', 'error');
    }
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>