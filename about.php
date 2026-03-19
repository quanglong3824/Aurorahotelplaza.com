<?php
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
require_once 'controllers/FrontAboutController.php';

initLanguage();

$controller = new FrontAboutController();
$data = $controller->getData();
extract($data);

include 'views/front-about.view.php';
