<?php
// Include navbar which also loads database connection and starts session
include 'includes/navbar.php';

// Arrays to store validation errors and success messages
$errors = [];
$success = "";

// Handle signup form submission
if (isset($_POST['submit'])) {

    // Validate CSRF token to prevent cross-site request forgery attacks
    check_csrf($_POST['csrf_token'] ?? '');

    // Collect and sanitise user input
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $dob      = $_POST['dob'];
    $password = $_POST['password'];

    // VALIDATION: Check if username already exists in the database
    // Uses a prepared statement to prevent SQL injection
    $checkUser = $mysqli->prepare("SELECT userId FROM users WHERE userName = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows > 0) {
        $errors[] = "Username already exists.";
    }

    // VALIDATION: Check if email already exists in the database
    // Uses a prepared statement to prevent SQL injection
    $checkEmail = $mysqli->prepare("SELECT userId FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $errors[] = "Email already exists.";
    }

    // VALIDATION: Date of birth checks
    // Get todays date for comparison
    $today = date('Y-m-d');

    // Prevent future dates being entered as date of birth
    if ($dob > $today) {
        $errors[] = "Date of birth cannot be in the future.";
    }

    // Enforce minimum age requirement of 13 years old
    $minAgeDate = date('Y-m-d', strtotime('-13 years'));
    if ($dob > $minAgeDate) {
        $errors[] = "You must be at least 13 years old to create an account.";
    }

    // VALIDATION: Password strength checks
    // Check for required character types using regex
    $uppercase = preg_match('@[A-Z]@', $password); // At least one uppercase letter
    $lowercase = preg_match('@[a-z]@', $password); // At least one lowercase letter
    $number    = preg_match('@[0-9]@', $password); // At least one number
    $special   = preg_match('@[^\w]@', $password); // At least one special character

    // Enforce all password requirements including minimum length of 8 characters
    if (!$uppercase || !$lowercase || !$number || !$special || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long and include an upper and lowercase character, number, and special symbol.";
    }

    // Only create account if all validation checks pass
    if (empty($errors)) {

        // Securely hash the password using bcrypt before storing
        // Ensures passwords are never stored in plain text
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database using a prepared statement
        $stmt = $mysqli->prepare("
            INSERT INTO users (userName, email, dob, password)
            VALUES (?, ?, ?, ?)
        ");

        // Bind all parameters as strings and execute the insert
        $stmt->bind_param("ssss", $username, $email, $dob, $hashedPassword);
        $ok = $stmt->execute();

        // Check if insert was successful and set appropriate message
        if ($ok) {
            $success = "Account created successfully!";
        } else {
            $errors[] = "Error creating account.";
        }
    }
}

// Generate a fresh CSRF token for the signup form
$csrf_token = make_csrf();
?>

<h2>Create Account</h2>

<?php if (!empty($errors)): ?>
    <!-- Display validation errors in a red card -->
    <div class="card" style="background:#ffdddd; border-left:4px solid red;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <!-- Escape output to prevent XSS attacks -->
                <li><?php echo escape_output($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <!-- Display success message in a green card with link to login -->
    <div class="card" style="background:#ddffdd; border-left:4px solid green;">
        <p><?php echo escape_output($success); ?></p>
        <a class="btn" href="login.php">Go to Login</a>
    </div>
<?php endif; ?>

<!-- Signup form -->
<div class="card">
<form action="signup.php" method="POST">

    <!-- Hidden CSRF token to protect against cross-site request forgery -->
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <!-- Username input field - repopulates on failed submission -->
    <label for="username">Username</label>
    <input type="text" name="username" id="username" 
           value="<?php echo isset($username) ? escape_output($username) : ''; ?>" required>

    <!-- Email input field - repopulates on failed submission -->
    <label for="email">Email</label>
    <input type="email" name="email" id="email"
           value="<?php echo isset($email) ? escape_output($email) : ''; ?>" required>

    <!-- Date of birth input field - repopulates on failed submission -->
    <label for="dob">Date of Birth</label>
    <input type="date" name="dob" id="dob"
           value="<?php echo isset($dob) ? escape_output($dob) : ''; ?>" required>

    <!-- Password input field -->
    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <!-- Password strength meter - updated dynamically via JavaScript below -->
    <div id="password-strength" class="strength-meter">
        <div id="strength-bar"></div>
    </div>

    <p id="strength-text"></p>

    <!-- Submit button -->
    <button type="submit" name="submit">Create Account</button>
</form>
</div>

<script>
// Password strength meter - dynamically updates as user types
const passwordInput = document.getElementById("password");
const strengthBar = document.getElementById("strength-bar");
const strengthText = document.getElementById("strength-text");

// Listen for input changes on the password field
passwordInput.addEventListener("input", function () {
    const password = passwordInput.value;
    let strength = 0;

    // Score the password based on character variety and length
    if (password.length >= 8) strength++;       // Minimum length
    if (/[A-Z]/.test(password)) strength++;     // Uppercase letter
    if (/[a-z]/.test(password)) strength++;     // Lowercase letter
    if (/[0-9]/.test(password)) strength++;     // Number
    if (/[^A-Za-z0-9]/.test(password)) strength++; // Special character

    // Update the strength bar width, colour and label based on score
    switch (strength) {
        case 0:
        case 1:
            // Very weak - red
            strengthBar.style.width = "20%";
            strengthBar.style.background = "red";
            strengthText.textContent = "Very Weak";
            strengthText.style.color = "red";
            break;
        case 2:
            // Weak - orange
            strengthBar.style.width = "40%";
            strengthBar.style.background = "orange";
            strengthText.textContent = "Weak";
            strengthText.style.color = "orange";
            break;
        case 3:
            // Medium - gold
            strengthBar.style.width = "60%";
            strengthBar.style.background = "gold";
            strengthText.textContent = "Medium";
            strengthText.style.color = "gold";
            break;
        case 4:
            // Strong - blue
            strengthBar.style.width = "80%";
            strengthBar.style.background = "blue";
            strengthText.textContent = "Strong";
            strengthText.style.color = "blue";
            break;
        case 5:
            // Very strong - green
            strengthBar.style.width = "100%";
            strengthBar.style.background = "green";
            strengthText.textContent = "Very Strong";
            strengthText.style.color = "green";
            break;
    }
});
</script>

<?php include 'includes/footer.php'; ?>