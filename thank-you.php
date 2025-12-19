<?php
// cart/thank-you.php - Thank you page after successful payment
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
    <title>Thank You for Your Purchase - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{
            margin:0;
            font-family:system-ui,-apple-system,"Segoe UI",sans-serif;
            background:#0f172a;
            color:#e5e7eb;
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:100vh;
            padding:1rem;
        }
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .card{
            background:#020617;
            border-radius:0.75rem;
            padding:2rem 2.5rem;
            border:1px solid #111827;
            max-width:600px;
            text-align:center;
            box-shadow:0 24px 50px rgba(15,23,42,0.9);
        }
        .success-icon{
            font-size:4rem;
            margin-bottom:1rem;
        }
        .card h1{
            font-size:1.8rem;
            margin-bottom:0.5rem;
            color:#e5e7eb;
        }
        .card p{
            font-size:0.95rem;
            color:#9ca3af;
            margin-bottom:0.75rem;
            line-height:1.6;
        }
        .order-details{
            background:#111827;
            border-radius:0.5rem;
            padding:1rem;
            margin:1.5rem 0;
            text-align:left;
        }
        .order-details p{
            margin:0.5rem 0;
            color:#d1d5db;
        }
        .order-details strong{
            color:#e5e7eb;
        }
        .btn{
            display:inline-block;
            margin-top:1rem;
            padding:0.7rem 1.5rem;
            border-radius:999px;
            border:none;
            font-size:1rem;
            font-weight:600;
            cursor:pointer;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:#f9fafb;
            text-decoration:none;
            transition:all 0.2s;
        }
        .btn:hover{
            filter:brightness(1.1);
            transform:translateY(-1px);
            box-shadow:0 4px 12px rgba(102,126,234,0.4);
        }
        .btn-secondary{
            display:inline-block;
            margin-top:0.75rem;
            padding:0.5rem 1rem;
            border-radius:999px;
            border:1px solid #374151;
            font-size:0.9rem;
            background:transparent;
            color:#9ca3af;
        }
        .btn-secondary:hover{
            background:#111827;
            color:#e5e7eb;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="success-icon">✅</div>
    <h1>Thank You for Your Purchase!</h1>
    <p>Your payment has been successfully processed.</p>

    <?php if ($order): ?>
        <div class="order-details">
            <p><strong>Order Number:</strong> #<?php echo (int)$order['order_id']; ?></p>
            <p><strong>Status:</strong> <?php echo h(ucfirst(str_replace('_', ' ', $order['order_status']))); ?></p>
            <p><strong>Total Amount:</strong> ₵<?php echo number_format((float)$order['total_amount'], 2); ?></p>
            <p><strong>Order Date:</strong> <?php echo h(date('F j, Y g:i A', strtotime($order['created_at']))); ?></p>
        </div>
        <p style="color:#d1d5db;">You will receive an email confirmation shortly with your order details.</p>
    <?php else: ?>
        <p style="color:#d1d5db;">Your order has been successfully placed. You can view it in your dashboard.</p>
    <?php endif; ?>

    <a href="../dashboard/index.php" class="btn">Go to Dashboard</a>
    
    <div style="margin-top:1rem;">
        <a href="../dashboard/orders.php" class="btn-secondary">View My Orders</a>
        <span style="color:#6b7280;margin:0 0.5rem;">|</span>
        <a href="../index.php" class="btn-secondary">Continue Shopping</a>
    </div>
</div>
</body>
</html>

