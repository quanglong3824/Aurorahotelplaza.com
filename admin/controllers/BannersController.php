<?php
/**
 * Aurora Hotel Plaza - Banners Controller
 * Handles data fetching and processing for banners management
 */

function getBannersData() {
    try {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT * FROM banners
            ORDER BY sort_order ASC, created_at DESC
        ");
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'banners' => $banners
        ];
        
    } catch (Exception $e) {
        error_log("Banners controller error: " . $e->getMessage());
        return [
            'banners' => []
        ];
    }
}
