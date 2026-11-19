<?php
// Include database connection, session initialisation and security headers
require_once __DIR__ . '/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSD Forum</title>
    <!-- Link to the main stylesheet -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Main navigation bar displayed on every page -->
<nav class="navbar">

    <!-- Left side of navbar - home link -->
    <div class="nav-left">
        <a href="index.php">Home</a>
    </div>

    <!-- Centre of navbar - search bar -->
    <div class="nav-center">
        <form method="POST" action="search.php">
            <!-- Hidden CSRF token to protect the search form against cross-site request forgery -->
            <input type="hidden" name="csrf_token" value="<?php echo make_csrf(); ?>">
            <input type="text" name="keyword" placeholder="Search posts..." required>
        </form>
    </div>

    <!-- Right side of navbar - dynamic links based on login status -->
    <ul class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Show these links when the user is logged in -->
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="post.php">Posts</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <!-- Show these links when the user is not logged in -->
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Main content area - opened here, closed in footer.php -->
<main>