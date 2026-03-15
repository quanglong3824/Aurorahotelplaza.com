<?php
namespace Aurora\Core\Services;

use Aurora\Core\Repositories\PostRepository;

/**
 * BlogService - Xử lý logic nghiệp vụ cho Blog
 */
class BlogService {
    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepository) {
        $this->postRepository = $postRepository;
    }

    /**
     * Lấy dữ liệu cho trang danh sách blog
     */
    public function getBlogListData(int $page, int $perPage, ?string $categorySlug = null): array {
        $offset = ($page - 1) * $perPage;
        
        $posts = $this->postRepository->getPublishedPosts($perPage, $offset, $categorySlug);
        $totalPosts = $this->postRepository->countPublishedPosts($categorySlug);
        $totalPages = ceil($totalPosts / $perPage);
        $categories = $this->postRepository->getAllCategories();

        // Fix image paths for list
        foreach ($posts as &$post) {
            $post = $this->fixPostImagePaths($post);
        }

        return [
            'page' => $page,
            'total_pages' => $totalPages,
            'category_slug' => $categorySlug,
            'posts' => $posts,
            'categories' => $categories
        ];
    }

    /**
     * Lấy dữ liệu chi tiết bài viết
     */
    public function getPostDetail(string $slug): ?array {
        $post = $this->postRepository->findBySlug($slug);
        if (!$post) return null;

        // Tăng view
        $this->postRepository->incrementViews($post['post_id']);

        // Sửa đường dẫn ảnh
        $post = $this->fixPostImagePaths($post);

        // Lấy bình luận
        $comments = $this->postRepository->getCommentsByPostId($post['post_id']);

        // Lấy bài viết liên quan
        $relatedPosts = $this->postRepository->getRelatedPosts($post['post_id'], $post['category_id']);
        foreach ($relatedPosts as &$rp) {
            $rp = $this->fixPostImagePaths($rp);
        }

        return [
            'post' => $post,
            'comments' => $comments,
            'related_posts' => $relatedPosts
        ];
    }

    /**
     * Xử lý gửi bình luận
     */
    public function submitComment(int $postId, int $userId, string $userName, string $userEmail, string $content, string $ipAddress): bool {
        return $this->postRepository->addComment([
            'post_id' => $postId,
            'user_id' => $userId,
            'author_name' => $userName,
            'author_email' => $userEmail,
            'content' => $content,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Chuẩn hóa đường dẫn ảnh (Legacy fix)
     */
    private function fixPostImagePaths(array $post): array {
        if (!empty($post['featured_image']) && strpos($post['featured_image'], '../uploads/') === 0) {
            $post['featured_image'] = str_replace('../uploads/', 'uploads/', $post['featured_image']);
        }
        
        if (!empty($post['gallery_images'])) {
            $gallery = json_decode($post['gallery_images'], true);
            if (is_array($gallery)) {
                $post['gallery_images'] = json_encode(array_map(function ($img) {
                    return strpos($img, '../uploads/') === 0 ? str_replace('../uploads/', 'uploads/', $img) : $img;
                }, $gallery));
            }
        }
        return $post;
    }
}
