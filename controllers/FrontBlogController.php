<?php

require_once __DIR__ . '/../src/Core/Repositories/PostRepository.php';
require_once __DIR__ . '/../src/Core/Services/BlogService.php';

use Aurora\Core\Repositories\PostRepository;
use Aurora\Core\Services\BlogService;

class FrontBlogController {
    private BlogService $blogService;

    public function __construct() {
        $db = getDB();
        $this->blogService = new BlogService(new PostRepository($db));
    }

    public function getData() {
        // Pagination setup
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = 9;

        // Category filter
        $category_slug = isset($_GET['category']) ? $_GET['category'] : '';

        return $this->blogService->getBlogListData($page, $per_page, $category_slug);
    }
}
