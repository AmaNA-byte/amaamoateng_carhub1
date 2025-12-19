<?php
session_start();

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * ✅ If already logged in, redirect based on role
 */
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'buyer';

    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'seller' || $role === 'buyer_seller') {
        header("Location: seller/dashboard.php");
    } else {
        header("Location: dashboard/index.php");
    }
    exit();
}

$errors = [];
$showResendLink = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || !isValidEmail($email)) {
        $errors[] = "Valid email is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {

                // ✅ Check if email is verified
                if (!(int)$user['is_verified']) {
                    $errors[] = "Please verify your email before logging in.";
                    $showResendLink = true;
                } else {
                    // ✅ Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];

                    if ($remember) {
                        setcookie('user_email', $email, time() + (86400 * 30), "/");
                    } else {
                        // optional: clear remember cookie if unchecked
                        setcookie('user_email', '', time() - 3600, "/");
                    }

                    // ✅ Redirect based on role (NO leading slash)
                    if ($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } elseif ($user['role'] === 'seller' || $user['role'] === 'buyer_seller') {
                        header("Location: seller/dashboard.php");
                    } else {
                        header("Location: dashboard/index.php");
                    }
                    exit();
                }

            } else {
                $errors[] = "Invalid email or password.";
            }
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CarHub</title>
    <link rel="stylesheet" href="assets/css/auth.css">

    <!-- ✅ Added background image + glass styling directly in this file -->
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(rgba(2,6,23,0.75), rgba(2,6,23,0.88)),
                url("assets/images/login-bg.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-box {
            background: rgba(15,23,42,0.82);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,0.25);
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>🚗 CarHub</h1>
            <p>Welcome back!</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($showResendLink): ?>
                    <div style="margin-top:0.6rem;">
                        <a href="resend-verification.php" class="link-small">Resend verification email</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required
                       value="<?php echo htmlspecialchars($_COOKIE['user_email'] ?? ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group-inline">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>>
                    Remember me
                </label>
                <a href="forgot-password.php" class="link-small">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>