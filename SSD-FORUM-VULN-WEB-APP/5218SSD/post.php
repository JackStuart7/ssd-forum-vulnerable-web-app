<?php
include 'includes/auth_check.php';
include 'includes/navbar.php';

$status = '';

if (isset($_POST['submit'])) { // form for making posts

    // Validate CSRF token to prevent cross-site request forgery attacks
    check_csrf($_POST['csrf_token'] ?? '');

    $file_path = null; // stays null if no file is uploaded (images are optional)

    $upload_error = $_FILES["image"]["error"] ?? UPLOAD_ERR_NO_FILE;

    if ($upload_error !== UPLOAD_ERR_OK && $upload_error !== UPLOAD_ERR_NO_FILE) {
        switch ($upload_error) {
            case UPLOAD_ERR_PARTIAL:    exit("File partially uploaded");
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:  exit("File too big");          // max size is set in php.ini
            case UPLOAD_ERR_NO_TMP_DIR: exit("No temp folder found");
            case UPLOAD_ERR_CANT_WRITE: exit("Unable to write file");  // check folder permissions
            default:                    exit("Unknown upload error");
        }
    }

    if ($upload_error === UPLOAD_ERR_OK) {

        // !!! INTENTIONALLY VULNERABLE - UNRESTRICTED FILE UPLOAD DEMO !!!
        // The original filename is trusted and no file type checks are done, so any file
        // (including a .php script) can be uploaded into a web-accessible folder.
        $filename = basename($_FILES["image"]["name"]);

        /* SECURE VERSION - swap this in to fix the vulnerability:
        $allowed = ["png" => "image/png", "jpg" => "image/jpeg", "jpeg" => "image/jpeg"];
        $ext  = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES["image"]["tmp_name"]); // check real content, not the browser-supplied type
        if (!isset($allowed[$ext]) || $allowed[$ext] !== $mime) {
            exit("File not allowed");
        }
        $filename = bin2hex(random_bytes(16)) . "." . $ext; // random name, user can't choose it
        */

        $upload_dir = __DIR__ . "/assets/posts/";

        // stop files with the same name overwriting each other by adding a number
        $base = $filename;
        $i = 1;
        while (file_exists($upload_dir . $filename)) {
            $filename = "($i)" . $base;
            $i++;
        }

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $upload_dir . $filename)) {
            // store a relative web path (works on Windows and Linux, doesn't leak server paths)
            $file_path = "assets/posts/" . $filename;
        }
    }

    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $userId  = $_SESSION['user_id'];

    if ($title !== '' && $content !== '') {
        $stmt = $mysqli->prepare("
            INSERT INTO posts (userId, title, content, file_path) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $userId, $title, $content, $file_path);
        $stmt->execute();
        $status = "Post created successfully.";
    } else {
        $status = "Title and content are required.";
    }
}
?>

<h2>Create a New Post</h2>

<?php if ($status): ?>
    <div class="card" style="background:#ddffdd; border-left:4px solid green;">
        <p><?php echo escape_output($status); ?></p>
    </div>
<?php endif; ?>

<div class="card">
    
<form method="POST" enctype="multipart/form-data">
    <!-- Hidden CSRF token to protect against cross-site request forgery -->
    <input type="hidden" name="csrf_token" value="<?php echo make_csrf(); ?>">

    <label>Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Content:</label><br>
    <textarea name="content" rows="5" cols="60" required></textarea><br><br>

    <label>File:</label><br>
    <div class="card">
        <h4>Upload an Image</h4>

        <label for="image" style="display:block; margin-bottom:10px; font-weight:bold;">Choose an image (max 1MB)</label>

        <input type="file" id="image" name="image" style="margin-bottom:20px; width:100%; padding:10px;">
    </div>

    <button type="submit" name="submit">Post</button>
</form>

</div>

<hr>
<h2>All Posts</h2>

<?php
$result = $mysqli->query("
    SELECT p.postId, p.title, p.content, u.userName
    FROM posts p
    JOIN users u ON p.userId = u.userId
    ORDER BY p.postId DESC
");

$posts = $result->fetch_all(MYSQLI_ASSOC);

if ($posts):
    foreach ($posts as $row):
?>
        <div class="card">
            <h3>
                <a href="view_post.php?postId=<?php echo $row['postId']; ?>">
                    <?php echo escape_output($row['title']); ?>
                </a>
            </h3>
            <p><strong>Posted By:</strong> <?php echo escape_output($row['userName']); ?></p>
            <p><?php echo escape_output($row['content']); ?></p>
        </div>
<?php
    endforeach;
else:
    echo "<p>No posts found.</p>";
endif;
?>

<?php include 'includes/footer.php'; ?>