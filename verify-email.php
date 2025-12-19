<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Check if email is set in session (from registration)
if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];
$errors = [];
$success = false;

// Check if there's a warning from registration
$warning = isset($_SESSION['verification_warning']) ? $_SESSION['verification_warning'] : '';
unset($_SESSION['verification_warning']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = clean($_POST['code']);

    if (empty($code)) {
        $errors[] = "Verification code is required";
    } else if (strlen($code) !== 6 || !ctype_digit($code)) {
        $errors[] = "Invalid code format. Must be 6 digits";
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Find user by email
            $query = "SELECT user_id, first_name FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check verification code
                $query = "SELECT * FROM email_verifications
                          WHERE user_id = :user_id
                          AND code = :code
                          AND expires_at > NOW()
                          AND is_used = 0";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user['user_id']);
                $stmt->bindParam(':code', $code);
                $stmt->execute();

                if ($stmt->rowCount() === 1) {
                    try {
                        $db->beginTransaction();

                        // Mark user as verified
                        $query = "UPDATE users SET is_verified = 1 WHERE user_id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $user['user_id']);
                        $stmt->execute();

                        // Mark code as used
                        $query = "UPDATE email_verifications SET is_used = 1 WHERE user_id = :user_id AND code = :code";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $user['user_id']);
                        $stmt->bindParam(':code', $code);
                        $stmt->execute();

                        $db->commit();

                        // Clear session
                        unset($_SESSION['verify_email']);

                        $success = true;

                    } catch (Exception $e) {
                        $db->rollBack();
                        $errors[] = "Verification failed: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "Invalid or expired code. Please check your email or request a new code.";
                }
            } else {
                $errors[] = "User not found";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
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
    <title>Verify Email - CarHub</title>
    <link rel="stylesheet" href="assets/css/auth.css">

    <!-- Match Login/Register look: same background + glass card -->
    <style>
        body{
            min-height:100vh;
            margin:0;
            background:
                linear-gradient(rgba(2,6,23,0.75), rgba(2,6,23,0.88)),
                url("assets/images/login-bg.jpg");
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
        }

        .auth-container{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
        }

        .auth-box{
            width:100%;
            max-width:520px;
            background: rgba(15,23,42,0.82);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,0.25);
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            padding: 28px 26px 24px;
        }

        .logo{
            text-align:center;
            margin-bottom:14px;
        }

        .logo h1{
            margin:0;
        }

        .logo p{
            margin:6px 0 0;
            color:#9ca3af;
            font-size:14px;
        }

        .verify-info{
            background: rgba(2,6,23,0.45);
            border: 1px solid rgba(148,163,184,0.18);
            border-radius: 12px;
            padding: 12px 12px;
            margin: 14px 0 18px;
            color:#cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }

        .email-display{
            margin-top:10px;
            background: rgba(15,23,42,0.55);
            border: 1px solid rgba(148,163,184,0.18);
            padding: 10px;
            border-radius: 10px;
            text-align:center;
            font-weight: 700;
            color:#c7d2fe;
            word-break: break-all;
        }

        .code-input-container{
            display:flex;
            justify-content:center;
            gap:10px;
            margin: 18px 0 14px;
            flex-wrap: wrap;
        }

        .code-input{
            width:56px;
            height:64px;
            font-size: 28px;
            font-weight: 800;
            text-align:center;
            border-radius: 12px;
            border: 1px solid rgba(148,163,184,0.25);
            background: rgba(2,6,23,0.35);
            color:#e5e7eb;
            outline:none;
            transition: border-color .2s, box-shadow .2s;
        }

        .code-input:focus{
            border-color: rgba(109,124,255,.75);
            box-shadow: 0 0 0 4px rgba(109,124,255,.18);
        }

        .btn-wide{
            width:100%;
            padding:12px 14px;
            border-radius:10px;
            border:0;
            background: linear-gradient(90deg, #6d7cff, #8b5cf6);
            color:white;
            font-weight:700;
            cursor:pointer;
        }
        .btn-wide:hover{ filter: brightness(1.05); }

        .resend-link{
            text-align:center;
            margin-top: 14px;
            color:#9ca3af;
            font-size: 14px;
        }
        .resend-link a{
            color:#c7d2fe;
            text-decoration:none;
            font-weight:700;
        }
        .resend-link a:hover{ text-decoration: underline; }

        .success-icon{
            width:72px;
            height:72px;
            border-radius: 18px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin: 6px auto 14px;
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.25);
            color: #86efac;
            font-size: 34px;
            font-weight: 900;
        }

        .message{
            text-align:center;
            color:#e5e7eb;
            font-size:16px;
            margin: 8px 0 14px;
        }

        /* If your auth.css already styles these, this just keeps it consistent */
        .alert{
            margin: 12px 0;
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>🚗 CarHub</h1>
            <p>Email Verification</p>
        </div>

        <?php if ($success): ?>
            <div class="success-icon">✓</div>
            <div class="message">Email verified successfully!</div>
            <a href="login.php" class="btn btn-primary" style="display:block; text-align:center; text-decoration:none;">Go to Login</a>

        <?php else: ?>

            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning">
                    <?php echo h($warning); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="verify-info">
                📧 We’ve sent a 6-digit verification code to:
                <div class="email-display"><?php echo h($email); ?></div>
                <div style="margin-top:10px;color:#9ca3af;">Enter the code below to verify your email.</div>
            </div>

            <form method="POST" id="verifyForm">
                <div class="code-input-container">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="one-time-code" inputmode="numeric">
                </div>

                <input type="hidden" name="code" id="fullCode">
                <button type="submit" class="btn-wide">Verify Email</button>
            </form>

            <div class="resend-link">
                Didn’t receive the code? <a href="resend-verification.php">Resend</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const inputs = document.querySelectorAll('.code-input');
    const form = document.getElementById('verifyForm');
    const fullCodeInput = document.getElementById('fullCode');

    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateFullCode();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6);
            if (/^\d+$/.test(pastedData)) {
                pastedData.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                updateFullCode();
                inputs[Math.min(pastedData.length, inputs.length - 1)].focus();
            }
        });
    });

    function updateFullCode() {
        fullCodeInput.value = Array.from(inputs).map(i => i.value).join('');
    }

    form.addEventListener('submit', function(e) {
        updateFullCode();
        if (fullCodeInput.value.length !== 6) {
            e.preventDefault();
            alert('Please enter all 6 digits');
        }
    });

    inputs[0].focus();
</script>
</body>
</html>