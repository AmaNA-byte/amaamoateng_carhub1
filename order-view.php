<?php
// admin/order-view.php
require_once 'auth.php'; // admin-only + $db + h()

error_reporting(E_ALL);
ini_set('display_errors', 1);

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    die('Invalid order ID');
}

// Handle admin status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];

    // Admin allowed statuses (adjust if you want)
    $allowed = ['pending','processing','shipped','delivered','cancelled','refunded'];

    if (!in_array($newStatus, $allowed, true)) {
        die("Invalid status.");
    }

    $stmt = $db->prepare("
        UPDATE orders
        SET order_status = :st, updated_at = NOW()
        WHERE order_id = :id
    ");
    $stmt->execute([
        ':st' => $newStatus,
        ':id' => $orderId
    ]);

    header("Location: order-view.php?id=" . $orderId);
    exit;
}

// Load order
$stmt = $db->prepare("
    SELECT order_id, buyer_id, total_amount, order_status,
           shipping_address, shipping_city, shipping_country,
           created_at, updated_at
    FROM orders
    WHERE order_id = :id
    LIMIT 1
");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found');
}

// Load items
$stmt = $db->prepare("
    SELECT order_item_id, seller_id, item_type, item_id, quantity, price, subtotal
    FROM order_items
    WHERE order_id = :id
    ORDER BY order_item_id ASC
");
$stmt->execute([':id' => $orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: suggest next status options based on current status
$currentStatus = $order['order_status'];
$nextOptions = [];

if ($currentStatus === 'pending') {
    $nextOptions = ['processing','cancelled'];
} elseif ($currentStatus === 'processing') {
    $nextOptions = ['shipped','cancelled'];
} elseif ($currentStatus === 'shipped') {
    $nextOptions = ['delivered'];
} elseif ($currentStatus === 'delivered') {
    $nextOptions = []; // finished
} else {
    $nextOptions = []; // cancelled/refunded/etc
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo (int)$order['order_id']; ?> - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}

        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}

        .sidebar{width:240px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.5rem 0.7rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}

        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.2rem;}

        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem;border:1px solid #111827;margin-bottom:1rem;}
        table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        th,td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}

        .badge{display:inline-block;padding:0.1rem 0.45rem;border-radius:999px;border:1px solid #374151;font-size:0.75rem;color:#9ca3af;}
        .btn{padding:0.45rem 0.9rem;border-radius:999px;border:none;font-size:0.85rem;cursor:pointer;background:#111827;color:#e5e7eb;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn:hover{filter:brightness(1.05);}
        select{background:#0b1220;border:1px solid #111827;border-radius:0.5rem;color:#e5e7eb;padding:0.35rem 0.5rem;}
        .row{display:flex;gap:0.6rem;align-items:center;flex-wrap:wrap;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🛠️ CarHub Admin</div>
    <div>
        <a href="orders.php">← Back to orders</a> ·
        <a href="dashboard.php">Dashboard</a> ·
        <a href="../index.php">View site</a> ·
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
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
        <h1 class="page-title">Order #<?php echo (int)$order['order_id']; ?></h1>

        <p class="page-subtitle">
            Status: <span class="badge"><?php echo h($order['order_status']); ?></span> ·
            Total: ₵<?php echo number_format((float)$order['total_amount'], 2); ?> ·
            Buyer ID: <?php echo (int)$order['buyer_id']; ?>
        </p>

        <section class="card">
            <h3 style="margin-top:0;">Admin actions</h3>

            <?php if (!empty($nextOptions)): ?>
                <form method="post" class="row">
                    <label class="badge">Update status</label>
                    <select name="new_status">
                        <?php foreach ($nextOptions as $st): ?>
                            <option value="<?php echo h($st); ?>"><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
                <p style="color:#9ca3af;margin-top:0.6rem;font-size:0.85rem;">
                    Tip: Only mark <strong>delivered</strong> after seller ships and buyer receives.
                </p>
            <?php else: ?>
                <p style="color:#9ca3af;">No actions available for this status.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h3 style="margin-top:0;">Shipping</h3>
            <p><?php echo nl2br(h($order['shipping_address'])); ?></p>
            <p><?php echo h($order['shipping_city']); ?>, <?php echo h($order['shipping_country']); ?></p>
            <p style="color:#9ca3af;font-size:0.85rem;">
                Created: <?php echo h($order['created_at']); ?> · Updated: <?php echo h($order['updated_at']); ?>
            </p>
        </section>

        <section class="card">
            <h3 style="margin-top:0;">Items</h3>
            <?php if (empty($items)): ?>
                <p style="color:#9ca3af;">No items for this order.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Item #</th>
                        <th>Type</th>
                        <th>Item ID</th>
                        <th>Seller ID</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th>Subtotal</th>
                        <th>Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?php echo (int)$it['order_item_id']; ?></td>
                            <td><?php echo $it['item_type'] === 'car' ? 'Car' : 'Spare part'; ?></td>
                            <td><?php echo (int)$it['item_id']; ?></td>
                            <td><?php echo (int)$it['seller_id']; ?></td>
                            <td><?php echo (int)$it['quantity']; ?></td>
                            <td>₵<?php echo number_format((float)$it['price'], 2); ?></td>
                            <td>₵<?php echo number_format((float)$it['subtotal'], 2); ?></td>
                            <td>
                                <?php if ($it['item_type'] === 'car'): ?>
                                    <a href="../car-details.php?id=<?php echo (int)$it['item_id']; ?>">View car</a>
                                <?php else: ?>
                                    <a href="../spare-part-details.php?id=<?php echo (int)$it['item_id']; ?>">View part</a>
                                <?php endif; ?>
                            </td>
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