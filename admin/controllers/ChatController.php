<?php
/**
 * Aurora Hotel Plaza - Chat Controller
 */

function getChatData() {
    return [
        'user_id' => (int) $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'],
        'user_name' => $_SESSION['user_name'] ?? 'Nhân viên'
    ];
}
