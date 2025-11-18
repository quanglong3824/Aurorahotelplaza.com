<?php
session_start();
require_once '../config/database.php';

$room_type_id = $_GET['id'] ?? null;
$is_edit = !empty($room_type_id);

$page_title = $is_edit ? 'Sửa loại phòng' : 'Thêm loại phòng';
$page_subtitle = $is_edit ? 'Cập nhật thông tin loại phòng' : 'Tạo loại phòng mới';

// Load room type data if editing
$room_type = null;
if ($is_edit) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM room_types WHERE room_type_id = :id");
        $stmt->execute([':id' => $room_type_id]);
        $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room_type) {
            header('Location: room-types.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Load room type error: " . $e->getMessage());
        header('Location: room-types.php');
        exit;
    }
}

include 'includes/admin-header.php';
?>

<div class="max-w-4xl">
    <div class="mb-6">
        <a href="room-types.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Quay lại
        </a>
    </div>

    <form action="api/save-room-type.php" method="POST" class="card">
        <div class="card-header">
            <h3 class="font-semibold text-lg"><?php echo $is_edit ? 'Sửa' : 'Thêm'; ?> loại phòng</h3>
        </div>
        
        <div class="card-body">
            <input type="hidden" name="room_type_id" value="<?php echo $room_type['room_type_id'] ?? ''; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group md:col-span-2">
                    <label class="form-label">Tên loại phòng *</label>
                    <input type="text" name="type_name" class="form-input" 
                           value="<?php echo htmlspecialchars($room_type['type_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Danh mục *</label>
                    <select name="category" class="form-select" required>
                        <option value="room" <?php echo ($room_type['category'] ?? '') === 'room' ? 'selected' : ''; ?>>Phòng</option>
                        <option value="apartment" <?php echo ($room_type['category'] ?? '') === 'apartment' ? 'selected' : ''; ?>>Căn hộ</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Giá cơ bản (VNĐ) *</label>
                    <input type="number" name="base_price" class="form-input" 
                           value="<?php echo $room_type['base_price'] ?? ''; ?>" 
                           min="0" step="1000" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sức chứa tối đa *</label>
                    <input type="number" name="max_occupancy" class="form-input" 
                           value="<?php echo $room_type['max_occupancy'] ?? '2'; ?>" 
                           min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Diện tích (m²)</label>
                    <input type="number" name="size_sqm" class="form-input" 
                           value="<?php echo $room_type['size_sqm'] ?? ''; ?>" 
                           min="0" step="0.1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Loại giường</label>
                    <input type="text" name="bed_type" class="form-input" 
                           value="<?php echo htmlspecialchars($room_type['bed_type'] ?? ''); ?>" 
                           placeholder="VD: 1 King, 2 Single">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-input" 
                           value="<?php echo $room_type['sort_order'] ?? '0'; ?>" 
                           min="0">
                </div>
                
                <div class="form-group md:col-span-2">
                    <label class="form-label">Mô tả ngắn</label>
                    <input type="text" name="short_description" class="form-input" 
                           value="<?php echo htmlspecialchars($room_type['short_description'] ?? ''); ?>" 
                           maxlength="255">
                </div>
                
                <div class="form-group md:col-span-2">
                    <label class="form-label">Mô tả chi tiết</label>
                    <textarea name="description" class="form-textarea" rows="5"><?php echo htmlspecialchars($room_type['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group md:col-span-2">
                    <label class="form-label">Tiện nghi</label>
                    <textarea name="amenities" class="form-textarea" rows="3" 
                              placeholder="Mỗi tiện nghi một dòng"><?php echo htmlspecialchars($room_type['amenities'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Nhập mỗi tiện nghi trên một dòng</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL hình thumbnail</label>
                    <input type="text" name="thumbnail" class="form-input" 
                           value="<?php echo htmlspecialchars($room_type['thumbnail'] ?? ''); ?>" 
                           placeholder="https://... (Không bắt buộc)">
                    <p class="text-xs text-gray-500 mt-1">Để trống nếu chưa có hình ảnh</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái *</label>
                    <select name="status" class="form-select" required>
                        <option value="active" <?php echo ($room_type['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="inactive" <?php echo ($room_type['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Tạm ngưng</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="card-footer flex justify-end gap-3">
            <a href="room-types.php" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">save</span>
                Lưu loại phòng
            </button>
        </div>
    </form>
</div>

<?php include 'includes/admin-footer.php'; ?>
