<?php
// ============================================================
// TalentBridge - Admin: All Applications
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('admin');

$pdo = getDB();

$statusFilter = $_GET['status'] ?? '';
$searchFilter = trim($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 20;

$where  = ["1=1"];
$params = [];
if ($statusFilter)  { $where[] = "a.status = ?";                                     $params[] = $statusFilter; }
if ($searchFilter)  { $where[] = "(u.name LIKE ? OR j.title LIKE ? OR c.name LIKE ?)"; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    JOIN companies c ON j.company_id = c.id
    WHERE $whereClause
");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT a.*, j.title AS job_title, j.type AS job_type, j.city AS job_city,
           u.name AS applicant_name, u.email AS applicant_email,
           c.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    JOIN companies c ON j.company_id = c.id
    WHERE $whereClause
    ORDER BY a.applied_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

$baseUrl = BASE_URL . '/admin/applications.php?' . http_build_query(array_filter(['status' => $statusFilter, 'q' => $searchFilter]));
$pageTitle = 'All Applications';
?>
<?php require_once '../admin/header.php'; ?>

<div class="dash-header">
    <h1 class="dash-title">All Applications</h1>
    <p class="dash-subtitle"><?= number_format($total) ?> application<?= $total != 1 ? 's' : '' ?></p>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <input type="text" name="q" class="form-control" placeholder="🔍 Search applicant, job or company..." value="<?= e($searchFilter) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <select name="status" class="form-control form-select">
                <option value="">All Statuses</option>
                <?php foreach (['pending','reviewed','shortlisted','interviewed','hired','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($statusFilter || $searchFilter): ?>
            <a href="<?= BASE_URL ?>/admin/applications.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrap">
    <?php if ($applications): ?>
    <table>
        <thead>
            <tr>
                <th>Applicant</th>
                <th>Job</th>
                <th>Company</th>
                <th>CV</th>
                <th>Applied</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td>
                    <strong><?= e($app['applicant_name']) ?></strong>
                    <div style="font-size:0.78rem;color:var(--mid);"><?= e($app['applicant_email']) ?></div>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/job.php?id=<?= $app['job_id'] ?>" target="_blank" style="font-weight:600;color:var(--primary);"><?= e($app['job_title']) ?></a>
                    <div style="font-size:0.75rem;color:var(--mid);">📍 <?= e($app['job_city']) ?></div>
                </td>
                <td style="font-size:0.85rem;"><?= e($app['company_name']) ?></td>
                <td style="text-align:center;">
                    <?php if ($app['cv_file']): ?>
                        <span class="badge badge-success">📄 Yes</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">None</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.82rem;color:var(--mid);"><?= timeAgo($app['applied_at']) ?></td>
                <td><?= statusBadge($app['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state" style="padding:50px;">
        <div class="empty-icon">📋</div>
        <h3 class="empty-title">No applications found</h3>
    </div>
    <?php endif; ?>
</div>

<?= paginationLinks($baseUrl, $page, $totalPages) ?>

<?php require_once '../admin/footer.php'; ?>
