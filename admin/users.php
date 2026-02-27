<?php
session_start();
require_once '../config/database.php';

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Quản lý người dùng';
$page_subtitle = 'Quản lý tài khoản nhân viên và admin';

// Get filter parameters
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["user_role != 'customer'"];
$params = [];

if ($role_filter !== 'all') {
    $where_clauses[] = "user_role = :role";
    $params[':role'] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

try {
    $db = getDB();

    // Get users
    $sql = "
        SELECT u.*,
               (SELECT COUNT(*) FROM bookings WHERE checked_in_by = u.user_id OR cancelled_by = u.user_id) as actions_count
        FROM users u
        $where_sql
        ORDER BY u.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN user_role = 'admin' THEN 1 ELSE 0 END) as admin,
            SUM(CASE WHEN user_role = 'sale' THEN 1 ELSE 0 END) as sale,
            SUM(CASE WHEN user_role = 'receptionist' THEN 1 ELSE 0 END) as receptionist,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
        FROM users
        WHERE user_role != 'customer'
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Users page error: " . $e->getMessage());
    $users = [];
    $counts = ['total' => 0, 'admin' => 0, 'sale' => 0, 'receptionist' => 0, 'active' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng nhân viên</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Admin</p>
        <p class="text-2xl font-bold text-purple-600"><?php echo $counts['admin']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Sale</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $counts['sale']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Lễ tân</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['receptionist']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hoạt động</p>
        <p class="text-2xl font-bold text-accent"><?php echo $counts['active']; ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2 flex-wrap">
        <div class="search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Tìm nhân viên..." class="form-input">
        </div>

        <select name="role" class="form-select">
            <option value="all">Tất cả vai trò</option>
            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="sale" <?php echo $role_filter === 'sale' ? 'selected' : ''; ?>>Sale</option>
            <option value="receptionist" <?php echo $role_filter === 'receptionist' ? 'selected' : ''; ?>>Lễ tân</option>
        </select>

        <select name="status" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Không hoạt động
            </option>
        </select>

        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
    </form>

    <button onclick="openUserModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm nhân viên
    </button>
</div>

<!-- Users Table -->
<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Số hành động</th>
                    <th>Đăng nhập cuối</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">manage_accounts</span>
                                <p class="empty-state-title">Chưa có nhân viên nào</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <?php if ($user['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar"
                                            class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent">person</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                            ID: <?php echo $user['user_id']; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td>
                                <?php
                                $role_config = [
                                    'admin' => ['class' => 'badge-purple', 'label' => 'Admin'],
                                    'sale' => ['class' => 'badge-blue', 'label' => 'Sale'],
                                    'receptionist' => ['class' => 'badge-green', 'label' => 'Lễ tân']
                                ];
                                $config = $role_config[$user['user_role']] ?? ['class' => 'badge-secondary', 'label' => $user['user_role']];
                                ?>
                                <span class="badge <?php echo $config['class']; ?>">
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['status'] === 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo $user['actions_count']; ?></td>
                            <td class="text-sm">
                                <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập'; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick='editUser(<?php echo json_encode($user); ?>)' class="action-btn"
                                        title="Sửa">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <button onclick="resetPassword(<?php echo $user['user_id']; ?>)"
                                        class="action-btn text-orange-600" title="Reset mật khẩu">
                                        <span class="material-symbols-outlined text-sm">lock_reset</span>
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $user['user_id']; ?>)"
                                            class="action-btn text-red-600" title="Xóa">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-semibold" id="modalTitle">Thêm nhân viên mới</h3>
            <button onclick="closeUserModal()"
                class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="user_id" name="user_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Họ tên *</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vai trò *</label>
                        <select id="user_role" name="user_role" class="form-select" required>
                            <option value="receptionist">Lễ tân</option>
                            <option value="sale">Sale</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group" id="passwordGroup">
                        <label class="form-label">Mật khẩu *</label>
                        <input type="password" id="password" name="password" class="form-input" minlength="6">
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Tối thiểu 6 ký tự
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Trạng thái *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeUserModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitUser()" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<style>
    .badge-purple {
        background: #f3e8ff;
        color: #7c3aed;
    }
</style>

<script>
    function openUserModal() {
        console.log('openUserModal called');
        const modal = document.getElementById('userModal');
        console.log('modal element:', modal);
        if (!modal) {
            alert('ERROR: Modal element #userModal not found!');
            return;
        }
        document.getElementById('modalTitle').textContent = 'Thêm nhân viên mới';
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passwordGroup').style.display = 'block';
        modal.classList.add('active');
        modal.style.display = 'flex'; // Force display as backup
        console.log('modal classes after:', modal.className);
        console.log('modal computed display:', getComputedStyle(modal).display);
    }

    function closeUserModal() {
        const modal = document.getElementById('userModal');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = ''; // Remove inline display
        }
    }

    function editUser(user) {
        document.getElementById('modalTitle').textContent = 'Sửa thông tin nhân viên';
        document.getElementById('user_id').value = user.user_id;
        document.getElementById('full_name').value = user.full_name;
        document.getElementById('email').value = user.email;
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('user_role').value = user.user_role;
        document.getElementById('status').value = user.status;
        document.getElementById('password').required = false;
        document.getElementById('password').value = '';
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('userModal').classList.add('active');
    }

    function submitUser() {
        const form = document.getElementById('userForm');

        // Kích hoạt HTML5 validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const userId = document.getElementById('user_id').value;
        const password = document.getElementById('password').value;

        // Double-check validation
        if (!fullName || !email) {
            showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
            return;
        }

        // Kiểm tra mật khẩu khi tạo mới
        if (!userId && (!password || password.length < 6)) {
            showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
            return;
        }

        // Disable button tránh double-click
        const submitBtn = document.querySelector('#userModal .btn-primary');
        const originalText = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang lưu...';
        }

        const formData = new FormData(form);

        fetch('api/save-user.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server error: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server trả về dữ liệu không hợp lệ');
                }
            })
            .then(data => {
                if (data.success) {
                    showToast('Lưu thành công!', 'success');
                    closeUserModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(error.message || 'Có lỗi kết nối server', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText || 'Lưu';
                }
            });
    }

    function resetPassword(userId) {
        const newPassword = prompt('Nhập mật khẩu mới (tối thiểu 6 ký tự):');
        if (!newPassword || newPassword.length < 6) {
            showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('new_password', newPassword);

        fetch('api/reset-password.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Reset mật khẩu thành công!', 'success');
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    }

    function deleteUser(userId) {
        if (!confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) return;

        fetch('api/delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Xóa thành công!', 'success');
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

    // Close modal when clicking outside
    document.getElementById('userModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeUserModal();
        }
    });
</script>

<?php include 'includes/admin-footer.php'; ?>