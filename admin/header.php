<?php
// ============================================================
// TalentBridge - Admin Header
// Developer: Hasibul Polok
// ============================================================
startSecureSession();
$currentUser = currentUser();

$currentFile = basename($_SERVER['PHP_SELF']);
function isAdminActive($file) {
    global $currentFile;
    return $currentFile === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | TalentBridge Admin' : 'Admin Panel | TalentBridge' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { padding-top: 0; }
        .main-content { padding-top: 0; }
        .admin-topbar {
            background: var(--dark);
            color: white;
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .admin-topbar .brand { font-family: var(--font-display); font-weight: 800; font-size: 1.1rem; color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .admin-topbar .brand-badge { background: var(--danger); color: white; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 50px; letter-spacing: 0.5px; text-transform: uppercase; }
        .admin-topbar .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
        .admin-topbar .topbar-user { font-size: 0.85rem; color: #94a3b8; }
        .admin-topbar .topbar-logout { font-size: 0.8rem; color: #f87171; text-decoration: none; }
        .admin-topbar .topbar-logout:hover { color: white; }
    </style>
</head>
<body>

<!-- Admin Topbar -->
<div class="admin-topbar">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="brand">
        <span style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;">⬡</span>
        TalentBridge
        <span class="brand-badge">Admin</span>
    </a>
    <div class="topbar-right">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="topbar-logout" style="color:#93c5fd;">View Site →</a>
        <span class="topbar-user">👤 <?= e($currentUser['name'] ?? 'Admin') ?></span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="topbar-logout">Logout</a>
    </div>
</div>

<div class="admin-layout">

    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div style="padding:0 8px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:16px;">
            <div style="font-size:0.75rem;color:#64748b;letter-spacing:1px;text-transform:uppercase;font-weight:700;margin-bottom:12px;">Main Menu</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= isAdminActive('dashboard.php') ?>">
            <span>📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/admin/jobs.php" class="<?= isAdminActive('jobs.php') ?>">
            <span>💼</span> Manage Jobs
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="<?= isAdminActive('users.php') ?>">
            <span>👥</span> Manage Users
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= isAdminActive('categories.php') ?>">
            <span>📂</span> Categories
        </a>
        <a href="<?= BASE_URL ?>/admin/applications.php" class="<?= isAdminActive('applications.php') ?>">
            <span>📋</span> Applications
        </a>
        <div style="padding:12px 8px 4px;margin-top:8px;border-top:1px solid rgba(255,255,255,0.07);">
            <div style="font-size:0.75rem;color:#64748b;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Quick Links</div>
        </div>
        <a href="<?= BASE_URL ?>/jobs.php" target="_blank"><span>🌐</span> View Job Board</a>
        <a href="<?= BASE_URL ?>/auth/logout.php" style="color:#f87171;margin-top:8px;"><span>🚪</span> Logout</a>
    </div>

    <!-- Admin Main Area -->
    <div class="admin-main">
        <?php displayFlash(); ?>
