<?php
// cart/order-success.php
session_start();
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;

if ($orderId > 0) {
    $stmt = $db->prepare("
        SELECT order_id, buyer_id, total_amount, order_status, created_at
        FROM orders
        WHERE order_id = :oid AND buyer_id = :bid
        LIMIT 1
    ");
    $stmt->execute([
        ':oid' => $orderId,
        ':bid' => $currentUserId
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Success - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;display:flex;align-items:center;justify-content:center;min-height:100vh;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .card{background:#020617;border-radius:0.75rem;padding:1.5rem 1.8rem;border:1px solid #111827;max-width:520px;text-align:center;box-shadow:0 24px 50px rgba(15,23,42,0.9);}
        .card h1{font-size:1.4rem;margin-bottom:0.5rem;}
        .card p{font-size:0.9rem;color:#9ca3af;margin-bottom:0.75rem;}
        .btn{display:inline-block;margin-top:0.75rem;padding:0.55rem 1.1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;background:linear-gradient(135deg,#667eea,#764ba2);color:#f9fafb;}
        .btn:hover{filter:brightness(1.05);}
    </style>
</head>
<body>
<div class="card">
    <h1>🎉 Order placed successfully</h1>

    <?php if ($order): ?>
        <p>Your order <strong>#<?php echo (int)$order['order_id']; ?></strong> has been recorded.</p>
        <p>Status: <strong><?php echo h($order['order_status']); ?></strong></p>
        <p>Total: <strong>₵<?php echo number_format((float)$order['total_amount'], 2); ?></strong></p>
        <p>Placed on: <?php echo h($order['created_at']); ?></p>
    <?php else: ?>
        <p>Your order has been placed. You can see it in your order history.</p>
    <?php endif; ?>

    <a href="../dashboard/orders.php" class="btn">View my orders</a>

    <div style="margin-top:0.6rem;">
        or <a href="../index.php">continue shopping</a>
    </div>
</div>
</body>
</html>