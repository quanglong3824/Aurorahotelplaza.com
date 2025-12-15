<?php
/**
 * Blog Interaction API - Handle likes, ratings, shares
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$post_id = (int)($_POST['post_id'] ?? $_GET['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

switch ($action) {
    case 'like':
        handleLike($db, $post_id, $user_id, $ip_address);
        break;
    case 'unlike':
        handleUnlike($db, $post_id, $user_id, $ip_address);
        break;
    case 'rate':
        $rating = (int)($_POST['rating'] ?? 0);
        handleRating($db, $post_id, $user_id, $ip_address, $rating);
        break;
    case 'share':
        $platform = $_POST['platform'] ?? 'other';
        handleShare($db, $post_id, $user_id, $ip_address, $platform);
        break;
    case 'get_status':
        getInteractionStatus($db, $post_id, $user_id, $ip_address);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleLike($db, $post_id, $user_id, $ip_address) {
    try {
        // Check if already liked
        if ($user_id) {
            $check = $db->prepare("SELECT like_id FROM blog_likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
        } else {
            $check = $db->prepare("SELECT like_id FROM blog_likes WHERE post_id = ? AND ip_address = ? AND user_id IS NULL");
            $check->execute([$post_id, $ip_address]);
        }
        
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already liked', 'already_liked' => true]);
            return;
        }
        
        // Insert like
        $stmt = $db->prepare("INSERT INTO blog_likes (post_id, user_id, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $ip_address]);
        
        // Update count
        $db->prepare("UPDATE blog_posts SET likes_count = likes_count + 1 WHERE post_id = ?")->execute([$post_id]);
        
        // Get new count
        $count = $db->prepare("SELECT likes_count FROM blog_posts WHERE post_id = ?");
        $count->execute([$post_id]);
        $likes = $count->fetchColumn();
        
        echo json_encode(['success' => true, 'likes_count' => (int)$likes, 'liked' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUnlike($db, $post_id, $user_id, $ip_address) {
    try {
        if ($user_id) {
            $stmt = $db->prepare("DELETE FROM blog_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM blog_likes WHERE post_id = ? AND ip_address = ? AND user_id IS NULL");
            $stmt->execute([$post_id, $ip_address]);
        }
        
        if ($stmt->rowCount() > 0) {
            $db->prepare("UPDATE blog_posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE post_id = ?")->execute([$post_id]);
        }
        
        $count = $db->prepare("SELECT likes_count FROM blog_posts WHERE post_id = ?");
        $count->execute([$post_id]);
        $likes = $count->fetchColumn();
        
        echo json_encode(['success' => true, 'likes_count' => (int)$likes, 'liked' => false]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleRating($db, $post_id, $user_id, $ip_address, $rating) {
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    try {
        // Check existing rating
        if ($user_id) {
            $check = $db->prepare("SELECT rating_id, rating FROM blog_ratings WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
        } else {
            $check = $db->prepare("SELECT rating_id, rating FROM blog_ratings WHERE post_id = ? AND ip_address = ? AND user_id IS NULL");
            $check->execute([$post_id, $ip_address]);
        }
        
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing rating
            $stmt = $db->prepare("UPDATE blog_ratings SET rating = ? WHERE rating_id = ?");
            $stmt->execute([$rating, $existing['rating_id']]);
        } else {
            // Insert new rating
            $stmt = $db->prepare("INSERT INTO blog_ratings (post_id, user_id, ip_address, rating) VALUES (?, ?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $ip_address, $rating]);
        }
        
        // Recalculate average
        $avg = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM blog_ratings WHERE post_id = ?");
        $avg->execute([$post_id]);
        $result = $avg->fetch(PDO::FETCH_ASSOC);
        
        $avg_rating = round($result['avg_rating'], 1);
        $rating_count = (int)$result['count'];
        
        // Update post
        $db->prepare("UPDATE blog_posts SET rating_avg = ?, rating_count = ? WHERE post_id = ?")
           ->execute([$avg_rating, $rating_count, $post_id]);
        
        echo json_encode([
            'success' => true, 
            'rating_avg' => $avg_rating, 
            'rating_count' => $rating_count,
            'user_rating' => $rating
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleShare($db, $post_id, $user_id, $ip_address, $platform) {
    $valid_platforms = ['facebook', 'twitter', 'linkedin', 'pinterest', 'copy_link', 'other'];
    if (!in_array($platform, $valid_platforms)) {
        $platform = 'other';
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO blog_shares (post_id, user_id, platform, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $platform, $ip_address]);
        
        $db->prepare("UPDATE blog_posts SET shares_count = shares_count + 1 WHERE post_id = ?")->execute([$post_id]);
        
        $count = $db->prepare("SELECT shares_count FROM blog_posts WHERE post_id = ?");
        $count->execute([$post_id]);
        $shares = $count->fetchColumn();
        
        echo json_encode(['success' => true, 'shares_count' => (int)$shares]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getInteractionStatus($db, $post_id, $user_id, $ip_address) {
    try {
        // Check if liked
        if ($user_id) {
            $like_check = $db->prepare("SELECT like_id FROM blog_likes WHERE post_id = ? AND user_id = ?");
            $like_check->execute([$post_id, $user_id]);
        } else {
            $like_check = $db->prepare("SELECT like_id FROM blog_likes WHERE post_id = ? AND ip_address = ? AND user_id IS NULL");
            $like_check->execute([$post_id, $ip_address]);
        }
        $is_liked = (bool)$like_check->fetch();
        
        // Get user rating
        if ($user_id) {
            $rating_check = $db->prepare("SELECT rating FROM blog_ratings WHERE post_id = ? AND user_id = ?");
            $rating_check->execute([$post_id, $user_id]);
        } else {
            $rating_check = $db->prepare("SELECT rating FROM blog_ratings WHERE post_id = ? AND ip_address = ? AND user_id IS NULL");
            $rating_check->execute([$post_id, $ip_address]);
        }
        $user_rating = $rating_check->fetchColumn() ?: 0;
        
        // Get post stats
        $stats = $db->prepare("SELECT likes_count, shares_count, rating_avg, rating_count FROM blog_posts WHERE post_id = ?");
        $stats->execute([$post_id]);
        $post_stats = $stats->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'is_liked' => $is_liked,
            'user_rating' => (int)$user_rating,
            'likes_count' => (int)($post_stats['likes_count'] ?? 0),
            'shares_count' => (int)($post_stats['shares_count'] ?? 0),
            'rating_avg' => (float)($post_stats['rating_avg'] ?? 0),
            'rating_count' => (int)($post_stats['rating_count'] ?? 0)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
