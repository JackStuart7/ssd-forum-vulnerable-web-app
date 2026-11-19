<?php
include 'includes/auth_check.php';
include 'includes/navbar.php';

if (!isset($_GET['postId']) || !ctype_digit($_GET['postId'])) {
    die("Post not found.");
}

$postId = (int) $_GET['postId'];

// Get the post, including the uploaded file path, and who posted it
$stmt = $mysqli->prepare("
    SELECT p.postId, p.title, p.content, p.file_path, u.userName 
    FROM posts p
    JOIN users u ON p.userId = u.userId
    WHERE p.postId = ?
");
$stmt->bind_param("i", $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die("Post does not exist.");
}
?>

<h2><?php echo escape_output($post['title']); ?></h2>
<h3>Posted By: <?php echo escape_output($post['userName']); ?></h3>


<?php if (!empty($post['file_path'])): ?>
    <!-- Only display an image if the post has one. basename() keeps it inside assets/posts/ -->
    <img src="assets/posts/<?php echo escape_output(rawurlencode(basename($post['file_path']))); ?>" width="192" height="108" alt="Post image">
<?php endif; ?>

<p><?php echo escape_output($post['content']); ?></p>

<hr>

<h3>Comments</h3>
<p><a href="comments.php?postId=<?php echo $post['postId']; ?>">View / Add comments</a></p>

<?php include 'includes/footer.php'; ?>