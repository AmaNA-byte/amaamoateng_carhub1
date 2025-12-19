<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'role' => $_SESSION['role']
    ];
}

function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] === $role || 
           ($_SESSION['role'] === 'buyer_seller' && in_array($role, ['buyer', 'seller']));
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../dashboard/index.php");
        exit();
    }
}

function requireSeller() {
    requireLogin();
    if (!hasRole('seller')) {
        header("Location: ../dashboard/index.php");
        exit();
    }
}
?>