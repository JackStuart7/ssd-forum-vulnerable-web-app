<?php

// must be logged in, then load navbar
include 'includes/auth_check.php';
include 'includes/navbar.php';
$status = '';

// Get postId from the URL so we know which post the comments belong to
$post_id = isset($_GET['postId']) ? (int) $_GET['postId'] : 0;

// stop if postId is not valid
if ($post_id <= 0) {
    die("Invalid post.");
}

// comment submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check CSRF token
    check_csrf($_POST['csrf_token'] ?? '');

    // ensure user is logged in before allowing them to comment
    if (!isset($_SESSION['user_id'])) {
        $status = "Error: You must be logged in to comment.";
    } else {

        $comment_text = trim($_POST['comment'] ?? '');

        // validate comment input
        if (!empty($comment_text)) {

            // insert comment into database
            $stmt = $mysqli->prepare("
                INSERT INTO comments (postId, userId, commentText)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment_text);
            $stmt->execute();

            header("Location: comments.php?postId=" . $post_id);
            exit;
        } else {
            $status = "Error: Comment cannot be empty.";
        }
    }
}

$csrf_token = make_csrf();

// retrieve all comments related to the current post, along with the username of the commenter
$stmt = $mysqli->prepare("
    SELECT c.commentText, c.createdAt, u.userName
    FROM comments c
    JOIN users u ON c.userId = u.userId
    WHERE c.postId = ?
    ORDER BY c.createdAt DESC
");


$stmt->bind_param("i", $post_id);
$stmt->execute();

// store the retrieved comments in an array for display
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
?>

<h2>Comments</h2>


<?php if ($status): ?>
    <!-- display status messages such as errors -->
    <div class="card" style="background: <?php echo strpos($status, 'Error') !== false ? '#ffdddd' : '#ddffdd'; ?>; border-left: 4px solid <?php echo strpos($status, 'Error') !== false ? 'red' : 'green'; ?>;">
        <p><?php echo escape_output($status); ?></p>
    </div>
<?php endif; ?>

<div class="card">

<!-- show comments if there are any -->
    <?php if (empty($comments)): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php foreach ($comments as $row): ?>
            <p>
                <strong><?php echo escape_output($row['userName']); ?></strong>:
                <?php echo escape_output($row['commentText']); ?>
                <br>
                <small><?php echo escape_output($row['createdAt']); ?></small>
            </p>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <label for="comment">Write a comment</label>
            <textarea name="comment" id="comment" required placeholder="Write a comment..."></textarea>

            <button type="submit">Post Comment</button>
        </form>
    </div>
<?php else: ?>

    <!-- prompt users to log in if they want to comment -->
    <div class="card">
        <p>You must log in to comment.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>