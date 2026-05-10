<?php
// ============================================================
// TalentBridge - Employer Dashboard
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo      = getDB();
$userId   = $_SESSION['user_id'];

// Fetch company
$compStmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
$compStmt->execute([$userId]);
$company  = $compStmt->fetch();

// Stats
$totalJobs = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
$totalJobs->execute([$userId]);
$totalJobs = $totalJobs->fetchColumn();

$activeJobs = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'approved' AND deadline >= CURDATE()");
$activeJobs->execute([$userId]);
$activeJobs = $activeJobs->fetchColumn();

$totalApps = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?");
$totalApps->execute([$userId]);
$totalApps = $totalApps->fetchColumn();

$newApps = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'pending'");
$newApps->execute([$userId]);
$newApps = $newApps->fetchColumn();

// Recent jobs
$recentJobs = $pdo->prepare("
    SELECT j.*, cat.name AS category_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
    FROM jobs j
    JOIN categories cat ON j.category_id = cat.id
    WHERE j.employer_id = ?
    ORDER BY j.created_at DESC
    LIMIT 5
");
$recentJobs->execute([$userId]);
$recentJobs = $recentJobs->fetchAll();

// Recent applicants
$recentApps = $pdo->prepare("
    SELECT a.*, j.title AS job_title, u.name AS applicant_name, u.email AS applicant_email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE j.employer_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$recentApps->execute([$userId]);
$recentApps = $recentApps->fetchAll();

$pageTitle = 'Employer Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard | TalentBridge</title>
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
                <div style="width:48px;height:48px;background:var(--secondary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;font-size:1.2rem;margin-bottom:10px;">
                    <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
                </div>
                <div class="sidebar-user-name"><?= e($company ? $company['name'] : $_SESSION['user']['name']) ?></div>
                <div class="sidebar-user-role">Employer</div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/employer/dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/employer/company.php"><span class="nav-icon">🏢</span> Company Profile</a>
                <a href="<?= BASE_URL ?>/employer/post-job.php"><span class="nav-icon">➕</span> Post a Job</a>
                <a href="<?= BASE_URL ?>/employer/jobs.php"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 class="dash-title">Welcome, <?= e($company ? $company['name'] : explode(' ', $_SESSION['user']['name'])[0]) ?>! 🏢</h1>
                    <p class="dash-subtitle">Manage your job postings and applicants</p>
                </div>
                <a href="<?= BASE_URL ?>/employer/post-job.php" class="btn btn-primary">➕ Post New Job</a>
            </div>

            <?php if (!$company): ?>
            <div style="background:#fef3c7;border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius);padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <strong>⚠️ Set up your company profile first!</strong>
                    <p style="color:var(--mid);font-size:0.875rem;margin-top:2px;">You need a company profile before posting jobs.</p>
                </div>
                <a href="<?= BASE_URL ?>/employer/company.php" class="btn btn-secondary btn-sm">Create Company Profile →</a>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💼</div>
                    <div class="stat-num"><?= $totalJobs ?></div>
                    <div class="stat-label">Total Jobs Posted</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-num"><?= $activeJobs ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon">📨</div>
                    <div class="stat-num"><?= $totalApps ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-num"><?= $newApps ?></div>
                    <div class="stat-label">New Applications</div>
                </div>
            </div>

            <!-- Recent Jobs -->
            <div class="table-wrap" style="margin-bottom:24px;">
                <div class="table-header">
                    <div class="table-title">💼 Recent Job Postings</div>
                    <a href="<?= BASE_URL ?>/employer/jobs.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <?php if ($recentJobs): ?>
                <table>
                    <thead><tr><th>Job Title</th><th>Category</th><th>Applications</th><th>Deadline</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentJobs as $job): ?>
                        <tr>
                            <td><strong><?= e($job['title']) ?></strong><br><span class="badge badge-type" style="margin-top:4px;"><?= jobTypeLabel($job['type']) ?></span></td>
                            <td><?= e($job['category_name']) ?></td>
                            <td><span style="font-weight:700;color:var(--primary);"><?= $job['app_count'] ?></span> applicants</td>
                            <td class="<?= isDeadlinePassed($job['deadline']) ? 'text-danger' : 'text-muted' ?>" style="font-size:0.82rem;"><?= formatDate($job['deadline']) ?></td>
                            <td><?= statusBadge($job['status']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/employer/applicants.php?job_id=<?= $job['id'] ?>" class="action-btn view">Applicants</a>
                                    <a href="<?= BASE_URL ?>/employer/edit-job.php?id=<?= $job['id'] ?>" class="action-btn edit">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding:40px;">
                    <div class="empty-icon">💼</div>
                    <h3 class="empty-title">No jobs posted yet</h3>
                    <a href="<?= BASE_URL ?>/employer/post-job.php" class="btn btn-primary">Post Your First Job</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Applicants -->
            <div class="table-wrap">
                <div class="table-header">
                    <div class="table-title">👥 Recent Applicants</div>
                    <a href="<?= BASE_URL ?>/employer/applicants.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <?php if ($recentApps): ?>
                <table>
                    <thead><tr><th>Applicant</th><th>Job</th><th>Applied</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentApps as $app): ?>
                        <tr>
                            <td>
                                <strong><?= e($app['applicant_name']) ?></strong><br>
                                <small style="color:var(--mid);"><?= e($app['applicant_email']) ?></small>
                            </td>
                            <td><?= e($app['job_title']) ?></td>
                            <td style="font-size:0.82rem;color:var(--mid);"><?= timeAgo($app['applied_at']) ?></td>
                            <td><?= statusBadge($app['status']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/employer/view-application.php?id=<?= $app['id'] ?>" class="action-btn view">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding:30px;"><p style="color:var(--mid);">No applications yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
