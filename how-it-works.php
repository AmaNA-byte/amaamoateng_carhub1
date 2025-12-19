<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How CarHub Works</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{
            margin:0;
            font-family:system-ui,-apple-system,"Segoe UI",sans-serif;
            background:#020617;
            color:#e5e7eb;
        }
        a{color:#a5b4fc;text-decoration:none;}
        a:hover{text-decoration:underline;}
        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:1rem 2rem;
            border-bottom:1px solid #111827;
        }
        .logo{font-weight:700;}
        .nav-actions a{
            margin-left:0.6rem;
            font-size:0.85rem;
        }
        .btn-primary{
            padding:0.4rem 1rem;
            background:linear-gradient(135deg,#667eea,#764ba2);
            border-radius:999px;
            color:white;
            font-weight:600;
        }
        .page{
            padding:3rem 10vw 4rem;
            max-width:900px;
            margin:0 auto;
        }
        h1{margin-bottom:0.4rem;}
        p{color:#9ca3af;font-size:0.95rem;line-height:1.6;}
        .step{
            margin-top:2rem;
            border-left:3px solid #667eea;
            padding-left:1rem;
        }
        .step h2{color:#a5b4fc;margin-bottom:0.25rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">📘 How It Works</div>
    <div class="nav-actions">
        <?php if ($isLoggedIn): ?>
            <a href="../dashboard/index.php">Dashboard</a>
            <a href="../logout.php" class="btn-primary">Logout</a>
        <?php else: ?>
            <a href="../login.php">Login</a>
            <a href="../register.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main class="page">
    <h1>The CarHub Process</h1>
    <p>Your guide to buying and selling safely on CarHub.</p>

    <div class="step">
        <h2>1. Create an account</h2>
        <p>
            Register using your email and verify your account with a secure 6-digit code.
            <a href="../register.php">Create an account</a>
        </p>
    </div>

    <div class="step">
        <h2>2. Browse cars and spare parts</h2>
        <p>
            Explore admin-approved listings from trusted sellers.
            <a href="../cars.php">Browse Cars</a> or
            <a href="../spare-parts.php">Browse Spare Parts</a>.
        </p>
    </div>

    <div class="step">
        <h2>3. Add items to your cart</h2>
        <p>
            Add cars or spare parts to your cart and adjust quantities before checkout.
        </p>
    </div>

    <div class="step">
        <h2>4. Checkout securely</h2>
        <p>
            Enter your shipping details and complete your purchase securely.
        </p>
    </div>

    <div class="step">
        <h2>5. Track your orders</h2>
        <p>
            View your order history and order details from your dashboard.
            <a href="../dashboard/index.php">Go to Dashboard</a>
        </p>
    </div>

    <div class="step">
        <h2>6. Become a seller</h2>
        <p>
            List cars or spare parts and wait for admin approval before they go live.
            <a href="../seller/dashboard.php">Seller Dashboard</a>
        </p>
    </div>
</main>

</body>
</html>