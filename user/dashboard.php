<?php
// ============================================================
// TalentBridge - Job Seeker Dashboard
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('jobseeker');

$pdo = getDB();
$userId = $_SESSION['user_id'];

// Stats
$totalApps  = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
$totalApps->execute([$userId]);
$totalApps  = $totalApps->fetchColumn();

$pendingApps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'pending'");
$pendingApps->execute([$userId]);
$pendingApps = $pendingApps->fetchColumn();

$savedCount  = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
$savedCount->execute([$userId]);
$savedCount  = $savedCount->fetchColumn();

$hiredCount  = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'hired'");
$hiredCount->execute([$userId]);
$hiredCount  = $hiredCount->fetchColumn();

// Recent applications
$stmt = $pdo->prepare("
    SELECT a.*, j.title AS job_title, j.type, c.name AS company_name, j.city
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentApps = $stmt->fetchAll();

// Profile completion
$profile = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profile->execute([$userId]);
$profile = $profile->fetch();
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]);
$user = $user->fetch();

$completion = 0;
$checks = ['phone' => $profile['phone'] ?? '', 'bio' => $profile['bio'] ?? '', 'skills' => $profile['skills'] ?? '', 'cv_file' => $profile['cv_file'] ?? '', 'city' => $profile['city'] ?? ''];
foreach ($checks as $v) if ($v) $completion += 20;

$pageTitle = 'My Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div style="width:48px;height:48px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;font-size:1.2rem;margin-bottom:10px;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="sidebar-user-name"><?= e($user['name']) ?></div>
                <div class="sidebar-user-role">Job Seeker</div>
                <div style="margin-top:10px;">
                    <div style="font-size:0.72rem;color:var(--mid);margin-bottom:4px;">Profile Completion</div>
                    <div style="background:var(--border);border-radius:50px;height:6px;overflow:hidden;">
                        <div style="width:<?= $completion ?>%;background:var(--primary);height:100%;border-radius:50px;transition:width 0.5s;"></div>
                    </div>
                    <div style="font-size:0.72rem;color:var(--primary);margin-top:3px;font-weight:600;"><?= $completion ?>% complete</div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/user/dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/user/profile.php"><span class="nav-icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/user/applications.php"><span class="nav-icon">📋</span> Applications</a>
                <a href="<?= BASE_URL ?>/user/saved-jobs.php"><span class="nav-icon">❤️</span> Saved Jobs</a>
                <a href="<?= BASE_URL ?>/jobs.php"><span class="nav-icon">🔍</span> Browse Jobs</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">Welcome back, <?= e(explode(' ', $user['name'])[0]) ?>! 👋</h1>
                <p class="dash-subtitle">Here's your job search activity overview</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📨</div>
                    <div class="stat-num"><?= $totalApps ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-num"><?= $pendingApps ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">🎉</div>
                    <div class="stat-num"><?= $hiredCount ?></div>
                    <div class="stat-label">Offers Received</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon">❤️</div>
                    <div class="stat-num"><?= $savedCount ?></div>
                    <div class="stat-label">Saved Jobs</div>
                </div>
            </div>

            <!-- Profile completion notice -->
            <?php if ($completion < 100): ?>
            <div style="background:var(--secondary-light);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius);padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <strong style="color:var(--dark);">⚡ Complete your profile</strong>
                    <p style="color:var(--mid);font-size:0.875rem;margin-top:2px;">A complete profile gets 5x more views from employers.</p>
                </div>
                <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-secondary btn-sm">Complete Profile →</a>
            </div>
            <?php endif; ?>

            <!-- Recent Applications -->
            <div class="table-wrap">
                <div class="table-header">
                    <div class="table-title">📋 Recent Applications</div>
                    <a href="<?= BASE_URL ?>/user/applications.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <?php if ($recentApps): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentApps as $app): ?>
                        <tr>
                            <td><strong><?= e($app['job_title']) ?></strong></td>
                            <td><?= e($app['company_name']) ?></td>
                            <td><span class="badge badge-type"><?= jobTypeLabel($app['type']) ?></span></td>
                            <td><?= timeAgo($app['applied_at']) ?></td>
                            <td><?= statusBadge($app['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding:40px;">
                    <div class="empty-icon">📨</div>
                    <h3 class="empty-title">No applications yet</h3>
                    <p class="empty-desc">Start applying to jobs to see them here.</p>
                    <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-primary">Browse Jobs</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
