<?php
// car_hub/seller/add-car.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ✅ Correct redirect (no leading slash)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentRole   = $_SESSION['role'] ?? 'buyer';

// ✅ Only allow sellers/buyer_seller/admin
if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

// Images table uses image_url column

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Form fields
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

    // Validation
    if ($brand === '' || $model === '') $errors[] = "Brand and model are required.";
    if ($year < 1900) $errors[] = "Enter a valid year.";
    if ($price <= 0) $errors[] = "Price must be greater than zero.";

    // Require at least one image
    if (empty($_FILES['car_images']['name'][0])) {
        $errors[] = "Please upload at least one car photo.";
    }

    if (empty($errors)) {
        try {
            // Insert car listing
            $stmt = $db->prepare("
                INSERT INTO cars (
                    seller_id, brand, model, year, price, mileage,
                    `condition`, body_type, transmission, fuel_type,
                    color, engine_size, description,
                    `status`, approval_status, created_at
                ) VALUES (
                    :seller_id, :brand, :model, :year, :price, :mileage,
                    :cond, :body_type, :transmission, :fuel_type,
                    :color, :engine_size, :description,
                    'available', 'pending', NOW()
                )
            ");
            $stmt->execute([
                ':seller_id'    => $currentUserId,
                ':brand'        => $brand,
                ':model'        => $model,
                ':year'         => $year,
                ':price'        => $price,
                ':mileage'      => $mileage,
                ':cond'         => $condition,
                ':body_type'    => $body_type,
                ':transmission' => $transmission,
                ':fuel_type'    => $fuel_type,
                ':color'        => $color,
                ':engine_size'  => $engine_size,
                ':description'  => $description,
            ]);

            $carId = (int)$db->lastInsertId();

            // =============================
            // Upload Images
            // =============================
            $uploadDir = __DIR__ . '/../uploads/cars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedExt = ['jpg','jpeg','png','webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB per image

            foreach ($_FILES['car_images']['tmp_name'] as $i => $tmp) {

                if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) continue;

                $originalName = $_FILES['car_images']['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) continue;

                if ($_FILES['car_images']['size'][$i] > $maxSize) continue;

                $newName = 'car_' . $carId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target = $uploadDir . $newName;

                if (move_uploaded_file($tmp, $target)) {
                    $relative = 'uploads/cars/' . $newName;

                    // Insert image record using imageable_type and imageable_id
                    $imgStmt = $db->prepare("
                        INSERT INTO images (imageable_type, imageable_id, image_url, is_primary)
                        VALUES ('car', :imageable_id, :image_url, :is_primary)
                    ");
                    $imgStmt->execute([
                        ':imageable_id' => $carId,
                        ':image_url'    => $relative,
                        ':is_primary'   => ($i === 0 ? 1 : 0)
                    ]);
                }
            }

            $success = "Car listing created with images and sent for approval.";

        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Car - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem;border:1px solid #111827;max-width:680px;}
        .page-title{font-size:1.3rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.2rem;}
        .form-group{margin-bottom:0.85rem;}
        .form-group label{display:block;font-size:0.85rem;margin-bottom:0.25rem;color:#9ca3af;}
        input,select,textarea{
            width:100%;padding:0.45rem 0.6rem;border-radius:0.5rem;border:1px solid #111827;
            background:#020617;color:#e5e7eb;font-size:0.85rem;
        }
        textarea{min-height:90px;resize:vertical;}
        .form-row{display:flex;gap:0.75rem;}
        .form-row .form-group{flex:1;}
        .btn{padding:0.5rem 1rem;border-radius:999px;border:none;font-size:0.9rem;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#f9fafb;}
        .btn-primary:hover{filter:brightness(1.05);}
        .alert{padding:0.55rem 0.75rem;border-radius:0.5rem;font-size:0.85rem;margin-bottom:0.75rem;}
        .alert-error{background:#7f1d1d;color:#fee2e2;}
        .alert-success{background:#14532d;color:#bbf7d0;}
        small{display:block;margin-top:0.35rem;color:#9ca3af;font-size:0.78rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🚗 CarHub · <span style="color:#a5b4fc;">Seller</span></div>
    <div>
        <a href="dashboard.php">Seller dashboard</a> ·
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>Seller menu</h3>
        <ul>
            <li><a href="dashboard.php">Overview</a></li>
            <li><a href="my-listings.php">My listings</a></li>
            <li><a href="add-car.php">Add new car</a></li>
            <li><a href="add-spare-part.php">Add spare part</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">Add new car</h1>
        <p class="page-subtitle">Create a listing for a car. Admins will review before it goes live.</p>

        <section class="card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e) echo h($e) . "<br>"; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Brand *</label>
                        <input type="text" name="brand" required>
                    </div>
                    <div class="form-group">
                        <label>Model *</label>
                        <input type="text" name="model" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Year *</label>
                        <input type="number" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price (₵) *</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mileage (km)</label>
                        <input type="number" name="mileage">
                    </div>
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="new">New</option>
                            <option value="used" selected>Used</option>
                            <option value="refurbished">Refurbished</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Body type</label>
                        <select name="body_type">
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="coupe">Coupe</option>
                            <option value="hatchback">Hatchback</option>
                            <option value="truck">Truck</option>
                            <option value="van">Van</option>
                            <option value="convertible">Convertible</option>
                            <option value="wagon">Wagon</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <select name="transmission">
                            <option value="automatic" selected>Automatic</option>
                            <option value="manual">Manual</option>
                            <option value="semi-automatic">Semi-automatic</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fuel type</label>
                        <select name="fuel_type">
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color">
                    </div>
                </div>

                <div class="form-group">
                    <label>Engine size</label>
                    <input type="text" name="engine_size" placeholder="e.g. 1.8L, 2.0L, V6">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Add extra information about the car..."></textarea>
                </div>

                <div class="form-group">
                    <label>Car Images *</label>
                    <input type="file" name="car_images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                    <small>Upload clear photos. First image becomes the main image.</small>
                </div>

                <button type="submit" class="btn btn-primary">Create listing</button>
            </form>
        </section>
    </main>
</div>

</body>
</html>