<?php
// admin/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/session.php';

// Must be logged in + admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Create DB connection for all admin pages
$database = new Database();
$db = $database->getConnection();

// Helper
function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}