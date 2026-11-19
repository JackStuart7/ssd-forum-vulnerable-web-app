<?php
require_once 'includes/database.php'; 

// Check the token before the navbar refreshes it
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keyword'])) {
    // This checks the token sent from the previous page
    check_csrf($_POST['csrf_token'] ?? '');
    
    // save the keyword for later
    $keyword = $_POST['keyword'];
    $show_results = true;
} else {
    $show_results = false;
}

// Must be logged in to search (checked before any HTML is output)
include 'includes/auth_check.php';

// Generates a FRESH token for the next search
include 'includes/navbar.php';
?>

<h2>Search Results</h2>
<hr>

<?php

// Only show results if we have a keyword to search for
if ($show_results) {

    // !!! INTENTIONALLY VULNERABLE - SQL INJECTION DEMO !!!
    // The keyword is concatenated straight into the query instead of using a prepared statement.
    // Secure version: $stmt = $mysqli->prepare("... WHERE posts.title LIKE ?"); with "%$keyword%" bound as a parameter.
    // Select the post and the user who posted it, where the title of the post matches the keyword
    $result = $mysqli->query("SELECT posts.postId, posts.title, posts.content, users.userName
        FROM posts
        INNER JOIN users ON posts.userId = users.userId
        WHERE posts.title LIKE '%$keyword%'
    ");

    //this will fetch all the results as an associative array, if there are no results it will return an empty array
    $results = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Check if we have results and display them
    if ($results) {
        // Loop through the results and display them
        foreach ($results as $fetch) {
?>
            <div> 
                <a href="view_post.php?postId=<?php echo $fetch['postId']; ?>"> <!-- Link to the post details page -->
                    <h3><?php echo htmlspecialchars($fetch['title']); ?></h3> <!-- display the title of the post, with HTML special characters escaped for security -->
                </a>

                <p><strong>Posted By:</strong>
                <!-- displays the username of the person who made the post -->
                    <?php echo htmlspecialchars($fetch['userName']); ?> 
                </p>
                <!-- this displays the first 120 characters of the post content and uses htmlspecialchars to prevent XSS attacks -->
                <p><?php echo htmlspecialchars(substr($fetch['content'], 0, 120)); ?>...</p> 
            </div>

            <hr>

<?php
        }
    } else {
        echo "<p>No posts found matching your search.</p>";
    }
}
?>

<?php require 'includes/footer.php'; ?>