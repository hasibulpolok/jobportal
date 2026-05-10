<?php
// ============================================================
// TalentBridge - Admin: Manage Users
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('admin');

$pdo = getDB();

// Handle ban/unban
if (isset($_GET['ban']) && is_numeric($_GET['ban'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $uid = intval($_GET['ban']);
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if ($u) {
        $newStatus = $u['status'] === 'banned' ? 'active' : 'banned';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);
        setFlash('success', 'User status updated to: ' . $newStatus);
    }
    redirect(BASE_URL . '/admin/users.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $uid = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$uid]);
    setFlash('success', 'User deleted permanently.');
    redirect(BASE_URL . '/admin/users.php');
}

// Filters
$roleFilter   = $_GET['role'] ?? '';
$searchFilter = trim($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 20;

$where  = ["u.role != 'admin'"];
$params = [];
if ($roleFilter)   { $where[] = "u.role = ?";                               $params[] = $roleFilter; }
if ($searchFilter) { $where[] = "(u.name LIKE ? OR u.email LIKE ?)";        $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereClause");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT u.*,
        (SELECT COUNT(*) FROM applications WHERE user_id = u.id) AS app_count,
        (SELECT COUNT(*) FROM jobs WHERE employer_id = u.id) AS job_count,
        (SELECT name FROM companies WHERE user_id = u.id LIMIT 1) AS company_name
    FROM users u
    WHERE $whereClause
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrf    = generateCSRF();
$baseUrl = BASE_URL . '/admin/users.php?' . http_build_query(array_filter(['role' => $roleFilter, 'q' => $searchFilter]));
$pageTitle = 'Manage Users';
?>
<?php require_once '../admin/header.php'; ?>

<div class="dash-header">
    <h1 class="dash-title">Manage Users</h1>
    <p class="dash-subtitle"><?= number_format($total) ?> user<?= $total != 1 ? 's' : '' ?></p>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <input type="text" name="q" class="form-control" placeholder="🔍 Search by name or email..." value="<?= e($searchFilter) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <select name="role" class="form-control form-select">
                <option value="">All Roles</option>
                <option value="jobseeker" <?= $roleFilter === 'jobseeker' ? 'selected' : '' ?>>Job Seekers</option>
                <option value="employer"  <?= $roleFilter === 'employer'  ? 'selected' : '' ?>>Employers</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($roleFilter || $searchFilter): ?>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Role tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <?php
    $roleCounts = [
        ''           => $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
        'jobseeker'  => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'jobseeker'")->fetchColumn(),
        'employer'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn(),
    ];
    foreach (['' => 'All Users', 'jobseeker' => 'Job Seekers', 'employer' => 'Employers'] as $val => $label):
    ?>
    <a href="<?= BASE_URL ?>/admin/users.php<?= $val ? '?role=' . $val : '' ?>"
       class="btn btn-sm <?= $roleFilter === $val ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $label ?> <span style="opacity:0.7;">(<?= $roleCounts[$val] ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
    <?php if ($users): ?>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Company / Activity</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr style="<?= $user['status'] === 'banned' ? 'opacity:0.6;' : '' ?>">
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;background:var(--primary-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;flex-shrink:0;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:0.9rem;"><?= e($user['name']) ?></div>
                            <div style="font-size:0.78rem;color:var(--mid);"><?= e($user['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge <?= $user['role'] === 'employer' ? 'badge-warning' : 'badge-info' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </td>
                <td style="font-size:0.82rem;">
                    <?php if ($user['role'] === 'employer'): ?>
                        <div><?= $user['company_name'] ? '🏢 ' . e($user['company_name']) : '<em style="color:var(--mid);">No company</em>' ?></div>
                        <div style="color:var(--mid);"><?= $user['job_count'] ?> jobs posted</div>
                    <?php else: ?>
                        <div style="color:var(--mid);"><?= $user['app_count'] ?> applications</div>
                    <?php endif; ?>
                </td>
                <td><?= statusBadge($user['status']) ?></td>
                <td style="font-size:0.82rem;color:var(--mid);"><?= formatDate($user['created_at'], 'd M Y') ?></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/admin/users.php?ban=<?= $user['id'] ?>&csrf=<?= $csrf ?>"
                           class="action-btn <?= $user['status'] === 'banned' ? 'view' : 'delete' ?>"
                           data-confirm="<?= $user['status'] === 'banned' ? 'Unban this user?' : 'Ban this user?' ?>">
                            <?= $user['status'] === 'banned' ? '✓ Unban' : '⊘ Ban' ?>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/users.php?delete=<?= $user['id'] ?>&csrf=<?= $csrf ?>"
                           class="action-btn delete"
                           data-confirm="Permanently delete this user? This cannot be undone.">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state" style="padding:50px;">
        <div class="empty-icon">👥</div>
        <h3 class="empty-title">No users found</h3>
        <p class="empty-desc">Try different search or filter criteria.</p>
    </div>
    <?php endif; ?>
</div>

<?= paginationLinks($baseUrl, $page, $totalPages) ?>

<?php require_once '../admin/footer.php'; ?>
