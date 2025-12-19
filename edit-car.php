<?php
// seller/edit-car.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$currentRole   = $_SESSION['role'] ?? 'buyer';

if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: /dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

$carId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($carId <= 0) {
    die("Invalid car ID.");
}

$errors = [];
$success = '';
$car = null;

// Fetch car and ensure ownership (unless admin)
try {
    $sql = "SELECT * FROM cars WHERE car_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$car) {
        die("Car not found.");
    }

    if ($currentRole !== 'admin' && (int)$car['seller_id'] !== $currentUserId) {
        die("You are not allowed to edit this listing.");
    }
} catch (Exception $e) {
    die("Error loading car.");
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand        = trim($_POST['brand'] ?? '');
    $model        = trim($_POST['model'] ?? '');
    $year         = (int)($_POST['year'] ?? 0);
    $price        = (float)($_POST['price'] ?? 0);
    $mileage      = (int)($_POST['mileage'] ?? 0);
    $condition    = $_POST['condition'] ?? 'used';
    $body_type    = $_POST['body_type'] ?? 'sedan';
    $transmission = $_POST['transmission'] ?? 'automatic';
    $fuel_type    = $_POST['fuel_type'] ?? 'petrol';
    $color        = trim($_POST['color'] ?? '');
    $engine_size  = trim($_POST['engine_size'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $status       = $_POST['status'] ?? $car['status'];

    if ($brand === '' || $model === '') {
        $errors[] = "Brand and model are required.";
    }
    if ($year < 1900) {
        $errors[] = "Enter a valid year.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero.";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE cars SET
                        brand = :brand,
                        model = :model,
                        year = :year,
                        price = :price,
                        mileage = :mileage,
                        `condition` = :cond,
                        body_type = :body_type,
                        transmission = :transmission,
                        fuel_type = :fuel_type,
                        color = :color,
                        engine_size = :engine_size,
                        description = :description,
                        `status` = :status
                    WHERE car_id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':brand'       => $brand,
                ':model'       => $model,
                ':year'        => $year,
                ':price'       => $price,
                ':mileage'     => $mileage,
                ':cond'        => $condition,
                ':body_type'   => $body_type,
                ':transmission'=> $transmission,
                ':fuel_type'   => $fuel_type,
                ':color'       => $color,
                ':engine_size' => $engine_size,
                ':description' => $description,
                ':status'      => $status,
                ':id'          => $carId,
            ]);

            $success = "Car listing updated successfully.";
            // Refresh car data
            $stmt = $db->prepare("SELECT * FROM cars WHERE car_id = :id");
            $stmt->execute([':id' => $carId]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Car #<?php echo $carId; ?> - CarHub</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{width:220px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.4rem 0.6rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}
        .main{flex:1;padding:1.75rem 2rem;}
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;max-width:640px;}
        .page-title{font-size:1.3rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}
        .form-group{margin-bottom:0.85rem;}
        .form-group label{display:block;font-size:0.85rem;margin-bottom:0.25rem;}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:0.45rem 0.6rem;border-radius:0.5rem;border:1px solid #111827;background:#020617;color:#e5e7eb;font-size:0.85rem;
        }
        textarea{min-height:80px;resize:vertical;}
        .form-row{display:flex;gap:0.75rem;}
        .form-row .form-group{flex:1;}
        .btn{padding:0.5rem 1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#f9fafb;}
        .btn-primary:hover{filter:brightness(1.05);}
        .alert{padding:0.45rem 0.7rem;border-radius:0.5rem;font-size:0.82rem;margin-bottom:0.75rem;}
        .alert-error{background:#7f1d1d;color:#fee2e2;}
        .alert-success{background:#14532d;color:#bbf7d0;}
    </style>
</head>
<body>
<header class="topbar">
    <div>🚗 CarHub · <span style="color:#a5b4fc;">Edit car</span></div>
    <div><a href="/seller/my-listings.php">My listings</a> · <a href="../logout.php">Logout</a></div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>Seller menu</h3>
        <ul>
            <li><a href="/seller/dashboard.php">Overview</a></li>
            <li><a href="/seller/my-listings.php">My listings</a></li>
            <li><a href="/seller/add-car.php">Add new car</a></li>
            <li><a href="/seller/add-spare-part.php">Add spare part</a></li>
            <li><a href="/seller/orders.php">Orders</a></li>
            <li><a href="/seller/reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">Edit car #<?php echo $carId; ?></h1>
        <p class="page-subtitle"><?php echo h($car['brand'].' '.$car['model']); ?></p>

        <section class="card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e) echo h($e) . "<br>"; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Brand *</label>
                        <input type="text" name="brand" value="<?php echo h($car['brand']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Model *</label>
                        <input type="text" name="model" value="<?php echo h($car['model']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Year *</label>
                        <input type="number" name="year" value="<?php echo h($car['year']); ?>" min="1900" max="<?php echo date('Y') + 1; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price (₵) *</label>
                        <input type="number" step="0.01" name="price" value="<?php echo h($car['price']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mileage (km)</label>
                        <input type="number" name="mileage" value="<?php echo h($car['mileage']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="new"        <?php if ($car['condition']==='new') echo 'selected'; ?>>New</option>
                            <option value="used"       <?php if ($car['condition']==='used') echo 'selected'; ?>>Used</option>
                            <option value="refurbished"<?php if ($car['condition']==='refurbished') echo 'selected'; ?>>Refurbished</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Body type</label>
                        <select name="body_type">
                            <?php
                            $bodyTypes = ['sedan','suv','coupe','hatchback','truck','van','convertible','wagon'];
                            foreach ($bodyTypes as $bt): ?>
                                <option value="<?php echo $bt; ?>" <?php if ($car['body_type']===$bt) echo 'selected'; ?>>
                                    <?php echo ucfirst($bt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <select name="transmission">
                            <option value="automatic"      <?php if ($car['transmission']==='automatic') echo 'selected'; ?>>Automatic</option>
                            <option value="manual"         <?php if ($car['transmission']==='manual') echo 'selected'; ?>>Manual</option>
                            <option value="semi-automatic" <?php if ($car['transmission']==='semi-automatic') echo 'selected'; ?>>Semi-automatic</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fuel type</label>
                        <select name="fuel_type">
                            <option value="petrol"  <?php if ($car['fuel_type']==='petrol') echo 'selected'; ?>>Petrol</option>
                            <option value="diesel"  <?php if ($car['fuel_type']==='diesel') echo 'selected'; ?>>Diesel</option>
                            <option value="electric"<?php if ($car['fuel_type']==='electric') echo 'selected'; ?>>Electric</option>
                            <option value="hybrid"  <?php if ($car['fuel_type']==='hybrid') echo 'selected'; ?>>Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="<?php echo h($car['color']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Engine size</label>
                    <input type="text" name="engine_size" value="<?php echo h($car['engine_size']); ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo h($car['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available" <?php if ($car['status']==='available') echo 'selected'; ?>>Available</option>
                        <option value="pending"   <?php if ($car['status']==='pending') echo 'selected'; ?>>Pending</option>
                        <option value="sold"      <?php if ($car['status']==='sold') echo 'selected'; ?>>Sold</option>
                        <option value="removed"   <?php if ($car['status']==='removed') echo 'selected'; ?>>Removed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>
