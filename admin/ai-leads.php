<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

$page_title = 'Khách Hàng Tiềm Năng (AI Leads)';
$page_subtitle = 'Danh sách khách hàng được AI trích xuất và phân tích tự động từ khung chat.';

// Lấy danh sách leads
try {
    $stmt = $db->query("
        SELECT l.*, c.last_message_at, 
               (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = l.conversation_id) as total_msgs
        FROM ai_extracted_leads l
        LEFT JOIN chat_conversations c ON l.conversation_id = c.conversation_id
        ORDER BY l.updated_at DESC
    ");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Nếu chưa có bảng, thử tạo tự động
    $db->exec("CREATE TABLE IF NOT EXISTS ai_extracted_leads (lead_id INT AUTO_INCREMENT PRIMARY KEY, conversation_id INT NOT NULL, full_name VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, room_interests TEXT DEFAULT NULL, intended_dates VARCHAR(255) DEFAULT NULL, ai_learned_summary TEXT DEFAULT NULL, potential_score ENUM('high', 'medium', 'low') DEFAULT 'medium', is_converted TINYINT(1) DEFAULT 0, status ENUM('new', 'contacted', 'closed', 'junk') DEFAULT 'new', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY unique_conv (conversation_id), KEY idx_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $leads = [];
}

require_once 'includes/admin-header.php';
?>

<div class="space-y-6 animate-fade-in">
    <!-- Thống kê nhanh -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card bg-white dark:bg-slate-800 border-l-4 border-indigo-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tổng số Leads</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo count($leads); ?></p>
        </div>
        <div class="stat-card bg-white dark:bg-slate-800 border-l-4 border-emerald-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Đã chốt đơn</p>
            <?php 
                $converted = array_filter($leads, function($l) { return $l['is_converted'] == 1; });
            ?>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo count($converted); ?></p>
        </div>
        <div class="stat-card bg-slate-900 text-white border-l-4 border-amber-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tỷ lệ chuyển đổi AI</p>
            <p class="text-3xl font-black text-amber-500"><?php echo count($leads) > 0 ? round((count($converted)/count($leads))*100, 1) : 0; ?>%</p>
        </div>
    </div>

    <!-- Danh sách Leads -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                        <th class="px-6 py-4">Khách hàng</th>
                        <th class="px-6 py-4">Nhu cầu & Ngày dự kiến</th>
                        <th class="px-6 py-4">AI Đã học được gì (Insights)</th>
                        <th class="px-6 py-4">Tiềm năng</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    <?php foreach ($leads as $lead): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($lead['full_name'] ?: 'Khách vãng lai'); ?></div>
                            <div class="text-xs text-slate-500"><?php echo $lead['phone']; ?></div>
                            <div class="text-[10px] text-slate-400 font-mono"><?php echo $lead['email']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400"><?php echo htmlspecialchars($lead['room_interests'] ?: 'Chưa rõ'); ?></div>
                            <div class="text-[10px] text-slate-500 mt-1 italic"><?php echo $lead['intended_dates'] ?: 'Đang tìm hiểu...'; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="max-w-[300px] text-xs text-slate-600 dark:text-slate-400 leading-relaxed bg-amber-50 dark:bg-amber-900/10 p-3 rounded-xl border border-amber-100 dark:border-amber-800/30">
                                <span class="material-symbols-outlined text-[14px] align-middle mr-1 text-amber-600">psychology</span>
                                <?php echo htmlspecialchars($lead['ai_learned_summary'] ?: 'Đang thu thập dữ liệu...'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                                $color = 'slate';
                                if($lead['potential_score'] == 'high') $color = 'emerald';
                                if($lead['potential_score'] == 'medium') $color = 'amber';
                                if($lead['potential_score'] == 'low') $color = 'rose';
                            ?>
                            <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700 dark:bg-<?php echo $color; ?>-900/30 dark:text-<?php echo $color; ?>-400">
                                <?php echo $lead['potential_score']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($lead['is_converted']): ?>
                                <span class="flex items-center gap-1 text-xs font-black text-emerald-600">
                                    <span class="material-symbols-outlined text-sm">check_circle</span> ĐÃ CHỐT
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-bold text-slate-400 italic">Chưa chốt</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <a href="chat.php?conv_id=<?php echo $lead['conversation_id']; ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-all flex items-center gap-2 text-xs font-bold">
                                <span class="material-symbols-outlined text-sm">chat</span> Xem Chat
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic text-sm">Chưa thu thập được khách hàng tiềm năng nào từ AI.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>