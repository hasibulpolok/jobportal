<?php
// ============================================================
// TalentBridge - Admin: Manage Jobs
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('admin');

$pdo = getDB();

// Handle approve
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $pdo->prepare("UPDATE jobs SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([intval($_GET['approve'])]);
    setFlash('success', 'Job approved and published successfully.');
    redirect(BASE_URL . '/admin/jobs.php');
}

// Handle reject
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $pdo->prepare("UPDATE jobs SET status = 'rejected', updated_at = NOW() WHERE id = ?")->execute([intval($_GET['reject'])]);
    setFlash('success', 'Job rejected.');
    redirect(BASE_URL . '/admin/jobs.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([intval($_GET['delete'])]);
    setFlash('success', 'Job deleted permanently.');
    redirect(BASE_URL . '/admin/jobs.php');
}

// Handle feature toggle
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $jobId = intval($_GET['feature']);
    $stmt = $pdo->prepare("SELECT is_featured FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $current = $stmt->fetchColumn();
    $pdo->prepare("UPDATE jobs SET is_featured = ? WHERE id = ?")->execute([$current ? 0 : 1, $jobId]);
    setFlash('success', 'Featured status updated.');
    redirect(BASE_URL . '/admin/jobs.php');
}

// Filters
$statusFilter   = $_GET['status'] ?? '';
$searchFilter   = trim($_GET['q'] ?? '');
$page           = max(1, intval($_GET['page'] ?? 1));
$perPage        = 15;

$where  = ["1=1"];
$params = [];
if ($statusFilter)  { $where[] = "j.status = ?";                          $params[] = $statusFilter; }
if ($searchFilter)  { $where[] = "(j.title LIKE ? OR c.name LIKE ?)";     $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j JOIN companies c ON j.company_id = c.id WHERE $whereClause");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT j.*, c.name AS company_name, u.name AS employer_name,
           cat.name AS category_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    JOIN users u ON j.employer_id = u.id
    JOIN categories cat ON j.category_id = cat.id
    WHERE $whereClause
    ORDER BY j.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$csrf    = generateCSRF();
$baseUrl = BASE_URL . '/admin/jobs.php?' . http_build_query(array_filter(['status' => $statusFilter, 'q' => $searchFilter]));
$pageTitle = 'Manage Jobs';
?>
<?php require_once '../admin/header.php'; ?>

<div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="dash-title">Manage Jobs</h1>
        <p class="dash-subtitle"><?= number_format($total) ?> total job<?= $total != 1 ? 's' : '' ?></p>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <input type="text" name="q" class="form-control" placeholder="🔍 Search jobs or companies..." value="<?= e($searchFilter) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <select name="status" class="form-control form-select">
                <option value="">All Statuses</option>
                <?php foreach (['pending','approved','rejected','closed','draft'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($statusFilter || $searchFilter): ?>
            <a href="<?= BASE_URL ?>/admin/jobs.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Quick filter tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <?php
    $tabCounts = [
        ''         => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
        'pending'  => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='rejected'")->fetchColumn(),
    ];
    $tabLabels = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    foreach ($tabLabels as $val => $label):
    ?>
    <a href="<?= BASE_URL ?>/admin/jobs.php<?= $val ? '?status=' . $val : '' ?>"
       class="btn btn-sm <?= $statusFilter === $val ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $label ?> <span style="opacity:0.7;">(<?= $tabCounts[$val] ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
    <?php if ($jobs): ?>
    <table>
        <thead>
            <tr>
                <th>Job</th>
                <th>Company</th>
                <th>Category</th>
                <th>Apps</th>
                <th>Deadline</th>
                <th>Featured</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td>
                    <strong style="font-size:0.9rem;"><?= e($job['title']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--mid);">
                        <span class="badge badge-type"><?= jobTypeLabel($job['type']) ?></span>
                        📍 <?= e($job['city']) ?>
                    </div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:0.875rem;"><?= e($job['company_name']) ?></div>
                    <div style="font-size:0.78rem;color:var(--mid);"><?= e($job['employer_name']) ?></div>
                </td>
                <td style="font-size:0.82rem;"><?= e($job['category_name']) ?></td>
                <td style="font-weight:700;color:var(--primary);text-align:center;"><?= $job['app_count'] ?></td>
                <td style="font-size:0.8rem;" class="<?= isDeadlinePassed($job['deadline']) ? 'text-danger' : 'text-muted' ?>">
                    <?= formatDate($job['deadline']) ?>
                </td>
                <td style="text-align:center;">
                    <a href="<?= BASE_URL ?>/admin/jobs.php?feature=<?= $job['id'] ?>&csrf=<?= $csrf ?>"
                       title="Toggle featured" style="font-size:1.1rem;text-decoration:none;">
                        <?= $job['is_featured'] ? '⭐' : '☆' ?>
                    </a>
                </td>
                <td><?= statusBadge($job['status']) ?></td>
                <td>
                    <div class="table-actions" style="flex-direction:column;gap:4px;">
                        <?php if ($job['status'] === 'pending'): ?>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?approve=<?= $job['id'] ?>&csrf=<?= $csrf ?>" class="action-btn view" style="background:#d1fae5;color:var(--success);">✓ Approve</a>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?reject=<?= $job['id'] ?>&csrf=<?= $csrf ?>" class="action-btn delete" data-confirm="Reject this job?">✕ Reject</a>
                        <?php elseif ($job['status'] === 'approved'): ?>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?reject=<?= $job['id'] ?>&csrf=<?= $csrf ?>" class="action-btn delete" data-confirm="Remove approval?">Unpublish</a>
                        <?php elseif ($job['status'] === 'rejected'): ?>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?approve=<?= $job['id'] ?>&csrf=<?= $csrf ?>" class="action-btn view" style="background:#d1fae5;color:var(--success);">Re-approve</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" target="_blank" class="action-btn edit">Preview</a>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?delete=<?= $job['id'] ?>&csrf=<?= $csrf ?>" class="action-btn delete"
                           data-confirm="Permanently delete this job and all its applications?">Delete</a>
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
        <p class="empty-desc">Try different filters.</p>
    </div>
    <?php endif; ?>
</div>

<?= paginationLinks($baseUrl, $page, $totalPages) ?>

<?php require_once '../admin/footer.php'; ?>
