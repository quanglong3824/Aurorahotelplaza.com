<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
require_once 'helpers/image-helper.php';
require_once 'controllers/FrontBlogController.php';

initLanguage();

$controller = new FrontBlogController();
$data = $controller->getData();
extract($data);

include 'views/front-blog.view.php';
