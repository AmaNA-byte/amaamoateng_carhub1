<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;border-bottom:1px solid #111827;}
        .logo{font-weight:700;font-size:1.2rem;}
        .page{padding:3rem 10vw 4rem;}
        .form-group{margin-bottom:1.2rem;}
        input,textarea{
            width:100%;padding:0.8rem;border-radius:0.6rem;border:1px solid #111827;
            background:#020617;color:#e5e7eb;font-size:1rem;
        }
        textarea{min-height:120px;}
        .btn-primary{
            padding:0.7rem 1.4rem;border-radius:999px;border:none;
            background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;cursor:pointer;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">📞 Contact CarHub</div>
    <div>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard/index.php">Dashboard</a>
            <a href="logout.php" class="btn-primary">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main class="page">
    <h1>We’d love to hear from you</h1>
    <p style="color:#9ca3af;">Got a question, complaint, or suggestion? Send us a message below.</p>

    <form method="post" action="#">
        <div class="form-group">
            <label>Your Name</label>
            <input type="text" required>
        </div>

        <div class="form-group">
            <label>Your Email</label>
            <input type="email" required>
        </div>

        <div class="form-group">
            <label>Your Message</label>
            <textarea required></textarea>
        </div>

        <button class="btn-primary">Send Message</button>
    </form>
</main>

</body>
</html>