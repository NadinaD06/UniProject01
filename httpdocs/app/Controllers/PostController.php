<?php
/**
 * PostController
 * Handles post-related actions
 */
namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Notification;
use App\Services\FileUploadService;

class PostController extends Controller {
    private $postModel;
    private $userModel;
    private $likeModel;
    private $commentModel;
    private $notificationModel;
    private $fileUpload;

    /**
     * Constructor
     */
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->postModel = new Post($pdo);
        $this->userModel = new User($pdo);
        $this->likeModel = new Like($pdo);
        $this->commentModel = new Comment($pdo);
        $this->notificationModel = new Notification();
        $this->fileUpload = new FileUploadService();
    }

    /**
     * Display feed page
     */
    public function index() {
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        $page = $this->get('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $posts = $this->postModel->getFeed($userId, $limit, $offset);
        
        return $this->view('posts/index', [
            'posts' => $posts,
            'page' => $page,
            'hasMore' => count($posts) === $limit
        ]);
    }

    /**
     * Create a new post
     */
    public function create() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->redirect('/feed');
        }
        
        $userId = $this->getCurrentUserId();
        $content = $this->post('content');
        $locationLat = $this->post('location_lat');
        $locationLng = $this->post('location_lng');
        
        // Handle image upload
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image = '/' . $filepath;
            }
        }
        
        $data = [
            'user_id' => $userId,
            'content' => $content,
            'image' => $image,
            'location_lat' => $locationLat,
            'location_lng' => $locationLng
        ];
        
        if ($this->postModel->create($data)) {
            $this->setFlash('success', 'Post created successfully');
        } else {
            $this->setFlash('error', 'Failed to create post');
        }
        
        $this->redirect('/feed');
    }

    /**
     * Like a post
     */
    public function like($postId) {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }
        
        $userId = $this->getCurrentUserId();
        
        try {
            if ($this->likeModel->toggleLike($postId, $userId)) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['error' => 'Failed to like post'], 500);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a comment to a post
     */
    public function comment($postId) {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }
        
        $userId = $this->getCurrentUserId();
        $content = $this->post('content');
        
        try {
            if ($this->commentModel->create([
                'post_id' => $postId,
                'user_id' => $userId,
                'content' => $content
            ])) {
                $comment = $this->commentModel->getLatest($postId);
                $this->jsonResponse([
                    'success' => true,
                    'comment' => $comment
                ]);
            } else {
                $this->jsonResponse(['error' => 'Failed to add comment'], 500);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a post
     */
    public function delete($postId) {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }
        
        $userId = $this->getCurrentUserId();
        $post = $this->postModel->getById($postId, $userId);
        
        if (!$post) {
            $this->jsonResponse(['error' => 'Post not found'], 404);
            return;
        }
        
        if ($post['user_id'] !== $userId && !$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 403);
            return;
        }
        
        try {
            if ($this->postModel->delete($postId)) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['error' => 'Failed to delete post'], 500);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get more posts for infinite scroll
     */
    public function loadMore() {
        $this->requireLogin();
        
        if (!$this->get()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }
        
        $userId = $this->getCurrentUserId();
        $page = $this->get('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $posts = $this->postModel->getFeed($userId, $limit, $offset);
        
        $this->jsonResponse([
            'success' => true,
            'posts' => $posts,
            'hasMore' => count($posts) === $limit
        ]);
    }

    /**
     * Get suggested friends for the current user
     */
    private function getSuggestedFriends() {
        // This would typically query the database for users who:
        // 1. Are not already friends
        // 2. Have mutual friends with the current user
        // 3. Are not blocked
        // For now, return empty array
        return [];
    }

    /**
     * Get trending topics
     */
    private function getTrendingTopics() {
        // This would typically query the database for hashtags that are:
        // 1. Most used in the last 24 hours
        // 2. Have a minimum number of posts
        // For now, return empty array
        return [];
    }

    /**
     * Get unread notifications count
     */
    private function getUnreadNotificationsCount() {
        // This would typically query the database for unread notifications
        // For now, return 0
        return 0;
    }
} 