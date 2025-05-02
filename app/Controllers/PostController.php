<?php
/**
 * PostController
 * Handles post-related actions
 */
namespace App\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;

class PostController extends Controller {
    private $postModel;
    private $commentModel;
    private $likeModel;
    private $notificationModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
        $this->likeModel = new Like();
        $this->notificationModel = new Notification();
    }

    /**
     * Create a new post
     */
    public function create() {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = $_POST['content'] ?? '';
            $locationLat = $_POST['location_lat'] ?? null;
            $locationLng = $_POST['location_lng'] ?? null;
            $locationName = $_POST['location_name'] ?? null;
            $userId = $_SESSION['user_id'];

            if (empty($content)) {
                $this->jsonResponse(['error' => 'Post content cannot be empty'], 400);
                return;
            }

            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'public/uploads/posts/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $this->jsonResponse(['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'], 400);
                    return;
                }

                $fileName = uniqid() . '.' . $fileType;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = $targetPath;
                }
            }

            $postId = $this->postModel->createPost($userId, $content, $imagePath, [
                'latitude' => $locationLat,
                'longitude' => $locationLng,
                'name' => $locationName
            ]);

            if ($postId) {
                $this->jsonResponse(['message' => 'Post created successfully', 'post_id' => $postId]);
            } else {
                $this->jsonResponse(['error' => 'Failed to create post'], 500);
            }
        } else {
            $this->view('posts/create');
        }
    }

    /**
     * Get post feed
     */
    public function feed() {
        $this->requireLogin();

        $page = (int) $this->get('page', 1);
        $perPage = 10;

        $feed = $this->postModel->getFeed($this->getCurrentUserId(), $page, $perPage);

        if ($this->isAjaxRequest()) {
            $this->json($feed);
        }

        $this->render('posts/feed', [
            'feed' => $feed
        ]);
    }

    /**
     * Get user's posts
     */
    public function userPosts() {
        $this->requireLogin();

        $userId = (int) $this->get('user_id');
        if (!$userId) {
            $userId = $this->getCurrentUserId();
        }

        $page = (int) $this->get('page', 1);
        $perPage = 10;

        $posts = $this->postModel->getUserPosts($userId, $page, $perPage);

        if ($this->isAjaxRequest()) {
            $this->json($posts);
        }

        $this->render('posts/user-posts', [
            'posts' => $posts,
            'userId' => $userId
        ]);
    }

    /**
     * Like a post
     */
    public function like() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $postId = $_POST['post_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$postId) {
            $this->jsonResponse(['error' => 'Post ID is required'], 400);
            return;
        }

        $likeId = $this->likeModel->addLike($userId, $postId);
        if ($likeId) {
            // Create notification for post owner
            $post = $this->postModel->getPost($postId);
            if ($post && $post['user_id'] != $userId) {
                $this->notificationModel->createNotification(
                    $post['user_id'],
                    'like',
                    $userId . ' liked your post',
                    $likeId
                );
            }

            $this->jsonResponse([
                'message' => 'Post liked successfully',
                'like_count' => $this->likeModel->getLikeCount($postId)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to like post'], 500);
        }
    }

    /**
     * Unlike a post
     */
    public function unlike() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $postId = $_POST['post_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$postId) {
            $this->jsonResponse(['error' => 'Post ID is required'], 400);
            return;
        }

        if ($this->likeModel->removeLike($userId, $postId)) {
            $this->jsonResponse([
                'message' => 'Post unliked successfully',
                'like_count' => $this->likeModel->getLikeCount($postId)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to unlike post'], 500);
        }
    }

    /**
     * Add comment to post
     */
    public function comment() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $postId = $_POST['post_id'] ?? null;
        $content = $_POST['content'] ?? '';
        $userId = $_SESSION['user_id'];

        if (!$postId || empty($content)) {
            $this->jsonResponse(['error' => 'Post ID and comment content are required'], 400);
            return;
        }

        $commentId = $this->commentModel->addComment($userId, $postId, $content);
        if ($commentId) {
            // Create notification for post owner
            $post = $this->postModel->getPost($postId);
            if ($post && $post['user_id'] != $userId) {
                $this->notificationModel->createNotification(
                    $post['user_id'],
                    'comment',
                    $userId . ' commented on your post',
                    $commentId
                );
            }

            $this->jsonResponse([
                'message' => 'Comment added successfully',
                'comment_count' => $this->commentModel->getCommentCount($postId)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to add comment'], 500);
        }
    }

    /**
     * Get post comments
     */
    public function comments($postId) {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $page = $_GET['page'] ?? 1;
        $comments = $this->commentModel->getPostComments($postId, $page);

        $this->jsonResponse($comments);
    }

    /**
     * Delete a post
     */
    public function delete() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $postId = $_POST['post_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$postId) {
            $this->jsonResponse(['error' => 'Post ID is required'], 400);
            return;
        }

        if ($this->postModel->deletePost($postId, $userId)) {
            $this->jsonResponse(['message' => 'Post deleted successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete post'], 500);
        }
    }
} 