<?php
// seller/reports.php
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
$currentRole   = $_SESSION['role'] ?? 'buyer';

if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: /dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

$totalRevenue = 0;
$totalOrders  = 0;
$monthlyStats = [];

try {
    // Total revenue & orders for this seller
    $sql = "
        SELECT 
            COALESCE(SUM(subtotal),0) AS revenue,
            COUNT(DISTINCT order_id) AS orders
        FROM order_items
        WHERE seller_id = :sid
          AND status IN ('paid','completed')
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $currentUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalRevenue = (float)$row['revenue'];
        $totalOrders  = (int)$row['orders'];
    }

    // Revenue by month (last 6 months)
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS ym,
            COALESCE(SUM(subtotal),0) AS revenue,
            COUNT(DISTINCT order_id) AS orders
        FROM order_items
        WHERE seller_id = :sid
          AND status IN ('paid','completed')
        GROUP BY ym
        ORDER BY ym DESC
        LIMIT 6
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $currentUserId]);
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore for now
}

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Reports - CarHub</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;max-width:800px;}
        .stats-row{display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;}
        .stat-block{flex:1;min-width:180px;background:#020617;border-radius:0.75rem;padding:0.75rem 0.9rem;border:1px solid #111827;}
        .stat-block h4{font-size:0.85rem;color:#9ca3af;margin-bottom:0.25rem;}
        .stat-block p{font-size:1.15rem;font-weight:600;margin:0;}
        .table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        .table th,.table td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}
    </style>
</head>
<body>
<header class="topbar">
    <div>📊 CarHub · <span style="color:#a5b4fc;">Seller reports</span></div>
    <div><a href="dashboard.php">Seller dashboard</a> · <a href="../logout.php">Logout</a></div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>Seller menu</h3>
        <ul>
            <li><a href="dashboard.php">Overview</a></li>
            <li><a href="my-listings.php">My listings</a></li>
            <li><a href="add-car.php">Add new car</a></li>
            <li><a href="add-spare-part.php">Add spare part</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">Reports & analytics</h1>
        <p class="page-subtitle">See how your sales are performing over time.</p>

        <section class="card">
            <div class="stats-row">
                <div class="stat-block">
                    <h4>Total revenue (all time)</h4>
                    <p>₵<?php echo number_format($totalRevenue, 2); ?></p>
                </div>
                <div class="stat-block">
                    <h4>Total orders (all time)</h4>
                    <p><?php echo $totalOrders; ?></p>
                </div>
            </div>
        </section>

        <section class="card">
            <h3>Revenue by month (last 6 months)</h3>
            <?php if (empty($monthlyStats)): ?>
                <p>No paid/completed orders yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue (₵)</th>
                        <th>Orders</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($monthlyStats as $m): ?>
                        <tr>
                            <td><?php echo h($m['ym']); ?></td>
                            <td>₵<?php echo number_format($m['revenue'], 2); ?></td>
                            <td><?php echo (int)$m['orders']; ?></td>
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
