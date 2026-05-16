<?php
// ============================================================
// TalentBridge - Employer: All Applicants
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

$jobFilter    = intval($_GET['job_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 15;

// Build where clause
$where  = ["j.employer_id = ?"];
$params = [$userId];
if ($jobFilter)    { $where[] = "j.id = ?";       $params[] = $jobFilter; }
if ($statusFilter) { $where[] = "a.status = ?";   $params[] = $statusFilter; }
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE $whereClause");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT a.*, j.title AS job_title, j.id AS job_id,
           u.name AS applicant_name, u.email AS applicant_email,
           up.profile_photo,
           up.phone, up.city, up.skills, up.linkedin
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN user_profiles up ON up.user_id = u.id
    WHERE $whereClause
    ORDER BY a.applied_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$applicants = $stmt->fetchAll();

// Jobs list for filter dropdown
$jobsStmt = $pdo->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
$jobsStmt->execute([$userId]);
$myJobs = $jobsStmt->fetchAll();

$baseUrl = BASE_URL . '/employer/applicants.php?' . http_build_query(array_filter(['job_id' => $jobFilter ?: '', 'status' => $statusFilter]));
$pageTitle = 'Applicants';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <?php
                $sidebarLogoStmt = getDB()->prepare("SELECT logo FROM companies WHERE user_id = ?");
                $sidebarLogoStmt->execute([$_SESSION['user_id']]);
                $sidebarLogoFile = $sidebarLogoStmt->fetchColumn();
                $sidebarLogoUrl  = getLogoUrl($sidebarLogoFile ?: null);
                if ($sidebarLogoUrl): ?>
                    <img src="<?= e($sidebarLogoUrl) ?>" alt="Logo"
                         style="width:48px;height:48px;border-radius:12px;object-fit:cover;margin-bottom:10px;border:2px solid var(--border);">
                <?php else: ?>
                    <div style="width:48px;height:48px;background:var(--secondary);color:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;font-size:1.2rem;margin-bottom:10px;">
                        <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="sidebar-user-name"><?= e($_SESSION['user']['name']) ?></div>
                <div class="sidebar-user-role">Employer</div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/employer/dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/employer/company.php"><span class="nav-icon">🏢</span> Company Profile</a>
                <a href="<?= BASE_URL ?>/employer/post-job.php"><span class="nav-icon">➕</span> Post a Job</a>
                <a href="<?= BASE_URL ?>/employer/jobs.php"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php" class="active"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">Applicants</h1>
                <p class="dash-subtitle"><?= number_format($total) ?> application<?= $total != 1 ? 's' : '' ?> found</p>
            </div>

            <!-- Filters -->
            <div class="filter-bar" style="margin-bottom:20px;">
                <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Filter by Job</label>
                        <select name="job_id" class="form-control form-select">
                            <option value="">All Jobs</option>
                            <?php foreach ($myJobs as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= $jobFilter == $j['id'] ? 'selected' : '' ?>><?= e($j['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Filter by Status</label>
                        <select name="status" class="form-control form-select">
                            <option value="">All Statuses</option>
                            <?php foreach (['pending','reviewed','shortlisted','interviewed','hired','rejected'] as $s): ?>
                                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if ($jobFilter || $statusFilter): ?>
                            <a href="<?= BASE_URL ?>/employer/applicants.php" class="btn btn-ghost">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <?php if ($applicants): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Job Applied</th>
                            <th>Contact</th>
                            <th>Skills</th>
                            <th>Applied</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applicants as $app): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?= renderAvatar($app['applicant_name'], $app['profile_photo'] ?? null, 36) ?>
                                    <div>
                                        <div style="font-weight:700;"><?= e($app['applicant_name']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--mid);"><?= e($app['applicant_email']) ?></div>
                                        <?php if ($app['city']): ?>
                                            <div style="font-size:0.75rem;color:var(--mid);">📍 <?= e($app['city']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.85rem;">
                                <a href="<?= BASE_URL ?>/job.php?id=<?= $app['job_id'] ?>" style="font-weight:600;color:var(--primary);"><?= e($app['job_title']) ?></a>
                            </td>
                            <td style="font-size:0.82rem;">
                                <?php if ($app['phone']): ?><div>📞 <?= e($app['phone']) ?></div><?php endif; ?>
                                <?php if ($app['linkedin']): ?>
                                    <a href="<?= e($app['linkedin']) ?>" target="_blank" style="font-size:0.78rem;">LinkedIn →</a>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.78rem;max-width:160px;color:var(--mid);">
                                <?= $app['skills'] ? truncate($app['skills'], 80) : '—' ?>
                            </td>
                            <td style="font-size:0.82rem;color:var(--mid);"><?= timeAgo($app['applied_at']) ?></td>
                            <td><?= statusBadge($app['status']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/employer/view-application.php?id=<?= $app['id'] ?>" class="action-btn view">Review</a>
                                    <?php if ($app['cv_file']): ?>
                                        <a href="<?= BASE_URL ?>/employer/download-cv.php?app_id=<?= $app['id'] ?>" class="action-btn edit">CV ↓</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding:50px;">
                    <div class="empty-icon">👥</div>
                    <h3 class="empty-title">No applicants found</h3>
                    <p class="empty-desc">
                        <?= ($jobFilter || $statusFilter) ? 'No applicants match your filters.' : 'No one has applied to your jobs yet.' ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <?= paginationLinks($baseUrl, $page, $totalPages) ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
