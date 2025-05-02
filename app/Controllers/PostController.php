<?php
/**
 * PostController
 * Handles post-related actions
 */
class PostController extends Controller {
    private $postModel;
    private $userModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
        $this->userModel = new User();
    }

    /**
     * Show feed page
     */
    public function index() {
        $this->requireLogin();

        $page = (int) $this->get('page', 1);
        $perPage = $this->config['pagination']['posts_per_page'];
        $userId = $this->getCurrentUserId();

        // Get posts for feed
        $posts = $this->postModel->getFeed($userId, $page, $perPage);

        $this->render('posts/index', [
            'posts' => $posts,
            'user' => $this->userModel->find($userId)
        ]);
    }

    /**
     * Show create post form
     */
    public function create() {
        $this->requireLogin();
        $this->render('posts/create');
    }

    /**
     * Handle post creation
     */
    public function store() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->redirect('/posts/create.php');
        }

        $content = $this->post('content');
        $locationName = $this->post('location_name');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $file = $this->files('image');

        // Validate input
        $errors = $this->validate(
            ['content' => $content],
            ['content' => 'required|max:1000']
        );

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors in the form.');
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $this->post();
            $this->redirect('/posts/create.php');
        }

        try {
            // Create post
            $postId = $this->postModel->createPost([
                'user_id' => $this->getCurrentUserId(),
                'content' => $content,
                'location_name' => $locationName,
                'latitude' => $latitude,
                'longitude' => $longitude
            ], $file);

            $this->setFlash('success', 'Post created successfully!');
            $this->redirect('/index.php');
        } catch (Exception $e) {
            $this->setFlash('error', 'An error occurred while creating the post.');
            $this->redirect('/posts/create.php');
        }
    }

    /**
     * Show single post
     */
    public function show() {
        $this->requireLogin();

        $postId = (int) $this->get('id');
        if (!$postId) {
            $this->redirect('/index.php');
        }

        // Get post with user details
        $post = $this->postModel->getWithUser($postId);
        if (!$post) {
            $this->setFlash('error', 'Post not found.');
            $this->redirect('/index.php');
        }

        // Get comments
        $page = (int) $this->get('page', 1);
        $perPage = $this->config['pagination']['comments_per_page'];
        $comments = $this->postModel->getComments($postId, $page, $perPage);

        $this->render('posts/show', [
            'post' => $post,
            'comments' => $comments
        ]);
    }

    /**
     * Handle post like/unlike
     */
    public function like() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID.'], 400);
        }

        try {
            $userId = $this->getCurrentUserId();
            $success = $this->postModel->like($postId, $userId);

            if ($success) {
                // Create notification for post owner
                $post = $this->postModel->find($postId);
                if ($post && $post['user_id'] !== $userId) {
                    $notificationModel = new Notification();
                    $notificationModel->create([
                        'user_id' => $post['user_id'],
                        'actor_id' => $userId,
                        'type' => 'like',
                        'reference_id' => $postId,
                        'is_read' => 0
                    ]);
                }
            }

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Handle post unlike
     */
    public function unlike() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID.'], 400);
        }

        try {
            $success = $this->postModel->unlike($postId, $this->getCurrentUserId());
            $this->json(['success' => $success]);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Handle comment creation
     */
    public function comment() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }

        $postId = (int) $this->post('post_id');
        $content = $this->post('content');

        // Validate input
        $errors = $this->validate(
            ['content' => $content],
            ['content' => 'required|max:500']
        );

        if (!empty($errors)) {
            $this->json(['error' => 'Please enter a valid comment.'], 400);
        }

        try {
            $userId = $this->getCurrentUserId();
            $commentId = $this->postModel->addComment($postId, $userId, $content);

            // Create notification for post owner
            $post = $this->postModel->find($postId);
            if ($post && $post['user_id'] !== $userId) {
                $notificationModel = new Notification();
                $notificationModel->create([
                    'user_id' => $post['user_id'],
                    'actor_id' => $userId,
                    'type' => 'comment',
                    'reference_id' => $postId,
                    'is_read' => 0
                ]);
            }

            $this->json([
                'success' => true,
                'comment' => [
                    'id' => $commentId,
                    'content' => $content,
                    'username' => $_SESSION['username'],
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get posts by location
     */
    public function getByLocation() {
        $this->requireLogin();

        $lat = (float) $this->get('lat');
        $lng = (float) $this->get('lng');
        $radius = (float) $this->get('radius', 10); // Default 10km radius
        $page = (int) $this->get('page', 1);
        $perPage = $this->config['pagination']['posts_per_page'];

        if (!$lat || !$lng) {
            $this->json(['error' => 'Invalid coordinates.'], 400);
        }

        try {
            $posts = $this->postModel->getByLocation($lat, $lng, $radius, $page, $perPage);
            $this->json($posts);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Delete post
     */
    public function delete() {
        $this->requireLogin();

        $postId = (int) $this->post('post_id');
        if (!$postId) {
            $this->json(['error' => 'Invalid post ID.'], 400);
        }

        // Check if user owns the post or is admin
        $post = $this->postModel->find($postId);
        if (!$post) {
            $this->json(['error' => 'Post not found.'], 404);
        }

        if ($post['user_id'] !== $this->getCurrentUserId() && !$this->isAdmin()) {
            $this->json(['error' => 'Unauthorized.'], 403);
        }

        try {
            $this->postModel->delete($postId);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get post statistics (admin only)
     */
    public function getStats() {
        $this->requireAdmin();

        $period = $this->get('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $this->json(['error' => 'Invalid period.'], 400);
        }

        try {
            $stats = $this->postModel->getStats($period);
            $this->json($stats);
        } catch (Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
} 