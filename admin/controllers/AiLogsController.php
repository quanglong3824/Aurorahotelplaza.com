<?php
/**
 * Aurora Hotel Plaza - AI Logs Controller
 */

function getAiLogsData() {
    $db = getDB();

    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Bộ lọc
    $type_filter = isset($_GET['type']) ? $_GET['type'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

    $where = [];
    $params = [];
    if ($type_filter) {
        $where[] = "ai_type = ?";
        $params[] = $type_filter;
    }
    if ($status_filter) {
        $where[] = "status = ?";
        $params[] = $status_filter;
    }

    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Lấy dữ liệu
    $stmt = $db->prepare("SELECT * FROM ai_logs $where_sql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng số để phân trang
    $total_stmt = $db->prepare("SELECT COUNT(*) FROM ai_logs $where_sql");
    $total_stmt->execute($params);
    $total_rows = $total_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    return [
        'logs' => $logs,
        'total_rows' => $total_rows,
        'total_pages' => $total_pages,
        'page' => $page,
        'type_filter' => $type_filter,
        'status_filter' => $status_filter
    ];
}
