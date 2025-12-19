<?php
// dashboard/order-details.php
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

$database = new Database();
$db = $database->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    die("Invalid order ID.");
}

// Make sure this order belongs to the logged-in user (buyer_id)
$order = null;
try {
    $sql = "SELECT order_id, buyer_id, total_amount, order_status, shipping_address, shipping_city, shipping_country, created_at
            FROM orders
            WHERE order_id = :oid AND buyer_id = :bid";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':oid' => $orderId,
        ':bid' => $currentUserId
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $order = null;
}

if (!$order) {
    die("Order not found.");
}

// Get order items
$items = [];
try {
    $sql = "SELECT order_item_id, seller_id, item_type, item_id, quantity, price, subtotal
            FROM order_items
            WHERE order_id = :oid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':oid' => $orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo (int)$order['order_id']; ?> - CarHub</title>
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
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;}
        .table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        .table th,.table td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}
        .badge{display:inline-block;font-size:0.7rem;padding:0.15rem 0.4rem;border-radius:999px;border:1px solid #374151;color:#9ca3af;}
    </style>
</head>
<body>
<header class="topbar">
    <div>📦 CarHub · <span style="color:#a5b4fc;">Order details</span></div>
    <div><a href="orders.php">Back to orders</a> · <a href="/logout.php">Logout</a></div>
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
        <h1 class="page-title">Order #<?php echo (int)$order['order_id']; ?></h1>
        <p class="page-subtitle">
            Status: <span class="badge"><?php echo h($order['order_status']); ?></span> ·
            Total: ₵<?php echo number_format($order['total_amount'], 2); ?> ·
            Date: <?php echo h($order['created_at']); ?>
        </p>

        <section class="card">
            <h2>Shipping</h2>
            <p><?php echo nl2br(h($order['shipping_address'])); ?></p>
            <p><?php echo h($order['shipping_city']); ?>, <?php echo h($order['shipping_country']); ?></p>
        </section>

        <section class="card">
            <h2>Items in this order</h2>
            <?php if (empty($items)): ?>
                <p>No items found for this order.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Item ID</th>
                        <th>Seller</th>
                        <th>Qty</th>
                        <th>Unit price (₵)</th>
                        <th>Subtotal (₵)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['item_type'] === 'car' ? 'Car' : 'Spare part'; ?></td>
                            <td><?php echo (int)$item['item_id']; ?></td>
                            <td><?php echo (int)$item['seller_id']; ?></td>
                            <td><?php echo (int)$item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo number_format($item['subtotal'], 2); ?></td>
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