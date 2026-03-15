<?php
/**
 * Aurora Hotel Plaza - AI Bug Tracker Controller
 */

require_once '../helpers/error-tracker.php';

function getAiBugData() {
    $db = getDB();
    
    // ─── Auto-create bảng error_logs nếu chưa tồn tại (tránh crash trên prod) ─────
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `error_logs` (
            `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `error_type`        VARCHAR(50)  NOT NULL DEFAULT 'unknown',
            `severity`          ENUM('critical','error','warning','info') NOT NULL DEFAULT 'error',
            `message`           TEXT NOT NULL,
            `file_path`         VARCHAR(500) DEFAULT NULL,
            `line_number`       INT UNSIGNED DEFAULT 0,
            `page_url`          VARCHAR(1000) DEFAULT NULL,
            `ip_address`        VARCHAR(45)  DEFAULT NULL,
            `user_agent`        VARCHAR(500) DEFAULT NULL,
            `session_id`        VARCHAR(128) DEFAULT NULL,
            `user_id`           INT UNSIGNED DEFAULT NULL,
            `context_data`      JSON         DEFAULT NULL,
            `fingerprint`       VARCHAR(32)  DEFAULT NULL,
            `occurrence_count`  INT UNSIGNED NOT NULL DEFAULT 1,
            `ai_analyzed`       TINYINT(1)   NOT NULL DEFAULT 0,
            `ai_analysis`       TEXT         DEFAULT NULL,
            `messenger_sent`    TINYINT(1)   NOT NULL DEFAULT 0,
            `messenger_sent_at` DATETIME     DEFAULT NULL,
            `status`            ENUM('open','in_progress','resolved','ignored') NOT NULL DEFAULT 'open',
            `resolved_by`       INT UNSIGNED DEFAULT NULL,
            `resolved_at`       DATETIME     DEFAULT NULL,
            `notes`             TEXT         DEFAULT NULL,
            `last_seen_at`      DATETIME     DEFAULT NULL,
            `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_severity`    (`severity`),
            KEY `idx_status`      (`status`),
            KEY `idx_fingerprint` (`fingerprint`),
            KEY `idx_created_at`  (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Auto-insert Telegram settings nếu chưa có
        $db->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
            ('telegram_bot_token', '', 'string', 'Telegram Bot Token'),
            ('telegram_chat_id',   '', 'string', 'Telegram Chat ID'),
            ('bug_tracker_enabled','1','boolean','Bat/tat bug tracker'),
            ('bug_tracker_min_severity','error','string','Muc do toi thieu gui Telegram')");
    } catch (\Throwable $e) {
        // Bảng đã tồn tại hoặc lỗi khác — bỏ qua
    }

    $tableOk = true;
    try {
        $db->query("SELECT 1 FROM error_logs LIMIT 1");
    } catch (\Throwable $e) {
        $tableOk = false;
    }

    // ─── Xử lý actions ────────────────────────────────────────────────────────────
    if ($tableOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            die('CSRF validation failed.');
        }
        $action = $_POST['action'] ?? '';
        $errorId = (int) ($_POST['error_id'] ?? 0);
        try {
            if ($action === 'resolve' && $errorId) {
                $db->prepare("UPDATE error_logs SET status='resolved', resolved_by=?, resolved_at=NOW() WHERE id=?")
                    ->execute([$_SESSION['user_id'], $errorId]);
                header('Location: ai-bug.php?msg=resolved');
                exit;
            }
            if ($action === 'ignore' && $errorId) {
                $db->prepare("UPDATE error_logs SET status='ignored' WHERE id=?")->execute([$errorId]);
                header('Location: ai-bug.php?msg=ignored');
                exit;
            }
            if ($action === 'reopen' && $errorId) {
                $db->prepare("UPDATE error_logs SET status='open', resolved_by=NULL, resolved_at=NULL WHERE id=?")->execute([$errorId]);
                header('Location: ai-bug.php?msg=reopened');
                exit;
            }
            if ($action === 'save_notes' && $errorId) {
                $db->prepare("UPDATE error_logs SET notes=? WHERE id=?")->execute([$_POST['notes'] ?? '', $errorId]);
                header('Location: ai-bug.php?id=' . $errorId . '&msg=notes_saved');
                exit;
            }
            if ($action === 'delete' && $errorId) {
                $db->prepare("DELETE FROM error_logs WHERE id=?")->execute([$errorId]);
                header('Location: ai-bug.php?msg=deleted');
                exit;
            }
            if ($action === 'reanalyze' && $errorId) {
                $row = $db->query("SELECT * FROM error_logs WHERE id=$errorId")->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    AuroraErrorTracker::analyzeWithAiAndNotify($errorId, [
                        'type' => $row['error_type'],
                        'severity' => $row['severity'],
                        'message' => $row['message'],
                        'file' => $row['file_path'],
                        'line' => $row['line_number'],
                        'url' => $row['page_url'],
                        'context' => json_decode($row['context_data'] ?? '{}', true),
                    ]);
                }
                header('Location: ai-bug.php?id=' . $errorId . '&msg=reanalyzed');
                exit;
            }
            if ($action === 'clear_resolved') {
                $db->exec("DELETE FROM error_logs WHERE status IN ('resolved','ignored')");
                header('Location: ai-bug.php?msg=cleared');
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[AiBug] Action error: ' . $e->getMessage());
        }
    }

    // ─── Xem chi tiết một lỗi ─────────────────────────────────────────────────────
    $viewId = (int) ($_GET['id'] ?? 0);
    $detailRow = null;
    if ($tableOk && $viewId) {
        try {
            $detailRow = $db->query("SELECT * FROM error_logs WHERE id=$viewId")->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
        }
    }

    // ─── Filters & List ──────────────────────────────────────────────────────────
    $filterSeverity = $_GET['severity'] ?? '';
    $filterType = $_GET['type'] ?? '';
    $filterStatus = $_GET['status'] ?? 'open';
    $filterSearch = $_GET['q'] ?? '';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = 25;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];
    if ($filterSeverity) {
        $where[] = 'severity = ?';
        $params[] = $filterSeverity;
    }
    if ($filterType) {
        $where[] = 'error_type = ?';
        $params[] = $filterType;
    }
    if ($filterStatus !== 'all') {
        $where[] = 'status = ?';
        $params[] = $filterStatus ?: 'open';
    }
    if ($filterSearch) {
        $where[] = 'message LIKE ?';
        $params[] = "%$filterSearch%";
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $totalRows = 0;
    $totalPages = 1;
    $errors = [];
    $stats = [];
    $typeList = [];

    if ($tableOk) {
        try {
            $totalStmt = $db->prepare("SELECT COUNT(*) FROM error_logs $whereSql");
            $totalStmt->execute($params);
            $totalRows = (int) $totalStmt->fetchColumn();
            $totalPages = max(1, ceil($totalRows / $limit));

            // Sử dụng bindValue cho LIMIT/OFFSET để bảo mật
            $listStmt = $db->prepare("SELECT * FROM error_logs $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            foreach ($params as $i => $param) {
                $listStmt->bindValue($i + 1, $param);
            }
            $listStmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $listStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $listStmt->execute();
            $errors = $listStmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = AuroraErrorTracker::getStats();
            $typeList = $db->query("SELECT DISTINCT error_type FROM error_logs ORDER BY error_type")->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            error_log('[AiBug] Query error: ' . $e->getMessage());
        }
    }

    return [
        'detailRow' => $detailRow,
        'errors' => $errors,
        'stats' => $stats,
        'typeList' => $typeList,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages,
        'page' => $page,
        'filterSeverity' => $filterSeverity,
        'filterType' => $filterType,
        'filterStatus' => $filterStatus,
        'filterSearch' => $filterSearch,
        'msg' => $_GET['msg'] ?? '',
        'db' => $db
    ];
}
