<?php
// Include navbar which also loads database connection, session and security headers
include 'includes/navbar.php';
?>

    <!-- Welcome card displayed on the homepage -->
    <div class="card" style="max-width:700px; padding:50px; text-align:center;">
        
        <!-- Main heading -->
        <h1 style="margin-bottom:20px;">Welcome to SSD Forum</h1>

        <!-- Brief description of the forum -->
        <p style="font-size:18px; color:#555; margin-bottom:30px;">
            A simple place to share posts, discuss ideas, and connect with others.
        </p>

        <!-- Prompt user to log in or sign up -->
        <p style="font-size:16px; margin-bottom:40px;">
            Please log in or sign up to view and create posts.
        </p>

        <!-- Navigation buttons to login and signup pages -->
        <div class="center-buttons">
            <a href="login.php" class="btn">Login</a>
            <a href="signup.php" class="btn">Sign Up</a>
        </div>
    </div>

<?php
// Include footer which closes the main content area, body and html tags
include 'includes/footer.php';
?>