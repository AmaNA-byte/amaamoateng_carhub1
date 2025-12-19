<?php
// car_hub/seller/add-spare-part.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ✅ Redirect correctly (no leading slash)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentRole   = isset($_SESSION['role']) ? $_SESSION['role'] : 'buyer';

// ✅ Only allow seller roles
if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

// Images table uses image_url column

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name              = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $category          = trim(isset($_POST['category']) ? $_POST['category'] : '');
    $compatible_brands = trim(isset($_POST['compatible_brands']) ? $_POST['compatible_brands'] : '');
    $compatible_models = trim(isset($_POST['compatible_models']) ? $_POST['compatible_models'] : '');
    $condition         = isset($_POST['condition']) ? $_POST['condition'] : 'used';
    $price             = (float)(isset($_POST['price']) ? $_POST['price'] : 0);
    $quantity          = (int)(isset($_POST['quantity']) ? $_POST['quantity'] : 0);
    $description       = trim(isset($_POST['description']) ? $_POST['description'] : '');

    if ($name === '') $errors[] = "Part name is required.";
    if ($price <= 0) $errors[] = "Price must be greater than zero.";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative.";

    // Require at least one image
    if (empty($_FILES['part_images']['name'][0])) {
        $errors[] = "Please upload at least one spare part photo.";
    }

    if (empty($errors)) {
        try {
            // Insert spare part
            $stmt = $db->prepare("
                INSERT INTO spare_parts (
                    seller_id,
                    name,
                    category,
                    compatible_brands,
                    compatible_models,
                    price,
                    `condition`,
                    quantity,
                    description,
                    `status`,
                    approval_status,
                    created_at
                ) VALUES (
                    :seller_id,
                    :name,
                    :category,
                    :compatible_brands,
                    :compatible_models,
                    :price,
                    :cond,
                    :qty,
                    :description,
                    'available',
                    'pending',
                    NOW()
                )
            ");
            $stmt->execute([
                ':seller_id'         => $currentUserId,
                ':name'              => $name,
                ':category'          => $category,
                ':compatible_brands' => $compatible_brands,
                ':compatible_models' => $compatible_models,
                ':price'             => $price,
                ':cond'              => $condition,
                ':qty'               => $quantity,
                ':description'       => $description,
            ]);

            $partId = (int)$db->lastInsertId();

            // Upload images
            $uploadDir = __DIR__ . '/../uploads/spare-parts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedExt = ['jpg','jpeg','png','webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            foreach ($_FILES['part_images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['part_images']['error'][$i] !== UPLOAD_ERR_OK) continue;

                $originalName = $_FILES['part_images']['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) continue;

                if ($_FILES['part_images']['size'][$i] > $maxSize) continue;

                $newName = 'part_' . $partId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target = $uploadDir . $newName;

                if (move_uploaded_file($tmp, $target)) {
                    $relative = 'uploads/spare-parts/' . $newName;

                    // Insert image record using imageable_type and imageable_id
                    $imgStmt = $db->prepare("
                        INSERT INTO images (imageable_type, imageable_id, image_url, is_primary)
                        VALUES ('spare_part', :imageable_id, :image_url, :is_primary)
                    ");
                    $imgStmt->execute([
                        ':imageable_id' => $partId,
                        ':image_url'    => $relative,
                        ':is_primary'   => ($i === 0 ? 1 : 0)
                    ]);
                }
            }

            $success = "Spare part listing created with images and sent for approval.";
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
    <title>Add Spare Part - CarHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
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
        .alert{padding:0.55rem 0.75rem;border-radius:0.5rem;font-size:0.85rem;margin-bottom:0.75rem;}
        .alert-error{background:#7f1d1d;color:#fee2e2;}
        .alert-success{background:#14532d;color:#bbf7d0;}
        small{display:block;margin-top:0.35rem;color:#9ca3af;font-size:0.78rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🧩 CarHub · <span style="color:#a5b4fc;">Seller</span></div>
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
        <h1 class="page-title">Add spare part</h1>
        <p class="page-subtitle">Create a spare part listing. Admins will review before it goes live.</p>

        <section class="card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error"><?php foreach ($errors as $e) echo h($e)."<br>"; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Part name *</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g. Brakes, Engine, Suspension">
                </div>

                <div class="form-group">
                    <label>Compatible brands</label>
                    <input type="text" name="compatible_brands" placeholder="e.g. Toyota, Hyundai">
                </div>

                <div class="form-group">
                    <label>Compatible models</label>
                    <input type="text" name="compatible_models" placeholder="e.g. Corolla 2014–2018">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="new">New</option>
                            <option value="used" selected>Used</option>
                            <option value="refurbished">Refurbished</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (₵) *</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>

                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" value="1" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>

                <div class="form-group">
                    <label>Spare Part Images *</label>
                    <input type="file" name="part_images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                    <small>First image becomes the main image.</small>
                </div>

                <button type="submit" class="btn btn-primary">Create listing</button>
            </form>
        </section>
    </main>
</div>

</body>
</html>