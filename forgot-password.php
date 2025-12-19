<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT user_id, first_name FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            $reset_token = generateToken();
            
            // Store token in database (you'll need a password_resets table)
            // For now, just send email
            $emailBody = getPasswordResetEmailTemplate($user['first_name'], $reset_token);
            sendEmail($email, "Reset Your Password - CarHub", $emailBody);
        }
        
        // Always show success to prevent email enumeration
        $success = "If that email exists, we've sent password reset instructions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CarHub</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo">
                <h1>🚗 CarHub</h1>
                <p>Reset your password</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            
            <div class="form-footer">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>