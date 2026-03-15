<?php
namespace Aurora\Core\Repositories;

use PDO;

/**
 * UserRepository - Handles database operations for users
 */
class UserRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new user
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at)
            VALUES (?, ?, ?, ?, 'customer', 'active', 0, NOW())
        ");
        $stmt->execute([
            $data['email'],
            $data['password_hash'],
            $data['full_name'],
            $data['phone']
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Fallback if lastInsertId fails (e.g. missing AUTO_INCREMENT or specific DB driver behavior)
        if (!$userId || $userId == 0) {
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $user['user_id'] ?? 0;
        }
        
        return (int)$userId;
    }

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(int $userId): void {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Reset admin password (specific for the emergency reset feature)
     */
    public function resetAdminPassword(string $passwordHash): bool {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, requires_password_change = 1 WHERE user_role = 'admin' LIMIT 1");
        return $stmt->execute([$passwordHash]) && $stmt->rowCount() > 0;
    }

    /**
     * Find the first active admin user
     */
    public function findAdmin(): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_role = 'admin' AND status = 'active' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create loyalty record for a user
     */
    public function createLoyaltyRecord(int $userId): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at) 
                VALUES (?, 0, 0, NOW())
            ");
            $stmt->execute([$userId]);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            error_log("Loyalty record creation failed for user $userId: " . $e->getMessage());
        }
    }

    /**
     * Get user profile with loyalty information
     */
    public function getProfileWithLoyalty(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, ul.current_points, ul.lifetime_points, mt.tier_id, mt.tier_name, mt.tier_name_en, 
                   mt.discount_percentage, mt.color_code, mt.benefits, mt.benefits_en, mt.tier_level
            FROM users u
            LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool {
        $fields = [];
        $params = [];
        
        $allowedFields = ['full_name', 'phone', 'address', 'date_of_birth', 'gender'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $passwordHash): bool {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$passwordHash, $userId]);
    }

    /**
     * Get points history
     */
    public function getPointsHistory(int $userId, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT * FROM points_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get contact history
     */
    public function getContactHistory(int $userId, int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT contact_code, subject, message, status, created_at, updated_at
            FROM contact_submissions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete user account (soft delete or status change)
     */
    public function deleteAccount(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE users SET status = 'deleted', updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Get loyalty info with next tier points
     */
    public function getLoyaltyInfo(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT ul.*, mt.tier_name, mt.tier_name_en, mt.tier_level, mt.discount_percentage, mt.benefits, mt.benefits_en, mt.color_code, mt.min_points,
                   (SELECT MIN(min_points) FROM membership_tiers WHERE min_points > ul.current_points) as next_tier_points
            FROM user_loyalty ul
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            WHERE ul.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all membership tiers
     */
    public function getMembershipTiers(): array {
        $stmt = $this->db->prepare("SELECT * FROM membership_tiers ORDER BY tier_level");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ensure loyalty record exists
     */
    public function ensureLoyaltyRecord(int $userId): void {
        $stmt = $this->db->prepare("SELECT 1 FROM user_loyalty WHERE user_id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $this->createLoyaltyRecord($userId);
        }
    }
}
