<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

$page_title = 'AI Competitor Intelligence';
$page_subtitle = 'Phân tích đối thủ đa tầng bằng AI (Structural, Semantic, Predictive).';

// Xử lý thêm đối thủ mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_competitor'])) {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $instruction = trim($_POST['instruction'] ?? '');
    
    if ($name && $url) {
        $stmt = $db->prepare("INSERT INTO competitor_intelligence (name, url, instruction, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$name, $url, $instruction]);
        header('Location: competitor-intelligence.php?success=1');
        exit;
    }
}

// Lấy danh sách đối thủ
$competitors = $db->query("SELECT * FROM competitor_intelligence ORDER BY created_at DESC")->fetchAll();

require_once 'includes/admin-header.php';
?>

<div class="space-y-6">
    <!-- Form thêm đối thủ -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight mb-4">Ra lệnh cho AI quét đối thủ</h3>
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="name" placeholder="Tên đối thủ (VD: Mường Thanh)" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
                <input type="url" name="url" placeholder="URL cần quét (VD: https://abc.com/rooms)" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
            </div>
            <div>
                <textarea name="instruction" placeholder="Ra lệnh cho AI (VD: Hãy phân tích các gói tiệc cưới của họ và so sánh với giá bên mình...)" rows="2"
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm"></textarea>
            </div>
            <button type="submit" name="add_competitor" 
                    class="w-full md:w-auto px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-none">
                <span class="material-symbols-outlined text-sm">rocket_launch</span>
                Gửi AI đi quét & Phân tích
            </button>
        </form>
    </div>

    <!-- Danh sách đối thủ -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Danh sách tình báo đối thủ</h3>
            <div class="flex gap-2">
                <a href="cron-competitor-intel.php" target="_blank" class="px-4 py-2 bg-emerald-500/10 text-emerald-600 rounded-lg text-xs font-bold hover:bg-emerald-500/20 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">play_arrow</span> Chạy Cron thủ công
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                        <th class="px-6 py-4">Đối thủ</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4">USP / Định vị</th>
                        <th class="px-6 py-4">Phân tích cuối</th>
                        <th class="px-6 py-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    <?php foreach ($competitors as $comp): 
                        $data = json_decode($comp['analysis_data'], true);
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors text-sm">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($comp['name']); ?></span>
                                <a href="<?php echo $comp['url']; ?>" target="_blank" class="text-[10px] text-indigo-500 hover:underline truncate max-w-[200px]"><?php echo $comp['url']; ?></a>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($comp['status'] === 'completed'): ?>
                                <span class="badge badge-success">Đã phân tích</span>
                            <?php elseif ($comp['status'] === 'processing'): ?>
                                <span class="badge badge-info animate-pulse">Đang xử lý...</span>
                            <?php elseif ($comp['status'] === 'error'): ?>
                                <span class="badge badge-danger" title="<?php echo htmlspecialchars($comp['error_message']); ?>">Lỗi API</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Đang chờ</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($data): ?>
                                <div class="max-w-[300px]">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white"><?php echo $data['semantic_analysis']['positioning'] ?? 'N/A'; ?></p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <?php foreach (array_slice($data['summary']['usp'] ?? [], 0, 2) as $usp): ?>
                                            <span class="text-[9px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded"><?php echo $usp; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 italic text-xs">Chưa có dữ liệu</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                            <?php echo $comp['last_analyzed'] ? date('H:i d/m', strtotime($comp['last_analyzed'])) : '—'; ?>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick='viewDetails(<?php echo json_encode($comp); ?>)' class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-indigo-600 transition-all">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Chi tiết Phân tích (Dạng slide-over hoặc modal rộng) -->
<div id="analysisModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200 dark:border-slate-700">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-900 dark:text-white" id="modalTitle">Chi tiết Tình báo</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-all">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="modalContent" class="p-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                <!-- Data will be injected here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(comp) {
    const modal = document.getElementById('analysisModal');
    const content = document.getElementById('modalContent');
    const title = document.getElementById('modalTitle');
    
    title.innerText = "Báo cáo Tình báo: " + comp.name;
    const data = JSON.parse(comp.analysis_data);
    
    let instructionHtml = comp.instruction ? `
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-xl mb-6 border border-indigo-100 dark:border-indigo-900/30">
            <p class="text-[10px] font-bold text-indigo-500 uppercase mb-1">Mệnh lệnh đã giao:</p>
            <p class="text-xs text-indigo-700 dark:text-indigo-300 italic font-medium">"${comp.instruction}"</p>
        </div>
    ` : '';
    
    if (!data) {
        content.innerHTML = instructionHtml + "<p class='text-center py-12 text-slate-400 italic'>Chưa có dữ liệu phân tích sâu.</p>";
    } else {
        content.innerHTML = instructionHtml + `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Cấp độ 1 -->
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-indigo-500 uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">list_alt</span> Cấp độ 1: Trích xuất thực thể
                    </h4>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl space-y-3">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Giá & Ưu đãi</p>
                            <p class="text-sm font-bold text-emerald-600">${data.summary.price_range}</p>
                            <ul class="text-xs text-slate-600 dark:text-slate-400 list-disc ml-4 mt-2">
                                ${(data.structural_extraction.promotions || []).map(p => `<li>${p}</li>`).join('')}
                            </ul>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">USP Đặc trưng</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                ${(data.summary.usp || []).map(u => `<span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[10px] font-bold">${u}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cấp độ 2 -->
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-purple-500 uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">psychology</span> Cấp độ 2: Phân tích ngữ nghĩa
                    </h4>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl space-y-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Định vị & Đối tượng</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 mt-1">${data.semantic_analysis.positioning}</p>
                            <p class="text-xs text-slate-500 italic mt-1">Nhắm tới: ${data.semantic_analysis.target_audience}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Sắc thái thương hiệu</p>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">${data.semantic_analysis.sentiment}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cấp độ 3 -->
            <div class="space-y-4 pt-4">
                <h4 class="text-xs font-black text-rose-500 uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">rocket_launch</span> Cấp độ 3: Chiến lược phản công
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-rose-100 dark:border-rose-900/30 p-4 rounded-xl">
                        <p class="text-[10px] font-bold text-rose-500 uppercase mb-3">Dự báo bước đi tiếp theo</p>
                        <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2">
                            ${(data.predictive_strategy.next_moves || []).map(m => `<li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-rose-400 mt-1 shrink-0"></span> ${m}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="bg-rose-500 text-white p-4 rounded-xl shadow-lg">
                        <p class="text-[10px] font-bold text-rose-100 uppercase mb-3">Đề xuất hành động cho Aurora</p>
                        <ul class="text-xs space-y-2">
                            ${(data.predictive_strategy.counter_strategies || []).map(s => `<li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-white mt-1 shrink-0"></span> ${s}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('analysisModal').classList.add('hidden');
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>

<?php require_once 'includes/admin-footer.php'; ?>
