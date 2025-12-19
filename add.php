<?php
// car_hub/cart/add.php
session_start();
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['car','spare_part'], true) || $id <= 0) {
    die("Invalid item.");
}

$db = (new Database())->getConnection();

// Find or create cart
$stmt = $db->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$cartId = (int)$stmt->fetchColumn();

if (!$cartId) {
    $stmt = $db->prepare("INSERT INTO shopping_cart (user_id, created_at, updated_at) VALUES (:uid, NOW(), NOW())");
    $stmt->execute([':uid' => $userId]);
    $cartId = (int)$db->lastInsertId();
}

// Check existing cart row
$stmt = $db->prepare("
    SELECT cart_item_id, quantity
    FROM cart_items
    WHERE cart_id = :cid AND item_type = :type AND item_id = :iid
    LIMIT 1
");
$stmt->execute([':cid' => $cartId, ':type' => $type, ':iid' => $id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($type === 'car') {
    // Cars are always quantity 1
    if ($existing) {
        // already in cart, do nothing
    } else {
        $stmt = $db->prepare("
            INSERT INTO cart_items (cart_id, item_type, item_id, quantity, added_at)
            VALUES (:cid, 'car', :iid, 1, NOW())
        ");
        $stmt->execute([':cid' => $cartId, ':iid' => $id]);
    }
} else {
    // spare_part: increment quantity
    if ($existing) {
        $stmt = $db->prepare("
            UPDATE cart_items
            SET quantity = quantity + 1
            WHERE cart_item_id = :id
        ");
        $stmt->execute([':id' => (int)$existing['cart_item_id']]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO cart_items (cart_id, item_type, item_id, quantity, added_at)
            VALUES (:cid, 'spare_part', :iid, 1, NOW())
        ");
        $stmt->execute([':cid' => $cartId, ':iid' => $id]);
    }
}

// Redirect back to where user came from (or cart)
$back = $_SERVER['HTTP_REFERER'] ?? '../cart/index.php';

// Add a flag so you can show a “Added to cart” message
if (strpos($back, '?') === false) {
    $back .= '?added=1';
} else {
    $back .= '&added=1';
}

header("Location: $back");
exit;