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
        
        // Check if user exists and is not verified
        $query = "SELECT user_id, first_name, is_verified 
                  FROM users 
                  WHERE email = :email AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            if ($user['is_verified']) {
                $errors[] = "This email is already verified. You can login.";
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Delete old tokens for this user
                    $query = "DELETE FROM email_verifications WHERE user_id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user['user_id']);
                    $stmt->execute();
                    
                    // Generate new token
                    $verification_token = generateToken();
                    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    // Store new token
                    $query = "INSERT INTO email_verifications (user_id, token, expires_at) 
                              VALUES (:user_id, :token, :expires_at)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user['user_id']);
                    $stmt->bindParam(':token', $verification_token);
                    $stmt->bindParam(':expires_at', $expires_at);
                    $stmt->execute();
                    
                    $db->commit();
                    
                    // Send email
                    $emailBody = getVerificationEmailTemplate($user['first_name'], $verification_token);
                    sendEmail($email, "Verify Your CarHub Account", $emailBody);
                    
                    $success = "Verification email sent! Please check your inbox.";
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Failed to send verification email. Please try again.";
                }
            }
        } else {
            // Don't reveal if email exists or not (security)
            $success = "If that email exists and is not verified, we've sent a verification link.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - CarHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="logo">
            <h1>🚗 CarHub</h1>
            <p>Resend Verification Email</p>
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
            
            <button type="submit" class="btn">Resend Verification Email</button>
        </form>
        
        <div class="form-footer">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>