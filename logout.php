<?php
// logout.php – lives in the ROOT: car_hub/logout.php

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Clear all session variables
$_SESSION = [];

// 2. Delete the session cookie (PHPSESSID)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Clear any custom “remember me” cookies you created
// (you had user_email – keep this)
setcookie('user_email', '', time() - 3600, '/');

// 4. Destroy the session on the server
session_destroy();

// 5. Redirect to homepage (or login if you prefer)
header('Location: index.php');
exit;