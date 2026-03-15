<?php

require_once __DIR__ . '/../src/Core/Repositories/PostRepository.php';
require_once __DIR__ . '/../src/Core/Services/BlogService.php';

use Aurora\Core\Repositories\PostRepository;
use Aurora\Core\Services\BlogService;

class FrontBlogDetailController {
    private BlogService $blogService;

    public function __construct() {
        $db = getDB();
        $this->blogService = new BlogService(new PostRepository($db));
    }

    public function getData() {
        $slug = $_GET['slug'] ?? '';
        if (empty($slug)) {
            return null;
        }

        $data = $this->blogService->getPostDetail($slug);
        if (!$data) return null;

        $success = '';
        $error = '';

        // Handle comment submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
            $result = $this->handleCommentSubmission($data['post']);
            $success = $result['success'] ?? '';
            $error = $result['error'] ?? '';
            
            // Re-fetch comments if successful to show the pending message and updated list (if any)
            if ($success) {
                // Actually the comment is pending, so it won't show in the list yet.
            }
        }

        return array_merge($data, [
            'success' => $success,
            'error' => $error
        ]);
    }

    private function handleCommentSubmission(array $post): array {
        if (!isset($_SESSION['user_id'])) {
            return ['error' => __('blog_page.login_required')];
        } 
        
        if (isset($post['allow_comments']) && (int) $post['allow_comments'] === 0) {
            return ['error' => __('blog_page.comments_disabled')];
        }

        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            return ['error' => __('blog_page.comment_empty')];
        }

        $userName = $_SESSION['user_name'] ?? 'User';
        $userEmail = $_SESSION['user_email'] ?? '';
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        $result = $this->blogService->submitComment(
            $post['post_id'], 
            $_SESSION['user_id'], 
            $userName, 
            $userEmail, 
            $content, 
            $ipAddress
        );

        if ($result) {
            return ['success' => __('blog_page.comment_pending')];
        } else {
            return ['error' => 'Error submitting comment.'];
        }
    }
}
