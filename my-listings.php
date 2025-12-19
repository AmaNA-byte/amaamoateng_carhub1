<?php
// seller/my-listings.php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentRole   = $_SESSION['role'] ?? 'buyer';

if (!in_array($currentRole, ['seller', 'buyer_seller', 'admin'], true)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$db = (new Database())->getConnection();

// Images table uses image_url column

// Load seller cars
$cars = [];
$parts = [];
try {
    $stmt = $db->prepare("
        SELECT car_id, brand, model, year, price, status, approval_status, created_at
        FROM cars
        WHERE seller_id = :sid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':sid' => $currentUserId]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // show error instead of blank
    die("Cars load error: " . h($e->getMessage()));
}

// Load seller spare parts
try {
    $stmt = $db->prepare("
        SELECT spare_part_id, name, category, price, quantity, status, approval_status, created_at
        FROM spare_parts
        WHERE seller_id = :sid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':sid' => $currentUserId]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Spare parts load error: " . h($e->getMessage()));
}

// Fetch primary images for cars + spare parts
$carThumbs = [];
$partThumbs = [];

function fetchPrimaryThumbs(PDO $db, $imageableType, $ids) {
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT imageable_id, image_url AS img
            FROM images
            WHERE imageable_type = ?
              AND is_primary = 1
              AND imageable_id IN ($placeholders)";
    $stmt = $db->prepare($sql);

    // imageable_type is first param, then ids
    $params = array_merge([$imageableType], $ids);
    $stmt->execute($params);

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['imageable_id']] = $row['img'];
    }
    return $map;
}

$carIds = [];
foreach ($cars as $c) $carIds[] = (int)$c['car_id'];

$partIds = [];
foreach ($parts as $p) $partIds[] = (int)$p['spare_part_id'];

try {
    $carThumbs  = fetchPrimaryThumbs($db, 'car', $carIds);
    $partThumbs = fetchPrimaryThumbs($db, 'spare_part', $partIds);
} catch (Exception $e) {
    // if images table has issues, listings still show
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Listings - Seller</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;} a:hover{text-decoration:underline;}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;}
        .layout{display:flex;min-height:calc(100vh - 52px);}
        .sidebar{width:220px;background:#020617;border-right:1px solid #111827;padding:1.5rem 1rem;font-size:0.9rem;}
        .sidebar h3{font-size:0.9rem;color:#9ca3af;margin-bottom:0.5rem;}
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.4rem 0.6rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}
        .main{flex:1;padding:1.75rem 2rem;}
        h1{margin:0 0 0.5rem;}
        .muted{color:#9ca3af;margin:0 0 1.25rem;}
        .card{background:#020617;border:1px solid #111827;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;}
        .section-title{font-size:1.05rem;margin:0 0 0.75rem;color:#e5e7eb;}
        table{width:100%;border-collapse:collapse;font-size:0.86rem;}
        th,td{padding:0.55rem 0.45rem;border-bottom:1px solid #111827;text-align:left;vertical-align:middle;}
        .thumb{width:72px;height:52px;border-radius:0.5rem;object-fit:cover;background:#111827;border:1px solid #111827;}
        .badge{display:inline-block;padding:0.12rem 0.45rem;border-radius:999px;border:1px solid #374151;font-size:0.72rem;color:#9ca3af;}
        .actions a{margin-right:0.6rem;}
    </style>
</head>
<body>

<header class="topbar">
    <div>🏪 CarHub · <span style="color:#a5b4fc;">Seller</span></div>
    <div>
        <a href="dashboard.php">Dashboard</a> ·
        <a href="../index.php">Home</a> ·
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
        <h1>My listings</h1>
        <p class="muted">See your cars and spare parts, their approval status, and quick actions.</p>

        <!-- Cars -->
        <section class="card">
            <h2 class="section-title">Cars</h2>

            <?php if (empty($cars)): ?>
                <p class="muted">You haven’t listed any cars yet. <a href="add-car.php">Add a car</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Car</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cars as $c): ?>
                        <?php
                            $cid = (int)$c['car_id'];
                            $img = isset($carThumbs[$cid]) ? $carThumbs[$cid] : null;
                        ?>
                        <tr>
                            <td>
                                <?php if ($img): ?>
                                    <img class="thumb" src="<?php echo h($img); ?>" alt="car">
                                <?php else: ?>
                                    <div class="thumb"></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h($c['brand'].' '.$c['model']); ?></td>
                            <td><?php echo (int)$c['year']; ?></td>
                            <td>₵<?php echo number_format((float)$c['price'], 2); ?></td>
                            <td><span class="badge"><?php echo h($c['status']); ?></span></td>
                            <td><span class="badge"><?php echo h($c['approval_status']); ?></span></td>
                            <td><?php echo h(substr($c['created_at'], 0, 10)); ?></td>
                            <td class="actions">
                                <a href="../car-details.php?id=<?php echo $cid; ?>">View</a>
                                <a href="edit-car.php?id=<?php echo $cid; ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Spare parts -->
        <section class="card">
            <h2 class="section-title">Spare Parts</h2>

            <?php if (empty($parts)): ?>
                <p class="muted">You haven’t listed any spare parts yet. <a href="add-spare-part.php">Add a spare part</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Part</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parts as $p): ?>
                        <?php
                            $pid = (int)$p['spare_part_id'];
                            $img = isset($partThumbs[$pid]) ? $partThumbs[$pid] : null;
                        ?>
                        <tr>
                            <td>
                                <?php if ($img): ?>
                                    <img class="thumb" src="<?php echo h($img); ?>" alt="part">
                                <?php else: ?>
                                    <div class="thumb"></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h($p['name']); ?></td>
                            <td><?php echo h($p['category']); ?></td>
                            <td>₵<?php echo number_format((float)$p['price'], 2); ?></td>
                            <td><?php echo (int)$p['quantity']; ?></td>
                            <td><span class="badge"><?php echo h($p['status']); ?></span></td>
                            <td><span class="badge"><?php echo h($p['approval_status']); ?></span></td>
                            <td><?php echo h(substr($p['created_at'], 0, 10)); ?></td>
                            <td class="actions">
                                <a href="../spare-part-details.php?id=<?php echo $pid; ?>">View</a>
                                <a href="edit-spare-part.php?id=<?php echo $pid; ?>">Edit</a>
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