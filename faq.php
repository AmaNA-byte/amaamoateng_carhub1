<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - CarHub</title>
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
        .faq-item{margin-bottom:1.5rem;border-bottom:1px solid #111827;padding-bottom:1rem;}
        .question{font-weight:600;font-size:1rem;margin-bottom:0.3rem;}
        .answer{font-size:0.95rem;color:#9ca3af;line-height:1.7;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">❓ CarHub FAQ</div>
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
    <h1>Frequently Asked Questions</h1>

    <div class="faq-item">
        <div class="question">How do I create an account?</div>
        <div class="answer">
            Click “Get Started” in the top-right corner, fill in your details, and verify your email with the 6-digit code we send you.
        </div>
    </div>

    <div class="faq-item">
        <div class="question">Can I be both a buyer and a seller?</div>
        <div class="answer">
            Yes. When registering, you can choose a role like buyer, seller, or both. Your dashboard will show tools for your role.
        </div>
    </div>

    <div class="faq-item">
        <div class="question">Are all cars on CarHub verified?</div>
        <div class="answer">
            All car and spare part listings must be approved by an admin before appearing in the “Browse” pages. 
            We still recommend you inspect the car in person before paying.
        </div>
    </div>

    <div class="faq-item">
        <div class="question">How does the cart and checkout work?</div>
        <div class="answer">
            When you find a car or part, click “Add to cart”. You can view all items in “My cart” and then proceed to checkout, 
            where you enter your shipping details and confirm the order.
        </div>
    </div>

    <div class="faq-item">
        <div class="question">What if I have a problem with a seller?</div>
        <div class="answer">
            First try to discuss directly with the seller using the contact details on their listing. If the issue is serious 
            (fraud, fake listing, etc.), you can report it using the contact form and our admins can review the account.
        </div>
    </div>
</main>

</body>
</html>