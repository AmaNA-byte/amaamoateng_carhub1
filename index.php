<?php
// dashboard/index.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ FIX: relative redirect (project is /car_hub)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$currentRole   = $_SESSION['role'];

$database = new Database();
$db = $database->getConnection();

function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// Load user info
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

// Stats
$totalOrders     = 0;
$completedOrders = 0;
$activeOrders    = 0;
$totalSpent      = 0.0;
$recentOrders    = [];

try {
    // total orders
    $sql = "SELECT COUNT(*) FROM orders WHERE buyer_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $totalOrders = (int) $stmt->fetchColumn();

    // delivered
    $sql = "SELECT COUNT(*) 
            FROM orders 
            WHERE buyer_id = :id 
              AND order_status = 'delivered'";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $completedOrders = (int) $stmt->fetchColumn();

    // active
    $sql = "SELECT COUNT(*) 
            FROM orders 
            WHERE buyer_id = :id 
              AND order_status IN ('pending','confirmed','processing','shipped')";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $activeOrders = (int) $stmt->fetchColumn();

    // total spent
    $sql = "SELECT COALESCE(SUM(total_amount), 0) 
            FROM orders 
            WHERE buyer_id = :id 
              AND order_status NOT IN ('cancelled','refunded')";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $totalSpent = (float) $stmt->fetchColumn();

    // recent orders
    $sql = "SELECT order_id, total_amount, order_status, created_at
            FROM orders
            WHERE buyer_id = :id
            ORDER BY created_at DESC
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;}
        .stat-card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem;border:1px solid #111827;}
        .stat-card h4{font-size:0.85rem;color:#9ca3af;margin-bottom:0.3rem;}
        .stat-card p{font-size:1.2rem;font-weight:600;margin:0;}
        .stat-card small{display:block;margin-top:0.2rem;font-size:0.75rem;color:#6b7280;}
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;}
        .card h2{font-size:1rem;margin-bottom:0.75rem;}
        .table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        .table th,.table td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}
        .badge{display:inline-block;font-size:0.7rem;padding:0.15rem 0.4rem;border-radius:999px;border:1px solid #374151;color:#9ca3af;}
    </style>
</head>
<body>

<header class="topbar">
    <div>👤 CarHub · <span style="color:#a5b4fc;">My account</span></div>
    <div>
        <!-- ✅ FIXED: use relative links from /dashboard -->
        <a href="../index.php">Home</a> ·
        <?php if ($currentRole === 'buyer_seller'): ?>
            <a href="../seller/dashboard.php">Seller View</a> ·
        <?php endif; ?>
        <a href="../cars.php">Browse Cars</a> ·
        <a href="../spare-parts.php">Spare Parts</a> ·
        <a href="../cart/index.php">My cart</a> ·
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>User menu</h3>
        <ul>
            <li><a href="index.php">Overview</a></li>
            <li><a href="profile.php">My profile</a></li>
            <li><a href="orders.php">My orders</a></li>
            <li><a href="reviews.php">My reviews</a></li>
            <li><a href="../cart/index.php">My cart</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">
            Welcome<?php if ($user): ?>, <?php echo h($user['first_name']); ?><?php endif; ?> 👋
        </h1>
        <p class="page-subtitle">Track your orders and manage your account.</p>

        <section class="stats-grid">
            <div class="stat-card">
                <h4>Total orders</h4>
                <p><?php echo $totalOrders; ?></p>
                <small>All orders placed with this account</small>
            </div>
            <div class="stat-card">
                <h4>Delivered orders</h4>
                <p><?php echo $completedOrders; ?></p>
                <small>Orders with status "delivered"</small>
            </div>
            <div class="stat-card">
                <h4>Active orders</h4>
                <p><?php echo $activeOrders; ?></p>
                <small>Pending, confirmed, processing, shipped</small>
            </div>
            <div class="stat-card">
                <h4>Total spent</h4>
                <p>₵<?php echo number_format($totalSpent, 2); ?></p>
                <small>Excludes cancelled/refunded orders</small>
            </div>
        </section>

        <section class="card">
            <h2>Recent orders</h2>
            <?php if (empty($recentOrders)): ?>
                <p>You haven’t placed any orders yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td>#<?php echo (int)$o['order_id']; ?></td>
                            <td><span class="badge"><?php echo h($o['order_status']); ?></span></td>
                            <td>₵<?php echo number_format($o['total_amount'], 2); ?></td>
                            <td><?php echo h($o['created_at']); ?></td>
                            <td><a href="order_details.php?id=<?php echo (int)$o['order_id']; ?>">View</a></td>
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