<?php
// Script to update service table images
// Run this script to fix image paths in the services table

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/database.php';

function updateServiceImages()
{
    try {
        $db = getDB();

        $updates = [
            2 => 'assets/img/post/conference/conference-1.jpg',
            3 => 'assets/img/post/restaurant/restaurant-1.jpg',
            4 => 'assets/img/post/office/office-1.jpg',
            5 => 'assets/img/post/restaurant/rooftop-bar-1.jpg'
        ];

        echo "Starting update of service images...\n";

        foreach ($updates as $id => $thumbnail) {
            // Check if service exists first
            $check = $db->prepare("SELECT service_id, service_name FROM services WHERE service_id = ?");
            $check->execute([$id]);
            $service = $check->fetch(PDO::FETCH_ASSOC);

            if ($service) {
                $stmt = $db->prepare("UPDATE services SET thumbnail = :thumbnail, updated_at = NOW() WHERE service_id = :id");
                $result = $stmt->execute([
                    ':thumbnail' => $thumbnail,
                    ':id' => $id
                ]);

                if ($result) {
                    echo "[SUCCESS] Updated Service ID {$id} ({$service['service_name']}): $thumbnail\n";
                } else {
                    echo "[ERROR] Failed to update Service ID {$id}\n";
                }
            } else {
                echo "[WARNING] Service ID {$id} not found.\n";
            }
        }

        echo "Update completed.\n";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Execute the function
updateServiceImages();
?>