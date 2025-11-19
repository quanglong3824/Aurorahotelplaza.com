<?php
/**
 * API: Update Permission
 * Cập nhật quyền cho role
 */

session_start();
require_once '../../helpers/permissions.php';

header('Content-Type: application/json');

// Only admin can update permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $role = $_POST['role'] ?? '';
    $module = $_POST['module'] ?? '';
    $action = $_POST['action'] ?? '';
    $allowed = isset($_POST['allowed']) ? (bool)$_POST['allowed'] : false;
    
    if (empty($role) || empty($module) || empty($action)) {
        throw new Exception('Missing required parameters');
    }
    
    // Cannot modify admin permissions
    if ($role === 'admin') {
        throw new Exception('Cannot modify admin permissions');
    }
    
    // Update permission
    $success = Permissions::updatePermission($role, $module, $action, $allowed);
    
    if (!$success) {
        throw new Exception('Failed to update permission');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Permission updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
