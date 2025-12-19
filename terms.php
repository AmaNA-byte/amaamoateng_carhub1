<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms & Conditions - CarHub</title>
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
        p{font-size:0.95rem;color:#9ca3af;line-height:1.7;}
        li{font-size:0.95rem;color:#9ca3af;line-height:1.6;margin-bottom:0.4rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">📜 CarHub Terms</div>
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
    <h1>Terms & Conditions</h1>
    <p>These Terms govern your use of the CarHub platform. By using this site, you agree to comply with them.</p>

    <h2>1. Use of the platform</h2>
    <ul>
        <li>You must provide accurate information when creating an account.</li>
        <li>You are responsible for all activity under your account.</li>
        <li>You agree not to misuse the site, attempt fraud, or harm other users.</li>
    </ul>

    <h2>2. Listings & content</h2>
    <ul>
        <li>Sellers are responsible for the accuracy of their car and spare part listings.</li>
        <li>Admins may remove any listing that is misleading, unsafe, or inappropriate.</li>
        <li>You must only upload content you have the right to use.</li>
    </ul>

    <h2>3. Payments & transactions</h2>
    <ul>
        <li>CarHub helps buyers and sellers connect but is not a party to private sales.</li>
        <li>Any payment gateways you use (like Paystack) may have their own terms.</li>
        <li>Disputes between buyers and sellers should first be handled directly between them.</li>
    </ul>

    <h2>4. Limitation of liability</h2>
    <p>
        CarHub is provided “as is”. We do not guarantee that every listing is accurate or that every seller is perfect.  
        We are not responsible for any loss, damage, or disputes that arise from using the platform.
    </p>

    <h2>5. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. When we do, we will update the date on this page.
        Your continued use of CarHub means you accept the updated Terms.
    </p>
</main>

</body>
</html>