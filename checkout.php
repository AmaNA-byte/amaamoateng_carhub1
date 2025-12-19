<?php
session_start();
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Get cart id
$stmt = $db->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$cartId = (int)$stmt->fetchColumn();

if (!$cartId) {
    header('Location: index.php');
    exit;
}

// Load cart items
$stmt = $db->prepare("SELECT cart_item_id, item_type, item_id, quantity FROM cart_items WHERE cart_id = :cid");
$stmt->execute([':cid' => $cartId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$cartItems) {
    header('Location: index.php');
    exit;
}

$errors = [];
$shipping_address = trim($_POST['shipping_address'] ?? '');
$shipping_city    = trim($_POST['shipping_city'] ?? '');
$shipping_country = trim($_POST['shipping_country'] ?? 'Ghana');

// Build priced items + stock checks + summary rows
$items = [];
$total = 0.0;

foreach ($cartItems as $ci) {
    $type = $ci['item_type'];
    $id   = (int)$ci['item_id'];
    $qty  = (int)$ci['quantity'];

    if ($qty <= 0) continue;

    if ($type === 'car') {
        $q = $db->prepare("SELECT car_id, seller_id, brand, model, year, price, status, approval_status FROM cars WHERE car_id = :id");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if (!$row) { $errors[] = "A car in your cart no longer exists."; continue; }
        if ($row['approval_status'] !== 'approved') { $errors[] = "A car in your cart is not approved."; continue; }
        if ($row['status'] !== 'available') { $errors[] = "A car in your cart is no longer available."; continue; }

        $price = (float)$row['price'];
        $subtotal = $price * 1;

        $items[] = [
            'type' => 'car',
            'item_id' => (int)$row['car_id'],
            'seller_id' => (int)$row['seller_id'],
            'qty' => 1,
            'price' => $price,
            'subtotal' => $subtotal,
            'title' => $row['brand'].' '.$row['model'],
            'meta' => 'Year '.$row['year']
        ];
        $total += $subtotal;

    } elseif ($type === 'spare_part') {
        $q = $db->prepare("SELECT spare_part_id, seller_id, name, category, price, quantity, approval_status, status FROM spare_parts WHERE spare_part_id = :id");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if (!$row) { $errors[] = "A spare part in your cart no longer exists."; continue; }
        if ($row['approval_status'] !== 'approved') { $errors[] = "A spare part in your cart is not approved."; continue; }
        if ($row['status'] !== 'available') { $errors[] = "A spare part in your cart is not available."; continue; }

        $availableQty = (int)$row['quantity'];
        if ($qty > $availableQty) {
            $errors[] = "Not enough stock for: ".$row['name']." (available: {$availableQty}).";
            continue;
        }

        $price = (float)$row['price'];
        $subtotal = $price * $qty;

        $items[] = [
            'type' => 'spare_part',
            'item_id' => (int)$row['spare_part_id'],
            'seller_id' => (int)$row['seller_id'],
            'qty' => $qty,
            'price' => $price,
            'subtotal' => $subtotal,
            'title' => $row['name'],
            'meta' => $row['category']
        ];
        $total += $subtotal;
    }
}

// Confirm checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($shipping_address === '' || $shipping_city === '') {
        $errors[] = "Shipping address and city are required.";
    }

    if (empty($errors) && $total > 0) {
        try {
            $db->beginTransaction();

            // ✅ Create order as pending_payment
            $stmt = $db->prepare("
                INSERT INTO orders (buyer_id, total_amount, order_status, shipping_address, shipping_city, shipping_country, created_at, updated_at)
                VALUES (:buyer_id, :total, 'pending_payment', :addr, :city, :country, NOW(), NOW())
            ");
            $stmt->execute([
                ':buyer_id' => $userId,
                ':total' => $total,
                ':addr' => $shipping_address,
                ':city' => $shipping_city,
                ':country' => $shipping_country
            ]);

            $orderId = (int)$db->lastInsertId();

            // Insert order items
            $stmtItem = $db->prepare("
                INSERT INTO order_items (order_id, seller_id, item_type, item_id, quantity, price, subtotal)
                VALUES (:order_id, :seller_id, :item_type, :item_id, :qty, :price, :subtotal)
            ");

            foreach ($items as $it) {
                $stmtItem->execute([
                    ':order_id' => $orderId,
                    ':seller_id' => $it['seller_id'],
                    ':item_type' => $it['type'],
                    ':item_id' => $it['item_id'],
                    ':qty' => $it['qty'],
                    ':price' => $it['price'],
                    ':subtotal' => $it['subtotal']
                ]);

                // Lock stock for spare parts
                if ($it['type'] === 'spare_part') {
                    $upd = $db->prepare("
                        UPDATE spare_parts
                        SET quantity = quantity - :qty
                        WHERE spare_part_id = :id AND quantity >= :qty
                    ");
                    $upd->execute([':qty' => $it['qty'], ':id' => $it['item_id']]);

                    if ($upd->rowCount() === 0) {
                        throw new Exception("Stock update failed (someone bought it first).");
                    }

                    // If stock hits 0, mark out_of_stock
                    $upd2 = $db->prepare("
                        UPDATE spare_parts
                        SET status = CASE WHEN quantity <= 0 THEN 'out_of_stock' ELSE status END
                        WHERE spare_part_id = :id
                    ");
                    $upd2->execute([':id' => $it['item_id']]);
                }

                // Cars: lock as sold immediately
                if ($it['type'] === 'car') {
                    $upd = $db->prepare("
                        UPDATE cars
                        SET status = 'sold'
                        WHERE car_id = :id AND status = 'available'
                    ");
                    $upd->execute([':id' => $it['item_id']]);

                    if ($upd->rowCount() === 0) {
                        throw new Exception("Car purchase failed (no longer available).");
                    }
                }
            }

            // Clear cart
            $db->prepare("DELETE FROM cart_items WHERE cart_id = :cid")->execute([':cid' => $cartId]);

            $db->commit();

            // ✅ Redirect to Paystack init instead of order-success
            header("Location: paystack-initial.php?order_id=" . $orderId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Checkout failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}
        .wrap{max-width:900px;margin:2rem auto;padding:0 1rem;}
        .card{background:#020617;border:1px solid #111827;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;}
        label{display:block;color:#9ca3af;font-size:0.85rem;margin-top:0.75rem;margin-bottom:0.25rem;}
        input{width:100%;padding:0.5rem;border-radius:0.5rem;border:1px solid #111827;background:#0b1220;color:#e5e7eb;}
        .btn{margin-top:1rem;padding:0.55rem 1rem;border-radius:999px;border:none;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;cursor:pointer;}
        .err{background:#7f1d1d;border:1px solid #991b1b;color:#fee2e2;padding:0.75rem;border-radius:0.5rem;margin-bottom:1rem;}
        .summary{color:#9ca3af;}
        table{width:100%;border-collapse:collapse;font-size:0.9rem;}
        th,td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;}
        .badge{display:inline-block;padding:0.12rem 0.5rem;border-radius:999px;border:1px solid #374151;font-size:0.75rem;color:#9ca3af;}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Checkout</h1>
    <p class="summary">Review your order before confirming.</p>

    <?php if (!empty($errors)): ?>
        <div class="err"><?php foreach ($errors as $e) echo h($e)."<br>"; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0;">Order summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <?php echo h($it['title']); ?><br>
                        <span class="badge"><?php echo h($it['meta']); ?></span>
                    </td>
                    <td><?php echo $it['type'] === 'car' ? 'Car' : 'Spare part'; ?></td>
                    <td><?php echo (int)$it['qty']; ?></td>
                    <td>₵<?php echo number_format((float)$it['price'],2); ?></td>
                    <td>₵<?php echo number_format((float)$it['subtotal'],2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:0.8rem;"><strong>Total: ₵<?php echo number_format((float)$total,2); ?></strong></p>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Shipping info</h3>
        <form method="post">
            <label>Shipping address *</label>
            <input name="shipping_address" value="<?php echo h($shipping_address); ?>" required>

            <label>City *</label>
            <input name="shipping_city" value="<?php echo h($shipping_city); ?>" required>

            <label>Country</label>
            <input name="shipping_country" value="<?php echo h($shipping_country ?: 'Ghana'); ?>">

            <button class="btn" type="submit">Confirm order</button>
        </form>
    </div>

    <p style="margin-top:1rem;">
        <a href="index.php">← Back to cart</a>
    </p>
</div>
</body>
</html>