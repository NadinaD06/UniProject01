<?php
/**
 * PostController
 * Handles post-related actions
 */
namespace App\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;

class PostController extends Controller {
    private $postModel;
    private $commentModel;
    private $likeModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
        $this->likeModel = new Like();
    }

    /**
     * Create a new post
     */
    public function create() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $content = $this->post('content');
        $locationLat = $this->post('location_lat');
        $locationLng = $this->post('location_lng');
        $locationName = $this->post('location_name');
        $image = $this->files('image');

        if (!$content) {
            $this->json(['error' => 'Post content is required'], 400);
        }

        try {
            $data = [
                'user_id' => $this->getCurrentUserId(),
                'content' => $content
            ];

            // Handle location if provided
            if ($locationLat && $locationLng) {
                $data['location_lat'] = $locationLat;
                $data['location_lng'] = $locationLng;
                $data['location_name'] = $locationName;
            }

            // Handle image upload if provided
            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $config = require __DIR__ . '/../config/config.php';
                $uploadDir = $config['upload']['directory'];
                $allowedTypes = $config['upload']['allowed_types'];
                $maxSize = $config['upload']['max_size'];

                // Validate file
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $image['tmp_name']);
                $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

                if ($image['size'] > $maxSize) {
                    $this->json(['error' => 'File size exceeds limit'], 400);
                }

                if (!in_array($extension, $allowedTypes)) {
                    $this->json(['error' => 'Invalid file type'], 400);
                }

                // Generate unique filename
                $filename = uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                // Move uploaded file
                if (!move_uploaded_file($image['tmp_name'], $filepath)) {
                    $this->json(['error' => 'Failed to move uploaded file'], 500);
                }

                $data['image_path'] = $filename;
            }

            $postId = $this->postModel->createPost($data);
            if ($postId) {
                $post = $this->postModel->getPost($postId, $this->getCurrentUserId());
                $this->json(['success' => true, 'post' => $post]);
            } else {
                $this->json(['error' => 'Failed to create post'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to create post: ' . $e->getMessage()], 500);
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
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID'], 400);
        }

        try {
            $success = $this->likeModel->likePost($postId, $this->getCurrentUserId());
            if ($success) {
                $likeCount = $this->likeModel->getLikeCount($postId);
                $this->json(['success' => true, 'like_count' => $likeCount]);
            } else {
                $this->json(['error' => 'Post is already liked'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to like post'], 500);
        }
    }

    /**
     * Unlike a post
     */
    public function unlike() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID'], 400);
        }

        try {
            $success = $this->likeModel->unlikePost($postId, $this->getCurrentUserId());
            if ($success) {
                $likeCount = $this->likeModel->getLikeCount($postId);
                $this->json(['success' => true, 'like_count' => $likeCount]);
            } else {
                $this->json(['error' => 'Post is not liked'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to unlike post'], 500);
        }
    }

    /**
     * Add comment to post
     */
    public function comment() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $postId = (int) $this->post('post_id');
        $content = $this->post('content');

        if (!$postId || !$content) {
            $this->json(['error' => 'Missing required fields'], 400);
        }

        try {
            $commentId = $this->commentModel->addComment(
                $postId,
                $this->getCurrentUserId(),
                $content
            );

            if ($commentId) {
                $comment = $this->commentModel->find($commentId);
                $this->json(['success' => true, 'comment' => $comment]);
            } else {
                $this->json(['error' => 'Failed to add comment'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to add comment'], 500);
        }
    }

    /**
     * Get post comments
     */
    public function comments() {
        $this->requireLogin();

        $postId = (int) $this->get('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID'], 400);
        }

        $page = (int) $this->get('page', 1);
        $perPage = 20;

        $comments = $this->commentModel->getPostComments($postId, $page, $perPage);

        if ($this->isAjaxRequest()) {
            $this->json($comments);
        }

        $this->render('posts/comments', [
            'comments' => $comments,
            'postId' => $postId
        ]);
    }

    /**
     * Delete a post
     */
    public function delete() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID'], 400);
        }

        try {
            $success = $this->postModel->deletePost($postId, $this->getCurrentUserId());
            if ($success) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Failed to delete post'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to delete post'], 500);
        }
    }
} 