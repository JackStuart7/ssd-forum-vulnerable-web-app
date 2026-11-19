<?php
// Include navbar which also loads database connection and starts session
include 'includes/navbar.php';

// Array to store any validation or authentication errors
$errors = [];

// Handle login form submission
if (isset($_POST['login'])) {

    // Validate CSRF token to prevent cross-site request forgery attacks
    check_csrf($_POST['csrf_token'] ?? '');

    // Collect and sanitise user input
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check rate limit by email address to prevent brute force attacks
    // If the user has exceeded the allowed number of attempts, block the login
    if (!check_rate_limit($email, $mysqli, 'login')) {

        // Log the failed attempt even when rate limited
        log_login_attempt($email, false, $mysqli);

        // Display error message to the user
        $errors[] = "Too many failed attempts. Try again later.";

    } else {

        // Query the database for the user with the provided email address
        // Uses a prepared statement to prevent SQL injection
        $stmt = $mysqli->prepare("
            SELECT userId, password
            FROM users
            WHERE email = ?
        ");

        // Bind the email parameter as a string and execute the query
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Fetch the result as an associative array
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify the submitted password against the stored bcrypt hash
        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {

            // Login successful - log the successful attempt
            log_login_attempt($email, true, $mysqli);

            // Give the user a new session ID to prevent session fixation
            regenerate_session_on_login();

            // Store the authenticated user's ID in the session
            $_SESSION['user_id'] = $user['userId'];

            // Redirect the user to the dashboard
            header("Location: dashboard.php");
            exit;

        } else {

            // Login failed - log the failed attempt for rate limiting tracking
            log_login_attempt($email, false, $mysqli);

            // Display a generic error message - avoid specifying whether
            // email or password was wrong to prevent user enumeration
            $errors[] = "Credentials are incorrect.";
        }
    }
}

// Generate a fresh CSRF token for the login form
$csrf_token = make_csrf();
?>

<h2>Login</h2>

<?php if (!empty($errors)): ?>
    <!-- Display any validation or authentication errors -->
    <div class="card" style="background:#ffdddd; border-left:4px solid red;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <!-- Escape output to prevent XSS attacks -->
                <li><?php echo escape_output($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Login form -->
<div class="card">
<form action="login.php" method="POST">

    <!-- Hidden CSRF token to protect against cross-site request forgery -->
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <!-- Email input field - repopulates on failed login -->
    <label for="email">Email</label> 
    <input type="email" name="email" placeholder="Email"
           value="<?php echo isset($email) ? escape_output($email) : ''; ?>" required>

    <!-- Password input field -->
    <label for="password">Password</label>            
    <input type="password" name="password" placeholder="Password" required>

    <!-- Submit button -->
    <button type="submit" name="login">Login</button>

    <!-- Link to password reset page -->
    <a href="forgot_password.php" class="forgot-link">Forgot your password?</a>
</form>
</div>

<?php include 'includes/footer.php'; ?>