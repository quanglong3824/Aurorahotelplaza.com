<?php
/**
 * Aurora Hotel Plaza - Permissions Controller
 * Handles permission management logic
 */

require_once '../helpers/permissions.php';

function getPermissionsData() {
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

        return [
            'matrix' => $matrix,
            'modules' => $modules
        ];
        
    } catch (Exception $e) {
        error_log("Permissions Controller error: " . $e->getMessage());
        return [
            'matrix' => [],
            'modules' => []
        ];
    }
}
