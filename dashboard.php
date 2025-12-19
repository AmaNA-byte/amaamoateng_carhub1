<?php
// seller/dashboard.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Correct login check (relative path)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$currentRole   = $_SESSION['role'] ?? 'buyer';

// ✅ Only allow seller roles
if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// DB connection
$database = new Database();
$db = $database->getConnection();

// Stats defaults
$totalCarListings  = 0;
$totalPartListings = 0;
$totalOrders       = 0;
$totalRevenue      = 0.0;

// Car listings
try {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM cars 
         WHERE seller_id = :sid AND status IN ('available','pending')"
    );
    $stmt->execute([':sid' => $currentUserId]);
    $totalCarListings = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Spare parts
try {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM spare_parts 
         WHERE seller_id = :sid AND status IN ('available','pending')"
    );
    $stmt->execute([':sid' => $currentUserId]);
    $totalPartListings = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Orders
try {
    $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT order_id) 
         FROM order_items 
         WHERE seller_id = :sid"
    );
    $stmt->execute([':sid' => $currentUserId]);
    $totalOrders = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Revenue
try {
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(subtotal),0) 
         FROM order_items 
         WHERE seller_id = :sid"
    );
    $stmt->execute([':sid' => $currentUserId]);
    $totalRevenue = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

// Seller info
$user = null;
try {
    $stmt = $db->prepare(
        "SELECT first_name, last_name, email 
         FROM users 
         WHERE user_id = :id"
    );
    $stmt->execute([':id' => $currentUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;} a:hover{text-decoration:underline;}

        .topbar{
            display:flex;justify-content:space-between;align-items:center;
            padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;
        }
        .topbar-right a{margin-left:1rem;font-size:0.9rem;}

        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{
            width:220px;background:#020617;border-right:1px solid #111827;
            padding:1.5rem 1rem;font-size:0.9rem;
        }
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.4rem 0.6rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}

        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}

        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;}
        .stat-card{background:#020617;border-radius:0.75rem;padding:1rem;border:1px solid #111827;}
        .stat-card h4{font-size:0.85rem;color:#9ca3af;}
        .stat-card p{font-size:1.2rem;font-weight:600;}

        .card{background:#020617;border-radius:0.75rem;padding:1rem;border:1px solid #111827;}
        .btn{padding:0.45rem 0.9rem;border-radius:999px;border:none;font-size:0.85rem;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
        .btn-secondary{background:#111827;color:#e5e7eb;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🚗 CarHub · <span style="color:#a5b4fc;">Seller</span></div>
    <div class="topbar-right">
        <a href="../index.php">Home</a>
        <?php if ($currentRole === 'buyer_seller'): ?>
            <a href="../dashboard/index.php">Buyer View</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </div>
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
        <h1 class="page-title">
            Welcome<?php if ($user): ?>, <?php echo htmlspecialchars($user['first_name']); ?><?php endif; ?> 👋
        </h1>
        <p class="page-subtitle">Manage your listings, orders, and revenue.</p>

        <section class="stats-grid">
            <div class="stat-card">
                <h4>Car listings</h4>
                <p><?php echo $totalCarListings; ?></p>
            </div>
            <div class="stat-card">
                <h4>Spare parts</h4>
                <p><?php echo $totalPartListings; ?></p>
            </div>
            <div class="stat-card">
                <h4>Orders</h4>
                <p><?php echo $totalOrders; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total revenue</h4>
                <p>₵<?php echo number_format($totalRevenue,2); ?></p>
            </div>
        </section>

        <section class="card">
            <h3>Quick actions</h3>
            <a href="add-car.php" class="btn btn-primary">+ Add new car</a>
            <a href="add-spare-part.php" class="btn btn-secondary">+ Add spare part</a>
            <a href="my-listings.php" class="btn btn-secondary">View listings</a>
            <a href="orders.php" class="btn btn-secondary">View orders</a>
        </section>
    </main>
</div>

</body>
</html>