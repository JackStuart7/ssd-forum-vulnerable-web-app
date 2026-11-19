<?php

// Buffer output so header() calls (redirects, auth checks) still work after HTML has started
ob_start();

require_once __DIR__ . '/../helper_functions.php';

// Security headers
// Frame protection - stops clickjacking
header("X-Frame-Options: DENY");

// Stops MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Control referrer information
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy basic version that works with our inline scripts
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';");

// Remove PHP version disclosure
header_remove("X-Powered-By");

// Start secure session
start_session_secure();

// Database settings are loaded from config.php (not committed - see config.example.php)
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("Missing includes/config.php - copy includes/config.example.php to includes/config.php and set your database details.");
}
$config = require $configFile;

// MySQLi connection
$mysqli = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);

if ($mysqli->connect_error) {
    die("Database connection failed");
}

$mysqli->set_charset("utf8mb4");

?>
