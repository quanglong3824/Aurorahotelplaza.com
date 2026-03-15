<?php
/**
 * Aurora Hotel Plaza - Service Packages Controller
 * Handles data fetching and processing for service packages management
 */

function getServicePackagesData() {
    $service_filter = $_GET['service'] ?? 'all';
    $search = $_GET['search'] ?? '';

    try {
        $db = getDB();

        // Get all main services
        $stmt = $db->query("
            SELECT s.*,
                   (SELECT COUNT(*) FROM service_packages WHERE service_id = s.service_id) as package_count
            FROM services s
            WHERE s.is_available = 1
            ORDER BY s.sort_order ASC
        ");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get packages for selected service
        $packages = [];
        if ($service_filter !== 'all') {
            $stmt = $db->prepare("
                SELECT * FROM service_packages
                WHERE service_id = :service_id
                ORDER BY sort_order ASC
            ");
            $stmt->execute([':service_id' => $service_filter]);
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get stats
        $stmt = $db->query("
            SELECT 
                COUNT(DISTINCT s.service_id) as total_services,
                COUNT(sp.package_id) as total_packages,
                SUM(CASE WHEN s.is_available = 1 THEN 1 ELSE 0 END) as available_services
            FROM services s
            LEFT JOIN service_packages sp ON s.service_id = sp.service_id
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'services' => $services,
            'packages' => $packages,
            'stats' => $stats,
            'service_filter' => $service_filter,
            'search' => $search
        ];

    } catch (Exception $e) {
        error_log("Service packages controller error: " . $e->getMessage());
        return [
            'services' => [],
            'packages' => [],
            'stats' => ['total_services' => 0, 'total_packages' => 0, 'available_services' => 0],
            'service_filter' => $service_filter,
            'search' => $search
        ];
    }
}
