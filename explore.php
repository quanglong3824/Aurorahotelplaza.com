<?php
require_once 'controllers/FrontExploreController.php';

$controller = new FrontExploreController();
$data = $controller->getData();

extract($data);

include 'views/front-explore.view.php';
