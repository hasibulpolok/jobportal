<?php
// ============================================================
// TalentBridge - Admin Dashboard
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('admin');

$pdo = getDB();

// Platform stats
$stats = [
    'total_users'     => $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
    'total_seekers'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'jobseeker'")->fetchColumn(),
    'total_employers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn(),
    'total_jobs'      => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'pending_jobs'    => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'pending'")->fetchColumn(),
    'active_jobs'     => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetchColumn(),
    'total_apps'      => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'total_companies' => $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
];

// Recent pending jobs for quick approval
$pendingJobs = $pdo->query("
    SELECT j.*, c.name AS company_name, u.name AS employer_name, cat.name AS category_name
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    JOIN users u ON j.employer_id = u.id
    JOIN categories cat ON j.category_id = cat.id
    WHERE j.status = 'pending'
    ORDER BY j.created_at DESC
    LIMIT 8
")->fetchAll();

// Recent registrations
$recentUsers = $pdo->query("
    SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 6
")->fetchAll();

$pageTitle = 'Dashboard';
?>
<?php require_once '../admin/header.php'; ?>

<div class="dash-header">
    <h1 class="dash-title">Admin Dashboard</h1>
    <p class="dash-subtitle">Platform overview for TalentBridge</p>
</div>

<!-- Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:32px;">
    <?php
    $statCards = [
        ['👥', $stats['total_users'],     'Total Users',     ''],
        ['🧑‍💼', $stats['total_seekers'],  'Job Seekers',     'green'],
        ['🏢', $stats['total_employers'],  'Employers',       ''],
        ['💼', $stats['total_jobs'],       'Total Jobs',      ''],
        ['⏳', $stats['pending_jobs'],     'Pending Jobs',    'orange'],
        ['✅', $stats['active_jobs'],      'Active Jobs',     'green'],
        ['📨', $stats['total_apps'],       'Applications',    ''],
        ['🏭', $stats['total_companies'],  'Companies',       ''],
    ];
    foreach ($statCards as [$icon, $num, $label, $color]):
    ?>
    <div class="stat-card <?= $color ?>">
        <div class="stat-icon"><?= $icon ?></div>
        <div class="stat-num"><?= number_format($num) ?></div>
        <div class="stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pending Jobs for Approval -->
<div class="table-wrap" style="margin-bottom:28px;">
    <div class="table-header">
        <div class="table-title">⏳ Pending Job Approvals</div>
        <a href="<?= BASE_URL ?>/admin/jobs.php?status=pending" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <?php if ($pendingJobs): ?>
    <table>
        <thead>
            <tr>
                <th>Job Title</th>
                <th>Employer / Company</th>
                <th>Category</th>
                <th>Type</th>
                <th>Posted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendingJobs as $job): ?>
            <tr>
                <td>
                    <strong><?= e($job['title']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--mid);">📍 <?= e($job['city']) ?></div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:0.875rem;"><?= e($job['company_name']) ?></div>
                    <div style="font-size:0.78rem;color:var(--mid);"><?= e($job['employer_name']) ?></div>
                </td>
                <td style="font-size:0.85rem;"><?= e($job['category_name']) ?></td>
                <td><span class="badge badge-type"><?= jobTypeLabel($job['type']) ?></span></td>
                <td style="font-size:0.82rem;color:var(--mid);"><?= timeAgo($job['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/admin/jobs.php?approve=<?= $job['id'] ?>&csrf=<?= generateCSRF() ?>" class="action-btn view" style="background:#d1fae5;color:var(--success);">✓ Approve</a>
                        <a href="<?= BASE_URL ?>/admin/jobs.php?reject=<?= $job['id'] ?>&csrf=<?= generateCSRF() ?>" class="action-btn delete" data-confirm="Reject this job posting?">✕ Reject</a>
                        <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" target="_blank" class="action-btn edit">Preview</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state" style="padding:30px;">
        <div class="empty-icon">✅</div>
        <h3 class="empty-title">All caught up!</h3>
        <p class="empty-desc">No jobs pending approval.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Users -->
<div class="table-wrap">
    <div class="table-header">
        <div class="table-title">👤 Recent Registrations</div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($recentUsers as $user): ?>
            <tr>
                <td><strong><?= e($user['name']) ?></strong></td>
                <td style="font-size:0.85rem;"><?= e($user['email']) ?></td>
                <td><?= statusBadge($user['role'] === 'employer' ? 'shortlisted' : 'reviewed') ?>
                    <span class="badge <?= $user['role'] === 'employer' ? 'badge-warning' : 'badge-info' ?>"><?= ucfirst($user['role']) ?></span>
                </td>
                <td><?= statusBadge($user['status']) ?></td>
                <td style="font-size:0.82rem;color:var(--mid);"><?= timeAgo($user['created_at']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $user['id'] ?>" class="action-btn view">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../admin/footer.php'; ?>
