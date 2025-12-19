<?php
// dashboard/reviews.php
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

$database = new Database();
$db = $database->getConnection();

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Reviews I wrote (reviews.reviewer_id)
$written   = [];
$received  = [];

try {
    $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                   u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.reviewee_id = u.user_id
            WHERE r.reviewer_id = :id
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $written = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                   u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.user_id
            WHERE r.reviewee_id = :id
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $currentUserId]);
    $received = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reviews - CarHub</title>
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
        .page-title{font-size:1.3rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}
        .card{background:#020617;border-radius:0.75rem;padding:1rem 1.1rem 1.25rem;border:1px solid #111827;margin-bottom:1rem;max-width:800px;}
        .review-list{list-style:none;padding:0;margin:0;}
        .review-item{padding:0.6rem 0;border-bottom:1px solid #111827;}
        .review-item:last-child{border-bottom:none;}
        .review-meta{font-size:0.8rem;color:#9ca3af;margin-bottom:0.25rem;}
        .rating{color:#facc15;font-size:0.9rem;}
    </style>
</head>
<body>
<header class="topbar">
    <div>⭐ CarHub · <span style="color:#a5b4fc;">My reviews</span></div>
    <div><a href="index.php">Dashboard</a> · <a href="/logout.php">Logout</a></div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>User menu</h3>
        <ul>
            <li><a href="index.php">Overview</a></li>
            <li><a href="profile.php">My profile</a></li>
            <li><a href="orders.php">My orders</a></li>
            <li><a href="reviews.php">My reviews</a></li>
            <li><a href="../cart/index.php">My cart</a></li>
        </ul>
    </aside>

    <main class="main">
        <h1 class="page-title">My reviews</h1>
        <p class="page-subtitle">See reviews you’ve written and what others say about you.</p>

        <section class="card">
            <h2>Reviews I wrote</h2>
            <?php if (empty($written)): ?>
                <p>You haven’t written any reviews yet.</p>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($written as $r): ?>
                        <li class="review-item">
                            <div class="review-meta">
                                For: <?php echo h($r['first_name'].' '.$r['last_name']); ?> ·
                                <span class="rating"><?php echo str_repeat('★', (int)$r['rating']); ?></span>
                                (<?php echo (int)$r['rating']; ?>/5) ·
                                <?php echo h($r['created_at']); ?>
                            </div>
                            <div><?php echo nl2br(h($r['comment'])); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Reviews about me</h2>
            <?php if (empty($received)): ?>
                <p>No one has reviewed you yet.</p>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($received as $r): ?>
                        <li class="review-item">
                            <div class="review-meta">
                                From: <?php echo h($r['first_name'].' '.$r['last_name']); ?> ·
                                <span class="rating"><?php echo str_repeat('★', (int)$r['rating']); ?></span>
                                (<?php echo (int)$r['rating']; ?>/5) ·
                                <?php echo h($r['created_at']); ?>
                            </div>
                            <div><?php echo nl2br(h($r['comment'])); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>