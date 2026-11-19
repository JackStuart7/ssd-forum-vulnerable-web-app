<?php
// Load the database connection, which also starts the session with secure cookie settings
// (HttpOnly + SameSite=Strict) and turns on output buffering
require_once __DIR__ . '/database.php';

// Prevent browser caching of protected pages
// This ensures sensitive pages are not stored in browser history or cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if the user is logged in by verifying the session variable
// If not logged in, redirect to the login page immediately
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>