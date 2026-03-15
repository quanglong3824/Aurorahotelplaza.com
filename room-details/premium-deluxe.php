<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
require_once __DIR__ . '/../controllers/FrontRoomDetailController.php';

initLanguage();

$controller = new FrontRoomDetailController();
$data = $controller->getData('premium-deluxe', 1800000);
extract($data);

include __DIR__ . '/../views/room-details/premium-deluxe.view.php';
