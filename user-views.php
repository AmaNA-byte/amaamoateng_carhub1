<?php
require_once 'auth.php';
requireAdmin();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    die('Invalid user ID');
}

$success = '';
$errors  = [];

// handle updates (role, active)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newRole   = $_POST['role'] ?? '';
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($newRole === '') {
        $errors[] = 'Role is required.';
    } else {
        try {
            $sql = "UPDATE users SET role = :role, is_active = :active, updated_at = NOW()
                    WHERE user_id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':role'   => $newRole,
                ':active' => $isActive,
                ':id'     => $userId,
            ]);
            $success = 'User updated successfully.';
        } catch (Exception $e) {
            $errors[] = 'Database error: '.$e->getMessage();
        }
    }
}

// load user
$sql = "SELECT user_id, first_name, last_name, email, phone_number, role, is_active, is_verified, created_at, updated_at
        FROM users
        WHERE user_id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User #<?php echo (int)$user['user_id']; ?> - Admin</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .logo{font-weight:700;font-size:1.1rem;}
        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{width:240px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.5rem 0.7rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}
        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem;border:1px solid #111827;margin-bottom:1rem;max-width:640px;}
        .form-group{margin-bottom:0.85rem;}
        .form-group label{display:block;font-size:0.85rem;margin-bottom:0.25rem;}
        .form-group input,.form-group select{
            width:100%;padding:0.45rem 0.6rem;border-radius:0.5rem;border:1px solid #111827;background:#020617;color:#e5e7eb;
        }
        .alert{padding:0.45rem 0.7rem;border-radius:0.5rem;font-size:0.82rem;margin-bottom:0.75rem;}
        .alert-error{background:#7f1d1d;color:#fee2e2;}
        .alert-success{background:#14532d;color:#bbf7d0;}
        .btn{padding:0.5rem 1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
    </style>
</head>
<body>
<header class="topbar">
    <div class="logo">🛠️ CarHub Admin</div>
    <div><a href="users.php">← Back to users</a></div>
</header>
<div class="layout">
    <aside class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="cars-pending.php">Pending Cars</a></li>
            <li><a href="parts-pending.php">Pending Parts</a></li>
            <li><a href="cars.php">All Cars</a></li>
            <li><a href="parts.php">All Spare Parts</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="reviews.php">Reviews</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </aside>
    <main class="main">
        <h1 class="page-title">User #<?php echo (int)$user['user_id']; ?></h1>
        <p class="page-subtitle"><?php echo h($user['first_name'].' '.$user['last_name']); ?></p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e) echo h($e)."<br>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Account details</h2>
            <p><strong>Email:</strong> <?php echo h($user['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo h($user['phone_number']); ?></p>
            <p><strong>Created:</strong> <?php echo h($user['created_at']); ?></p>
            <p><strong>Last updated:</strong> <?php echo h($user['updated_at']); ?></p>
        </section>

        <section class="card">
            <h2>Admin controls</h2>
            <form method="post">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <?php foreach (['buyer','seller','buyer_seller','admin'] as $r): ?>
                            <option value="<?php echo $r; ?>" <?php if ($user['role'] === $r) echo 'selected'; ?>>
                                <?php echo ucfirst(str_replace('_',' / ',$r)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?php if ($user['is_active']) echo 'checked'; ?>>
                        Account is active
                    </label>
                </div>
                <button class="btn" type="submit">Save changes</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>