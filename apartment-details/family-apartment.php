<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
require_once __DIR__ . '/../controllers/FrontApartmentDetailController.php';

initLanguage();

$controller = new FrontApartmentDetailController();
$data = $controller->getData('family-apartment', 6500000);

if (!$data) {
    header('Location: ../apartments.php');
    exit;
}

extract($data);

require_once __DIR__ . '/../views/apartment-details/family-apartment.view.php';
