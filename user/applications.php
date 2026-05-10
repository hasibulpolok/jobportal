<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
startSecureSession();
requireRole('jobseeker');
$pdo    = getDB();
$userId = $_SESSION['user_id'];
$page   = max(1, intval($_GET['page'] ?? 1));
$status = $_GET['status'] ?? '';
$where  = ["a.user_id = ?"];
$params = [$userId];
if ($status) { $where[] = "a.status = ?"; $params[] = $status; }
$whereClause = implode(' AND ', $where);
$perPage = 10;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications a WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT a.*, j.title AS job_title, j.type, j.city, j.deadline, c.name AS company_name FROM applications a JOIN jobs j ON a.job_id = j.id JOIN companies c ON j.company_id = c.id WHERE $whereClause ORDER BY a.applied_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$applications = $stmt->fetchAll();
$userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$baseUrl = BASE_URL . '/user/applications.php?' . http_build_query(array_filter(['status' => $status]));
$pageTitle = 'My Applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My Applications | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<div class="container">
  <div class="dashboard">
    <div class="sidebar">
      <div class="sidebar-header">
        <div style="width:48px;height:48px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;font-size:1.2rem;margin-bottom:10px;"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <div class="sidebar-user-name"><?= e($user['name']) ?></div>
        <div class="sidebar-user-role">Job Seeker</div>
      </div>
      <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/user/dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="<?= BASE_URL ?>/user/profile.php"><span class="nav-icon">👤</span> My Profile</a>
        <a href="<?= BASE_URL ?>/user/applications.php" class="active"><span class="nav-icon">📋</span> Applications</a>
        <a href="<?= BASE_URL ?>/user/saved-jobs.php"><span class="nav-icon">❤️</span> Saved Jobs</a>
        <a href="<?= BASE_URL ?>/jobs.php"><span class="nav-icon">🔍</span> Browse Jobs</a>
        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
        <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
      </nav>
    </div>
    <div class="dash-content">
      <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div>
          <h1 class="dash-title">My Applications</h1>
          <p class="dash-subtitle"><?= number_format($total) ?> application<?= $total != 1 ? 's' : '' ?> found</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php $statuses=['' => 'All','pending'=>'Pending','reviewed'=>'Reviewed','shortlisted'=>'Shortlisted','interviewed'=>'Interviewed','hired'=>'Hired','rejected'=>'Rejected'];
          foreach($statuses as $val=>$label): ?>
          <a href="<?= BASE_URL ?>/user/applications.php?status=<?= $val ?>" class="btn btn-sm <?= $status===$val?'btn-primary':'btn-ghost' ?>"><?= $label ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if($applications): ?>
      <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach($applications as $app): ?>
        <div class="card">
          <div class="card-body" style="padding:20px;">
            <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
              <div class="company-logo" style="width:48px;height:48px;font-size:1rem;border-radius:10px;flex-shrink:0;"><?= strtoupper(substr($app['company_name'],0,1)) ?></div>
              <div style="flex:1;min-width:200px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                  <div>
                    <h3 style="font-family:var(--font-display);font-weight:700;font-size:1rem;margin-bottom:2px;">
                      <a href="<?= BASE_URL ?>/job.php?id=<?= $app['job_id'] ?>" style="color:var(--dark);"><?= e($app['job_title']) ?></a>
                    </h3>
                    <div style="font-size:0.85rem;color:var(--mid);"><?= e($app['company_name']) ?> &middot; <?= e($app['city']) ?></div>
                  </div>
                  <?= statusBadge($app['status']) ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:10px;font-size:0.8rem;color:var(--mid);">
                  <span>💼 <?= jobTypeLabel($app['type']) ?></span>
                  <span>📅 Applied <?= timeAgo($app['applied_at']) ?></span>
                  <span>⏰ Deadline: <?= formatDate($app['deadline']) ?></span>
                  <?php if($app['cv_file']): ?>
                  <a href="<?= BASE_URL ?>/uploads/cvs/<?= e($app['cv_file']) ?>" target="_blank" style="color:var(--primary);font-weight:600;">📎 View CV</a>
                  <?php endif; ?>
                </div>
                <?php if($app['cover_letter']): ?>
                <div style="margin-top:10px;background:var(--bg);border-radius:var(--radius-sm);padding:12px;font-size:0.82rem;color:var(--dark-3);line-height:1.6;">
                  <strong style="display:block;margin-bottom:4px;color:var(--mid);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Cover Letter</strong>
                  <?= nl2br(e(truncate($app['cover_letter'], 200))) ?>
                </div>
                <?php endif; ?>
                <?php if($app['employer_note']): ?>
                <div style="margin-top:8px;background:#dbeafe;border-radius:var(--radius-sm);padding:10px 12px;font-size:0.82rem;color:#1e40af;">
                  <strong>💬 Employer Note:</strong> <?= e($app['employer_note']) ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?= paginationLinks($baseUrl, $page, $totalPages) ?>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3 class="empty-title">No applications found</h3>
        <p class="empty-desc">You haven't applied to any jobs yet. Start browsing!</p>
        <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-primary">Browse Jobs</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
