<?php
require_once 'config/environment.php';
require_once 'config/database.php';
require_once 'config/performance.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
require_once 'controllers/FrontRoomsController.php';

initLanguage();

$controller = new FrontRoomsController();
$data = $controller->getData();
extract($data);

include 'views/front-rooms.view.php';
