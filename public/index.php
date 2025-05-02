<?php
/**
 * UniSocial Site
 * Entry point for the application
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../app/config/config.php';

// Initialize application
$app = new App\Core\Application($config);

// Run the application
$app->run();