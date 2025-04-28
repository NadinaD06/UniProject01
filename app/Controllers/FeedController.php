<?php
/**
* app/Controllers/FeedController.php
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Services\CacheService;

class FeedController extends Controller {
    private $post;
    private $cache;
    
    public function __construct() {
        parent::__construct();
        $this->post = new Post();
        $this->cache = new CacheService();
    }
    
    public function index() {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        
        // Get feed posts
        $posts = $this->cache->remember("user_{$userId}_feed", function() use ($userId) {
            return $this->post->getFeedPosts($userId, 10, 0);
        }, 300); // Cache for 5 minutes
        
        return $this->view('feed/index', [
            'posts' => $posts
        ]);
    }
    
    public function loadMore() {
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        $offset = $data['offset'] ?? 0;
        
        // Get feed posts
        $posts = $this->cache->remember("user_{$userId}_feed_offset_{$offset}", function() use ($userId, $offset) {
            return $this->post->getFeedPosts($userId, 10, $offset);
        }, 300); // Cache for 5 minutes
        
        return $this->success([
            'posts' => $posts,
            'has_more' => count($posts) === 10
        ]);
    }
}