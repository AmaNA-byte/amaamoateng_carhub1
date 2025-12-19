<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Not Found - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;display:flex;align-items:center;justify-content:center;min-height:100vh;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .card{background:#020617;border-radius:1rem;border:1px solid #111827;padding:2rem 2.5rem;max-width:420px;text-align:center;box-shadow:0 20px 50px rgba(15,23,42,0.85);}
        h1{font-size:2.5rem;margin-bottom:0.5rem;}
        p{font-size:0.95rem;color:#9ca3af;margin-bottom:1rem;}
        .btn{display:inline-block;margin-top:0.5rem;padding:0.6rem 1.2rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
    </style>
</head>
<body>
<div class="card">
    <h1>404</h1>
    <p>The page you’re looking for doesn’t exist or may have been moved.</p>
    <a href="../index.php" class="btn">Back to Home</a>
    <?php if ($isLoggedIn): ?>
        <div style="margin-top:0.5rem;font-size:0.85rem;">
            or go to your <a href="../dashboard/index.php">dashboard</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>