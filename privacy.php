<?php
require_once 'controllers/FrontPrivacyController.php';

$controller = new FrontPrivacyController();
$data = $controller->getData();

extract($data);

include 'views/front-privacy.view.php';
