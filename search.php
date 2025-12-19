<?php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$db = $database->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$q = trim($_GET['q'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);

$cars  = [];
$parts = [];
$error = '';

if ($q !== '') {
    try {
        // search cars
        $sql = "SELECT c.car_id, c.brand, c.model, c.year, c.price, c.`condition`
                FROM cars c
                INNER JOIN users u ON c.seller_id = u.user_id
                WHERE (c.brand LIKE :q OR c.model LIKE :q)
                AND c.status = 'available'
                AND c.approval_status = 'approved'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':q' => "%{$q}%"]);
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // search spare parts
        $sql = "SELECT sp.spare_part_id, sp.name, sp.category, sp.price, sp.`condition`
                FROM spare_parts sp
                INNER JOIN users u ON sp.seller_id = u.user_id
                WHERE (sp.name LIKE :q 
                   OR sp.category LIKE :q 
                   OR sp.compatible_models LIKE :q 
                   OR sp.compatible_brands LIKE :q)
                AND sp.status = 'available'
                AND sp.approval_status = 'approved'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':q' => "%{$q}%"]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;border-bottom:1px solid #111827;background:#020617;}
        .logo{font-weight:700;font-size:1.2rem;}
        .nav-links a{margin-right:1rem;color:#9ca3af;font-size:0.9rem;}
        .nav-links a:hover{color:#e5e7eb;}
        .btn-nav-primary{padding:0.4rem 0.9rem;border-radius:999px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .page{padding:2rem 10vw 3rem;}
        .search-box{margin-bottom:1.5rem;}
        .search-box input{width:100%;max-width:400px;padding:0.6rem 0.8rem;border-radius:999px;border:1px solid #111827;background:#020617;color:#e5e7eb;}
        .section-title{margin-top:2rem;font-size:1.1rem;color:#a5b4fc;}
        .result-list{list-style:none;padding:0;margin:0.5rem 0;}
        .result-item{padding:0.6rem 0;border-bottom:1px solid #111827;}
        .result-title{font-weight:600;}
        .result-meta{font-size:0.85rem;color:#9ca3af;}
        .btn-small{display:inline-block;margin-top:0.3rem;padding:0.3rem 0.7rem;border-radius:999px;border:none;font-size:0.8rem;cursor:pointer;background:#111827;color:#e5e7eb;}
        .btn-primary-small{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .error-box{background:#7f1d1d;padding:0.6rem 0.8rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.85rem;color:#fee2e2;}
    </style>
</head>
<body>

<header class="topbar">
    <div class="logo">🔍 CarHub Search</div>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../cars.php">Browse Cars</a>
        <a href="../spare-parts.php">Spare Parts</a>
        <a href="how-it-works.php">How it works</a>
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
    <form class="search-box" method="get">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Search cars or spare parts..." />
    </form>

    <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
    <?php endif; ?>

    <?php if ($q === ''): ?>
        <p style="color:#9ca3af;">Start typing to search for a car or spare part.</p>
    <?php else: ?>
        <h2 class="section-title">Cars matching "<?php echo h($q); ?>"</h2>
        <?php if (empty($cars)): ?>
            <p style="color:#6b7280;">No cars found.</p>
        <?php else: ?>
            <ul class="result-list">
                <?php foreach ($cars as $car): ?>
                    <li class="result-item">
                        <div class="result-title">
                            <?php echo h($car['brand'].' '.$car['model']); ?> (<?php echo (int)$car['year']; ?>)
                        </div>
                        <div class="result-meta">
                            Condition: <?php echo h($car['condition']); ?> · 
                            Price: ₵<?php echo number_format($car['price'], 2); ?>
                        </div>
                        <a href="../car-details.php?id=<?php echo (int)$car['car_id']; ?>" class="btn-small">View details</a>
                        <?php if ($isLoggedIn): ?>
                            <a href="../cart/add.php?type=car&id=<?php echo (int)$car['car_id']; ?>" class="btn-small btn-primary-small">Add to cart</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 class="section-title">Spare parts matching "<?php echo h($q); ?>"</h2>
        <?php if (empty($parts)): ?>
            <p style="color:#6b7280;">No spare parts found.</p>
        <?php else: ?>
            <ul class="result-list">
                <?php foreach ($parts as $p): ?>
                    <li class="result-item">
                        <div class="result-title">
                            <?php echo h($p['name']); ?> (<?php echo h($p['category']); ?>)
                        </div>
                        <div class="result-meta">
                            Condition: <?php echo h($p['condition']); ?> · 
                            Price: ₵<?php echo number_format($p['price'], 2); ?>
                        </div>
                        <a href="../spare-part-details.php?id=<?php echo (int)$p['spare_part_id']; ?>" class="btn-small">View details</a>
                        <?php if ($isLoggedIn): ?>
                            <a href="../cart/add.php?type=spare_part&id=<?php echo (int)$p['spare_part_id']; ?>" class="btn-small btn-primary-small">Add to cart</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>