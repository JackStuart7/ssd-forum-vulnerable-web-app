<?php
// Include database connection which also initialises the session
require_once 'includes/database.php';

// Clear all session data by overwriting the session array with an empty array
$_SESSION = [];

// Destroy the session completely to ensure the user is fully logged out
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");
exit;
?>