<?php
include 'includes/navbar.php';

$status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') { // checks if form was submitted 
    check_csrf($_POST['csrf_token'] ?? ''); // verify csrf first

    $email = $_POST['email'] ?? '';
    if (!empty($email)) {
        
        // call create_reset_request 
        $status = create_reset_request($email, $mysqli);
    }
}

// generate token for form, calls the function
$csrf_token = make_csrf();

// Get logged in user's email if available
$current_email = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT email FROM users WHERE userId = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $current_email = $user['email'];
    }
}
?>

<h2>Reset your password</h2>

<?php if ($status): ?><!-- alert box that changes colour red email not found, greent link sent -->
    <div class="card" style="background: <?php echo strpos($status, 'Error') !== false ? '#ffdddd' : '#ddffdd'; ?>; border-left: 4px solid <?php echo strpos($status, 'Error') !== false ? 'red' : 'green'; ?>;">
        <p><?php echo $status; ?></p>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"> <!-- Hidden field that prevents Cross-Site Request Forgery attacks -->

        <label for="email">Email address</label>
        <input type="email" name="email" id="email"
               value="<?php echo escape_output($current_email); ?>"
               placeholder="Enter your email"
               required>

        <button type="submit" name="submit">Send Reset Link</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
        <a href="login.php" class="forgot-link">Back to login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>