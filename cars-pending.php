<?php
require_once 'auth.php'; // admin-only + $db + h()

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detect images path column name (image_path or file_path)
// Images table uses image_url column

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'], $_POST['action'])) {
    // Double-check admin role for security (defense in depth)
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
    
    $carId   = (int)$_POST['car_id'];
    $action  = $_POST['action'];
    $adminId = (int)($_SESSION['user_id'] ?? 0);

    if ($carId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $db->prepare("
            UPDATE cars
            SET approval_status = :status,
                approved_by = :admin_id,
                approval_date = NOW()
            WHERE car_id = :id
        ");
        $stmt->execute([
            ':status'   => $newStatus,
            ':admin_id' => $adminId,
            ':id'       => $carId
        ]);
    }

    header('Location: cars-pending.php');
    exit;
}

// Load pending cars
$stmt = $db->query("
    SELECT car_id, brand, model, year, price, seller_id, created_at
    FROM cars
    WHERE approval_status = 'pending'
    ORDER BY created_at DESC
");
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch primary images for these cars
$thumbs = [];
try {
    $carIds = [];
    foreach ($cars as $c) $carIds[] = (int)$c['car_id'];

    if (!empty($carIds)) {
        $placeholders = implode(',', array_fill(0, count($carIds), '?'));

        $imgSql = "SELECT imageable_id, image_url AS img
                   FROM images
                   WHERE imageable_type = 'car'
                     AND is_primary = 1
                     AND imageable_id IN ($placeholders)";
        $imgStmt = $db->prepare($imgSql);
        $imgStmt->execute($carIds);

        foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $thumbs[(int)$row['imageable_id']] = $row['img'];
        }
    }
} catch (Exception $e) {
    // ignore if images table has issues
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Cars - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;}a:hover{text-decoration:underline;}

        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}

        .sidebar{width:240px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.5rem 0.7rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}

        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}

        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem;border:1px solid #111827;margin-bottom:1rem;}

        table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        th,td{padding:0.5rem 0.4rem;border-bottom:1px solid #111827;text-align:left;vertical-align:middle;}

        .thumb{
            width:72px;height:52px;border-radius:0.5rem;object-fit:cover;
            background:#111827;border:1px solid #111827;
        }

        .btn{padding:0.35rem 0.7rem;border-radius:999px;border:none;font-size:0.8rem;cursor:pointer;}
        .btn-approve{background:#14532d;color:#bbf7d0;}
        .btn-reject{background:#7f1d1d;color:#fee2e2;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🛠️ CarHub Admin</div>
    <div>
        <a href="dashboard.php">Dashboard</a> ·
        <a href="../index.php">View site</a> ·
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="cars-pending.php">Pending Cars</a></li>
            <li><a href="parts-pending.php">Pending Parts</a></li>
            <li><a href="cars.php">All Cars</a></li>
            <li><a href="parts.php">All Parts</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="reviews.php">Reviews</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">Pending cars</h1>
        <p class="page-subtitle">Approve or reject car listings before they appear publicly.</p>

        <section class="card">
            <?php if (empty($cars)): ?>
                <p style="color:#9ca3af;">No pending cars at the moment.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>ID</th>
                        <th>Car</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Seller ID</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cars as $c): ?>
                        <?php
                            $cid = (int)$c['car_id'];
                            $img = isset($thumbs[$cid]) ? $thumbs[$cid] : null;
                        ?>
                        <tr>
                            <td>
                                <?php if ($img): ?>
                                    <img class="thumb" src="<?php echo h($img); ?>" alt="car">
                                <?php else: ?>
                                    <div class="thumb"></div>
                                <?php endif; ?>
                            </td>
                            <td>#<?php echo $cid; ?></td>
                            <td><?php echo h($c['brand'].' '.$c['model']); ?></td>
                            <td><?php echo (int)$c['year']; ?></td>
                            <td>₵<?php echo number_format((float)$c['price'], 2); ?></td>
                            <td><?php echo (int)$c['seller_id']; ?></td>
                            <td><?php echo h(substr($c['created_at'], 0, 10)); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="car_id" value="<?php echo $cid; ?>">
                                    <button class="btn btn-approve" name="action" value="approve">Approve</button>
                                </form>
                                <form method="post" style="display:inline;margin-left:0.25rem;">
                                    <input type="hidden" name="car_id" value="<?php echo $cid; ?>">
                                    <button class="btn btn-reject" name="action" value="reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>