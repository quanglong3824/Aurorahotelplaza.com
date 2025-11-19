<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/permissions.php';

// Only admin can manage permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$page_title = 'Quản lý phân quyền';
$page_subtitle = 'Cấu hình quyền truy cập cho Sale và Lễ tân';

try {
    $db = getDB();
    
    // Get permission matrix
    $matrix = Permissions::getPermissionMatrix();
    
    // Get all modules and actions
    $stmt = $db->query("
        SELECT DISTINCT module, action
        FROM role_permissions
        WHERE role IN ('receptionist', 'sale')
        ORDER BY module, action
    ");
    $all_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by module
    $modules = [];
    foreach ($all_permissions as $perm) {
        if (!isset($modules[$perm['module']])) {
            $modules[$perm['module']] = [];
        }
        $modules[$perm['module']][] = $perm['action'];
    }
    
} catch (Exception $e) {
    error_log("Permissions page error: " . $e->getMessage());
    $matrix = [];
    $modules = [];
}

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
            <div class="flex-1">
                <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Hướng dẫn phân quyền</p>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    Bật/tắt các quyền cho từng vai trò. <strong>Admin</strong> luôn có toàn quyền.
                    <strong>Lễ tân (Receptionist)</strong> quản lý check-in/out và phòng.
                    <strong>Sale</strong> tập trung vào booking và khách hàng.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Permission Matrix -->
<div class="card">
    <div class="card-header">
        <h3 class="font-bold text-lg">Ma trận phân quyền</h3>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="text-left p-4 font-semibold">Module</th>
                        <th class="text-left p-4 font-semibold">Hành động</th>
                        <th class="text-center p-4 font-semibold bg-purple-50 dark:bg-purple-900/20">
                            <div class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-purple-600">badge</span>
                                Lễ tân
                            </div>
                        </th>
                        <th class="text-center p-4 font-semibold bg-green-50 dark:bg-green-900/20">
                            <div class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-green-600">sell</span>
                                Sale
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $module => $actions): ?>
                        <?php 
                        $module_labels = [
                            'bookings' => 'Đặt phòng',
                            'customers' => 'Khách hàng',
                            'rooms' => 'Phòng',
                            'pricing' => 'Giá phòng',
                            'promotions' => 'Khuyến mãi',
                            'loyalty' => 'Thành viên',
                            'payments' => 'Thanh toán',
                            'reports' => 'Báo cáo',
                            'settings' => 'Cài đặt',
                            'permissions' => 'Phân quyền'
                        ];
                        $module_label = $module_labels[$module] ?? ucfirst($module);
                        ?>
                        <?php foreach ($actions as $index => $action): ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <?php if ($index === 0): ?>
                                    <td rowspan="<?php echo count($actions); ?>" class="p-4 font-semibold text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                        <?php echo $module_label; ?>
                                    </td>
                                <?php endif; ?>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo Permissions::getPermissionLabel($module, $action); ?>
                                </td>
                                <td class="p-4 text-center bg-purple-50/50 dark:bg-purple-900/10">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="permission-toggle sr-only peer" 
                                               data-role="receptionist"
                                               data-module="<?php echo $module; ?>"
                                               data-action="<?php echo $action; ?>"
                                               <?php echo ($matrix['receptionist'][$module][$action] ?? false) ? 'checked' : ''; ?>>
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                    </label>
                                </td>
                                <td class="p-4 text-center bg-green-50/50 dark:bg-green-900/10">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="permission-toggle sr-only peer" 
                                               data-role="sale"
                                               data-module="<?php echo $module; ?>"
                                               data-action="<?php echo $action; ?>"
                                               <?php echo ($matrix['sale'][$module][$action] ?? false) ? 'checked' : ''; ?>>
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Handle permission toggle
document.querySelectorAll('.permission-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const role = this.dataset.role;
        const module = this.dataset.module;
        const action = this.dataset.action;
        const allowed = this.checked;
        
        // Save permission
        fetch('api/update-permission.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `role=${role}&module=${module}&action=${action}&allowed=${allowed ? 1 : 0}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(`Đã ${allowed ? 'bật' : 'tắt'} quyền ${action} cho ${role}`, 'success');
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
                // Revert toggle
                this.checked = !allowed;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Có lỗi xảy ra', 'error');
            // Revert toggle
            this.checked = !allowed;
        });
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
