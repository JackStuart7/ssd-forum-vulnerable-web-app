<?php

//session function
function start_session_secure() { // starts a session only if one isn't already running
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => 1, // stops javascript from reading the session cookie, prevents xss stealing sessions
            'cookie_samesite' => 'Strict' // cookie only sent to same site, stops csrf attacks
        ]);
    }
}

// CSRF token function
function make_csrf() {
    if (empty($_SESSION['csrf'])) { // if there's no token yet, make one, don't want to generate new ones every page load
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // 32 bytes of random turned into hex (64 characters)
    }
    // escape it just in case
    return htmlspecialchars($_SESSION['csrf']);
}

// function to check CSRF tokens
function check_csrf($token) { // this gets called when forms are submitted to check the token matches
    // hash_equals does a constant-time comparison so the token can't be guessed via timing
    if (!isset($_SESSION['csrf']) || !is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        // don't give too much info, just kill it, attackers don't need to know why
        die("Request failed validation");
    }
    // Token is per-session (OWASP synchronizer token pattern) rather than one-time use,
    // so other forms already on the page (e.g. the navbar search) keep working.
    // A fresh token is issued on login - see regenerate_session_on_login().
}

// called after a successful login to stop session fixation
function regenerate_session_on_login() {
    session_regenerate_id(true);  // new session ID, old one deleted
    unset($_SESSION['csrf']);     // new CSRF token for the logged-in session
}

function escape_output($data) { // wrapper function so i don't have to type ENT_QUOTES, 'UTF-8' every single time
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // converts special chars to html entities - stops xss
}

// builds the full reset link including domain and path
// TODO: replace with actual email sending later, $email is there ready for when we switch to real emails
function send_reset_email($email, $token) {
    // auto-detects if we're using https or http, gets host and path - should work on any server
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    $link = $protocol . $host . $path . "/reset_password.php?token=" . urlencode($token);
    return $link;
}

//function to create a password reset token for a user
function create_reset_request($email, $db) {

    // check rate limit first - only 3 attempts per hour
    if (!check_rate_limit($email, $db, 'reset')) {
            return "Too many reset attempts. Try again later."; 
        }
    // find the user by email
    $find = $db->prepare("SELECT userId FROM users WHERE email = ?"); // prepared statement stops sql injection
    $find->bind_param("s", $email);
    $find->execute();
    $result = $find->get_result();

    if ($result->num_rows === 0) {
        return "If the email exists you will receive a reset link"; // generic message doesn't reveal if email exists or not
    }

    $user = $result->fetch_assoc(); 

    $token  = bin2hex(random_bytes(32)); // generate 32 random bytes (256 bits) cryptographically secure
    $hash   = password_hash($token, PASSWORD_DEFAULT); // hash the token before storing, same as passwords
    $expire = date('Y-m-d H:i:s', time() + 3600); // expires after 1 hour 

    // save the hashed token to database
    $save = $db->prepare("
        INSERT INTO password_resets (userId, tokenHash, expires)
        VALUES (?, ?, ?)
    ");
    $save->bind_param("iss", $user['userId'], $hash, $expire);
    $save->execute();

    $reset_link = send_reset_email($email, $token);

    // shows link for testing, would be emailed in production
    $safe_link = escape_output($reset_link);
    return "If the email exists you will receive a reset link:<br><a href=\"$safe_link\">$safe_link</a>";
}

//Rate limit functions 
function check_rate_limit($email, $db, $type = 'reset') {
    
    if ($type == 'reset') {
        // check how many reset requests this user has made in last hour
        $check = $db->prepare("
            SELECT COUNT(*) as attempts 
            FROM password_resets 
            WHERE userId = (SELECT userId FROM users WHERE email = ?)
            AND created_at > NOW() - INTERVAL 1 HOUR
        ");
        $check->bind_param("s", $email);
    } else {
        // check login attempts for this user, only counting failures
        $find = $db->prepare("SELECT userId FROM users WHERE email = ?");
        $find->bind_param("s", $email);
        $find->execute();
        $user_result = $find->get_result();
        
        if ($user_result->num_rows === 0) {
            return true; // no user found lets it proceed 
        }
        
        $user = $user_result->fetch_assoc();
        
        $check = $db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE userId = ? 
            AND created_at > NOW() - INTERVAL 1 HOUR
            AND success = 0
        ");
        $check->bind_param("i", $user['userId']);
    }
    
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && $row['attempts'] >= 3) {
        return false; // too many attempts and blocks it
    }
    return true; // under the limit lets them try again
}

function log_login_attempt($email, $success, $db) {
    
    // get the user id first
    $find = $db->prepare("SELECT userId FROM users WHERE email = ?");
    $find->bind_param("s", $email);
    $find->execute();
    $result = $find->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // log the attempt whether it was success or fail
        $log = $db->prepare("
            INSERT INTO login_attempts (userId, success, ip_address) 
            VALUES (?, ?, ?)
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $log->bind_param("iis", $user['userId'], $success, $ip);
        $log->execute();
    }
}
function validate_password($password) {
    $errors = []; //Creates an empty array to collect any missing requirements If empty at the end the password is strong

    if (strlen($password) < 8) {
        $errors[] = "at least 8 characters"; // checks length
    }
    if (!preg_match('/[A-Z]/', $password)) {  // checks uppercase
        $errors[] = "one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) { // checks lowercase
        $errors[] = "one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) { // checks numbers
        $errors[] = "one number";
    }
    if (!preg_match('/[!@#$%^&*(),.?\":{}|<>]/', $password)) {  //checks specail characters 
        $errors[] = "one special character";
    }

    if (empty($errors)) {
        return ['valid' => true, 'message' => 'Password strong'];
    } else {
        return ['valid' => false, 'message' => 'Password needs: ' . implode(', ', $errors)];
    }
}