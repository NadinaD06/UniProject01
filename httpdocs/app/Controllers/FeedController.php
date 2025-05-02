<?php
/**
* app/Controllers/FeedController.php
* Improved Feed Controller with proper caching
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\CacheService;
use App\Models\Notification;

class FeedController extends Controller {
    private $post;
    private $user;
    private $cache;
    private $notification;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->post = new Post($pdo);
        $this->user = new User($pdo);
        $this->cache = new CacheService();
        $this->notification = new Notification($pdo);
    }
    
    /**
     * Display the feed page
     * 
     * @return string Rendered view
     */
    public function index() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        $category = $_GET['category'] ?? null;
        $filter = $_GET['filter'] ?? 'following';
        
        // Get feed posts with optimized caching (5 minute cache)
        $cacheKey = "user_{$userId}_feed_{$filter}_{$category}_0";
        $posts = $this->cache->remember($cacheKey, function() use ($userId, $filter, $category) {
            return $this->getFeedData($userId, $filter, $category, 10, 0);
        }, 300);
        
        // Get trending tags for the sidebar
        $trendingTags = $this->cache->remember('trending_tags', function() {
            return $this->post->getTrendingTags(10);
        }, 3600); // Cache for 1 hour
        
        // Get suggested users to follow
        $suggestedUsers = [];
        if ($filter === 'following') {
            $suggestedUsers = $this->cache->remember("user_{$userId}_suggested", function() use ($userId) {
                return $this->user->getSuggestedUsers($userId, 5);
            }, 1800); // Cache for 30 minutes
        }
        
        // Get user's own data
        $userData = $this->auth->user();
        $userStats = $this->user->getUserStats($userId);
        
        // Get feed categories
        $categories = $this->cache->remember('categories', function() {
            return $this->post->getCategories();
        }, 86400); // Cache for 24 hours
        
        return $this->view('feed/index', [
            'posts' => $posts,
            'filter' => $filter,
            'category' => $category,
            'trending_tags' => $trendingTags,
            'suggested_users' => $suggestedUsers,
            'user' => $userData,
            'user_stats' => $userStats,
            'categories' => $categories
        ]);
    }
    
    /**
     * Load more posts for infinite scrolling
     * 
     * @return void JSON response
     */
    public function loadMore() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        $offset = (int)($data['offset'] ?? 0);
        $filter = $data['filter'] ?? 'following';
        $category = $data['category'] ?? null;
        
        // Validate offset
        if ($offset < 0) {
            return $this->error('Invalid offset');
        }
        
        // Get more feed posts with caching
        $cacheKey = "user_{$userId}_feed_{$filter}_{$category}_{$offset}";
        $posts = $this->cache->remember($cacheKey, function() use ($userId, $filter, $category, $offset) {
            return $this->getFeedData($userId, $filter, $category, 10, $offset);
        }, 300); // Cache for 5 minutes
        
        return $this->success([
            'posts' => $posts,
            'has_more' => count($posts) === 10
        ]);
    }
    
    /**
     * Get feed data based on filter and category
     * 
     * @param int $userId User ID
     * @param string $filter Filter type (following, trending, latest)
     * @param string|null $category Category filter
     * @param int $limit Number of posts to fetch
     * @param int $offset Offset for pagination
     * @return array Posts data
     */
    private function getFeedData($userId, $filter, $category, $limit, $offset) {
        switch ($filter) {
            case 'following':
                // Posts from followed users only
                return $this->post->getFeedPosts($userId, $limit, $offset, true, $category);
                
            case 'trending':
                // Trending posts
                return $this->post->getTrendingPosts($userId, $limit, $offset, $category);
                
            case 'latest':
            default:
                // Latest posts from everyone
                return $this->post->getFeedPosts($userId, $limit, $offset, false, $category);
        }
    }
    
    /**
     * Display the explore page
     * 
     * @return string Rendered view
     */
    public function explore() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        $category = $_GET['category'] ?? null;
        $tag = $_GET['tag'] ?? null;
        
        // Get trending posts
        $cacheKey = "explore_trending_{$category}_{$tag}_0";
        $posts = $this->cache->remember($cacheKey, function() use ($userId, $category, $tag) {
            if ($tag) {
                return $this->post->getPostsByTag($tag, $userId, 20, 0);
            } else {
                return $this->post->getTrendingPosts($userId, 20, 0, $category);
            }
        }, 600); // Cache for 10 minutes
        
        // Get categories
        $categories = $this->cache->remember('categories', function() {
            return $this->post->getCategories();
        }, 86400); // Cache for 24 hours
        
        // Get trending tags
        $trendingTags = $this->cache->remember('trending_tags', function() {
            return $this->post->getTrendingTags(20);
        }, 3600); // Cache for 1 hour
        
        return $this->view('feed/explore', [
            'posts' => $posts,
            'categories' => $categories,
            'trending_tags' => $trendingTags,
            'current_category' => $category,
            'current_tag' => $tag
        ]);
    }
    
    /**
     * Load more posts for explore page
     * 
     * @return void JSON response
     */
    public function loadMoreExplore() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        $offset = (int)($data['offset'] ?? 0);
        $category = $data['category'] ?? null;
        $tag = $data['tag'] ?? null;
        
        // Validate offset
        if ($offset < 0) {
            return $this->error('Invalid offset');
        }
        
        // Get more posts with caching
        $cacheKey = "explore_trending_{$category}_{$tag}_{$offset}";
        $posts = $this->cache->remember($cacheKey, function() use ($userId, $category, $tag, $offset) {
            if ($tag) {
                return $this->post->getPostsByTag($tag, $userId, 20, $offset);
            } else {
                return $this->post->getTrendingPosts($userId, 20, $offset, $category);
            }
        }, 600); // Cache for 10 minutes
        
        return $this->success([
            'posts' => $posts,
            'has_more' => count($posts) === 20
        ]);
    }
    
    /**
     * Search for posts, users, and tags
     * 
     * @return void JSON response
     */
    public function search() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        $query = $data['q'] ?? '';
        
        // Validate query
        if (empty($query) || strlen($query) < 2) {
            return $this->error('Search query too short');
        }
        
        // Search for posts
        $posts = $this->post->searchPosts($query, $userId, 5);
        
        // Search for users
        $users = $this->user->searchUsers($query, 5);
        
        // Search for tags
        $tags = $this->post->searchTags($query, 5);
        
        return $this->success([
            'posts' => $posts,
            'users' => $users,
            'tags' => $tags
        ]);
    }
    
    /**
     * Mark a post as viewed (increment view count)
     * 
     * @return void JSON response
     */
    public function viewPost() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $postId = (int)($data['post_id'] ?? 0);
        
        // Validate post ID
        if ($postId <= 0) {
            return $this->error('Invalid post ID');
        }
        
        // Increment view count
        $success = $this->post->incrementViews($postId);
        
        if ($success) {
            return $this->success();
        } else {
            return $this->error('Failed to increment view count');
        }
    }
    
    /**
     * Like or unlike a post
     * 
     * @return void JSON response
     */
    public function toggleLike() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        $postId = (int)($data['post_id'] ?? 0);
        
        // Validate post ID
        if ($postId <= 0) {
            return $this->error('Invalid post ID');
        }
        
        // Toggle like
        $result = $this->post->toggleLike($postId, $userId);
        
        // Clear cache for this user's feed
        $this->clearUserFeedCache($userId);
        
        return $this->success($result);
    }
    
    /**
     * Save or unsave a post
     * 
     * @return void JSON response
     */
    public function toggleSave() {
        // Check authentication
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        $postId = (int)($data['post_id'] ?? 0);
        
        // Validate post ID
        if ($postId <= 0) {
            return $this->error('Invalid post ID');
        }
        
        // Toggle save
        $result = $this->post->toggleSave($postId, $userId);
        
        return $this->success($result);
    }
    
    /**
     * Clear user feed cache when user interactions change
     * 
     * @param int $userId User ID
     */
    private function clearUserFeedCache($userId) {
        // Clear all feed caches for this user
        $this->cache->forget("user_{$userId}_feed_following_*");
        $this->cache->forget("user_{$userId}_feed_trending_*");
        $this->cache->forget("user_{$userId}_feed_latest_*");
    }
    
    public function create() {
        if (!$this->isPost()) {
            $this->redirect('/feed');
        }
        
        $content = $this->getPost('content');
        $imageUrl = $this->getPost('image_url');
        $location = $this->getPost('location');
        
        if (empty($content)) {
            $this->json([
                'success' => false,
                'message' => 'Post content cannot be empty'
            ]);
            return;
        }
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'content' => $content,
            'image_url' => $imageUrl,
            'location' => $location
        ];
        
        if ($this->post->create($data)) {
            $this->json([
                'success' => true,
                'message' => 'Post created successfully'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to create post'
            ]);
        }
    }
    
    public function like() {
        if (!$this->isPost()) {
            $this->redirect('/feed');
        }
        
        $postId = $this->getPost('post_id');
        
        if ($this->post->like($postId, $_SESSION['user_id'])) {
            $this->notification->createLikeNotification($postId, $postId, $_SESSION['user_id']);
            $this->json([
                'success' => true,
                'message' => 'Post liked successfully'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to like post'
            ]);
        }
    }
    
    public function unlike() {
        if (!$this->isPost()) {
            $this->redirect('/feed');
        }
        
        $postId = $this->getPost('post_id');
        
        if ($this->post->unlike($postId, $_SESSION['user_id'])) {
            $this->json([
                'success' => true,
                'message' => 'Post unliked successfully'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to unlike post'
            ]);
        }
    }
    
    public function delete() {
        if (!$this->isPost()) {
            $this->redirect('/feed');
        }
        
        $postId = $this->getPost('post_id');
        $post = $this->post->getById($postId);
        
        if (!$post || $post['user_id'] != $_SESSION['user_id']) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            return;
        }
        
        if ($this->post->delete($postId)) {
            $this->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to delete post'
            ]);
        }
    }
}