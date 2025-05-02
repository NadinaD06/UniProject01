<?php
namespace App\Services;

class RateLimiter {
    private $redis;
    private $prefix = 'rate_limit:';
    
    public function __construct() {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    /**
     * Check if action is allowed
     */
    public function isAllowed($key, $limit, $window) {
        $current = $this->redis->get($this->prefix . $key);
        
        if (!$current) {
            $this->redis->setex($this->prefix . $key, $window, 1);
            return true;
        }
        
        if ($current >= $limit) {
            return false;
        }
        
        $this->redis->incr($this->prefix . $key);
        return true;
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts($key) {
        $current = $this->redis->get($this->prefix . $key);
        return $current ? max(0, 5 - $current) : 5;
    }
    
    /**
     * Get time until reset
     */
    public function getTimeUntilReset($key) {
        return $this->redis->ttl($this->prefix . $key);
    }
    
    /**
     * Reset rate limit
     */
    public function reset($key) {
        return $this->redis->del($this->prefix . $key);
    }
} 