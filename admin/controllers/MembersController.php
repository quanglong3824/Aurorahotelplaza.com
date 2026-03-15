<?php
/**
 * Aurora Hotel Plaza - Members Controller
 */

function getMembersData() {
    // Get filter
    $tier_filter = $_GET['tier'] ?? 'all';
    $user_type_filter = $_GET['user_type'] ?? 'all';
    $search = $_GET['search'] ?? '';

    try {
        $db = getDB();

        // Get all tiers for filter
        $stmt = $db->query("SELECT * FROM membership_tiers ORDER BY tier_level ASC");
        $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build query
        $where = [];
        $params = [];

        if ($user_type_filter === 'customer') {
            $where[] = "u.user_role = 'customer'";
        } elseif ($user_type_filter === 'guest') {
            $where[] = "u.user_role = 'guest'";
        } else {
            $where[] = "u.user_role IN ('customer', 'guest')";
        }

        if ($tier_filter !== 'all') {
            if ($tier_filter === 'no_tier') {
                $where[] = "ul.tier_id IS NULL";
            } else {
                $where[] = "ul.tier_id = :tier_id";
                $params[':tier_id'] = $tier_filter;
            }
        }

        if (!empty($search)) {
            $where[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $where_sql = implode(' AND ', $where);

        // Get members with loyalty info
        $stmt = $db->prepare("
            SELECT 
                u.user_id,
                u.email,
                u.full_name,
                u.phone,
                u.user_role,
                u.created_at,
                ul.current_points,
                ul.lifetime_points,
                ul.tier_updated_at,
                mt.tier_name,
                mt.tier_level,
                mt.color_code,
                mt.discount_percentage,
                (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id AND status != 'cancelled') as total_bookings,
                (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.user_id AND payment_status = 'paid') as total_spent
            FROM users u
            LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            WHERE $where_sql
            ORDER BY ul.current_points DESC, u.created_at DESC
        ");

        $stmt->execute($params);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get stats
        $stmt = $db->query("
            SELECT 
                COUNT(DISTINCT u.user_id) as total_members,
                SUM(ul.current_points) as total_points,
                COUNT(DISTINCT CASE WHEN ul.tier_id IS NOT NULL THEN u.user_id END) as members_with_tier
            FROM users u
            LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
            WHERE u.user_role IN ('customer', 'guest')
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Members controller error: " . $e->getMessage());
        $members = [];
        $tiers = [];
        $stats = ['total_members' => 0, 'total_points' => 0, 'members_with_tier' => 0];
    }

    return [
        'members' => $members,
        'tiers' => $tiers,
        'stats' => $stats,
        'tier_filter' => $tier_filter,
        'user_type_filter' => $user_type_filter,
        'search' => $search
    ];
}
