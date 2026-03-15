<div class="space-y-6">

    <!-- Flash message -->
    <?php if ($msg): ?>
        <div id="flashMsg"
            class="flex items-center gap-3 p-4 rounded-xl text-sm font-medium
        <?php echo in_array($msg, ['resolved', 'deleted', 'cleared', 'ignored']) ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-blue-50 text-blue-800 border border-blue-200'; ?>">
            <span class="material-symbols-outlined text-xl">
                <?php echo in_array($msg, ['resolved', 'deleted', 'cleared', 'ignored']) ? 'check_circle' : 'info'; ?>
            </span>
            <?php
            $msgs = [
                'resolved' => 'Đã đánh dấu lỗi là đã giải quyết.',
                'ignored' => 'Đã bỏ qua lỗi này.',
                'reopened' => 'Đã mở lại lỗi.',
                'deleted' => 'Đã xóa lỗi.',
                'cleared' => 'Đã xóa tất cả lỗi đã giải quyết/bỏ qua.',
                'notes_saved' => 'Đã lưu ghi chú.',
                'reanalyzed' => 'Đã gửi yêu cầu phân tích lại cho AI.',
            ];
            echo $msgs[$msg] ?? $msg;
            ?>
            <button onclick="document.getElementById('flashMsg').remove()"
                class="ml-auto text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <?php
        $statCards = [
            ['label' => 'Tổng lỗi', 'value' => $stats['total'] ?? 0, 'icon' => 'bug_report', 'color' => 'gray'],
            ['label' => 'Critical', 'value' => $stats['critical'] ?? 0, 'icon' => 'dangerous', 'color' => 'red'],
            ['label' => 'Error', 'value' => $stats['error'] ?? 0, 'icon' => 'error', 'color' => 'orange'],
            ['label' => 'Warning', 'value' => $stats['warning'] ?? 0, 'icon' => 'warning', 'color' => 'yellow'],
            ['label' => '24h gần nhất', 'value' => $stats['last_24h'] ?? 0, 'icon' => 'schedule', 'color' => 'purple'],
            ['label' => 'AI phân tích', 'value' => $stats['ai_analyzed'] ?? 0, 'icon' => 'psychology', 'color' => 'indigo'],
            ['label' => 'Gửi Telegram', 'value' => $stats['telegram_sent'] ?? 0, 'icon' => 'send', 'color' => 'blue'],
            ['label' => 'Đã giải quyết', 'value' => $stats['resolved'] ?? 0, 'icon' => 'check_circle', 'color' => 'green'],
        ];
        foreach ($statCards as $card):
            $colorMap = [
                'red' => 'text-red-600 bg-red-50',
                'orange' => 'text-orange-600 bg-orange-50',
                'yellow' => 'text-yellow-600 bg-yellow-50',
                'green' => 'text-green-600 bg-green-50',
                'blue' => 'text-blue-600 bg-blue-50',
                'indigo' => 'text-indigo-600 bg-indigo-50',
                'purple' => 'text-purple-600 bg-purple-50',
                'gray' => 'text-gray-600 bg-gray-100',
            ];
            $cls = $colorMap[$card['color']] ?? 'text-gray-600 bg-gray-100';
            ?>
            <div class="stat-card text-center">
                <div class="w-10 h-10 rounded-xl <?php echo $cls; ?> flex items-center justify-center mx-auto mb-2">
                    <span class="material-symbols-outlined text-xl">
                        <?php echo $card['icon']; ?>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($card['value']); ?>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    <?php echo $card['label']; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($detailRow): ?>
        <!-- ═══ DETAIL VIEW ═══════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <a href="ai-bug.php?severity=<?php echo $filterSeverity; ?>&status=<?php echo $filterStatus; ?>"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-gray-500">arrow_back</span>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded
                            <?php echo match ($detailRow['severity']) {
                                'critical' => 'bg-red-100 text-red-700',
                                'error' => 'bg-orange-100 text-orange-700',
                                'warning' => 'bg-yellow-100 text-yellow-700',
                                default => 'bg-gray-100 text-gray-700',
                            }; ?>">
                                <?php echo strtoupper($detailRow['severity']); ?>
                            </span>
                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                <?php echo htmlspecialchars($detailRow['error_type']); ?>
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded
                            <?php echo match ($detailRow['status']) {
                                'resolved' => 'bg-green-100 text-green-700',
                                'ignored' => 'bg-gray-100 text-gray-500',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                default => 'bg-red-50 text-red-700',
                            }; ?>">
                                <?php
                                $statusLabels = ['open' => 'Mở', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã giải quyết', 'ignored' => 'Bỏ qua'];
                                echo $statusLabels[$detailRow['status']] ?? $detailRow['status']; ?>
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mt-1">Bug #
                            <?php echo $detailRow['id']; ?>
                        </h2>
                    </div>
                </div>
                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <?php if ($detailRow['status'] === 'open' || $detailRow['status'] === 'in_progress'): ?>
                        <form method="POST" class="inline">
                            <?php echo Security::getCSRFInput(); ?>
                            <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                            <input type="hidden" name="action" value="resolve">
                            <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition-colors">
                                <span class="material-symbols-outlined text-base">check_circle</span>
                                Đã giải quyết
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <?php echo Security::getCSRFInput(); ?>
                            <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                            <input type="hidden" name="action" value="ignore">
                            <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-300 transition-colors">
                                <span class="material-symbols-outlined text-base">visibility_off</span>
                                Bỏ qua
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="inline">
                            <?php echo Security::getCSRFInput(); ?>
                            <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                            <input type="hidden" name="action" value="reopen">
                            <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                                <span class="material-symbols-outlined text-base">refresh</span>
                                Mở lại
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" class="inline">
                        <?php echo Security::getCSRFInput(); ?>
                        <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                        <input type="hidden" name="action" value="reanalyze">
                        <button type="submit"
                            class="flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                            <span class="material-symbols-outlined text-base">psychology</span>
                            Phân tích lại AI
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Error detail -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Message -->
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-red-500 text-base">error</span>
                            <span class="text-xs font-bold text-red-600 uppercase tracking-wide">Thông điệp lỗi</span>
                        </div>
                        <pre
                            class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap font-mono break-all"><?php echo htmlspecialchars($detailRow['message']); ?></pre>
                    </div>

                    <!-- File & Location -->
                    <?php if ($detailRow['file_path']): ?>
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-gray-400 text-base">code</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Vị trí trong code</span>
                            </div>
                            <code
                                class="text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($detailRow['file_path']); ?></code>
                            <?php if ($detailRow['line_number']): ?>
                                <span class="ml-3 text-xs bg-gray-200 dark:bg-slate-600 px-2 py-0.5 rounded font-mono">
                                    Dòng
                                    <?php echo $detailRow['line_number']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Context data -->
                    <?php
                    $ctx = json_decode($detailRow['context_data'] ?? '{}', true);
                    if (!empty($ctx)):
                        ?>
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-gray-400 text-base">dataset</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Context / Stack
                                    Trace</span>
                            </div>
                            <pre
                                class="text-xs text-gray-700 dark:text-gray-300 overflow-auto max-h-48 whitespace-pre-wrap font-mono"><?php echo htmlspecialchars(json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        </div>
                    <?php endif; ?>

                    <!-- AI Analysis -->
                    <div
                        class="rounded-xl border-2 overflow-hidden <?php echo $detailRow['ai_analyzed'] ? 'border-indigo-200 dark:border-indigo-700' : 'border-dashed border-gray-300 dark:border-slate-600'; ?>">
                        <div
                            class="flex items-center justify-between px-4 py-3 <?php echo $detailRow['ai_analyzed'] ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'bg-gray-50 dark:bg-slate-700/40'; ?>">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-indigo-500 text-xl">psychology</span>
                                <span class="font-bold text-sm text-indigo-700 dark:text-indigo-300">Phân tích AI
                                    (Gemini)</span>
                            </div>
                            <?php if (!$detailRow['ai_analyzed']): ?>
                                <span class="text-xs text-gray-400">Chưa phân tích</span>
                            <?php elseif ($detailRow['messenger_sent']): ?>
                                <span class="text-xs flex items-center gap-1 text-green-600 font-medium">
                                    <span class="material-symbols-outlined text-base">send</span>
                                    Gửi Telegram
                                    <?php echo $detailRow['messenger_sent_at'] ? date('H:i d/m', strtotime($detailRow['messenger_sent_at'])) : ''; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <?php if ($detailRow['ai_analysis']): ?>
                                <div
                                    class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300 text-sm leading-relaxed whitespace-pre-wrap">
                                    <?php echo nl2br(htmlspecialchars($detailRow['ai_analysis'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-400 italic">AI chưa phân tích lỗi này. Nhấn "Phân tích lại AI" để
                                    kích hoạt.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-gray-400 text-base">edit_note</span>
                            <span class="font-bold text-sm text-gray-700 dark:text-gray-300">Ghi chú của Admin</span>
                        </div>
                        <form method="POST">
                            <?php echo Security::getCSRFInput(); ?>
                            <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                            <input type="hidden" name="action" value="save_notes">
                            <textarea name="notes" rows="3" placeholder="Thêm ghi chú, bước khắc phục..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($detailRow['notes'] ?? ''); ?></textarea>
                            <button type="submit"
                                class="mt-2 px-4 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                                Lưu ghi chú
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Meta -->
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-4 space-y-3 text-sm">
                        <h4
                            class="font-bold text-gray-700 dark:text-gray-300 text-xs uppercase tracking-wide mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">info</span>
                            Thông tin
                        </h4>
                        <?php
                        $metaItems = [
                            ['icon' => 'calendar_today', 'label' => 'Thời gian', 'value' => date('H:i:s m/d/Y', strtotime($detailRow['created_at']))],
                            ['icon' => 'visibility', 'label' => 'Lần cuối thấy', 'value' => $detailRow['last_seen_at'] ? date('H:i m/d/Y', strtotime($detailRow['last_seen_at'])) : '—'],
                            ['icon' => 'repeat', 'label' => 'Số lần xảy ra', 'value' => number_format($detailRow['occurrence_count'])],
                            ['icon' => 'language', 'label' => 'URL', 'value' => $detailRow['page_url'] ? '<span class="break-all text-xs">' . htmlspecialchars($detailRow['page_url']) . '</span>' : '—'],
                            ['icon' => 'person', 'label' => 'User ID', 'value' => $detailRow['user_id'] ?: 'Khách'],
                            ['icon' => 'device_hub', 'label' => 'IP', 'value' => htmlspecialchars($detailRow['ip_address'] ?? '—')],
                            ['icon' => 'fingerprint', 'label' => 'Fingerprint', 'value' => '<code class="text-xs font-mono text-gray-500">' . htmlspecialchars($detailRow['fingerprint'] ?? '—') . '</code>'],
                        ];
                        foreach ($metaItems as $item):
                            ?>
                            <div class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-gray-400 text-base mt-0.5 shrink-0">
                                    <?php echo $item['icon']; ?>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs text-gray-400">
                                        <?php echo $item['label']; ?>
                                    </div>
                                    <div class="text-gray-800 dark:text-gray-200 font-medium mt-0.5">
                                        <?php echo $item['value']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Danger zone -->
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                        <h4 class="font-bold text-red-700 text-xs uppercase tracking-wide mb-3">Hành động nguy hiểm</h4>
                        <form method="POST" onsubmit="return confirm('Xác nhận xóa lỗi này?')">
                            <?php echo Security::getCSRFInput(); ?>
                            <input type="hidden" name="error_id" value="<?php echo $detailRow['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">
                                <span class="material-symbols-outlined text-base">delete_forever</span>
                                Xóa vĩnh viễn
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══ LIST VIEW ════════════════════════════════════════════════════════ -->

        <!-- Filters & Actions bar -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-5">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base">search</span>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filterSearch); ?>"
                        placeholder="Tìm kiếm lỗi..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Severity -->
                <select name="severity"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tất cả mức độ</option>
                    <option value="critical" <?php echo $filterSeverity === 'critical' ? 'selected' : ''; ?>>Critical
                    </option>
                    <option value="error" <?php echo $filterSeverity === 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="warning" <?php echo $filterSeverity === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    <option value="info" <?php echo $filterSeverity === 'info' ? 'selected' : ''; ?>>Info</option>
                </select>

                <!-- Type -->
                <select name="type"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($typeList as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $filterType === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Status -->
                <select name="status"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Đang mở</option>
                    <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>Đang xử lý
                    </option>
                    <option value="resolved" <?php echo $filterStatus === 'resolved' ? 'selected' : ''; ?>>Đã giải quyết
                    </option>
                    <option value="ignored" <?php echo $filterStatus === 'ignored' ? 'selected' : ''; ?>>Bỏ qua</option>
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                </select>

                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                    Lọc
                </button>
                <a href="ai-bug.php"
                    class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                    Xóa lọc
                </a>
            </form>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                <p class="text-sm text-gray-500">Tìm thấy <strong class="text-gray-900 dark:text-white">
                        <?php echo number_format($totalRows); ?>
                    </strong> lỗi</p>
                <form method="POST" onsubmit="return confirm('Xóa toàn bộ lỗi đã giải quyết/bỏ qua?')">
                    <?php echo Security::getCSRFInput(); ?>
                    <input type="hidden" name="action" value="clear_resolved">
                    <button type="submit"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition-colors font-medium">
                        <span class="material-symbols-outlined text-sm">delete_sweep</span>
                        Xóa đã giải quyết/bỏ qua
                    </button>
                </form>
            </div>
        </div>

        <!-- Error List -->
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <?php if (empty($errors)): ?>
                <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <span class="material-symbols-outlined text-6xl mb-4 text-green-400">verified</span>
                    <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">Không có lỗi nào!</p>
                    <p class="text-sm mt-1">Tất cả hệ thống đang hoạt động bình thường.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    ID / Thời gian</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Mức / Loại</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-2/5">
                                    Thông điệp lỗi</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    URL</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Lần</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    AI</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Trạng thái</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <?php foreach ($errors as $err):
                                $severityStyle = match ($err['severity']) {
                                    'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'error' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                    'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                                $rowBg = $err['severity'] === 'critical' ? 'bg-red-50/30 dark:bg-red-900/10' : '';
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors <?php echo $rowBg; ?>">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-mono font-bold text-xs text-gray-400">#
                                            <?php echo $err['id']; ?>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <?php echo date('H:i d/m', strtotime($err['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?php echo $severityStyle; ?>">
                                            <?php echo strtoupper($err['severity']); ?>
                                        </span>
                                        <div class="text-xs text-gray-400 mt-1 font-mono">
                                            <?php echo htmlspecialchars($err['error_type']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-800 dark:text-gray-200 text-xs line-clamp-2 font-medium">
                                            <?php echo htmlspecialchars(substr($err['message'], 0, 180)); ?>
                                        </p>
                                        <?php if ($err['file_path']): ?>
                                            <p class="text-[10px] text-gray-400 font-mono mt-0.5 truncate max-w-xs">
                                                <?php echo htmlspecialchars(basename($err['file_path'])); ?>:
                                                <?php echo $err['line_number']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 max-w-[160px]">
                                        <p class="text-[10px] text-gray-400 truncate"
                                            title="<?php echo htmlspecialchars($err['page_url'] ?? ''); ?>">
                                            <?php
                                            $urlParsed = parse_url($err['page_url'] ?? '');
                                            echo htmlspecialchars(($urlParsed['path'] ?? '') . ('?' . ($urlParsed['query'] ?? '') !== '?' ? '?' . ($urlParsed['query'] ?? '') : ''));
                                            ?>
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="text-sm font-bold <?php echo $err['occurrence_count'] > 10 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'; ?>">
                                            <?php echo $err['occurrence_count']; ?>x
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($err['ai_analyzed']): ?>
                                            <span class="material-symbols-outlined text-indigo-500 text-base"
                                                title="Đã phân tích">psychology</span>
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-gray-300 text-base"
                                                title="Chưa phân tích">help_outline</span>
                                        <?php endif; ?>
                                        <?php if ($err['messenger_sent']): ?>
                                            <span class="material-symbols-outlined text-green-500 text-base ml-0.5"
                                                title="Đã gửi Messenger">send</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $statusStyle = match ($err['status']) {
                                            'resolved' => 'bg-green-100 text-green-700',
                                            'ignored' => 'bg-gray-100 text-gray-500',
                                            'in_progress' => 'bg-blue-100 text-blue-700',
                                            default => 'bg-red-100 text-red-700',
                                        };
                                        $statusLabel = match ($err['status']) {
                                            'resolved' => 'Đã xử lý',
                                            'ignored' => 'Bỏ qua',
                                            'in_progress' => 'Đang xử lý',
                                            default => 'Mở',
                                        };
                                        ?>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold <?php echo $statusStyle; ?>">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="ai-bug.php?id=<?php echo $err['id']; ?>&severity=<?php echo $filterSeverity; ?>&status=<?php echo $filterStatus; ?>"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors">
                                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-slate-700">
                        <p class="text-sm text-gray-500">Trang
                            <?php echo $page; ?> /
                            <?php echo $totalPages; ?>
                        </p>
                        <div class="flex gap-1.5">
                            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&severity=<?php echo $filterSeverity; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&q=<?php echo urlencode($filterSearch); ?>"
                                    class="px-3 py-1.5 text-sm rounded-lg font-medium transition-colors
                       <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Settings Card -->
    <div id="settingsCard"
        class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6">
        <button onclick="document.getElementById('settingsBody').classList.toggle('hidden')"
            class="w-full flex items-center justify-between text-left">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gray-500">settings</span>
                <span class="font-bold text-gray-800 dark:text-white">Cài đặt Bug Tracker &amp; Telegram</span>
            </div>
            <span class="material-symbols-outlined text-gray-400">expand_more</span>
        </button>
        <div id="settingsBody" class="mt-4 hidden">
            <form method="POST" action="api/save-bug-tracker-settings.php" class="space-y-4">
                <?php echo Security::getCSRFInput(); ?>
                <?php
                $settingsValues = [];
                try {
                    $sStmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('telegram_bot_token','telegram_chat_id','bug_tracker_enabled','bug_tracker_min_severity')");
                    foreach ($sStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                        $settingsValues[$s['setting_key']] = $s['setting_value'];
                    }
                } catch (\Throwable $e) {
                }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Telegram Bot Token
                            <span class="text-xs text-gray-400 font-normal ml-1">(lấy từ @BotFather)</span>
                        </label>
                        <input type="text" name="telegram_bot_token"
                            value="<?php echo htmlspecialchars($settingsValues['telegram_bot_token'] ?? ''); ?>"
                            placeholder="1234567890:AAF..." autocomplete="off"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Telegram Chat ID
                            <span class="text-xs text-gray-400 font-normal ml-1">(ID cá nhân hoặc group)</span>
                        </label>
                        <input type="text" name="telegram_chat_id"
                            value="<?php echo htmlspecialchars($settingsValues['telegram_chat_id'] ?? ''); ?>"
                            placeholder="123456789 hoặc -1001234567890"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mức độ tối
                            thiểu gửi Telegram</label>
                        <select name="bug_tracker_min_severity"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="critical" <?php echo ($settingsValues['bug_tracker_min_severity'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critical only</option>
                            <option value="error" <?php echo ($settingsValues['bug_tracker_min_severity'] ?? 'error') === 'error' ? 'selected' : ''; ?>>Error + Critical</option>
                            <option value="warning" <?php echo ($settingsValues['bug_tracker_min_severity'] ?? '') === 'warning' ? 'selected' : ''; ?>>Warning + above</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Trạng thái hệ
                            thống</label>
                        <select name="bug_tracker_enabled"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="1" <?php echo ($settingsValues['bug_tracker_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Bật</option>
                            <option value="0" <?php echo ($settingsValues['bug_tracker_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Tắt</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                        Lưu cài đặt
                    </button>
                    <a href="api/test-messenger.php" target="_blank"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                        Test gửi Telegram
                    </a>
                </div>

                <div
                    class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 text-xs text-blue-700 dark:text-blue-300 space-y-1.5">
                    <p class="font-bold">Hướng dẫn lấy Bot Token &amp; Chat ID (mất 2 phút):</p>
                    <p>1. Mở Telegram, tìm <strong>@BotFather</strong> &rarr; gõ <code
                            class="bg-blue-100 px-1 rounded">/newbot</code></p>
                    <p>2. Đặt tên bot &rarr; BotFather sẽ trả về <strong>Bot Token</strong> dạng <code
                            class="bg-blue-100 px-1 rounded">1234567890:AAF...</code></p>
                    <p>3. Nhắn tin cho bot của bạn vừa tạo (gửi chữ gì cũng được)</p>
                    <p>4. Mở URL: <code
                            class="bg-blue-100 px-1 rounded font-mono">https://api.telegram.org/bot<b>TOKEN</b>/getUpdates</code>
                    </p>
                    <p>5. Tìm <strong>"id"</strong> trong <code>message.from</code> &rarr; đó là <strong>Chat
                            ID</strong> của bạn</p>
                </div>
            </form>
        </div>
    </div>

</div>
