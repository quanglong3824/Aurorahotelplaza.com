<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
require_once '../helpers/competitor-analyzer.php';

echo "<h2>🚀 Kích hoạt CHIẾN DỊCH CÀN QUÉT CHIẾN LƯỢC (Massive Scout)...</h2>";
echo "<p>Đang tận dụng mô hình AI cao cấp (GLM-5/Gemini Pro) để phân tích toàn thị trường Biên Hòa...</p>";

$result = CompetitorAnalyzer::runMassiveStrategicScout();

if ($result['success']) {
    $dossier = $result['data'];
    echo "<div style='background:#f1f5f9; padding:20px; border-radius:12px; margin-top:20px; border:1px solid #e2e8f0;'>";
    echo "<h3 style='color:#4f46e5; margin-top:0;'>Hồ sơ Tình báo: " . $dossier['market_overview'] . "</h3>";
    
    echo "<h4>🎯 Danh sách mục tiêu đã nhận diện:</h4><ul>";
    foreach ($dossier['targets'] as $target) {
        $threat = ($target['threat_level'] === 'high') ? '🔴 Cao' : (($target['threat_level'] === 'medium') ? '🟠 Trung bình' : '🟢 Thấp');
        echo "<li><strong>{$target['name']}</strong> (Nguy cơ: $threat) - <em>Ưu thế: {$target['usp_discovery']}</em></li>";
    }
    echo "</ul>";

    echo "<h4>💡 Đề xuất hành động chiến lược:</h4><ul>";
    foreach ($dossier['strategic_recommendations'] as $rec) {
        echo "<li>$rec</li>";
    }
    echo "</ul>";
    echo "</div>";

    echo "<p style='color:green; font-weight:bold; margin-top:20px;'>✔️ Thành công! Các đối thủ mới đã được đưa vào hàng đợi quét sâu.</p>";
    echo "<p><a href='competitor-intelligence.php'>Quay lại trang Tình báo</a></p>";
} else {
    echo "<div style='background:#fef2f2; padding:20px; border-radius:12px; margin-top:20px; border:1px solid #fee2e2;'>";
    echo "<h3 style='color:#dc2626; margin-top:0;'>❌ Lỗi CHIẾN DỊCH CÀN QUÉT</h3>";
    echo "<p style='color:#991b1b;'>Lý do: " . $result['error'] . "</p>";
    echo "<p style='font-size:12px; color:#b91c1c;'>Gợi ý: Kiểm tra xem model GLM-5 có đang bị giới hạn hoặc API Key có đúng không.</p>";
    echo "</div>";
    echo "<p><a href='competitor-intelligence.php'>Quay lại trang Tình báo</a></p>";
}
