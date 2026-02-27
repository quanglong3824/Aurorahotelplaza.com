<?php
/**
 * Aurora Hotel Plaza - Detailed Pricing Management
 * Quản lý bảng giá chi tiết theo cấu trúc bảng giá lễ tân
 */
session_start();
require_once '../config/database.php';

$page_title = 'Bảng giá phòng chi tiết';
$page_subtitle = 'Giá đã bao gồm 5% phí dịch vụ và 8% VAT';

try {
    $db = getDB();

    // Get all room types with extended pricing columns
    $stmt = $db->query("
        SELECT rt.*, 
            (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') AS available_rooms,
            (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id) AS total_rooms
        FROM room_types rt 
        WHERE rt.status = 'active' 
        ORDER BY rt.category, rt.sort_order
    ");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Pricing page error: " . $e->getMessage());
    $room_types = [];
}

// Group rooms by category
$hotel_rooms = array_filter($room_types, fn($r) => $r['category'] === 'room');
$apartments = array_filter($room_types, fn($r) => $r['category'] === 'apartment');

include 'includes/admin-header.php';
?>

<!-- Quick Actions -->
<div class="flex flex-wrap gap-3 mb-6">
    <a href="room-type-form.php" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm loại phòng
    </a>
    <a href="pricing.php" class="btn btn-secondary btn-sm">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại
    </a>
</div>

<!-- Hotel Rooms Pricing -->
<div class="card mb-6">
    <div class="card-header">
        <div>
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">hotel</span>
                Bảng giá Phòng Khách sạn
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Aurora Hotel Plaza - 4 sao | 253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Tỉnh Đồng Nai
            </p>
        </div>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr style="background: linear-gradient(135deg, #1e293b, #334155);">
                        <th class="text-white">Loại phòng</th>
                        <th class="text-white">Diện tích</th>
                        <th class="text-white">View</th>
                        <th class="text-white">Loại giường</th>
                        <th class="text-white text-right">Giá công bố</th>
                        <th class="text-white text-right">Giá 2 người</th>
                        <th class="text-white text-right">Giá 1 người</th>
                        <th class="text-white text-right">Giá ngắn hạn</th>
                        <th class="text-white text-center">Phòng trống</th>
                        <th class="text-white">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hotel_rooms)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-8 text-gray-500">Chưa có loại phòng nào</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hotel_rooms as $type): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
                                <td class="font-semibold">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $type['size_sqm'] ? number_format($type['size_sqm']) . 'm²' : '-'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($type['view_type'] ?? 'Thành phố'); ?></td>
                                <td class="text-sm"><?php echo htmlspecialchars($type['bed_type'] ?? '-'); ?></td>
                                <td class="text-right text-gray-500">
                                    <?php if ($type['price_published']): ?>
                                        <span
                                            class="line-through"><?php echo number_format($type['price_published'], 0, ',', '.'); ?>đ</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <span class="font-bold text-lg" style="color: #d4af37;">
                                        <?php echo $type['price_double_occupancy']
                                            ? number_format($type['price_double_occupancy'], 0, ',', '.') . 'đ'
                                            : number_format($type['base_price'], 0, ',', '.') . 'đ'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <?php echo $type['price_single_occupancy']
                                        ? number_format($type['price_single_occupancy'], 0, ',', '.') . 'đ'
                                        : '-'; ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($type['price_short_stay']): ?>
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo number_format($type['price_short_stay'], 0, ',', '.'); ?>đ
                                        </span>
                                        <br>
                                        <span class="text-xs text-gray-500">Dưới 4h, checkout trước 22h</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Không khả dụng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $type['available_rooms'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $type['available_rooms']; ?>/<?php echo $type['total_rooms']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="room-type-form.php?id=<?php echo $type['room_type_id']; ?>"
                                        class="btn btn-sm btn-outline" title="Chỉnh sửa">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Apartment Pricing -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="font-bold text-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-green-500">apartment</span>
            Bảng giá Căn hộ
        </h3>
        <p class="text-sm text-gray-500 mt-1">Giá theo ngày và theo tuần (từ 7 đêm)</p>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr style="background: linear-gradient(135deg, #065f46, #047857);">
                        <th class="text-white">Loại căn hộ</th>
                        <th class="text-white text-center">Diện tích</th>
                        <th class="text-white text-center">Số người</th>
                        <th class="text-white text-right">Giá/ngày</th>
                        <th class="text-white text-right">Giá/tuần</th>
                        <th class="text-white text-right">TB/đêm (tuần)</th>
                        <th class="text-white text-center">Trống</th>
                        <th class="text-white">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($apartments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">Chưa có căn hộ nào</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($apartments as $apt):
                            $hasOnePerson = !empty($apt['price_daily_single']);
                            $hasTwoPerson = !empty($apt['price_daily_double']);
                            $rowspan = ($hasOnePerson && $hasTwoPerson) ? 2 : 1;
                            ?>
                            <!-- Row for 1 person -->
                            <?php if ($hasOnePerson): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <td class="font-semibold" rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($apt['type_name']); ?>
                                    </td>
                                    <td class="text-center" rowspan="<?php echo $rowspan; ?>">
                                        <?php echo $apt['size_sqm'] ? number_format($apt['size_sqm']) . 'm²' : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">1 người</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-bold" style="color: #d4af37;">
                                            <?php echo number_format($apt['price_daily_single'], 0, ',', '.'); ?>đ
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <?php echo $apt['price_weekly_single']
                                            ? number_format($apt['price_weekly_single'], 0, ',', '.') . 'đ'
                                            : '-'; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($apt['price_avg_weekly_single']): ?>
                                            <span class="text-green-600 font-semibold">
                                                <?php echo number_format($apt['price_avg_weekly_single'], 0, ',', '.'); ?>đ
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" rowspan="<?php echo $rowspan; ?>">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $apt['available_rooms'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $apt['available_rooms']; ?>/<?php echo $apt['total_rooms']; ?>
                                        </span>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <a href="room-type-form.php?id=<?php echo $apt['room_type_id']; ?>"
                                            class="btn btn-sm btn-outline" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- Row for 2 persons -->
                            <?php if ($hasTwoPerson): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <?php if (!$hasOnePerson): ?>
                                        <td class="font-semibold"><?php echo htmlspecialchars($apt['type_name']); ?></td>
                                        <td class="text-center">
                                            <?php echo $apt['size_sqm'] ? number_format($apt['size_sqm']) . 'm²' : '-'; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <span class="badge badge-success">2 người</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-bold" style="color: #d4af37;">
                                            <?php echo number_format($apt['price_daily_double'], 0, ',', '.'); ?>đ
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <?php echo $apt['price_weekly_double']
                                            ? number_format($apt['price_weekly_double'], 0, ',', '.') . 'đ'
                                            : '-'; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($apt['price_avg_weekly_double']): ?>
                                            <span class="text-green-600 font-semibold">
                                                <?php echo number_format($apt['price_avg_weekly_double'], 0, ',', '.'); ?>đ
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (!$hasOnePerson): ?>
                                        <td class="text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $apt['available_rooms'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $apt['available_rooms']; ?>/<?php echo $apt['total_rooms']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="room-type-form.php?id=<?php echo $apt['room_type_id']; ?>"
                                                class="btn btn-sm btn-outline" title="Chỉnh sửa">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pricing Policies -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Extra Guest Fees -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">person_add</span>
                Phí khách thêm (bao gồm ăn sáng)
            </h3>
        </div>
        <div class="card-body">
            <table class="w-full">
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3">Trẻ em dưới 1m (chiều cao)</td>
                        <td class="py-3 text-right">
                            <span class="badge badge-success">Miễn phí</span>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3">Trẻ em từ 1m đến dưới 1m3</td>
                        <td class="py-3 text-right font-bold" style="color: #d4af37;">
                            200.000đ
                        </td>
                    </tr>
                    <tr>
                        <td class="py-3">Người lớn & trẻ em trên 1m3</td>
                        <td class="py-3 text-right font-bold" style="color: #d4af37;">
                            400.000đ
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Extra Bed -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-orange-500">single_bed</span>
                Giường phụ
            </h3>
        </div>
        <div class="card-body">
            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                <span>Phí giường phụ/đêm</span>
                <span class="text-2xl font-bold" style="color: #d4af37;">650.000đ</span>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">info</span>
                    <strong>Lưu ý:</strong> Không áp dụng cho căn hộ
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Tax Info Note -->
<div class="card">
    <div class="card-body">
        <div class="flex items-start gap-4">
            <div
                class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-blue-600">receipt_long</span>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Thông tin thuế và phí dịch vụ</h4>
                <p class="text-gray-600 dark:text-gray-400">
                    Tất cả giá phòng đã <strong>bao gồm 5% phí dịch vụ và 8% VAT</strong>.
                    Khách hàng không phải trả thêm bất kỳ phí nào khác ngoài giá niêm yết.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="badge badge-info">5% Service Charge</span>
                    <span class="badge badge-primary">8% VAT</span>
                    <span class="badge badge-success">Đã bao gồm</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>