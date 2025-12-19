<?php
// seller/edit-spare-part.php
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

$partId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($partId <= 0) {
    die("Invalid part ID.");
}

$errors = [];
$success = '';
$part = null;

try {
    $sql = "SELECT * FROM spare_parts WHERE spare_part_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $partId]);
    $part = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$part) {
        die("Part not found.");
    }

    if ($currentRole !== 'admin' && (int)$part['seller_id'] !== $currentUserId) {
        die("You are not allowed to edit this listing.");
    }
} catch (Exception $e) {
    die("Error loading part.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name              = trim($_POST['name'] ?? '');
    $category          = trim($_POST['category'] ?? '');
    $compatible_brands = trim($_POST['compatible_brands'] ?? '');
    $compatible_models = trim($_POST['compatible_models'] ?? '');
    $condition         = $_POST['condition'] ?? 'used';
    $price             = (float)($_POST['price'] ?? 0);
    $quantity          = (int)($_POST['quantity'] ?? 0);
    $description       = trim($_POST['description'] ?? '');
    $status            = $_POST['status'] ?? $part['status'];

    if ($name === '') {
        $errors[] = "Part name is required.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero.";
    }
    if ($quantity < 0) {
        $errors[] = "Quantity cannot be negative.";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE spare_parts SET
                        name = :name,
                        category = :category,
                        compatible_brands = :comp_brands,
                        compatible_models = :comp_models,
                        `condition` = :cond,
                        price = :price,
                        quantity = :qty,
                        description = :description,
                        `status` = :status
                    WHERE spare_part_id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name'        => $name,
                ':category'    => $category,
                ':comp_brands' => $compatible_brands,
                ':comp_models' => $compatible_models,
                ':cond'        => $condition,
                ':price'       => $price,
                ':qty'         => $quantity,
                ':description' => $description,
                ':status'      => $status,
                ':id'          => $partId,
            ]);

            $success = "Spare part listing updated successfully.";

            $stmt = $db->prepare("SELECT * FROM spare_parts WHERE spare_part_id = :id");
            $stmt->execute([':id' => $partId]);
            $part = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Edit Spare Part #<?php echo $partId; ?> - CarHub</title>
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
        .form-group input,.form-group textarea,.form-group select{
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
    <div>🧩 CarHub · <span style="color:#a5b4fc;">Edit spare part</span></div>
    <div>
  <a href="my-listings.php">My listings</a> ·
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
        <h1 class="page-title">Edit spare part #<?php echo $partId; ?></h1>
        <p class="page-subtitle"><?php echo h($part['name']); ?></p>

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
                <div class="form-group">
                    <label>Part name *</label>
                    <input type="text" name="name" value="<?php echo h($part['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo h($part['category']); ?>">
                </div>

                <div class="form-group">
                    <label>Compatible brands</label>
                    <input type="text" name="compatible_brands" value="<?php echo h($part['compatible_brands']); ?>">
                </div>

                <div class="form-group">
                    <label>Compatible models</label>
                    <input type="text" name="compatible_models" value="<?php echo h($part['compatible_models']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="new"        <?php if ($part['condition']==='new') echo 'selected'; ?>>New</option>
                            <option value="used"       <?php if ($part['condition']==='used') echo 'selected'; ?>>Used</option>
                            <option value="refurbished"<?php if ($part['condition']==='refurbished') echo 'selected'; ?>>Refurbished</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (₵) *</label>
                        <input type="number" step="0.01" name="price" value="<?php echo h($part['price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" value="<?php echo h($part['quantity']); ?>" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo h($part['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available" <?php if ($part['status']==='available') echo 'selected'; ?>>Available</option>
                        <option value="pending"   <?php if ($part['status']==='pending') echo 'selected'; ?>>Pending</option>
                        <option value="removed"   <?php if ($part['status']==='removed') echo 'selected'; ?>>Removed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>
