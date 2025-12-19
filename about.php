<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}
        a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;border-bottom:1px solid #111827;background:#020617;}
        .logo{font-weight:700;font-size:1.2rem;}
        .nav-links a{margin-right:1rem;color:#9ca3af;font-size:0.9rem;}
        .nav-links a:hover{color:#e5e7eb;}
        .btn-nav-primary{padding:0.4rem 0.9rem;border-radius:999px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .page{padding:3rem 10vw 4rem;}
        h1{font-size:2rem;margin-bottom:0.5rem;}
        p{font-size:1rem;color:#9ca3af;line-height:1.6;}
        .section{margin-top:2.5rem;}
        .highlight{color:#a5b4fc;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">🚗 CarHub</div>
    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="cars.php">Browse Cars</a>
        <a href="spare-parts.php">Spare Parts</a>
        <a href="how-it-works.php">How it works</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </nav>
    <div>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard/index.php" class="highlight">Dashboard</a>
            <a href="logout.php" class="btn-nav-primary">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-nav-primary">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main class="page">
    <h1>About <span class="highlight">CarHub</span></h1>
    <p>
        CarHub is a modern Ghanaian marketplace designed to make buying and selling cars and spare parts 
        simple, transparent, and safe. Whether you're a first-time buyer, a dealer, or an independent seller, 
        CarHub helps you connect with verified users and trusted sellers.
    </p>

    <div class="section">
        <h2 class="highlight">Our Mission</h2>
        <p>
            To create the most reliable, user-friendly automotive marketplace in Ghana — where transparency, 
            fair pricing, and convenience come first.
        </p>
    </div>

    <div class="section">
        <h2 class="highlight">Why CarHub?</h2>
        <p>
            ✦ Verified sellers & approved listings <br>
            ✦ Secure transactions & digital marketplace <br>
            ✦ Easy-to-use shopping cart and checkout <br>
            ✦ Modern dashboard for buyers, sellers, and admins <br>
            ✦ Trusted by students, mechanics, dealers, and private sellers nationwide
        </p>
    </div>
</main>
</body>
</html>