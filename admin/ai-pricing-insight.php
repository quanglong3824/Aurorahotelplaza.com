<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../helpers/ai-helper.php';
$db = getDB();

$page_title = 'AI Phân tích Giá phòng Khu vực';
$page_subtitle = 'Xem giá phòng trung bình theo mùa của các khu vực lân cận do AI phân tích và duyệt áp dụng.';

$success_msg = '';
$error_msg = '';

// Handle Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_pricing'])) {
    $prices_to_approve = $_POST['prices'] ?? []; 
    
    if (!empty($prices_to_approve)) {
        $stmt = $db->prepare("INSERT INTO room_pricing (room_type_id, start_date, end_date, price, pricing_type, description) VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($prices_to_approve as $index => $p) {
            if (isset($_POST['approve'][$index])) {
                try {
                    $stmt->execute([
                        $p['room_type_id'],
                        $p['start_date'],
                        $p['end_date'],
                        $p['price'],
                        $p['pricing_type'],
                        $p['description']
                    ]);
                    $count++;
                } catch (Exception $e) {
                    $error_msg .= "Lỗi cập nhật cho phòng ID {$p['room_type_id']}: " . $e->getMessage() . "<br>";
                }
            }
        }
        if ($count > 0) {
            $success_msg = "Đã duyệt và cập nhật $count mức giá phòng thành công!";
        } else if (empty($error_msg)) {
            $error_msg = "Bạn chưa chọn mục nào để duyệt.";
        }
    }
}

// Fetch Room Types
$room_types = $db->query("SELECT room_type_id, type_name, category, base_price FROM room_types WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

$ai_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_pricing'])) {
    $prompt = "Bạn là chuyên gia phân tích giá khách sạn. Hãy phân tích giá phòng trung bình theo mùa của các khu vực lân cận Aurora Hotel Plaza.
    Dữ liệu phòng hiện tại: " . json_encode($room_types) . ".
    
    YÊU CẦU BẮT BUỘC:
    1. Chỉ trả về một MẢNG JSON duy nhất.
    2. Không có văn bản giải thích trước hoặc sau JSON.
    3. Không sử dụng dấu ngoặc khối code (```json).
    4. Cấu trúc mỗi đối tượng:
    {
        \"room_type_id\": (ID số),
        \"room_name\": \"(Tên phòng)\",
        \"season\": \"(Tên mùa)\",
        \"start_date\": \"YYYY-MM-DD\",
        \"end_date\": \"YYYY-MM-DD\",
        \"suggested_price\": (Số nguyên),
        \"pricing_type\": \"seasonal\"
    }
    
    Các mùa cần phân tích:
    - Mùa cao điểm: 2026-06-01 đến 2026-08-31
    - Mùa thấp điểm: 2026-09-01 đến 2026-11-30";
    
    // Call AI
    $active_provider = get_active_ai_provider();
    $ai_response = call_ai_sync($prompt, $db);
    
    // Check if the response is empty or an error from the helper
    if (empty(trim($ai_response))) {
        $ai_results = null;
        $json_error = "AI không trả về dữ liệu (Rỗng). Có thể do lỗi kết nối hoặc API Key hết hạn. (Provider: $active_provider)";
    } else if (strpos($ai_response, 'Lỗi:') === 0 || strpos($ai_response, 'Hệ thống đang quá tải') !== false) {
        $ai_results = null;
        $json_error = $ai_response . " (Provider: $active_provider)";
    } else {
        // Improved JSON extraction: Find the first [ and the last ]
        $json_start = strpos($ai_response, '[');
        $json_end = strrpos($ai_response, ']');
        
        if ($json_start !== false && $json_end !== false && $json_end > $json_start) {
            $json_str = substr($ai_response, $json_start, $json_end - $json_start + 1);
            
            // Clean up common AI-generated JSON issues
            $json_str = preg_replace('/,\s*([\]\}])/', '$1', $json_str); // Remove trailing commas
            
            $ai_results = json_decode($json_str, true);
            $json_error = ($ai_results === null) ? json_last_error_msg() : '';
        } else {
            $ai_results = null;
            $json_error = "Không tìm thấy định dạng mảng JSON [ ] trong phản hồi. (Provider: $active_provider)";
        }
    }
    
    if (!$ai_results || !is_array($ai_results)) {
        $error_msg = "Lỗi xử lý dữ liệu AI: " . $json_error . ". <br>Độ dài phản hồi: " . strlen($ai_response) . " ký tự. <br>Nội dung nhận được: <pre class='text-[10px] mt-2 p-2 bg-slate-100 dark:bg-slate-900 rounded overflow-x-auto'>" . htmlspecialchars(substr($ai_response, 0, 1000)) . "</pre>";
    }
}

require_once 'includes/admin-header.php';
?>

<div class="space-y-6">
    <?php if ($success_msg): ?>
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 p-4 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <p class="font-medium text-sm"><?php echo $success_msg; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 p-4 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <p class="font-medium text-sm"><?php echo $error_msg; ?></p>
        </div>
    <?php endif; ?>

    <!-- Nút Yêu cầu AI -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex items-center justify-between">
        <div>
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Thu thập tình báo giá</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Yêu cầu AI quét giá các khách sạn tương đương trong khu vực lân cận để đề xuất giá theo mùa.</p>
        </div>
        <form method="POST">
            <button type="submit" name="analyze_pricing" 
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all text-sm flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-none">
                <span class="material-symbols-outlined text-sm">smart_toy</span>
                Phân tích giá lân cận bằng AI
            </button>
        </form>
    </div>

    <!-- Kết quả AI -->
    <?php if (!empty($ai_results)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Kết quả phân tích (Đề xuất)</h3>
        </div>
        <form method="POST" action="">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                            <th class="px-6 py-4 text-center w-16">Duyệt</th>
                            <th class="px-6 py-4">Loại phòng</th>
                            <th class="px-6 py-4">Mùa / Mô tả</th>
                            <th class="px-6 py-4">Thời gian áp dụng</th>
                            <th class="px-6 py-4">Giá đề xuất (VND)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                        <?php foreach ($ai_results as $index => $result): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors text-sm">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" name="approve[<?php echo $index; ?>]" value="1" checked
                                       class="w-5 h-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                <!-- Ẩn dữ liệu để gửi lên khi duyệt -->
                                <input type="hidden" name="prices[<?php echo $index; ?>][room_type_id]" value="<?php echo htmlspecialchars($result['room_type_id']); ?>">
                                <input type="hidden" name="prices[<?php echo $index; ?>][start_date]" value="<?php echo htmlspecialchars($result['start_date']); ?>">
                                <input type="hidden" name="prices[<?php echo $index; ?>][end_date]" value="<?php echo htmlspecialchars($result['end_date']); ?>">
                                <input type="hidden" name="prices[<?php echo $index; ?>][price]" value="<?php echo htmlspecialchars($result['suggested_price']); ?>">
                                <input type="hidden" name="prices[<?php echo $index; ?>][pricing_type]" value="<?php echo htmlspecialchars($result['pricing_type'] ?? 'seasonal'); ?>">
                                <input type="hidden" name="prices[<?php echo $index; ?>][description]" value="<?php echo htmlspecialchars('AI Đề xuất: ' . $result['season']); ?>">
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300">
                                <?php echo htmlspecialchars($result['room_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-amber-600 font-medium">
                                <?php echo htmlspecialchars($result['season']); ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <?php echo date('d/m/Y', strtotime($result['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($result['end_date'])); ?>
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                <?php echo number_format($result['suggested_price'], 0, ',', '.'); ?> ₫
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex justify-end">
                <button type="submit" name="approve_pricing" 
                        class="px-8 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-all text-sm flex items-center gap-2 shadow-lg shadow-emerald-200 dark:shadow-none">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Duyệt & Cập nhật Giá
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin-footer.php'; ?>
