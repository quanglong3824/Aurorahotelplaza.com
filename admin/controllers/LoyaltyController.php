<?php
/**
 * Aurora Hotel Plaza - Loyalty Controller
 */

function getLoyaltyData() {
    try {
        $db = getDB();
        
        // Get membership tiers
        $stmt = $db->query("
            SELECT mt.*,
                   (SELECT COUNT(*) FROM user_loyalty WHERE tier_id = mt.tier_id) as member_count
        FROM membership_tiers mt
        ORDER BY mt.min_points ASC
    ");
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get loyalty stats
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT ul.user_id) as total_members,
            SUM(ul.current_points) as total_points,
            AVG(ul.current_points) as avg_points
        FROM user_loyalty ul
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    } catch (Exception $e) {
        error_log("Loyalty controller error: " . $e->getMessage());
        $tiers = [];
        $stats = ['total_members' => 0, 'total_points' => 0, 'avg_points' => 0];
    }

    return [
        'tiers' => $tiers,
        'stats' => $stats
    ];
}
