<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">people</span>
            </div>
            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($stats['total_members']); ?>
            </div>
            <div class="text-sm text-gray-600">Tổng thành viên</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <div
                class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">workspace_premium</span>
            </div>
            <div class="text-3xl font-bold mb-1" style="color: #d4af37;">
                <?php echo number_format($stats['members_with_tier']); ?>
            </div>
            <div class="text-sm text-gray-600">Có hạng thành viên</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">stars</span>
            </div>
            <div class="text-3xl font-bold text-green-600 mb-1"><?php echo number_format($stats['total_points']); ?>
            </div>
            <div class="text-sm text-gray-600">Tổng điểm tích lũy</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-input"
                    placeholder="Tìm theo tên, email, SĐT...">
            </div>

            <div class="min-w-[150px]">
                <select name="user_type" class="form-select">
                    <option value="all" <?php echo $user_type_filter === 'all' ? 'selected' : ''; ?>>Tất cả (Gốc & Vãng
                        lai)</option>
                    <option value="customer" <?php echo $user_type_filter === 'customer' ? 'selected' : ''; ?>>User Gốc
                    </option>
                    <option value="guest" <?php echo $user_type_filter === 'guest' ? 'selected' : ''; ?>>Khách Vãng lai
                    </option>
                </select>
            </div>

            <div class="min-w-[200px]">
                <select name="tier" class="form-select">
                    <option value="all" <?php echo $tier_filter === 'all' ? 'selected' : ''; ?>>Tất cả hạng</option>
                    <option value="no_tier" <?php echo $tier_filter === 'no_tier' ? 'selected' : ''; ?>>Chưa có hạng
                    </option>
                    <?php foreach ($tiers as $tier): ?>
                        <option value="<?php echo $tier['tier_id']; ?>" <?php echo $tier_filter == $tier['tier_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tier['tier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">search</span>
                Tìm kiếm
            </button>

            <?php if (!empty($search) || $tier_filter !== 'all'): ?>
                <a href="members.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined text-sm">clear</span>
                    Xóa bộ lọc
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Members List -->
<div class="card">
    <div class="card-header">
        <h3 class="font-bold text-lg">Danh sách thành viên (<?php echo count($members); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($members)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">person_search</span>
                <p class="empty-state-title">Không tìm thấy thành viên</p>
                <p class="empty-state-description">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thành viên</th>
                            <th>Hạng</th>
                            <th>Điểm hiện tại</th>
                            <th>Tổng điểm</th>
                            <th>Đơn hàng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Ngày tham gia</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                            <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-semibold"><?php echo htmlspecialchars($member['full_name']); ?>
                                                </p>
                                                <?php if ($member['user_role'] === 'guest'): ?>
                                                    <span class="badge badge-warning"
                                                        style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">Vãng lai</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?>
                                            </p>
                                            <?php if ($member['phone']): ?>
                                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($member['phone']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($member['tier_name']): ?>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                                style="background-color: <?php echo $member['color_code']; ?>20;">
                                                <span class="material-symbols-outlined text-sm"
                                                    style="color: <?php echo $member['color_code']; ?>;">workspace_premium</span>
                                            </div>
                                            <div>
                                                <p class="font-semibold" style="color: <?php echo $member['color_code']; ?>;">
                                                    <?php echo htmlspecialchars($member['tier_name']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    Giảm <?php echo $member['discount_percentage']; ?>%
                                                </p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Chưa có hạng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="font-bold text-green-600">
                                        <?php echo number_format($member['current_points'] ?? 0); ?>
                                    </span>
                                    <span class="material-symbols-outlined text-sm text-green-600 align-middle">stars</span>
                                </td>
                                <td>
                                    <span class="text-gray-600">
                                        <?php echo number_format($member['lifetime_points'] ?? 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $member['total_bookings'] ?? 0; ?> đơn
                                    </span>
                                </td>
                                <td>
                                    <span class="font-semibold" style="color: #d4af37;">
                                        <?php echo number_format($member['total_spent'] ?? 0, 0, ',', '.'); ?>VND
                                    </span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600">
                                        <?php echo date('m/d/Y', strtotime($member['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="customer-detail.php?id=<?php echo $member['user_id']; ?>" class="action-btn"
                                            title="Xem chi tiết">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                        <button
                                            onclick="adjustPoints(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')"
                                            class="action-btn" title="Điều chỉnh điểm">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Adjust Points Modal -->
<div id="adjustPointsModal" class="modal">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="font-bold text-lg">Điều chỉnh điểm thưởng</h3>
            <button onclick="closeAdjustPointsModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="adjustPointsForm" onsubmit="savePointsAdjustment(event)">
            <div class="modal-body space-y-4">
                <input type="hidden" name="user_id" id="adjust_user_id">

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Thành viên:</p>
                    <p class="font-semibold text-gray-900 dark:text-white" id="adjust_user_name"></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Loại điều chỉnh</label>
                    <select name="adjustment_type" class="form-select" required>
                        <option value="add">Cộng điểm</option>
                        <option value="subtract">Trừ điểm</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Số điểm *</label>
                    <input type="number" name="points" class="form-input" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Lý do *</label>
                    <textarea name="reason" class="form-textarea" rows="3" required
                        placeholder="Nhập lý do điều chỉnh điểm..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeAdjustPointsModal()" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
    function adjustPoints(userId, userName) {
        document.getElementById('adjust_user_id').value = userId;
        document.getElementById('adjust_user_name').textContent = userName;
        document.getElementById('adjustPointsModal').classList.add('active');
        document.getElementById('adjustPointsForm').reset();
        document.getElementById('adjust_user_id').value = userId; // Reset lại sau khi form reset
    }

    function closeAdjustPointsModal() {
        document.getElementById('adjustPointsModal').classList.remove('active');
    }

    function savePointsAdjustment(e) {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch('api/adjust-points.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Điều chỉnh điểm thành công!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    }
</script>