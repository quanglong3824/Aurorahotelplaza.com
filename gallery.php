<?php
require_once 'controllers/FrontGalleryController.php';

$controller = new FrontGalleryController();
$data = $controller->getData();

extract($data);

include 'views/front-gallery.view.php';
