<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: dashboard/index.php");
    exit();
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $email = clean($_POST['email']);
    $phone_number = clean($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = clean($_POST['role']);

    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !isValidEmail($email)) $errors[] = "Valid email is required";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (!in_array($role, ['buyer', 'seller', 'buyer_seller'])) $errors[] = "Invalid role";

    // Check if email exists
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            if (!$db) {
                $errors[] = "Database connection failed";
            } else {
                $query = "SELECT user_id FROM users WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $errors[] = "Email already registered";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    // Register user
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db->beginTransaction();

            // Insert user
            $query = "INSERT INTO users
                      (email, password_hash, first_name, last_name, phone_number, `role`, is_active, is_verified)
                      VALUES
                      (:email, :password_hash, :first_name, :last_name, :phone_number, :role, 1, 0)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':role', $role);

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert user");
            }

            $user_id = $db->lastInsertId();

            // Generate 6-digit code
            $verification_code = generateVerificationCode();
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Store code
            $query = "INSERT INTO email_verifications (user_id, code, expires_at)
                      VALUES (:user_id, :code, :expires_at)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':code', $verification_code);
            $stmt->bindParam(':expires_at', $expires_at);

            if (!$stmt->execute()) {
                throw new Exception("Failed to store verification code");
            }

            $db->commit();

            // Send email
            $emailBody = getVerificationEmailTemplate($first_name, $verification_code);
            $emailSent = sendEmail($email, "Verify Your CarHub Account", $emailBody);

            if (!$emailSent) {
                // Still successful, but warn about email
                $_SESSION['verify_email'] = $email;
                $_SESSION['verification_warning'] = "Account created but email failed to send. Contact support for your verification code.";
                header("Location: verify-email.php");
                exit();
            }

            // Redirect to verification page
            $_SESSION['verify_email'] = $email;
            header("Location: verify-email.php");
            exit();

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarHub</title>
    <link rel="stylesheet" href="assets/css/auth.css">

    <!-- Match Login page look: same background + glass card -->
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
            width: 100%;
            max-width: 520px;
        }

        /* Make long register form feel cleaner */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 560px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>🚗 CarHub</h1>
            <p>Create your account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required value="<?php echo h($_POST['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required value="<?php echo h($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" value="<?php echo h($_POST['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>I want to *</label>
                    <select name="role" required>
                        <option value="">Select an option</option>
                        <option value="buyer" <?php echo (($_POST['role'] ?? '') === 'buyer') ? 'selected' : ''; ?>>Buy cars & parts</option>
                        <option value="seller" <?php echo (($_POST['role'] ?? '') === 'seller') ? 'selected' : ''; ?>>Sell cars & parts</option>
                        <option value="buyer_seller" <?php echo (($_POST['role'] ?? '') === 'buyer_seller') ? 'selected' : ''; ?>>Both buy and sell</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" id="password" name="password" required minlength="8">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText"></small>
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>