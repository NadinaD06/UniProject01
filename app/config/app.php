<?php
/**
 * Application Configuration
 */

return [
    /**
     * Application Environment
     * 
     * Options: development, testing, production
     */
    'environment' => getenv('APP_ENV') ?: 'development',
    
    /**
     * Application Name
     */
    'name' => 'ArtSpace',
    
    /**
     * Application URL
     */
    'url' => getenv('APP_URL') ?: 'http://localhost',
    
    /**
     * Application Timezone
     */
    'timezone' => 'UTC',
    
    /**
     * Database Configuration
     */
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_NAME') ?: 'artspace',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    /**
     * Session Configuration
     */
    'session' => [
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    
    /**
     * Cookie Configuration
     */
    'cookie' => [
        'prefix' => 'artspace_',
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    
    /**
     * Mail Configuration
     */
    'mail' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => getenv('MAIL_PORT') ?: 2525,
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@artspace.com',
            'name' => getenv('MAIL_FROM_NAME') ?: 'ArtSpace',
        ],
    ],
    
    /**
     * File Storage Configuration
     */
    'storage' => [
        'uploads_directory' => 'uploads',
        'profile_images' => 'profiles',
        'post_images' => 'posts',
        'max_upload_size' => 10485760, // 10MB in bytes
        'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif'],
    ],
    
    /**
     * WebSocket Configuration
     */
    'websocket' => [
        'enabled' => getenv('WEBSOCKET_ENABLED') === 'true',
        'url' => getenv('WEBSOCKET_URL') ?: 'ws://localhost:8080',
        'api_key' => getenv('WEBSOCKET_API_KEY') ?: 'artspace_api_key',
    ],
    
    /**
     * Cache Configuration
     */
    'cache' => [
        'driver' => getenv('CACHE_DRIVER') ?: 'file', // file, redis, memcached
        'prefix' => 'artspace_cache_',
        'ttl' => 3600, // Default TTL in seconds (1 hour)
        'redis' => [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'password' => getenv('REDIS_PASSWORD') ?: null,
        ],
        'memcached' => [
            'host' => getenv('MEMCACHED_HOST') ?: '127.0.0.1',
            'port' => getenv('MEMCACHED_PORT') ?: 11211,
        ],
    ],
    
    /**
     * Authentication Configuration
     */
    'auth' => [
        'password_min_length' => 8,
        'remember_me_expiry' => 2592000, // 30 days in seconds
        'password_reset_expiry' => 3600, // 1 hour in seconds
        'max_login_attempts' => 5, // Maximum login attempts before lockout
        'lockout_time' => 900, // Lockout time in seconds (15 minutes)
    ],
    
    /**
     * API Configuration
     */
    'api' => [
        'rate_limit' => 60, // Requests per minute
        'token_expiry' => 86400, // 24 hours in seconds
    ],
    
    /**
     * Application Features Configuration
     */
    'features' => [
        'registration' => true, // Allow new user registrations
        'email_verification' => true, // Require email verification for new accounts
        'user_following' => true, // Enable user following functionality
        'direct_messaging' => true, // Enable direct messaging between users
        'comments' => true, // Enable comments on posts
        'post_sharing' => true, // Enable post sharing functionality
        'reporting' => true, // Enable content reporting
        'search' => true, // Enable search functionality
        'notifications' => true, // Enable notifications
    ],
    
    /**
     * Social Login Configuration
     */
    'social' => [
        'google' => [
            'enabled' => getenv('GOOGLE_LOGIN_ENABLED') === 'true',
            'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'redirect' => getenv('APP_URL') . '/auth/google/callback',
        ],
        'facebook' => [
            'enabled' => getenv('FACEBOOK_LOGIN_ENABLED') === 'true',
            'client_id' => getenv('FACEBOOK_CLIENT_ID') ?: '',
            'client_secret' => getenv('FACEBOOK_CLIENT_SECRET') ?: '',
            'redirect' => getenv('APP_URL') . '/auth/facebook/callback',
        ],
        'twitter' => [
            'enabled' => getenv('TWITTER_LOGIN_ENABLED') === 'true',
            'client_id' => getenv('TWITTER_CLIENT_ID') ?: '',
            'client_secret' => getenv('TWITTER_CLIENT_SECRET') ?: '',
            'redirect' => getenv('APP_URL') . '/auth/twitter/callback',
        ],
    ],
];