<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>PostgreSQL Installation Check</h2>";

// Check if PostgreSQL is installed
echo "<h3>PostgreSQL Installation:</h3>";
$output = [];
exec('which psql', $output);
echo "PostgreSQL client (psql) found: " . (!empty($output) ? 'Yes' : 'No') . "<br>";
if (!empty($output)) {
    echo "Location: " . $output[0] . "<br>";
}

// Check PostgreSQL service status
echo "<h3>PostgreSQL Service Status:</h3>";
$output = [];
exec('systemctl status postgresql', $output);
echo "Service status:<br>";
foreach ($output as $line) {
    echo htmlspecialchars($line) . "<br>";
}

// Check PostgreSQL configuration
echo "<h3>PostgreSQL Configuration:</h3>";
$config_files = [
    '/etc/postgresql/*/main/postgresql.conf',
    '/var/lib/postgresql/*/main/postgresql.conf'
];

foreach ($config_files as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "Found config file: $file<br>";
            $content = file_get_contents($file);
            if (strpos($content, 'listen_addresses') !== false) {
                echo "listen_addresses setting found<br>";
            }
        }
    }
}

// Check PostgreSQL logs
echo "<h3>PostgreSQL Logs:</h3>";
$log_files = [
    '/var/log/postgresql/postgresql-*.log',
    '/var/lib/postgresql/*/main/pg_log/*.log'
];

foreach ($log_files as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "Found log file: $file<br>";
            $content = file_get_contents($file);
            echo "Last 5 lines of log:<br>";
            $lines = array_slice(explode("\n", $content), -5);
            foreach ($lines as $line) {
                echo htmlspecialchars($line) . "<br>";
            }
        }
    }
}

// Check if we can connect to PostgreSQL using psql
echo "<h3>PostgreSQL Connection Test:</h3>";
$output = [];
exec('psql -h localhost -U ' . escapeshellarg($config['DB_USER']) . ' -d ' . escapeshellarg($config['DB_NAME']) . ' -c "SELECT version();" 2>&1', $output);
echo "Connection test result:<br>";
foreach ($output as $line) {
    echo htmlspecialchars($line) . "<br>";
}
?> 