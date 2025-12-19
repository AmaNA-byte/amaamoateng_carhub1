<?php

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Delete expired email verification tokens
$query = "DELETE FROM email_verifications WHERE expires_at < NOW()";
$stmt = $db->prepare($query);
$stmt->execute();
$deleted_verifications = $stmt->rowCount();

// Delete expired password reset tokens
$query = "DELETE FROM password_resets WHERE expires_at < NOW()";
$stmt = $db->prepare($query);
$stmt->execute();
$deleted_resets = $stmt->rowCount();

echo "Cleanup completed:\n";
echo "- Deleted {$deleted_verifications} expired verification tokens\n";
echo "- Deleted {$deleted_resets} expired password reset tokens\n";
?>