<?php
// ============================================================
// TalentBridge - Employer: My Jobs
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $delId = intval($_GET['delete']);
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->execute([$delId, $userId]);
    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$delId]);
        setFlash('success', 'Job deleted successfully.');
    }
    redirect(BASE_URL . '/employer/jobs.php');
}

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$statusFilter = $_GET['status'] ?? '';

$where  = ["j.employer_id = ?"];
$params = [$userId];
if ($statusFilter) { $where[] = "j.status = ?"; $params[] = $statusFilter; }
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j WHERE $whereClause");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT j.*, cat.name AS category_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
    FROM jobs j
    JOIN categories cat ON j.category_id = cat.id
    WHERE $whereClause
    ORDER BY j.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$csrf = generateCSRF();
$baseUrl = BASE_URL . '/employer/jobs.php?' . http_build_query(array_filter(['status' => $statusFilter]));
$pageTitle = 'My Jobs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | TalentBridge</title>
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
                <div class="sidebar-user-name"><?= e($_SESSION['user']['name']) ?></div>
                <div class="sidebar-user-role">Employer</div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/employer/dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/employer/company.php"><span class="nav-icon">🏢</span> Company Profile</a>
                <a href="<?= BASE_URL ?>/employer/post-job.php"><span class="nav-icon">➕</span> Post a Job</a>
                <a href="<?= BASE_URL ?>/employer/jobs.php" class="active"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 class="dash-title">My Jobs</h1>
                    <p class="dash-subtitle"><?= number_format($total) ?> job<?= $total != 1 ? 's' : '' ?></p>
                </div>
                <a href="<?= BASE_URL ?>/employer/post-job.php" class="btn btn-primary btn-sm">➕ Post New Job</a>
            </div>

            <!-- Status Filter -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'closed' => 'Closed'] as $val => $label): ?>
                <a href="<?= BASE_URL ?>/employer/jobs.php<?= $val ? '?status=' . $val : '' ?>"
                   class="btn btn-sm <?= $statusFilter === $val ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <div class="table-wrap">
                <?php if ($jobs): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Category</th>
                            <th>Applicants</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <strong><?= e($job['title']) ?></strong>
                                <br><span class="badge badge-type" style="margin-top:4px;"><?= jobTypeLabel($job['type']) ?></span>
                                <span style="font-size:0.75rem;color:var(--mid);display:block;margin-top:2px;">📍 <?= e($job['city']) ?></span>
                            </td>
                            <td style="font-size:0.85rem;"><?= e($job['category_name']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/employer/applicants.php?job_id=<?= $job['id'] ?>"
                                   style="font-weight:700;color:var(--primary);font-size:1rem;"><?= $job['app_count'] ?></a>
                                <span style="font-size:0.75rem;color:var(--mid);display:block;">applicants</span>
                            </td>
                            <td style="font-size:0.82rem;" class="<?= isDeadlinePassed($job['deadline']) ? 'text-danger' : 'text-muted' ?>">
                                <?= formatDate($job['deadline']) ?>
                                <?= isDeadlinePassed($job['deadline']) ? '<br><small>Expired</small>' : '' ?>
                            </td>
                            <td><?= statusBadge($job['status']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/employer/applicants.php?job_id=<?= $job['id'] ?>" class="action-btn view">Applicants</a>
                                    <a href="<?= BASE_URL ?>/employer/edit-job.php?id=<?= $job['id'] ?>" class="action-btn edit">Edit</a>
                                    <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" target="_blank" class="action-btn view" style="background:#f0fdf4;color:var(--success);">Preview</a>
                                    <a href="<?= BASE_URL ?>/employer/jobs.php?delete=<?= $job['id'] ?>&csrf=<?= $csrf ?>"
                                       class="action-btn delete"
                                       data-confirm="Are you sure you want to delete this job? All applications will also be deleted.">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding:50px;">
                    <div class="empty-icon">💼</div>
                    <h3 class="empty-title">No jobs found</h3>
                    <p class="empty-desc"><?= $statusFilter ? 'No jobs with this status.' : 'You haven\'t posted any jobs yet.' ?></p>
                    <a href="<?= BASE_URL ?>/employer/post-job.php" class="btn btn-primary">Post Your First Job</a>
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
