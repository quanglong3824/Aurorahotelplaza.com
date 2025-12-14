<?php
/**
 * Database Fix Tool - Aurora Hotel Plaza
 * Công cụ sửa lỗi cấu trúc database (thiếu AUTO_INCREMENT)
 */
session_start();
require_once '../config/database.php';

// Chỉ admin mới được truy cập
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Cho phép truy cập nếu chưa có user nào (database mới)
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_role = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0 && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        // Tiếp tục nếu có lỗi
    }
}

$page_title = 'Sửa lỗi Database';
$page_subtitle = 'Công cụ kiểm tra và sửa cấu trúc database';

$messages = [];
$errors = [];

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'check_structure':
                // Kiểm tra cấu trúc bảng users
                $stmt = $db->query("SHOW CREATE TABLE users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $create_sql = $result['Create Table'] ?? '';
                
                if (strpos($create_sql, 'AUTO_INCREMENT') === false) {
                    $errors[] = "Bảng users THIẾU AUTO_INCREMENT - Đây là nguyên nhân gây lỗi!";
                } else {
                    $messages[] = "Bảng users đã có AUTO_INCREMENT ✓";
                }
                
                if (strpos($create_sql, 'PRIMARY KEY') === false) {
                    $errors[] = "Bảng users THIẾU PRIMARY KEY!";
                } else {
                    $messages[] = "Bảng users đã có PRIMARY KEY ✓";
                }
                
                // Kiểm tra user_id = 0
                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_id = 0");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result['count'] > 0) {
                    $errors[] = "Có {$result['count']} users với user_id = 0 - Cần sửa!";
                } else {
                    $messages[] = "Không có user nào với user_id = 0 ✓";
                }
                
                // Kiểm tra duplicate email
                $stmt = $db->query("SELECT email, COUNT(*) as count FROM users GROUP BY email HAVING count > 1");
                $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($duplicates) > 0) {
                    foreach ($duplicates as $dup) {
                        $errors[] = "Email duplicate: {$dup['email']} ({$dup['count']} lần)";
                    }
                } else {
                    $messages[] = "Không có email duplicate ✓";
                }
                break;
                
            case 'fix_structure':
                // Backup và sửa cấu trúc
                $db->beginTransaction();
                
                try {
                    // Lấy danh sách users hiện tại (unique by email)
                    $stmt = $db->query("
                        SELECT email, password_hash, full_name, phone, address, date_of_birth, 
                               gender, avatar, user_role, status, email_verified, 
                               MIN(created_at) as created_at, MAX(updated_at) as updated_at, MAX(last_login) as last_login
                        FROM users 
                        GROUP BY email
                        ORDER BY MIN(created_at) ASC
                    ");
                    $users_backup = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Xóa tất cả users
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("TRUNCATE TABLE users");
                    
                    // Sửa cấu trúc bảng
                    $db->exec("ALTER TABLE users MODIFY user_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
                    
                    // Thêm unique key cho email nếu chưa có
                    try {
                        $db->exec("ALTER TABLE users ADD UNIQUE KEY email_unique (email)");
                    } catch (Exception $e) {
                        // Ignore nếu đã có
                    }
                    
                    // Insert lại users
                    $stmt = $db->prepare("
                        INSERT INTO users (email, password_hash, full_name, phone, address, date_of_birth, 
                                          gender, avatar, user_role, status, email_verified, created_at, updated_at, last_login)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($users_backup as $user) {
                        $stmt->execute([
                            $user['email'], $user['password_hash'], $user['full_name'], $user['phone'],
                            $user['address'], $user['date_of_birth'], $user['gender'], $user['avatar'],
                            $user['user_role'], $user['status'], $user['email_verified'],
                            $user['created_at'], $user['updated_at'], $user['last_login']
                        ]);
                    }
                    
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $db->commit();
                    
                    $messages[] = "Đã sửa cấu trúc bảng users thành công!";
                    $messages[] = "Đã khôi phục " . count($users_backup) . " users (unique by email)";
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
                
            case 'fix_loyalty':
                // Sửa bảng user_loyalty
                $db->beginTransaction();
                
                try {
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("TRUNCATE TABLE user_loyalty");
                    
                    // Sửa cấu trúc
                    try {
                        $db->exec("ALTER TABLE user_loyalty MODIFY loyalty_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
                        $db->exec("ALTER TABLE user_loyalty ADD UNIQUE KEY user_id_unique (user_id)");
                    } catch (Exception $e) {
                        // Ignore nếu đã có
                    }
                    
                    // Tạo loyalty cho tất cả users
                    $db->exec("
                        INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
                        SELECT user_id, 0, 0, NOW() FROM users
                    ");
                    
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $db->commit();
                    
                    $messages[] = "Đã sửa bảng user_loyalty thành công!";
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
                
            case 'fix_activity_logs':
                // Sửa bảng activity_logs
                try {
                    $db->exec("TRUNCATE TABLE activity_logs");
                    $db->exec("ALTER TABLE activity_logs MODIFY log_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
                    $messages[] = "Đã sửa bảng activity_logs thành công!";
                } catch (Exception $e) {
                    $errors[] = "Lỗi sửa activity_logs: " . $e->getMessage();
                }
                break;
                
            case 'create_admin':
                // Tạo tài khoản admin mới
                $admin_email = 'admin@aurorahotelplaza.com';
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                
                // Kiểm tra admin đã tồn tại chưa
                $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$admin_email]);
                
                if ($stmt->fetch()) {
                    $errors[] = "Tài khoản admin đã tồn tại!";
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at)
                        VALUES (?, ?, 'Administrator', '0123456789', 'admin', 'active', 1, NOW())
                    ");
                    $stmt->execute([$admin_email, $admin_password]);
                    $messages[] = "Đã tạo tài khoản admin: $admin_email / admin123";
                }
                break;
        }
        
    } catch (Exception $e) {
        $errors[] = "Lỗi: " . $e->getMessage();
    }
}

// Lấy thông tin hiện tại
$db_info = [];
try {
    $db = getDB();
    
    // Đếm users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $db_info['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Đếm users với user_id = 0
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_id = 0");
    $db_info['users_id_zero'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Kiểm tra AUTO_INCREMENT
    $stmt = $db->query("SHOW CREATE TABLE users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $db_info['has_auto_increment'] = strpos($result['Create Table'] ?? '', 'AUTO_INCREMENT') !== false;
    $db_info['has_primary_key'] = strpos($result['Create Table'] ?? '', 'PRIMARY KEY') !== false;
    
    // Lấy danh sách users
    $stmt = $db->query("SELECT user_id, email, full_name, user_role, status, created_at FROM users ORDER BY created_at DESC LIMIT 20");
    $db_info['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $errors[] = "Không thể kết nối database: " . $e->getMessage();
}

include 'includes/admin-header.php';
?>

<div class="space-y-6">
    <!-- Messages -->
    <?php if (!empty($messages)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            <?php foreach ($messages as $msg): ?>
            <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Database Status -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Trạng thái Database</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card">
                    <p class="text-sm text-gray-500">Tổng Users</p>
                    <p class="text-2xl font-bold"><?php echo $db_info['total_users'] ?? 0; ?></p>
                </div>
                <div class="stat-card <?php echo ($db_info['users_id_zero'] ?? 0) > 0 ? 'bg-red-50' : ''; ?>">
                    <p class="text-sm text-gray-500">Users ID = 0</p>
                    <p class="text-2xl font-bold <?php echo ($db_info['users_id_zero'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo $db_info['users_id_zero'] ?? 0; ?>
                    </p>
                </div>
                <div class="stat-card <?php echo !($db_info['has_auto_increment'] ?? false) ? 'bg-red-50' : ''; ?>">
                    <p class="text-sm text-gray-500">AUTO_INCREMENT</p>
                    <p class="text-2xl font-bold <?php echo ($db_info['has_auto_increment'] ?? false) ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo ($db_info['has_auto_increment'] ?? false) ? '✓' : '✗'; ?>
                    </p>
                </div>
                <div class="stat-card <?php echo !($db_info['has_primary_key'] ?? false) ? 'bg-red-50' : ''; ?>">
                    <p class="text-sm text-gray-500">PRIMARY KEY</p>
                    <p class="text-2xl font-bold <?php echo ($db_info['has_primary_key'] ?? false) ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo ($db_info['has_primary_key'] ?? false) ? '✓' : '✗'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Công cụ sửa lỗi</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="check_structure">
                    <button type="submit" class="btn btn-info w-full">
                        <span class="material-symbols-outlined text-sm">search</span>
                        Kiểm tra cấu trúc
                    </button>
                </form>
                
                <form method="POST" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn sửa cấu trúc bảng users? Dữ liệu sẽ được backup và khôi phục.');">
                    <input type="hidden" name="action" value="fix_structure">
                    <button type="submit" class="btn btn-warning w-full">
                        <span class="material-symbols-outlined text-sm">build</span>
                        Sửa bảng Users
                    </button>
                </form>
                
                <form method="POST" class="inline" onsubmit="return confirm('Sửa bảng user_loyalty?');">
                    <input type="hidden" name="action" value="fix_loyalty">
                    <button type="submit" class="btn btn-warning w-full">
                        <span class="material-symbols-outlined text-sm">loyalty</span>
                        Sửa bảng Loyalty
                    </button>
                </form>
                
                <form method="POST" class="inline" onsubmit="return confirm('Xóa và sửa bảng activity_logs?');">
                    <input type="hidden" name="action" value="fix_activity_logs">
                    <button type="submit" class="btn btn-warning w-full">
                        <span class="material-symbols-outlined text-sm">history</span>
                        Sửa Activity Logs
                    </button>
                </form>
                
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="create_admin">
                    <button type="submit" class="btn btn-success w-full">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        Tạo Admin mới
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Danh sách Users (20 mới nhất)</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Tên</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($db_info['users'])): ?>
                        <?php foreach ($db_info['users'] as $user): ?>
                        <tr class="<?php echo $user['user_id'] == 0 ? 'bg-red-50' : ''; ?>">
                            <td class="<?php echo $user['user_id'] == 0 ? 'text-red-600 font-bold' : ''; ?>">
                                <?php echo $user['user_id']; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['user_role'] === 'admin' ? 'badge-danger' : 'badge-info'; ?>">
                                    <?php echo $user['user_role']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
