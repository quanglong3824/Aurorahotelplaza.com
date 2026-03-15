<?php
/**
 * Aurora Hotel Plaza - AI Assistant
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/AiAssistantController.php';

$page_title = 'Trợ lý Admin AI (Super AI)';
$page_subtitle = 'Trợ lý ảo hỗ trợ trực tiếp quản trị CSDL, phân tích và thực thi lệnh tự động.';

// Get Data from Controller
$data = getAiAssistantData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/ai-assistant.view.php';

// Load Footer
include 'includes/admin-footer.php';
