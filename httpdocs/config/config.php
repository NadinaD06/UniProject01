<?php
// Define base paths first
define('BASE_PATH', realpath(dirname(__DIR__)));  // This resolves to /var/www/vhosts/s4413713-ctxxxx.uogs.co.uk/httpdocs/httpdocs
define('APP_PATH', BASE_PATH . '/app');  // Remove the extra /httpdocs
define('CONFIG_PATH', BASE_PATH . '/config');

// Site configuration
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('PUBLIC_PATH', BASE_PATH . '/public');  // Remove the extra /httpdocs
define('UPLOAD_DIR', PUBLIC_PATH . '/uploads');
define('UPLOAD_DIR', PUBLIC_PATH . '/uploads');

// Database configuration
define('DB_HOST', 'localhost:3306');  // Added port number
define('DB_NAME', 's4413713_socialSite');  // Your Plesk database name
define('DB_USER', 's4413713_root');  // Your Plesk database username
define('DB_PASS', '83N*psb81');  // Your Plesk database password 