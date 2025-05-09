<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    die("Configuration file not found at: " . $configFile);
}
require_once $configFile;

// Display all defined paths
echo "<h2>Defined Paths:</h2>";
echo "<pre>";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "HTTPDOCS_PATH: " . HTTPDOCS_PATH . "\n";
echo "APP_PATH: " . APP_PATH . "\n";
echo "CONFIG_PATH: " . CONFIG_PATH . "\n";
echo "PUBLIC_PATH: " . PUBLIC_PATH . "\n";
echo "UPLOAD_DIR: " . UPLOAD_DIR . "\n";
echo "</pre>";

// Verify directories exist
echo "<h2>Directory Verification:</h2>";
echo "<pre>";
echo "BASE_PATH exists: " . (is_dir(BASE_PATH) ? 'Yes' : 'No') . "\n";
echo "HTTPDOCS_PATH exists: " . (is_dir(HTTPDOCS_PATH) ? 'Yes' : 'No') . "\n";
echo "APP_PATH exists: " . (is_dir(APP_PATH) ? 'Yes' : 'No') . "\n";
echo "CONFIG_PATH exists: " . (is_dir(CONFIG_PATH) ? 'Yes' : 'No') . "\n";
echo "PUBLIC_PATH exists: " . (is_dir(PUBLIC_PATH) ? 'Yes' : 'No') . "\n";
echo "UPLOAD_DIR exists: " . (is_dir(UPLOAD_DIR) ? 'Yes' : 'No') . "\n";
echo "</pre>"; 