<?php
require_once 'config/database.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';

class FrontApartmentsController {
    public function getData() {
        initLanguage();

        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT * FROM room_types 
                WHERE status = 'active' AND category = 'apartment'
                ORDER BY sort_order ASC, type_name ASC
            ");
            $stmt->execute();
            $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Apartments page error: " . $e->getMessage());
            $apartments = [];
        }

        // Phân loại căn hộ
        $new_apartments = array_filter($apartments, fn($apt) => $apt['sort_order'] <= 10);
        $old_apartments = array_filter($apartments, fn($apt) => $apt['sort_order'] > 10);

        return [
            'apartments' => $apartments,
            'new_apartments' => $new_apartments,
            'old_apartments' => $old_apartments,
            'lang' => getLang()
        ];
    }
}
