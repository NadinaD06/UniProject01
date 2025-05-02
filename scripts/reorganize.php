<?php
/**
 * Reorganize Project Structure
 * This script reorganizes the project files into a cleaner structure
 */

// Create necessary directories
$directories = [
    'app/config',
    'app/controllers',
    'app/core',
    'app/migrations',
    'app/models',
    'app/routes',
    'app/views',
    'public/assets/css',
    'public/assets/js',
    'public/assets/images',
    'public/uploads',
    'tests/unit',
    'tests/integration'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Move files to their proper locations
$moves = [
    // Move test files to tests directory
    'test_*.php' => 'tests/integration/',
    'db_test*.php' => 'tests/integration/',
    'plesk_db_*.php' => 'tests/integration/',
    
    // Move configuration files
    'config/*.php' => 'app/config/',
    
    // Move view files
    '*.php' => 'app/views/',
    
    // Move asset files
    'assets/css/*' => 'public/assets/css/',
    'assets/js/*' => 'public/assets/js/',
    'assets/images/*' => 'public/assets/images/',
    
    // Move upload files
    'uploads/*' => 'public/uploads/'
];

foreach ($moves as $pattern => $destination) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (is_file($file) && !is_dir($file)) {
            $newPath = $destination . basename($file);
            if (rename($file, $newPath)) {
                echo "Moved $file to $newPath\n";
            } else {
                echo "Failed to move $file\n";
            }
        }
    }
}

// Create .htaccess in public directory
$htaccess = <<<EOT
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default character set
AddDefaultCharset UTF-8

# Enable CORS
Header set Access-Control-Allow-Origin "*"
EOT;

file_put_contents('public/.htaccess', $htaccess);
echo "Created public/.htaccess\n";

// Create composer.json if it doesn't exist
if (!file_exists('composer.json')) {
    $composer = <<<EOT
{
    "name": "unisocial/site",
    "description": "A social media platform for university students",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "ext-pdo": "*",
        "ext-json": "*"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "app/"
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    }
}
EOT;
    file_put_contents('composer.json', $composer);
    echo "Created composer.json\n";
}

// Create index.php in public directory if it doesn't exist
if (!file_exists('public/index.php')) {
    $index = <<<EOT
<?php
/**
 * UniSocial Site
 * Entry point for the application
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
\$config = require __DIR__ . '/../app/config/config.php';

// Initialize application
\$app = new App\Core\Application(\$config);

// Run the application
\$app->run();
EOT;
    file_put_contents('public/index.php', $index);
    echo "Created public/index.php\n";
}

echo "\nReorganization complete!\n";
echo "Please run 'composer install' to install dependencies.\n"; 