<?php
// Define base paths first
define('BASE_PATH', realpath(dirname(__DIR__)));  // This will resolve the correct parent directory
define('HTTPDOCS_PATH', BASE_PATH . '/httpdocs');  // Add httpdocs explicitly
define('APP_PATH', HTTPDOCS_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');

// Site configuration
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);  // This will automatically use your domain
define('PUBLIC_PATH', HTTPDOCS_PATH . '/public');
define('UPLOAD_DIR', PUBLIC_PATH . '/uploads');

// Database configuration
define('DB_HOST', 'localhost:3306');  // Added port number
define('DB_NAME', 's4413713_ctxxxx');  // Your Plesk database name
define('DB_USER', 's4413713_ctxxxx');  // Your Plesk database username
define('DB_PASS', '');  // Your Plesk database password 