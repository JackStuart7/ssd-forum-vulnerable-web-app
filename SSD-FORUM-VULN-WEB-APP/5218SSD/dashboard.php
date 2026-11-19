<?php
// Check the user is logged in - redirects to login page if not authenticated
// (must run before any HTML is output so the redirect header can be sent)
include 'includes/auth_check.php';

// Include navbar which also loads database connection, session and security headers
include 'includes/navbar.php';
?>

<!-- Dashboard heading -->
<h2>Dashboard</h2>

<!-- Welcome card displayed after successful login -->
<div class="card" style="text-align:center;">
    <h3>Welcome to your dashboard</h3>
    <p>You are successfully logged in.</p>
</div>

<?php
// Include footer which closes the main content area, body and html tags
include 'includes/footer.php';
?>