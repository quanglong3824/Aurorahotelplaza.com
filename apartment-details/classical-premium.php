<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
require_once __DIR__ . '/../controllers/FrontApartmentDetailController.php';

initLanguage();

$controller = new FrontApartmentDetailController();
$data = $controller->getData('classical-premium', 4800000);

if (!$data) {
    header('Location: ../apartments.php');
    exit;
}

extract($data);

require_once __DIR__ . '/../views/apartment-details/classical-premium.view.php';
