<?php
require_once 'controllers/FrontTermsController.php';

$controller = new FrontTermsController();
$data = $controller->getData();

extract($data);

include 'views/front-terms.view.php';
