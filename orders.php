<?php
// car_hub/seller/orders.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentRole   = isset($_SESSION['role']) ? $_SESSION['role'] : 'buyer';

if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

$flash = '';
$errors = [];

/**
 * Safety rule:
 * If an order has items from multiple sellers, we won't let a single seller change the global order_status.
 * We'll allow status changes ONLY if the order belongs to this seller alone.
 */
function orderBelongsToSingleSeller(PDO $db, int $orderId, int $sellerId): bool {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT seller_id) FROM order_items WHERE order_id = :oid");
    $stmt->execute([':oid' => $orderId]);
    $distinct = (int)$stmt->fetchColumn();
    if ($distinct !== 1) return false;

    $stmt = $db->prepare("SELECT seller_id FROM order_items WHERE order_id = :oid LIMIT 1");
    $stmt->execute([':oid' => $orderId]);
    $onlySeller = (int)$stmt->fetchColumn();

    return ($onlySeller === $sellerId);
}

// Handle seller status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];

    // Allowed seller transitions
    $allowed = ['processing', 'shipped'];

    if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
        $errors[] = "Invalid status update.";
    } else {
        // Confirm this seller is involved in this order
        $stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = :oid AND seller_id = :sid");
        $stmt->execute([':oid' => $orderId, ':sid' => $currentUserId]);
        $hasItems = (int)$stmt->fetchColumn() > 0;

        if (!$hasItems) {
            $errors[] = "You can't update an order that doesn't include your items.";
        } else {
            // Only allow if order is single-seller (prevents messing other sellers)
            if (!orderBelongsToSingleSeller($db, $orderId, $currentUserId)) {
                $errors[] = "This order contains items from multiple sellers. Status updates are disabled for safety.";
            } else {
                // Update order status
                $stmt = $db->prepare("
                    UPDATE orders
                    SET order_status = :st, updated_at = NOW()
                    WHERE order_id = :oid
                ");
                $stmt->execute([':st' => $newStatus, ':oid' => $orderId]);
                $flash = "Order #{$orderId} updated to '{$newStatus}'.";
            }
        }
    }
}

// Load seller orders (distinct orders that contain this seller’s items)
try {
    $stmt = $db->prepare("
        SELECT DISTINCT o.order_id, o.buyer_id, o.total_amount, o.order_status,
               o.shipping_address, o.shipping_city, o.shipping_country,
               o.created_at, o.updated_at
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.order_id
        WHERE oi.seller_id = :sid
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':sid' => $currentUserId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Orders load error: " . h($e->getMessage()));
}

// Load items for each order (only this seller’s items)
$orderItemsByOrder = [];
$orderSellerCounts = []; // number of sellers per order (to decide if status is editable)

try {
    // get seller counts per order
    $stmt = $db->prepare("
        SELECT order_id, COUNT(DISTINCT seller_id) AS seller_count
        FROM order_items
        WHERE order_id IN (
            SELECT DISTINCT order_id FROM order_items WHERE seller_id = :sid
        )
        GROUP BY order_id
    ");
    $stmt->execute([':sid' => $currentUserId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $orderSellerCounts[(int)$r['order_id']] = (int)$r['seller_count'];
    }

    // get this seller's items per order
    $stmt = $db->prepare("
        SELECT order_item_id, order_id, item_type, item_id, quantity, price, subtotal
        FROM order_items
        WHERE seller_id = :sid
        ORDER BY order_id DESC, order_item_id ASC
    ");
    $stmt->execute([':sid' => $currentUserId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $it) {
        $oid = (int)$it['order_id'];
        if (!isset($orderItemsByOrder[$oid])) $orderItemsByOrder[$oid] = [];
        $orderItemsByOrder[$oid][] = $it;
    }
} catch (Exception $e) {
    // keep going
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Orders - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
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
        .card{background:#020617;border:1px solid #111827;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;}
        .muted{color:#9ca3af;}
        .badge{display:inline-block;padding:0.12rem 0.5rem;border-radius:999px;border:1px solid #374151;font-size:0.75rem;color:#9ca3af;}
        table{width:100%;border-collapse:collapse;font-size:0.88rem;}
        th,td{padding:0.55rem 0.45rem;border-bottom:1px solid #111827;text-align:left;vertical-align:top;}
        .btn{padding:0.35rem 0.75rem;border-radius:999px;border:none;font-size:0.82rem;cursor:pointer;background:#111827;color:#e5e7eb;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn:hover{filter:brightness(1.05);}
        .notice{background:#14532d;border:1px solid #166534;color:#bbf7d0;padding:0.65rem;border-radius:0.6rem;margin-bottom:1rem;}
        .err{background:#7f1d1d;border:1px solid #991b1b;color:#fee2e2;padding:0.65rem;border-radius:0.6rem;margin-bottom:1rem;}
        .row{display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;margin-top:0.5rem;}
        .small{font-size:0.8rem;color:#9ca3af;}
        select{background:#0b1220;border:1px solid #111827;border-radius:0.5rem;color:#e5e7eb;padding:0.35rem 0.5rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div>📦 CarHub · <span style="color:#a5b4fc;">Seller</span></div>
    <div>
        <a href="dashboard.php">Dashboard</a> ·
        <a href="../index.php">Home</a> ·
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
        <h1>Orders</h1>
        <p class="muted">These are orders that contain your items.</p>

        <?php if ($flash): ?>
            <div class="notice"><?php echo h($flash); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="err"><?php foreach ($errors as $e) echo h($e)."<br>"; ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="card">
                <p class="muted">No orders yet for your listings.</p>
            </div>
        <?php else: ?>

            <?php foreach ($orders as $o): ?>
                <?php
                    $oid = (int)$o['order_id'];
                    $sellerCount = isset($orderSellerCounts[$oid]) ? (int)$orderSellerCounts[$oid] : 1;
                    $isSingleSellerOrder = ($sellerCount === 1);
                    $itemsForOrder = isset($orderItemsByOrder[$oid]) ? $orderItemsByOrder[$oid] : [];
                ?>

                <div class="card">
                    <div class="row">
                        <strong>Order #<?php echo $oid; ?></strong>
                        <span class="badge"><?php echo h($o['order_status']); ?></span>
                        <span class="small">Total: ₵<?php echo number_format((float)$o['total_amount'], 2); ?></span>
                        <span class="small">Placed: <?php echo h(substr($o['created_at'],0,10)); ?></span>
                        <?php if (!$isSingleSellerOrder): ?>
                            <span class="badge">Multi-seller order</span>
                        <?php endif; ?>
                    </div>

                    <div class="small" style="margin-top:0.35rem;">
                        Ship to: <?php echo h($o['shipping_address']); ?>, <?php echo h($o['shipping_city']); ?>, <?php echo h($o['shipping_country']); ?>
                    </div>

                    <div style="margin-top:0.75rem;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Subtotal</th>
                                    <th>Links</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($itemsForOrder as $it): ?>
                                <tr>
                                    <td>#<?php echo (int)$it['item_id']; ?></td>
                                    <td><?php echo $it['item_type'] === 'car' ? 'Car' : 'Spare part'; ?></td>
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
                    </div>

                    <div class="row">
                        <form method="post" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                            <input type="hidden" name="order_id" value="<?php echo $oid; ?>">

                            <label class="small">Update status:</label>
                            <select name="new_status" <?php echo $isSingleSellerOrder ? '' : 'disabled'; ?>>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                            </select>

                            <button class="btn btn-primary" type="submit" <?php echo $isSingleSellerOrder ? '' : 'disabled'; ?>>
                                Save
                            </button>

                            <?php if (!$isSingleSellerOrder): ?>
                                <span class="small">Status updates disabled (multi-seller order).</span>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

</body>
</html>