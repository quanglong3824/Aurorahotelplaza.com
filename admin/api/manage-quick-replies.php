<?php
/**
 * admin/api/manage-quick-replies.php
 * CRUD Quick Replies — POST (upsert) | DELETE
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

try {
    $db = getDB();
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $method = $_SERVER['REQUEST_METHOD'];

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $id = (int) ($data['reply_id'] ?? 0);
        if (!$id)
            throw new Exception('Thiếu reply_id');

        $stmt = $db->prepare("DELETE FROM chat_quick_replies WHERE reply_id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── POST: INSERT or UPDATE ────────────────────────────────────────────────
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false]);
        exit;
    }

    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    $shortcut = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($data['shortcut'] ?? '')));
    $category = trim($data['category'] ?? 'Chung') ?: 'Chung';
    $sort_order = (int) ($data['sort_order'] ?? 0);
    $is_active = isset($data['is_active']) ? (int) $data['is_active'] : 1;
    $reply_id = (int) ($data['reply_id'] ?? 0);

    if (!$title || !$content)
        throw new Exception('Tiêu đề và nội dung không được để trống');

    if ($reply_id) {
        // UPDATE
        $stmt = $db->prepare("
            UPDATE chat_quick_replies
            SET title = ?, content = ?, shortcut = ?, category = ?,
                sort_order = ?, is_active = ?, updated_at = NOW()
            WHERE reply_id = ?
        ");
        $stmt->execute([$title, $content, $shortcut ?: null, $category, $sort_order, $is_active, $reply_id]);
        echo json_encode(['success' => true, 'reply_id' => $reply_id]);
    } else {
        // INSERT
        $stmt = $db->prepare("
            INSERT INTO chat_quick_replies (title, content, shortcut, category, sort_order, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $content, $shortcut ?: null, $category, $sort_order, $is_active]);
        echo json_encode(['success' => true, 'reply_id' => $db->lastInsertId()]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
