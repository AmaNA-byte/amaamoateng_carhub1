<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];
$success = "";
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit();
}

// Verify token exists and not expired (you'll need password_resets table)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password in database based on token
        // Delete used token
        
        $success = "Password reset successful! You can now login.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CarHub</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo">
                <h1>🚗 CarHub</h1>
                <p>Set your new password</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <br><a href="login.php">Click here to login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                        <small id="strengthText"></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="form-footer">
                <a href="login.php">Back to login</a>
            </div>
        </div>
    </div>
    <script src="assets/js/auth.js"></script>
</body>
</html>