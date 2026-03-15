<?php
/**
 * Aurora Hotel Plaza - Contacts Controller
 * Handles data fetching and processing for contact submissions management
 */

require_once '../helpers/auth-middleware.php';

function getContactsData() {
    // Get filter parameters from GET
    $status_filter = $_GET['status'] ?? 'all';
    $subject_filter = $_GET['subject'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Build query
    $where_clauses = ["1=1"];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "c.status = :status";
        $params[':status'] = $status_filter;
    }

    if ($subject_filter !== 'all') {
        $where_clauses[] = "c.subject = :subject";
        $params[':subject'] = $subject_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR c.message LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Initial state
    $contacts = [];
    $total_contacts = 0;
    $stats = ['new' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
    $subjects = [];

    try {
        $db = getDB();
        
        // Get total count
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM contact_submissions c WHERE {$where_sql}");
        $count_stmt->execute($params);
        $total_contacts = (int) $count_stmt->fetchColumn();
        
        // Get contacts with pagination
        $stmt = $db->prepare("
            SELECT c.*, u.full_name as assigned_name,
                   COALESCE(c.contact_code, LPAD(c.id, 8, '0')) as display_code
            FROM contact_submissions c
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE {$where_sql}
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $stats_stmt = $db->query("
            SELECT status, COUNT(*) as count 
            FROM contact_submissions 
            GROUP BY status
        ");
        while ($row = $stats_stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = (int) $row['count'];
        }
        
        // Get unique subjects for filter
        $subjects_stmt = $db->query("SELECT DISTINCT subject FROM contact_submissions WHERE subject IS NOT NULL ORDER BY subject");
        $subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        error_log("Contacts Controller error: " . $e->getMessage());
    }

    $total_pages = ceil($total_contacts / $per_page);

    return [
        'contacts' => $contacts,
        'total_contacts' => $total_contacts,
        'total_pages' => $total_pages,
        'stats' => $stats,
        'subjects' => $subjects,
        'status_filter' => $status_filter,
        'subject_filter' => $subject_filter,
        'search' => $search,
        'page' => $page
    ];
}
