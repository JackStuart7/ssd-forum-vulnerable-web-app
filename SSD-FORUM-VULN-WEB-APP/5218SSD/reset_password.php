<?php
include 'includes/navbar.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

// Fetch all valid, unused tokens
$get_tokens = $mysqli->prepare("
    SELECT pr.id, pr.userId, pr.tokenHash, u.email
    FROM password_resets pr
    JOIN users u ON pr.userId = u.userId
    WHERE pr.expires > NOW() AND pr.used = 0
");
$get_tokens->execute();
$result = $get_tokens->get_result();
$resets = $result->fetch_all(MYSQLI_ASSOC);

$valid = null;
// loop through each valid token and see if the one from the URL matches any of them
foreach ($resets as $r) {
    if (password_verify($token, $r['tokenHash'])) {
        $valid = $r;
        break;
    }
}

if (!$valid) {
    $error = "This reset link is invalid or expired"; // either token doesn't exist, expired, or already used
}

// if form was submitted and we have a valid token, process the password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid) {
    check_csrf($_POST['csrf_token'] ?? ''); // check the hidden token matches, stops csrf attacks

    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    // basic checks, do they match and are they strong enough
    if ($new_pass !== $confirm) {
        $error = "Passwords don't match";
    } else {
        $validation = validate_password($new_pass); // check password strength
        if (!$validation['valid']) {
            $error = $validation['message']; // show them what's missing
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT); // hash the new password 
            // update the users table with the new password hash
            $update = $mysqli->prepare("
                UPDATE users
                SET password = ?
                WHERE userId = ?
            ");
            $update->bind_param("si", $hash, $valid['userId']);
            $update->execute();

            $used = $mysqli->prepare("
                UPDATE password_resets
                SET used = 1
                WHERE id = ?
            ");
            $used->bind_param("i", $valid['id']);
            $used->execute();

            $success = true;
        }
    }
}

// csrf token for form
$csrf_token = make_csrf();
?>

<h2>Create New Password</h2>

<?php if ($error): ?>
    <div class="card" style="background:#ffdddd; border-left:4px solid red;">
        <p><?php echo escape_output($error); ?></p>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="card" style="background:#ddffdd; border-left:4px solid green;">
        <p>Password updated! You can now <a href="login.php">login</a></p>
    </div>
<?php elseif ($valid): ?> <!-- Shows Form Only for Valid Token If token invalid/expired, user sees error message instead -->
    <div class="card">
        <p style="margin-bottom:20px;">Resetting password for: <strong><?php echo escape_output($valid['email']); ?></strong></p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">  <!-- Hidden field that prevents cross-site request forgery attacks -->

            <label for="new_password">New password</label>
            <input type="password" name="new_password" id="new_password" required>

            <div id="password-strength" class="strength-meter">
                <div id="strength-bar"></div>
            </div>
            <p id="strength-text"></p>

            <label for="confirm_password">Confirm password</label>
            <input type="password" name="confirm_password" id="confirm_password" required> <!-- required browser prevents submission if empty --> 

            <button type="submit" name="submit">Update Password</button>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <p>Invalid reset link. <a href="forgot_password.php">Request a new one</a></p>
    </div>
<?php endif; ?>

<!-- password meter JS used for strength bar -->
<script>
const passwordInput = document.getElementById("new_password");
const strengthBar = document.getElementById("strength-bar");
const strengthText = document.getElementById("strength-text");

if (passwordInput) {
    passwordInput.addEventListener("input", function () {
        const password = passwordInput.value;
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthBar.style.width = "20%";
                strengthBar.style.background = "red";
                strengthText.textContent = "Very Weak";
                strengthText.style.color = "red";
                break;
            case 2:
                strengthBar.style.width = "40%";
                strengthBar.style.background = "orange";
                strengthText.textContent = "Weak";
                strengthText.style.color = "orange";
                break;
            case 3:
                strengthBar.style.width = "60%";
                strengthBar.style.background = "gold";
                strengthText.textContent = "Medium";
                strengthText.style.color = "gold";
                break;
            case 4:
                strengthBar.style.width = "80%";
                strengthBar.style.background = "blue";
                strengthText.textContent = "Strong";
                strengthText.style.color = "blue";
                break;
            case 5:
                strengthBar.style.width = "100%";
                strengthBar.style.background = "green";
                strengthText.textContent = "Very Strong";
                strengthText.style.color = "green";
                break;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>