<?php session_start(); if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Cosmic Sound</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="auth-logo-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
                <h1>Cosmic Sound</h1>
                <p>Create your account</p>
            </div>
            <div id="alert"></div>
            <form onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-input" placeholder="Your name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-input" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" class="form-input" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" class="form-input" placeholder="Repeat your password" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            <p class="auth-link">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
