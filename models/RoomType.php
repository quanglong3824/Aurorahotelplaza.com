<?php
/**
 * RoomType Model - Business Logic Layer
 */

class RoomType {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all active room types
     */
    public function getAll($active_only = true) {
        $sql = "SELECT * FROM room_types";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order, base_price";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get room type by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get room type by slug
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    /**
     * Create room type
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO room_types (
                code, name, slug, description, base_price, 
                max_guests, area_sqm, bed_type, amenities, 
                images, is_active, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['code'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['base_price'],
            $data['max_guests'],
            $data['area_sqm'] ?? null,
            $data['bed_type'] ?? null,
            $data['amenities'] ?? null,
            $data['images'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update room type
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE room_types SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete room type
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM room_types WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
