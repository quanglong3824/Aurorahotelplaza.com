<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../controllers/FrontApartmentDetailController.php';

initLanguage();

$controller = new FrontApartmentDetailController();
$data = $controller->getData('premium-apartment', 4200000);
extract($data);

include __DIR__ . '/../views/apartment-details/premium-apartment.view.php';
