<?php
// ============================================================
// TalentBridge - HTML Header
// Developer: Hasibul Polok
// ============================================================
startSecureSession();
$currentUser = currentUser();
$role = userRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | TalentBridge' : 'TalentBridge - Find Your Dream Job' ?></title>
    <meta name="description" content="TalentBridge - Bangladesh's premier job portal connecting talented professionals with top employers.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <span class="brand-icon">⬡</span>
            <span class="brand-text">Talent<strong>Bridge</strong></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <div class="navbar-menu" id="navMenu">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link">Home</a>
            <a href="<?= BASE_URL ?>/jobs.php" class="nav-link">Browse Jobs</a>

            <?php if (isLoggedIn()): ?>
                <?php if ($role === 'jobseeker'): ?>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="<?= BASE_URL ?>/user/profile.php" class="nav-link">Profile</a>
                    <a href="<?= BASE_URL ?>/user/applications.php" class="nav-link">Applications</a>
                <?php elseif ($role === 'employer'): ?>
                    <a href="<?= BASE_URL ?>/employer/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="<?= BASE_URL ?>/employer/post-job.php" class="nav-link">Post Job</a>
                    <a href="<?= BASE_URL ?>/employer/jobs.php" class="nav-link">My Jobs</a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link">Admin Panel</a>
                <?php endif; ?>
                <div class="nav-user-menu">
                    <button class="nav-user-btn" id="userMenuBtn">
                        <span class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></span>
                        <span><?= e(explode(' ', $currentUser['name'] ?? 'User')[0]) ?></span>
                        <span class="caret">▾</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong><?= e($currentUser['name'] ?? '') ?></strong>
                            <small><?= ucfirst($role) ?></small>
                        </div>
                        <?php if ($role === 'jobseeker'): ?>
                            <a href="<?= BASE_URL ?>/user/profile.php">My Profile</a>
                            <a href="<?= BASE_URL ?>/user/saved-jobs.php">Saved Jobs</a>
                        <?php elseif ($role === 'employer'): ?>
                            <a href="<?= BASE_URL ?>/employer/company.php">Company Profile</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="nav-link">Login</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="main-content">
<?php displayFlash(); ?>
