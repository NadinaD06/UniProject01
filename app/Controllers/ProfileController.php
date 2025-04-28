<?php
class ProfileController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function show($username) {
        // Get user information
        $stmt = $this->pdo->prepare("
            SELECT * FROM users
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            header("HTTP/1.0 404 Not Found");
            require_once __DIR__ . '/../Views/404.php';
            return;
        }

        // Get post count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM posts
            WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $postCount = $stmt->fetchColumn();

        // Get follower count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM follows
            WHERE followed_id = ?
        ");
        $stmt->execute([$user['id']]);
        $followerCount = $stmt->fetchColumn();

        // Get following count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM follows
            WHERE follower_id = ?
        ");
        $stmt->execute([$user['id']]);
        $followingCount = $stmt->fetchColumn();

        // Get user's posts
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments
            FROM posts p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $posts = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/profile/index.php';
    }

    public function follow() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $followed_id = $_POST['user_id'] ?? 0;

            // Check if already following
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM follows
                WHERE follower_id = ? AND followed_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $followed_id]);
            $alreadyFollowing = $stmt->fetchColumn() > 0;

            if ($alreadyFollowing) {
                // Unfollow
                $stmt = $this->pdo->prepare("
                    DELETE FROM follows
                    WHERE follower_id = ? AND followed_id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $followed_id]);
            } else {
                // Follow
                $stmt = $this->pdo->prepare("
                    INSERT INTO follows (follower_id, followed_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $followed_id]);
            }

            // Get updated follower count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM follows
                WHERE followed_id = ?
            ");
            $stmt->execute([$followed_id]);
            $followerCount = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'followers' => $followerCount]);
            exit;
        }
    }
} 