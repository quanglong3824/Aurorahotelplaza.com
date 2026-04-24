<?php
/**
 * API: Refresh Gemini API Keys
 * POST /admin/api/refresh-gemini-keys.php
 *
 * Use this when you add a new key to .env and still get 429 errors
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';
require_once '../../helpers/api_key_manager.php';

// Check admin auth
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'refresh';

    switch ($action) {
        case 'refresh':
            $result = refresh_keys_from_env();
            $keys = get_all_valid_keys();
            $debug = debug_key_status();

            echo json_encode([
                'success' => $result,
                'message' => 'Keys refreshed. Found ' . count($keys) . ' valid keys.',
                'keys_count' => count($keys),
                'key_status' => $debug
            ]);
            break;

        case 'clear_limits':
            clear_all_rate_limits();
            $debug = debug_key_status();

            echo json_encode([
                'success' => true,
                'message' => 'All rate limits cleared.',
                'key_status' => $debug
            ]);
            break;

        case 'debug':
            $keys = get_all_valid_keys();
            $limits = get_key_rate_limits();
            $debug = debug_key_status();

            echo json_encode([
                'success' => true,
                'keys_count' => count($keys),
                'key_status' => $debug,
                'rate_limits_raw' => $limits,
                'ai_config_path' => defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : 'not defined'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'POST method required']);
}