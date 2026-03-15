<?php
namespace Aurora\Core\Repositories;

use PDO;

/**
 * PostRepository - Đóng gói các truy vấn SQL liên quan đến bài viết (Blog)
 */
class PostRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Lấy danh sách bài viết có phân trang và lọc theo category
     */
    public function getPublishedPosts(int $limit, int $offset, ?string $categorySlug = null): array {
        $select = "SELECT p.*, u.full_name as author_name, bc.category_name, bc.category_name_en, bc.slug as category_slug,
                   (SELECT COUNT(*) FROM blog_comments WHERE post_id = p.post_id AND status = 'approved') as comment_count";
        $from = "FROM blog_posts p 
                 LEFT JOIN users u ON p.author_id = u.user_id 
                 LEFT JOIN blog_categories bc ON p.category_id = bc.category_id";
        $where = "WHERE p.status = 'published'";
        $params = [];

        if ($categorySlug) {
            $where .= " AND bc.slug = :category_slug";
            $params[':category_slug'] = $categorySlug;
        }

        $order = "ORDER BY p.published_at DESC, p.post_id DESC";
        $limitClause = "LIMIT :limit OFFSET :offset";

        $sql = "$select $from $where $order $limitClause";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số bài viết đã xuất bản
     */
    public function countPublishedPosts(?string $categorySlug = null): int {
        $from = "FROM blog_posts p 
                 LEFT JOIN blog_categories bc ON p.category_id = bc.category_id";
        $where = "WHERE p.status = 'published'";
        $params = [];

        if ($categorySlug) {
            $where .= " AND bc.slug = ?";
            $params[] = $categorySlug;
        }

        $stmt = $this->db->prepare("SELECT COUNT(p.post_id) as total $from $where");
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Lấy tất cả danh mục blog
     */
    public function getAllCategories(): array {
        $stmt = $this->db->query("SELECT category_name, category_name_en, slug FROM blog_categories ORDER BY sort_order ASC, category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy bài viết chi tiết theo slug
     */
    public function findBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as author_name, u.avatar, bc.category_name, bc.category_name_en, bc.slug as category_slug
            FROM blog_posts p
            LEFT JOIN users u ON p.author_id = u.user_id
            LEFT JOIN blog_categories bc ON p.category_id = bc.category_id
            WHERE p.slug = ? AND p.status = 'published'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Tăng lượt xem bài viết
     */
    public function incrementViews(int $postId): void {
        $stmt = $this->db->prepare("UPDATE blog_posts SET views = views + 1 WHERE post_id = ?");
        $stmt->execute([$postId]);
    }

    /**
     * Lấy bình luận của bài viết
     */
    public function getCommentsByPostId(int $postId): array {
        $stmt = $this->db->prepare("
            SELECT c.*, u.full_name as user_name, u.avatar
            FROM blog_comments c
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE c.post_id = ? AND c.status = 'approved'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy các bài viết liên quan
     */
    public function getRelatedPosts(int $postId, int $categoryId, int $limit = 3): array {
        $stmt = $this->db->prepare("
            SELECT * FROM blog_posts
            WHERE status = 'published' AND post_id != ? AND category_id = ?
            ORDER BY published_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $postId, PDO::PARAM_INT);
        $stmt->bindValue(2, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm bình luận mới
     */
    public function addComment(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO blog_comments (post_id, user_id, author_name, author_email, content, status, ip_address)
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");
        return $stmt->execute([
            $data['post_id'],
            $data['user_id'],
            $data['author_name'],
            $data['author_email'],
            $data['content'],
            $data['ip_address']
        ]);
    }
}
