<?php
// dashboard/profile.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$db = (new Database())->getConnection();

$errors  = [];
$success = '';

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');

    if ($first_name === '') {
        $errors[] = "First name is required.";
    }

    if (empty($errors)) {
        try {
            $sql  = "UPDATE users SET first_name = :fn, last_name = :ln WHERE user_id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':fn' => $first_name,
                ':ln' => $last_name,
                ':id' => $currentUserId,
            ]);
            $success = "Profile updated successfully.";
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || strlen($newPassword) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match.";
    } else {
        try {
            $sql  = "SELECT password_hash FROM users WHERE user_id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $currentUserId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $sql  = "UPDATE users SET password_hash = :ph WHERE user_id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':ph' => $newHash,
                    ':id' => $currentUserId,
                ]);
                $success = "Password changed successfully.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Reload user data
$user = null;
try {
    $sql = "SELECT first_name, last_name, email, role, created_at
            FROM users
            WHERE user_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - CarHub</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{width:220px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.4rem 0.6rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}
        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.3rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;max-width:640px;}
        .form-group{margin-bottom:0.85rem;}
        .form-group label{display:block;font-size:0.85rem;margin-bottom:0.25rem;}
        .form-group input{
            width:100%;padding:0.45rem 0.6rem;border-radius:0.5rem;border:1px solid #111827;background:#020617;color:#e5e7eb;font-size:0.85rem;
        }
        .btn{padding:0.5rem 1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#f9fafb;}
        .btn-secondary{background:#111827;color:#e5e7eb;}
        .btn:hover{filter:brightness(1.05);}
        .alert{padding:0.45rem 0.7rem;border-radius:0.5rem;font-size:0.82rem;margin-bottom:0.75rem;}
        .alert-error{background:#7f1d1d;color:#fee2e2;}
        .alert-success{background:#14532d;color:#bbf7d0;}
    </style>
</head>
<body>
<header class="topbar">
    <div>👤 CarHub · <span style="color:#a5b4fc;">My profile</span></div>
    <div><a href="index.php">Dashboard</a> · <a href="/logout.php">Logout</a></div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>User menu</h3>
        <ul>
            <li><a href="index.php">Overview</a></li>
            <li><a href="profile.php">My profile</a></li>
            <li><a href="orders.php">My orders</a></li>
            <li><a href="reviews.php">My reviews</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">My profile</h1>
        <p class="page-subtitle">Update your details and change your password.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e) echo h($e)."<br>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo h($success); ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Profile information</h2>
            <form method="post">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>First name</label>
                    <input type="text" name="first_name" value="<?php echo h($user['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last name</label>
                    <input type="text" name="last_name" value="<?php echo h($user['last_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email (read-only)</label>
                    <input type="email" value="<?php echo h($user['email'] ?? ''); ?>" disabled>
                </div>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </section>

        <section class="card">
            <h2>Change password</h2>
            <form method="post">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label>Current password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm new password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-secondary">Update password</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>
