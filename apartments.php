<?php
require_once 'controllers/FrontApartmentsController.php';

$controller = new FrontApartmentsController();
$data = $controller->getData();

extract($data);

include 'views/front-apartments.view.php';
