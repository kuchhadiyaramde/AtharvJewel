<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — Atharv Jewel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        :root { --brand: #dd2476; --brand2: #ff512f; }
        body { background: #f4f5f7; }
        .sidebar {
            width: 240px; min-height: 100vh;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand2) 100%);
            position: fixed; top: 0; left: 0; z-index: 100;
        }
        .sidebar .brand { padding: 1.5rem 1.2rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar .brand h5 { color: #fff; font-weight: 700; margin: 0; letter-spacing: .5px; }
        .sidebar .brand small { color: rgba(255,255,255,0.6); font-size: .75rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8); padding: .65rem 1.2rem;
            border-radius: .4rem; margin: .15rem .5rem; font-size: .9rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2); color: #fff;
        }
        .sidebar .nav-link i { width: 1.4rem; }
        .main-wrap { margin-left: 240px; min-height: 100vh; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e8e8e8;
            padding: .75rem 1.5rem; display: flex; align-items: center;
            justify-content: space-between;
        }
        .topbar h4 { margin: 0; font-weight: 600; color: #333; font-size: 1.15rem; }
        .content { padding: 1.5rem; }
        .stat-card { border: none; border-radius: .75rem; }
        .stat-card .icon { width: 3rem; height: 3rem; border-radius: .6rem; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .table th { font-size: .8rem; text-transform: uppercase; color: #888; font-weight: 600; border-bottom: 2px solid #eee; }
        .badge-new { background: #198754; font-size: .7rem; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <h5><i class="fa fa-gem me-2"></i>Atharv Jewel</h5>
        <small>Admin Panel</small>
    </div>
    <nav class="mt-3">
        <?php
        $current = basename($_SERVER['PHP_SELF'], '.php');
        $links = [
            'dashboard'  => ['icon' => 'fa-chart-pie',    'label' => 'Dashboard'],
            'products'   => ['icon' => 'fa-boxes-stacked', 'label' => 'Products'],
            'categories' => ['icon' => 'fa-tags',          'label' => 'Categories'],
        ];
        foreach ($links as $page => $info):
        ?>
            <a href="<?= $page ?>.php" class="nav-link <?= $current === $page ? 'active' : '' ?>">
                <i class="fa <?= $info['icon'] ?> me-2"></i><?= $info['label'] ?>
            </a>
        <?php endforeach; ?>
        <hr style="border-color:rgba(255,255,255,0.2); margin:.5rem 1rem;">
        <a href="../index.html" class="nav-link" target="_blank">
            <i class="fa fa-globe me-2"></i>View Site
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fa fa-right-from-bracket me-2"></i>Logout
        </a>
    </nav>
</aside>

<!-- Main content wrapper -->
<div class="main-wrap">
    <div class="topbar">
        <h4><?= htmlspecialchars($pageTitle ?? '') ?></h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small"><i class="fa fa-user-circle me-1"></i><?= htmlspecialchars($adminUsername) ?></span>
        </div>
    </div>
    <div class="content">
