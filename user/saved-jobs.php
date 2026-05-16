<?php
// ============================================================
// TalentBridge - Saved Jobs
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('jobseeker');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT j.*, c.name AS company_name, c.logo AS company_logo, cat.name AS category_name,
           sj.saved_at
    FROM saved_jobs sj
    JOIN jobs j ON sj.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    JOIN categories cat ON j.category_id = cat.id
    WHERE sj.user_id = ?
    ORDER BY sj.saved_at DESC
");
$stmt->execute([$userId]);
$savedJobs = $stmt->fetchAll();

$pageTitle = 'Saved Jobs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>window.BASE_URL='<?= BASE_URL ?>';window.CSRF_TOKEN='<?= generateCSRF() ?>';</script>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <?php
                $sidebarPhotoStmt = getDB()->prepare("SELECT profile_photo FROM user_profiles WHERE user_id = ?");
                $sidebarPhotoStmt->execute([$_SESSION['user_id']]);
                $sidebarPhoto = $sidebarPhotoStmt->fetchColumn();
                ?>
                <?= renderAvatar($_SESSION['user']['name'], $sidebarPhoto ?: null, 48, 'margin-bottom:10px;') ?>
                <div class="sidebar-user-name"><?= e($_SESSION['user']['name']) ?></div>
                <div class="sidebar-user-role">Job Seeker</div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/user/dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/user/profile.php"><span class="nav-icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/user/applications.php"><span class="nav-icon">📋</span> Applications</a>
                <a href="<?= BASE_URL ?>/user/saved-jobs.php" class="active"><span class="nav-icon">❤️</span> Saved Jobs</a>
                <a href="<?= BASE_URL ?>/jobs.php"><span class="nav-icon">🔍</span> Browse Jobs</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">Saved Jobs</h1>
                <p class="dash-subtitle"><?= count($savedJobs) ?> job<?= count($savedJobs) != 1 ? 's' : '' ?> saved</p>
            </div>

            <?php if ($savedJobs): ?>
            <div class="jobs-grid">
                <?php foreach ($savedJobs as $job): ?>
                <div class="job-card" id="saved-job-<?= $job['id'] ?>">
                    <div class="job-card-top">
                        <div class="company-logo">
                            <?php if ($job['company_logo']): ?>
                                <img src="<?= BASE_URL ?>/uploads/logos/<?= e($job['company_logo']) ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($job['company_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="job-card-info">
                            <h3 class="job-title">
                                <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>"><?= e($job['title']) ?></a>
                            </h3>
                            <div class="company-name"><?= e($job['company_name']) ?></div>
                        </div>
                        <button class="save-job-btn" data-job-id="<?= $job['id'] ?>" data-saved="1"
                                style="background:none;border:none;font-size:1.2rem;cursor:pointer;margin-left:auto;padding:4px;"
                                title="Remove from saved">❤️</button>
                    </div>
                    <div class="job-meta">
                        <span class="badge badge-type"><?= jobTypeLabel($job['type']) ?></span>
                        <span class="job-meta-item">📍 <?= e($job['city']) ?></span>
                        <span class="job-meta-item">📂 <?= e($job['category_name']) ?></span>
                    </div>
                    <p style="font-size:0.85rem;color:var(--mid);line-height:1.6;margin-bottom:14px;"><?= truncate($job['description'], 100) ?></p>
                    <div class="job-card-footer">
                        <div>
                            <div class="job-salary"><?= formatSalary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?></div>
                            <div style="font-size:0.75rem;color:var(--mid);margin-top:2px;">Saved <?= timeAgo($job['saved_at']) ?></div>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <?php if (isDeadlinePassed($job['deadline'])): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">❤️</div>
                <h3 class="empty-title">No saved jobs</h3>
                <p class="empty-desc">Bookmark jobs you're interested in and come back to apply later.</p>
                <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-primary">Browse Jobs</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Override save-job-btn behavior on this page to remove card on unsave
document.querySelectorAll('.save-job-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const jobId = btn.dataset.jobId;
        try {
            const resp = await fetch(`${window.BASE_URL}/user/save-job.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `job_id=${jobId}&action=unsave&csrf_token=${window.CSRF_TOKEN}`
            });
            const data = await resp.json();
            if (data.success) {
                const card = document.getElementById('saved-job-' + jobId);
                if (card) { card.style.opacity = '0'; card.style.transform = 'scale(0.95)'; card.style.transition = 'all 0.3s'; setTimeout(() => card.remove(), 300); }
            }
        } catch (err) { console.error(err); }
    });
});
</script>
</body>
</html>
