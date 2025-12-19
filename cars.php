<?php
require_once 'auth.php'; // admin-only + $db + h()

$statusFilter   = $_GET['status'] ?? '';
$approvalFilter = $_GET['approval'] ?? '';
$search         = trim($_GET['q'] ?? '');

$sql = "SELECT
            car_id,
            brand,
            model,
            year,
            price,
            status,
            approval_status,
            seller_id,
            created_at
        FROM cars
        WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($approvalFilter !== '') {
    $sql .= " AND approval_status = :appr";
    $params[':appr'] = $approvalFilter;
}

if ($search !== '') {
    $sql .= " AND (brand LIKE :q OR model LIKE :q)";
    $params[':q'] = "%{$search}%";
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("All cars DB error: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Cars - CarHub Admin</title>
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
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}

        .filters{margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;}
        .filters input,.filters select{
            background:#020617;border:1px solid #111827;border-radius:0.5rem;
            color:#e5e7eb;padding:0.35rem 0.6rem;font-size:0.85rem;
        }
        .filters label{font-size:0.8rem;color:#9ca3af;margin-bottom:0.2rem;}
        .filters-group{display:flex;flex-direction:column;}

        .btn{padding:0.45rem 0.9rem;border-radius:999px;border:none;font-size:0.85rem;cursor:pointer;background:#111827;color:#e5e7eb;}
        .btn:hover{filter:brightness(1.05);}

        .card{background:#020617;border-radius:0.75rem;padding:1rem;border:1px solid #111827;}
        table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        th,td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}

        .badge{display:inline-block;padding:0.1rem 0.45rem;border-radius:999px;border:1px solid #374151;font-size:0.7rem;color:#9ca3af;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🛠️ CarHub Admin</div>
    <div>
        <a href="dashboard.php">Dashboard</a> ·
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
        <h1 class="page-title">All cars</h1>
        <p class="page-subtitle">Search, filter and inspect every car listing.</p>

        <form method="get" class="filters">
            <div class="filters-group">
                <label>Search (brand/model)</label>
                <input type="text" name="q" value="<?php echo h($search); ?>">
            </div>

            <div class="filters-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Any</option>
                    <?php foreach (['available','pending','sold','removed'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php if ($statusFilter === $st) echo 'selected'; ?>>
                            <?php echo ucfirst($st); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filters-group">
                <label>Approval</label>
                <select name="approval">
                    <option value="">Any</option>
                    <?php foreach (['pending','approved','rejected'] as $ap): ?>
                        <option value="<?php echo $ap; ?>" <?php if ($approvalFilter === $ap) echo 'selected'; ?>>
                            <?php echo ucfirst($ap); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn" type="submit">Filter</button>
        </form>

        <section class="card">
            <?php if (empty($cars)): ?>
                <p style="color:#9ca3af;">No cars found.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Car</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Seller ID</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cars as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['car_id']; ?></td>
                            <td><?php echo h($c['brand'].' '.$c['model']); ?></td>
                            <td><?php echo h($c['year']); ?></td>
                            <td>₵<?php echo number_format((float)$c['price'], 2); ?></td>
                            <td><span class="badge"><?php echo h($c['status']); ?></span></td>
                            <td><span class="badge"><?php echo h($c['approval_status']); ?></span></td>
                            <td><?php echo (int)$c['seller_id']; ?></td>
                            <td><?php echo h($c['created_at']); ?></td>
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