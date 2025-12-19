<?php
require_once 'auth.php'; // includes session, role check, $db, and h()

// Filters
$roleFilter = $_GET['role'] ?? '';
$search     = trim($_GET['q'] ?? '');

$sql = "SELECT 
            user_id,
            first_name,
            last_name,
            email,
            phone_number,
            role,
            is_active,
            is_verified,
            created_at
        FROM users
        WHERE 1=1";
$params = [];

if ($roleFilter !== '') {
    $sql .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

if ($search !== '') {
    $sql .= " AND (first_name LIKE :q OR last_name LIKE :q OR email LIKE :q)";
    $params[':q'] = "%{$search}%";
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Users page DB error: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users - CarHub Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{width:240px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.5rem 0.7rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}
        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.25rem;}
        .filters{display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;margin-bottom:1rem;}
        .filters label{font-size:0.8rem;color:#9ca3af;margin-bottom:0.25rem;display:block;}
        .filters input,.filters select{
            background:#020617;border:1px solid #111827;border-radius:0.5rem;
            color:#e5e7eb;padding:0.4rem 0.6rem;font-size:0.85rem;
        }
        .filters .group{display:flex;flex-direction:column;}
        .btn{padding:0.45rem 0.9rem;border-radius:999px;border:none;font-size:0.85rem;cursor:pointer;background:#111827;color:#e5e7eb;}
        .btn:hover{filter:brightness(1.05);}
        .card{background:#020617;border:1px solid #111827;border-radius:0.75rem;padding:1rem;}
        table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        th,td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}
        .badge{display:inline-block;padding:0.1rem 0.45rem;border-radius:999px;border:1px solid #374151;font-size:0.7rem;color:#9ca3af;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🛠️ CarHub Admin</div>
    <div>
        <a href="../logout.php">Logout</a>
    </div>
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
            <li><a href="parts.php">All Parts</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="reviews.php">Reviews</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">View all registered users and filter by role.</p>

        <form method="get" class="filters">
            <div class="group">
                <label>Search (name/email)</label>
                <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="e.g. ama, admin@...">
            </div>

            <div class="group">
                <label>Role</label>
                <select name="role">
                    <option value="">Any</option>
                    <?php foreach (['buyer','seller','buyer_seller','admin'] as $r): ?>
                        <option value="<?php echo $r; ?>" <?php if ($roleFilter === $r) echo 'selected'; ?>>
                            <?php echo h($r); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn" type="submit">Filter</button>
        </form>

        <section class="card">
            <?php if (empty($users)): ?>
                <p style="color:#9ca3af;">No users found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Active</th>
                            <th>Verified</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo (int)$u['user_id']; ?></td>
                            <td><?php echo h(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')); ?></td>
                            <td><?php echo h($u['email']); ?></td>
                            <td><?php echo h($u['phone_number']); ?></td>
                            <td><span class="badge"><?php echo h($u['role']); ?></span></td>
                            <td><?php echo ((int)$u['is_active'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td><?php echo ((int)$u['is_verified'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td><?php echo h($u['created_at']); ?></td>
                            <td><a href="user-views.php?id=<?php echo (int)$u['user_id']; ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>