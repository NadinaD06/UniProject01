<?php
class PostController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // Get all posts with user information
        $stmt = $this->pdo->query("
            SELECT p.*, u.username, u.avatar as user_avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ");
        $posts = $stmt->fetchAll();

        // Get trending topics
        $stmt = $this->pdo->query("
            SELECT name, COUNT(*) as count
            FROM posts
            WHERE created_at > NOW() - INTERVAL '7 days'
            GROUP BY name
            ORDER BY count DESC
            LIMIT 5
        ");
        $trendingTopics = $stmt->fetchAll();

        // Get suggested users
        $stmt = $this->pdo->query("
            SELECT id, username, avatar
            FROM users
            WHERE id != ?
            ORDER BY RANDOM()
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $suggestedUsers = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/feed/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';

            if (empty($title) || empty($content)) {
                $error = "Title and content are required";
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO posts (user_id, title, content)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $title, $content]);
                header('Location: /feed');
                exit;
            }
        }
    }

    public function like() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_id = $_POST['post_id'] ?? 0;

            // Check if already liked
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM likes
                WHERE post_id = ? AND user_id = ?
            ");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $alreadyLiked = $stmt->fetchColumn() > 0;

            if ($alreadyLiked) {
                // Unlike
                $stmt = $this->pdo->prepare("
                    DELETE FROM likes
                    WHERE post_id = ? AND user_id = ?
                ");
                $stmt->execute([$post_id, $_SESSION['user_id']]);
            } else {
                // Like
                $stmt = $this->pdo->prepare("
                    INSERT INTO likes (post_id, user_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$post_id, $_SESSION['user_id']]);
            }

            // Get updated like count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM likes
                WHERE post_id = ?
            ");
            $stmt->execute([$post_id]);
            $likeCount = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'likes' => $likeCount]);
            exit;
        }
    }

    public function comment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_id = $_POST['post_id'] ?? 0;
            $content = $_POST['content'] ?? '';

            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
                exit;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO comments (post_id, user_id, content)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);

            // Get updated comment count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM comments
                WHERE post_id = ?
            ");
            $stmt->execute([$post_id]);
            $commentCount = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'comments' => $commentCount]);
            exit;
        }
    }
} 