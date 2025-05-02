<?php
/**
 * PostController
 * Handles post-related actions
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Services\FileUploadService;

class PostController extends Controller {
    private $postModel;
    private $commentModel;
    private $likeModel;
    private $notificationModel;
    private $fileUpload;

    /**
     * Constructor
     */
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->postModel = new Post($pdo);
        $this->commentModel = new Comment();
        $this->likeModel = new Like();
        $this->notificationModel = new Notification();
        $this->fileUpload = new FileUploadService();
    }

    /**
     * Show the feed page
     */
    public function index() {
        // Get posts for feed
        $posts = $this->postModel->getAllPosts(10, 0);
        
        // Get suggested friends
        $suggestedFriends = $this->getSuggestedFriends();
        
        // Get trending topics
        $trendingTopics = $this->getTrendingTopics();
        
        // Get unread notifications count
        $unreadNotifications = $this->getUnreadNotificationsCount();
        
        return $this->view('feed/index', [
            'posts' => $posts,
            'suggestedFriends' => $suggestedFriends,
            'trendingTopics' => $trendingTopics,
            'unreadNotifications' => $unreadNotifications
        ]);
    }

    /**
     * Create a new post
     */
    public function create() {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get input data
        $content = $_POST['content'] ?? '';
        $image = $_FILES['image'] ?? null;
        
        // Validate content
        if (empty($content)) {
            return $this->json(['success' => false, 'message' => 'Post content is required'], 400);
        }
        
        // Handle image upload if present
        $imageUrl = null;
        if ($image && $image['error'] === UPLOAD_ERR_OK) {
            $imageUrl = $this->fileUpload->uploadImage($image, 'posts');
            if (!$imageUrl) {
                return $this->json(['success' => false, 'message' => 'Error uploading image'], 500);
            }
        }
        
        // Create post
        $postId = $this->postModel->create(
            $_SESSION['user_id'],
            $content,
            $imageUrl
        );
        
        if (!$postId) {
            return $this->json(['success' => false, 'message' => 'Error creating post'], 500);
        }
        
        // Get created post with user info
        $post = $this->postModel->getById($postId);
        
        return $this->json([
            'success' => true,
            'data' => $post
        ]);
    }

    /**
     * Update a post
     */
    public function update($id) {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get input data
        parse_str(file_get_contents('php://input'), $data);
        $content = $data['content'] ?? '';
        
        // Validate content
        if (empty($content)) {
            return $this->json(['success' => false, 'message' => 'Post content is required'], 400);
        }
        
        // Get post
        $post = $this->postModel->getById($id);
        
        // Check if post exists and user owns it
        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
            return $this->json(['success' => false, 'message' => 'Post not found or unauthorized'], 404);
        }
        
        // Update post
        $success = $this->postModel->update($id, ['content' => $content]);
        
        if (!$success) {
            return $this->json(['success' => false, 'message' => 'Error updating post'], 500);
        }
        
        return $this->json(['success' => true]);
    }

    /**
     * Delete a post
     */
    public function delete($id) {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get post
        $post = $this->postModel->getById($id);
        
        // Check if post exists and user owns it
        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
            return $this->json(['success' => false, 'message' => 'Post not found or unauthorized'], 404);
        }
        
        // Delete post image if exists
        if ($post['image_url']) {
            $this->fileUpload->deleteImage($post['image_url']);
        }
        
        // Delete post
        $success = $this->postModel->delete($id);
        
        if (!$success) {
            return $this->json(['success' => false, 'message' => 'Error deleting post'], 500);
        }
        
        return $this->json(['success' => true]);
    }

    /**
     * Like/Unlike a post
     */
    public function like($id) {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get post
        $post = $this->postModel->getById($id);
        
        // Check if post exists
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }
        
        // Check if user already liked the post
        $isLiked = $this->postModel->isLiked($id, $_SESSION['user_id']);
        
        if ($isLiked) {
            // Unlike post
            $success = $this->postModel->unlikePost($id, $_SESSION['user_id']);
        } else {
            // Like post
            $success = $this->postModel->likePost($id, $_SESSION['user_id']);
        }
        
        if (!$success) {
            return $this->json(['success' => false, 'message' => 'Error updating like status'], 500);
        }
        
        return $this->json(['success' => true]);
    }

    /**
     * Get comments for a post
     */
    public function getComments($id) {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get comments
        $comments = $this->postModel->getComments($id);
        
        return $this->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Add a comment to a post
     */
    public function addComment($id) {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Get input data
        $content = $_POST['content'] ?? '';
        
        // Validate content
        if (empty($content)) {
            return $this->json(['success' => false, 'message' => 'Comment content is required'], 400);
        }
        
        // Get post
        $post = $this->postModel->getById($id);
        
        // Check if post exists
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }
        
        // Add comment
        $commentId = $this->postModel->addComment($id, $_SESSION['user_id'], $content);
        
        if (!$commentId) {
            return $this->json(['success' => false, 'message' => 'Error adding comment'], 500);
        }
        
        // Get created comment with user info
        $comment = $this->postModel->getCommentById($commentId);
        
        return $this->json([
            'success' => true,
            'data' => $comment
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