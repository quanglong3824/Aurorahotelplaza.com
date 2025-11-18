<?php
session_start();
require_once '../config/database.php';

$room_id = $_GET['id'] ?? null;
$is_edit = !empty($room_id);

$page_title = $is_edit ? 'Sửa phòng' : 'Thêm phòng';
$page_subtitle = $is_edit ? 'Cập nhật thông tin phòng' : 'Tạo phòng mới';

// Load room data if editing
$room = null;
if ($is_edit) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = :id");
        $stmt->execute([':id' => $room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            header('Location: rooms.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Load room error: " . $e->getMessage());
        header('Location: rooms.php');
        exit;
    }
}

// Get room types
try {
    $db = getDB();
    $stmt = $db->query("SELECT room_type_id, type_name FROM room_types WHERE status = 'active' ORDER BY type_name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $room_types = [];
}

include 'includes/admin-header.php';
?>

<div class="max-w-2xl">
    <div class="mb-6">
        <a href="rooms.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Quay lại
        </a>
    </div>

    <form action="api/save-room.php" method="POST" class="card">
        <div class="card-header">
            <h3 class="font-semibold text-lg"><?php echo $is_edit ? 'Sửa' : 'Thêm'; ?> phòng</h3>
        </div>
        
        <div class="card-body">
            <input type="hidden" name="room_id" value="<?php echo $room['room_id'] ?? ''; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Số phòng *</label>
                    <input type="text" name="room_number" class="form-input" 
                           value="<?php echo htmlspecialchars($room['room_number'] ?? ''); ?>" 
                           placeholder="VD: 101, A201" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Loại phòng *</label>
                    <select name="room_type_id" class="form-select" required>
                        <option value="">-- Chọn loại phòng --</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['room_type_id']; ?>" 
                                    <?php echo ($room['room_type_id'] ?? '') == $type['room_type_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tầng</label>
                    <input type="number" name="floor" class="form-input" 
                           value="<?php echo $room['floor'] ?? ''; ?>" 
                           min="0" placeholder="VD: 1, 2, 3...">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tòa nhà</label>
                    <input type="text" name="building" class="form-input" 
                           value="<?php echo htmlspecialchars($room['building'] ?? ''); ?>" 
                           placeholder="VD: A, B, Main">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái *</label>
                    <select name="status" class="form-select" required>
                        <option value="available" <?php echo ($room['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Trống</option>
                        <option value="occupied" <?php echo ($room['status'] ?? '') === 'occupied' ? 'selected' : ''; ?>>Đang sử dụng</option>
                        <option value="maintenance" <?php echo ($room['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                        <option value="cleaning" <?php echo ($room['status'] ?? '') === 'cleaning' ? 'selected' : ''; ?>>Đang dọn</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lần dọn cuối</label>
                    <input type="datetime-local" name="last_cleaned" class="form-input" 
                           value="<?php echo $room['last_cleaned'] ? date('Y-m-d\TH:i', strtotime($room['last_cleaned'])) : ''; ?>">
                </div>
                
                <div class="form-group md:col-span-2">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="notes" class="form-textarea" rows="3"><?php echo htmlspecialchars($room['notes'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="card-footer flex justify-end gap-3">
            <a href="rooms.php" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">save</span>
                Lưu phòng
            </button>
        </div>
    </form>
</div>

<?php include 'includes/admin-footer.php'; ?>
