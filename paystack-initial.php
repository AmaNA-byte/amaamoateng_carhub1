<?php
session_start();
require_once '../config/database.php';
require_once '../config/paystack.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) die('Invalid order');

$db = (new Database())->getConnection();

// Load order (must belong to buyer + must be pending_payment)
$stmt = $db->prepare("
    SELECT order_id, buyer_id, total_amount, order_status
    FROM orders
    WHERE order_id = :id AND buyer_id = :uid
    LIMIT 1
");
$stmt->execute([
    ':id'  => $orderId,
    ':uid' => (int)$_SESSION['user_id']
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('Order not found');
if ($order['order_status'] !== 'pending_payment') die('Order is not pending payment');

$email = $_SESSION['email'] ?? '';
if ($email === '') die('Email missing in session');

// Paystack amount must be in pesewas
$amountPesewas = (int)round(((float)$order['total_amount']) * 100);

// Callback URL - dynamically determine based on current request
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = str_replace('\\', '/', dirname($scriptPath));
$callbackUrl = $protocol . '://' . $host . $basePath . '/cart/paystack-verify.php';

// Unique reference
$reference = "CARHUB_" . $orderId . "_" . time();

// Save transaction record (recommended)
try {
    $stmtTx = $db->prepare("
        INSERT INTO transactions (order_id, reference, amount, currency, status, created_at)
        VALUES (:order_id, :ref, :amt, 'GHS', 'initialized', NOW())
    ");
    $stmtTx->execute([
        ':order_id' => $orderId,
        ':ref'      => $reference,
        ':amt'      => (float)$order['total_amount']
    ]);
} catch (Exception $e) {
    // If your transactions table columns differ, you can remove this block.
}

$payload = [
    'email'        => $email,
    'amount'       => $amountPesewas,
    'reference'    => $reference,
    'callback_url' => $callbackUrl,
    'metadata'     => [
        'order_id' => $orderId
    ]
];

$ch = curl_init(PAYSTACK_BASE_URL . '/transaction/initialize');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),

    //  TEMPORARY: DISABLE SSL (LOCALHOST ONLY)
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);


$response = curl_exec($ch);

if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    die("cURL error: " . htmlspecialchars($err));
}
curl_close($ch);

$result = json_decode($response, true);

if (!$result || empty($result['status'])) {
    die("Paystack init failed: " . htmlspecialchars($response));
}

if (empty($result['data']['authorization_url'])) {
    die("Paystack init missing authorization_url: " . htmlspecialchars($response));
}

// Redirect buyer to Paystack checkout page
header("Location: " . $result['data']['authorization_url']);
exit;