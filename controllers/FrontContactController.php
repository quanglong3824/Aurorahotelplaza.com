<?php

class FrontContactController {
    public function getData() {
        $is_logged_in = isset($_SESSION['user_id']);
        $user_name = $user_email = $user_phone = '';

        if ($is_logged_in) {
            try {
                $db = getDB();
                if ($db) {
                    $stmt = $db->prepare("SELECT full_name, email, phone FROM users WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $user_name = $user['full_name'] ?? '';
                        $user_email = $user['email'] ?? '';
                        $user_phone = $user['phone'] ?? '';
                    }
                }
            } catch (Exception $e) {
                $user_name = $_SESSION['user_name'] ?? '';
                $user_email = $_SESSION['user_email'] ?? '';
            }
        }

        return [
            'is_logged_in' => $is_logged_in,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone
        ];
    }
}
