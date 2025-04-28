<?php
/**
* app/Services/CacheService.php
**/
namespace App\Services;

class CacheService {
    private $enabled = true;
    private $path;
    
    public function __construct() {
        $this->path = __DIR__ . '/../../storage/cache';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }
    
    /**
     * Get cache key
     * 
     * @param string $key Cache key
     * @return string Cache key hash
     */
    protected function getCacheKey($key) {
        return md5($key);
    }
    
    /**
     * Get cache file path
     * 
     * @param string $key Cache key
     * @return string Cache file path
     */
    protected function getCacheFile($key) {
        return $this->path . '/' . $this->getCacheKey($key) . '.cache';
    }
    
    /**
     * Check if cache exists and is valid
     * 
     * @param string $key Cache key
     * @param int $ttl TTL in seconds
     * @return bool True if cache exists and is valid
     */
    public function has($key, $ttl = 3600) {
        if (!$this->enabled) {
            return false;
        }
        
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        // Check if cache has expired
        $modTime = filemtime($file);
        
        if (time() - $modTime > $ttl) {
            // Cache has expired, delete it
            $this->forget($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get cache value
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if cache doesn't exist
     * @param int $ttl TTL in seconds
     * @return mixed Cache value or default
     */
    public function get($key, $default = null, $ttl = 3600) {
        if (!$this->has($key, $ttl)) {
            return $default;
        }
        
        $file = $this->getCacheFile($key);
        $content = file_get_contents($file);
        
        return unserialize($content);
    }
    
    /**
     * Set cache value
     * 
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @return bool Success status
     */
    public function set($key, $value) {
        if (!$this->enabled) {
            return false;
        }
        
        $file = $this->getCacheFile($key);
        
        return file_put_contents($file, serialize($value)) !== false;
    }
    
    /**
     * Get cache value or set it if it doesn't exist
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate value
     * @param int $ttl TTL in seconds
     * @return mixed Cache value
     */
    public function remember($key, $callback, $ttl = 3600) {
        if ($this->has($key, $ttl)) {
            return $this->get($key);
        }
        
        $value = $callback();
        
        $this->set($key, $value);
        
        return $value;
    }
    
    /**
     * Delete cache
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function forget($key) {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     * 
     * @return bool Success status
     */
    public function flush() {
        $files = glob($this->path . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Enable caching
     */
    public function enable() {
        $this->enabled = true;
    }
    
    /**
     * Disable caching
     */
    public function disable() {
        $this->enabled = false;
    }
}