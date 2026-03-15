<?php
require_once 'config/database.php';
require_once 'helpers/language.php';

class FrontTermsController {
    public function getData() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        initLanguage();

        return [
            'page_title' => 'Điều khoản sử dụng',
            'lang' => getLang()
        ];
    }
}
