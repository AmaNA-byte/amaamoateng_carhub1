<?php
// car_hub/admin/settings.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ auth.php is inside /admin
$authPath = __DIR__ . '/auth.php';

if (!file_exists($authPath)) {
    die("❌ settings.php cannot find auth.php at: " . htmlspecialchars($authPath));
}

require_once $authPath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - CarHub Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e5e7eb;}
        a{color:#a5b4fc;text-decoration:none;} a:hover{text-decoration:underline;}

        .topbar{
            display:flex;justify-content:space-between;align-items:center;
            padding:0.75rem 1.5rem;background:#020617;border-bottom:1px solid #111827;
        }
        .layout{display:flex;min-height:calc(100vh - 52px);}

        .sidebar{
            width:240px;background:#020617;border-right:1px solid #111827;
            padding:1.5rem 1rem;font-size:0.9rem;
        }
        .sidebar ul{list-style:none;padding:0;margin:0;}
        .sidebar li{margin-bottom:0.35rem;}
        .sidebar a{display:block;padding:0.5rem 0.7rem;border-radius:0.5rem;color:#e5e7eb;}
        .sidebar a:hover{background:#111827;}

        .main{flex:1;padding:1.75rem 2rem;}
        .page-title{font-size:1.4rem;margin-bottom:0.25rem;}
        .page-subtitle{font-size:0.9rem;color:#9ca3af;margin-bottom:1.5rem;}

        .card{
            background:#020617;border:1px solid #111827;border-radius:0.75rem;
            padding:1.25rem;max-width:850px;
        }
        .muted{color:#9ca3af;font-size:0.9rem;line-height:1.6;}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
        @media(max-width:800px){.row{grid-template-columns:1fr;}}

        label{display:block;font-size:0.85rem;color:#9ca3af;margin-bottom:0.25rem;}
        input,select{
            width:100%;padding:0.5rem 0.6rem;border-radius:0.5rem;
            border:1px solid #111827;background:#0b1220;color:#e5e7eb;
            font-size:0.9rem;
        }

        .btn{
            margin-top:1rem;padding:0.55rem 1rem;border-radius:999px;border:none;
            font-size:0.85rem;cursor:pointer;background:#111827;color:#e5e7eb;
        }
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn:hover{filter:brightness(1.05);}
        .notice{
            margin-top:0.8rem;padding:0.75rem;border-radius:0.75rem;border:1px solid #1f2937;
            background:#0b1220;color:#9ca3af;font-size:0.85rem;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div>🛠️ CarHub Admin</div>
    <div>
        <a href="dashboard.php">Dashboard</a> ·
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
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Platform configuration (safe placeholder page).</p>

        <section class="card">
            <div class="row">
                <div>
                    <label>Site name</label>
                    <input type="text" value="CarHub" disabled>
                </div>
                <div>
                    <label>Support email</label>
                    <input type="email" value="support@carhub.local" disabled>
                </div>
                <div>
                    <label>Payments provider</label>
                    <select disabled>
                        <option selected>Paystack (planned)</option>
                    </select>
                </div>
                <div>
                    <label>Maintenance mode</label>
                    <select disabled>
                        <option selected>Off</option>
                        <option>On</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" type="button" disabled>
                Save settings (coming soon)
            </button>

            <div class="notice">
                
            </div>
        </section>
    </main>
</div>

</body>
</html>