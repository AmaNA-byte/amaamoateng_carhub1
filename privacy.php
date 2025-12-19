<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;border-bottom:1px solid #111827;background:#020617;}
        .logo{font-weight:700;font-size:1.2rem;}
        .nav-links a{margin-right:1rem;color:#9ca3af;font-size:0.9rem;}
        .nav-links a:hover{color:#e5e7eb;}
        .btn-nav-primary{padding:0.4rem 0.9rem;border-radius:999px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .page{padding:3rem 10vw 4rem;max-width:900px;margin:0 auto;}
        h1{font-size:2rem;margin-bottom:0.5rem;}
        h2{font-size:1.2rem;margin-top:2rem;color:#a5b4fc;}
        p,li{font-size:0.95rem;color:#9ca3af;line-height:1.7;}
        ul{margin-left:1.2rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">🔒 CarHub Privacy</div>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../cars.php">Browse Cars</a>
        <a href="../spare-parts.php">Spare Parts</a>
        <a href="how-it-works.php">How it works</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </nav>
    <div>
        <?php if ($isLoggedIn): ?>
            <a href="../dashboard/index.php">Dashboard</a>
            <a href="../logout.php" class="btn-nav-primary">Logout</a>
        <?php else: ?>
            <a href="../login.php">Login</a>
            <a href="../register.php" class="btn-nav-primary">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main class="page">
    <h1>Privacy Policy</h1>
    <p>This Privacy Policy explains how CarHub collects, uses, and protects your personal information.</p>

    <h2>1. Information we collect</h2>
    <ul>
        <li>Account details (name, email, phone number, password hash).</li>
        <li>Listing information when you post cars or spare parts.</li>
        <li>Basic device and usage information (e.g., pages visited, actions taken).</li>
    </ul>

    <h2>2. How we use your information</h2>
    <ul>
        <li>To create and manage your CarHub account.</li>
        <li>To show your listings and connect you with buyers or sellers.</li>
        <li>To improve the platform and support you when you need help.</li>
    </ul>

    <h2>3. Who can see your information</h2>
    <ul>
        <li>Other users may see limited profile information (like name and phone) on listings.</li>
        <li>Administrators can see more details to manage the platform and handle issues.</li>
        <li>We do not sell your personal information to third parties.</li>
    </ul>

    <h2>4. Security</h2>
    <p>
        We use reasonable technical measures to keep your account and data safe. However, no system is 100% secure, 
        so you should also keep your password safe and log out on shared devices.
    </p>

    <h2>5. Your choices</h2>
    <ul>
        <li>You can update your name and password from your dashboard.</li>
        <li>You can request that we deactivate your account if you no longer wish to use CarHub.</li>
    </ul>
</main>

</body>
</html>