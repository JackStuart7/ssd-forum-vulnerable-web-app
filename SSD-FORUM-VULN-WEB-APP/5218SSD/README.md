# SSD Forum: Deliberately Vulnerable Web App

> ⚠️ **Warning:** This application contains **intentional security vulnerabilities** for training and demonstration. Run it **only on your local machine** (e.g. XAMPP on localhost). Never deploy it to a public server.

A small PHP/MySQL discussion forum built as a team for the Secure Software Development module at University. Each team member owned one feature and its matching vulnerability class, either demonstrating the attack, the defence, or both.

## Features and security topics

| Feature | Security topic | State in this version |
|---|---|---|
| Login, signup, rate limiting | Brute force, password storage, session security | ✅ Secured: bcrypt hashing, 3-failure lockout per hour, session ID regenerated on login, generic error messages |
| Password reset | CSRF, token security | ✅ Secured: CSRF tokens, random 256-bit reset tokens stored hashed, 1-hour expiry, single use |
| Comments | Cross-site scripting (XSS) | ✅ Secured: all output escaped with `htmlspecialchars` |
| Search bar | SQL injection | ❌ **Vulnerable:** keyword concatenated into the query (`search.php`) |
| Post image upload | Unrestricted file upload | ❌ **Vulnerable:** any file type accepted, including `.php` (`post.php`) |

Each vulnerable spot is marked `INTENTIONALLY VULNERABLE` in the code, with the secure version alongside it.

### Example attacks (local only)

**SQL injection:** log in, then search for:
```
zzz' UNION SELECT 1,userName,password,email FROM users-- -
```
Usernames and password hashes appear in the results. **Fix:** a prepared statement with the keyword bound as a parameter.

**Malicious file upload:** create a post and attach a file called `test.php` containing `<?php echo "code ran"; ?>`, then visit `assets/posts/test.php`. The server executes it. **Fix:** allow-list extensions, check the real MIME type with `finfo`, and give uploads random filenames (see the commented secure block in `post.php`).

### Other defences in place
- Prepared statements everywhere except the deliberate search flaw
- CSRF tokens on every state-changing form (constant-time comparison)
- Session cookies set `HttpOnly` and `SameSite=Strict`
- Security headers: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, Referrer-Policy
- Protected pages send no-cache headers and redirect when logged out

## Setup (XAMPP)

1. Copy the `5218SSD` folder into `xampp/htdocs/`.
2. Copy `includes/config.example.php` to `includes/config.php` and change the password or port if your MySQL setup differs from the XAMPP defaults.
3. Start Apache and MySQL in the XAMPP control panel.
4. Open http://localhost/phpmyadmin, go to the **SQL** tab, and run the contents of `sql.sql`.
5. *(Optional)* Run `test_data.sql` the same way for demo posts and a demo login: `demo@example.com` / `Demo123!`
6. Visit http://localhost/5218SSD/index.php

## Tech stack
PHP 8, MySQL/MariaDB (mysqli), HTML, CSS, JavaScript

## Team
Built as a five-person team project. Names are omitted for privacy.

| Feature | Owner |
|---|---|
| Login, signup, rate limiting | **Jack Stuart** (me) |
| Password reset / CSRF | Team member 1 |
| Search / SQL injection | Team member 2 |
| Comments / XSS | Team member 3 |
| File uploads | Team member 4 |
