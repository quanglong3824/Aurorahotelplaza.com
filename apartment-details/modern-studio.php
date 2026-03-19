<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
require_once __DIR__ . '/../controllers/FrontApartmentDetailController.php';

initLanguage();

$controller = new FrontApartmentDetailController();
$data = $controller->getData('modern-studio', 2600000);
extract($data);

include __DIR__ . '/../views/apartment-details/modern-studio.view.php';
