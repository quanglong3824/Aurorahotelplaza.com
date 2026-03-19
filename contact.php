<?php
session_start();
require_once 'config/database.php';
require_once 'config/environment.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
require_once 'controllers/FrontContactController.php';

initLanguage();

$controller = new FrontContactController();
$data = $controller->getData();
extract($data);

include 'views/front-contact.view.php';
