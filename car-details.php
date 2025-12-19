<?php
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = (new Database())->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Images table uses image_url column
$isLoggedIn = isset($_SESSION['user_id']);

$carId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($carId <= 0) {
    die("Invalid car ID.");
}

// Load car with seller information using JOIN
$car = null;
$error = '';

try {
    $stmt = $db->prepare("
    SELECT
        c.car_id,
        c.brand,
        c.model,
        c.year,
        c.price,
        c.mileage,
        c.`condition`,
        c.body_type,
        c.transmission,
        c.fuel_type,
        c.color,
        c.engine_size,
        c.description,
        c.created_at,
        c.seller_id,
        u.first_name AS seller_first_name,
        u.last_name AS seller_last_name,
        u.email AS seller_email,
        u.phone_number AS seller_phone
    FROM cars c
    INNER JOIN users u ON c.seller_id = u.user_id
    WHERE c.car_id = :id
    AND c.status = 'available'
    AND c.approval_status = 'approved'
    LIMIT 1
");

    $stmt->execute([':id' => $carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$car) {
        $error = "This car is not available (it may be pending approval, sold, or removed).";
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Load images
$images = [];
$primaryImage = null;

if ($car) {
    try {
        $stmt = $db->prepare("
            SELECT image_url AS img, is_primary
            FROM images
            WHERE imageable_type = 'car' AND imageable_id = :id
            ORDER BY is_primary DESC, uploaded_at ASC
        ");
        $stmt->execute([':id' => $carId]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($images)) {
            $primaryImage = $images[0]['img']; // first is primary due to ORDER BY
        }
    } catch (Exception $e) {
        // ignore images errors (car page can still load)
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $car ? h($car['brand'].' '.$car['model']) : 'Car Details'; ?> - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;position:sticky;top:0;z-index:10;}
        .nav-links a{margin-right:1rem;font-size:0.9rem;color:#9ca3af;}
        .nav-links a:hover{color:#e5e7eb;}
        .btn-nav-primary{padding:0.4rem 0.9rem;border-radius:999px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .page{padding:1.5rem 7vw 2.5rem;max-width:1100px;margin:0 auto;}
        .error-box{background:#7f1d1d;padding:0.6rem 0.8rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.85rem;color:#fee2e2;}
        .grid{display:grid;grid-template-columns:1.2fr 0.8fr;gap:1.25rem;}
        @media (max-width: 900px){.grid{grid-template-columns:1fr;}}
        .card{background:#020617;border:1px solid #111827;border-radius:0.9rem;padding:1rem;}
        .title{font-size:1.5rem;font-weight:700;margin:0 0 0.25rem;}
        .muted{color:#9ca3af;font-size:0.9rem;margin:0 0 0.75rem;}
        .price{font-size:1.2rem;font-weight:700;margin:0.6rem 0 0.25rem;}
        .badge{display:inline-block;padding:0.12rem 0.5rem;border-radius:999px;border:1px solid #374151;font-size:0.75rem;margin-right:0.35rem;color:#9ca3af;}
        .main-img{width:100%;height:380px;object-fit:cover;border-radius:0.8rem;background:#111827;}
        .thumbs{display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;}
        .thumbs img{width:90px;height:70px;object-fit:cover;border-radius:0.6rem;border:1px solid #111827;cursor:pointer;}
        .specs{margin-top:0.75rem;display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;}
        .spec{font-size:0.9rem;color:#d1d5db;}
        .spec span{color:#9ca3af;}
        .desc{margin-top:0.75rem;color:#d1d5db;white-space:pre-wrap;font-size:0.92rem;line-height:1.5;}
        .btn{display:inline-block;padding:0.55rem 1.1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn-secondary{background:#111827;color:#e5e7eb;}
        .seller-line{color:#d1d5db;font-size:0.92rem;margin:0.35rem 0;}
        .small{font-size:0.8rem;color:#9ca3af;}
    </style>
    <script>
        function setMain(src){
            const img = document.getElementById('mainImage');
            if (img) img.src = src;
        }
    </script>
</head>
<body>

<header class="topbar">
    <div style="font-weight:700;">🚗 CarHub</div>
    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="cars.php">Browse Cars</a>
        <a href="spare-parts.php">Spare Parts</a>
        <a href="additional/how-it-works.php">How it works</a>
    </nav>
    <div>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard/index.php">Dashboard</a>
            <a href="logout.php" class="btn-nav-primary">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-nav-primary">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main class="page">
    <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
    <?php else: ?>

        <div class="grid">
            <!-- LEFT: Images + description -->
            <section class="card">
                <h1 class="title"><?php echo h($car['brand'].' '.$car['model']); ?></h1>
                <p class="muted">Year <?php echo h($car['year']); ?> · Listed <?php echo h(substr($car['created_at'],0,10)); ?></p>

                <?php if ($primaryImage): ?>
                    <img id="mainImage" class="main-img" src="<?php echo h($primaryImage); ?>" alt="Car image">
                <?php else: ?>
                    <div class="main-img"></div>
                <?php endif; ?>

                <?php if (!empty($images)): ?>
                    <div class="thumbs">
                        <?php foreach ($images as $img): ?>
                            <img src="<?php echo h($img['img']); ?>" alt="thumb"
                                 onclick="setMain('<?php echo h($img['img']); ?>')">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="specs">
                    <div class="spec"><span>Condition:</span> <?php echo h($car['condition']); ?></div>
                    <div class="spec"><span>Body type:</span> <?php echo h($car['body_type']); ?></div>
                    <div class="spec"><span>Transmission:</span> <?php echo h($car['transmission']); ?></div>
                    <div class="spec"><span>Fuel:</span> <?php echo h($car['fuel_type']); ?></div>
                    <div class="spec"><span>Mileage:</span> <?php echo (int)$car['mileage']; ?> km</div>
                    <div class="spec"><span>Color:</span> <?php echo h($car['color']); ?></div>
                    <div class="spec"><span>Engine:</span> <?php echo h($car['engine_size']); ?></div>
                </div>

                <?php if (!empty($car['description'])): ?>
                    <div class="desc"><?php echo h($car['description']); ?></div>
                <?php endif; ?>
            </section>

            <!-- RIGHT: Purchase + seller -->
            <aside class="card">
                <div class="price">₵<?php echo number_format((float)$car['price'], 2); ?></div>
                <div style="margin-bottom:0.6rem;">
                    <span class="badge"><?php echo ucfirst(h($car['condition'])); ?></span>
                    <span class="badge"><?php echo ucfirst(h($car['body_type'])); ?></span>
                </div>

                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-primary" href="cart/add.php?type=car&id=<?php echo (int)$car['car_id']; ?>">Add to cart</a>
                <?php else: ?>
                    <a class="btn btn-secondary" href="login.php">Login to buy</a>
                <?php endif; ?>

                <hr style="border:none;border-top:1px solid #111827;margin:1rem 0;">

                <h3 style="margin:0 0 0.5rem;">Seller</h3>
                <div class="seller-line"><?php echo h($car['seller_first_name'].' '.$car['seller_last_name']); ?></div>

                <?php if (!empty($car['seller_phone'])): ?>
                    <div class="seller-line"><span class="small">Phone:</span> <?php echo h($car['seller_phone']); ?></div>
                <?php endif; ?>

                <?php if (!empty($car['seller_email'])): ?>
                    <div class="seller-line"><span class="small">Email:</span> <?php echo h($car['seller_email']); ?></div>
                <?php endif; ?>

                <div class="small" style="margin-top:0.75rem;">
                    Tip: Always inspect the car in person before final payment.
                </div>

                <div style="margin-top:1rem;">
                    <a href="cars.php" class="small">← Back to browse</a>
                </div>
            </aside>
        </div>

    <?php endif; ?>
</main>

</body>
</html>