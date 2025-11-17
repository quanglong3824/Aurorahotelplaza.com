<?php
/**
 * Test Database Connection and Insert Sample Data
 */

require_once '../config/database.php';

echo "<h2>Testing Database Connection</h2>";

// Test connection
$result = checkDBConnection(true);

if ($result['success']) {
    echo "<h3>✅ Database connected successfully!</h3>";
    
    $db = getDB();
    
    // Insert sample room types if not exists
    echo "<h3>Inserting sample room types...</h3>";
    
    $room_types = [
        [
            'code' => 'DELUXE',
            'name' => 'Phòng Deluxe',
            'slug' => 'phong-deluxe',
            'description' => 'Phòng Deluxe sang trọng với đầy đủ tiện nghi',
            'base_price' => 1200000,
            'max_guests' => 2,
            'area_sqm' => 35,
            'bed_type' => 'Giường King',
            'amenities' => json_encode(['WiFi', 'TV', 'Minibar', 'Điều hòa']),
            'is_active' => 1,
            'sort_order' => 1
        ],
        [
            'code' => 'PREMIUM_DELUXE',
            'name' => 'Premium Deluxe',
            'slug' => 'premium-deluxe',
            'description' => 'Phòng Premium Deluxe với không gian rộng rãi',
            'base_price' => 1500000,
            'max_guests' => 3,
            'area_sqm' => 45,
            'bed_type' => 'Giường King + Sofa',
            'amenities' => json_encode(['WiFi', 'TV', 'Minibar', 'Điều hòa', 'Bồn tắm']),
            'is_active' => 1,
            'sort_order' => 2
        ],
        [
            'code' => 'VIP_SUITE',
            'name' => 'VIP Suite',
            'slug' => 'vip-suite',
            'description' => 'Suite VIP cao cấp với dịch vụ Butler',
            'base_price' => 3000000,
            'max_guests' => 4,
            'area_sqm' => 80,
            'bed_type' => 'Giường King + Phòng khách',
            'amenities' => json_encode(['WiFi', 'TV', 'Minibar', 'Điều hòa', 'Bồn tắm', 'Ban công', 'Butler']),
            'is_active' => 1,
            'sort_order' => 3
        ]
    ];
    
    foreach ($room_types as $room_type) {
        try {
            // Check if exists
            $stmt = $db->prepare("SELECT id FROM room_types WHERE code = ?");
            $stmt->execute([$room_type['code']]);
            
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO room_types (code, name, slug, description, base_price, max_guests, area_sqm, bed_type, amenities, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $room_type['code'],
                    $room_type['name'],
                    $room_type['slug'],
                    $room_type['description'],
                    $room_type['base_price'],
                    $room_type['max_guests'],
                    $room_type['area_sqm'],
                    $room_type['bed_type'],
                    $room_type['amenities'],
                    $room_type['is_active'],
                    $room_type['sort_order']
                ]);
                echo "✅ Inserted: {$room_type['name']}<br>";
            } else {
                echo "⏭️ Already exists: {$room_type['name']}<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error inserting {$room_type['name']}: " . $e->getMessage() . "<br>";
        }
    }
    
    // Insert sample rooms
    echo "<h3>Inserting sample rooms...</h3>";
    
    $stmt = $db->prepare("SELECT id, code FROM room_types");
    $stmt->execute();
    $types = $stmt->fetchAll();
    
    foreach ($types as $type) {
        for ($i = 1; $i <= 5; $i++) {
            $room_number = substr($type['code'], 0, 3) . sprintf('%02d', $i);
            
            try {
                $stmt = $db->prepare("SELECT id FROM rooms WHERE room_number = ?");
                $stmt->execute([$room_number]);
                
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("
                        INSERT INTO rooms (room_type_id, room_number, floor, status)
                        VALUES (?, ?, ?, 'available')
                    ");
                    $stmt->execute([$type['id'], $room_number, ceil($i / 2)]);
                    echo "✅ Inserted room: {$room_number}<br>";
                } else {
                    echo "⏭️ Room exists: {$room_number}<br>";
                }
            } catch (Exception $e) {
                echo "❌ Error inserting room {$room_number}: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<h3>✅ Setup completed!</h3>";
    echo "<p><a href='./index.php'>Go to Booking Page</a></p>";
    
} else {
    echo "<h3>❌ Database connection failed!</h3>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>
