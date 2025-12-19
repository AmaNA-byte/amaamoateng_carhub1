<?php
session_start();
require_once '../config/database.php';
require_once '../config/paystack.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Paystack can send either 'reference' or 'trxref' parameter
$reference = $_GET['reference'] ?? $_GET['trxref'] ?? '';
if ($reference === '') die('No reference provided');

$ch = curl_init(PAYSTACK_BASE_URL . "/transaction/verify/" . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY
    ],

    //  TEMPORARY: SSL OFF (LOCALHOST ONLY)
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);

if ($response === false) {
    die("cURL error (verify): " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Paystack HTTP error ($httpCode): " . htmlspecialchars($response));
}

$result = json_decode($response, true);

if (!$result || !isset($result['status'])) {
    die("Invalid Paystack response: " . htmlspecialchars($response));
}

if ($result['status'] !== true) {
    die("Paystack verification failed: " . htmlspecialchars($response));
}

$data = $result['data'];

if ($data['status'] !== 'success') {
    die("Payment not successful. Status: " . htmlspecialchars($data['status']));
}

$orderId = (int)($data['metadata']['order_id'] ?? 0);
if ($orderId <= 0) die('Order ID missing in metadata');

$db = (new Database())->getConnection();
$stmt = $db->prepare("
    UPDATE orders 
    SET order_status = 'paid', updated_at = NOW() 
    WHERE order_id = :id
");
$stmt->execute([':id' => $orderId]);

header("Location: thank-you.php?order_id=" . $orderId);
exit;
