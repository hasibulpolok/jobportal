<?php
// ============================================================
// TalentBridge - View Application (Employer)
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];
$appId  = intval($_GET['id'] ?? 0);

if (!$appId) { setFlash('error', 'Invalid application.'); redirect(BASE_URL . '/employer/applicants.php'); }

// Fetch and verify ownership
$stmt = $pdo->prepare("
    SELECT a.*, j.title AS job_title, j.id AS job_id, j.type AS job_type, j.city AS job_city,
           u.name AS applicant_name, u.email AS applicant_email, u.created_at AS member_since,
           up.phone, up.city, up.country, up.bio, up.skills, up.experience, up.education,
           up.cv_file AS profile_cv, up.linkedin, up.website
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN user_profiles up ON up.user_id = u.id
    WHERE a.id = ? AND j.employer_id = ?
");
$stmt->execute([$appId, $userId]);
$application = $stmt->fetch();

if (!$application) { setFlash('error', 'Application not found.'); redirect(BASE_URL . '/employer/applicants.php'); }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $newStatus   = $_POST['status'] ?? '';
    $employerNote = trim($_POST['employer_note'] ?? '');
    $validStatuses = ['pending','reviewed','shortlisted','interviewed','hired','rejected'];

    if (in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE applications SET status = ?, employer_note = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $employerNote, $appId]);
        setFlash('success', 'Application status updated to: ' . ucfirst($newStatus));
        redirect(BASE_URL . '/employer/view-application.php?id=' . $appId);
    }
}

$pageTitle = 'View Application';
$statusColors = [
    'pending'=>'warning','reviewed'=>'info','shortlisted'=>'primary',
    'interviewed'=>'info','hired'=>'success','rejected'=>'danger'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">
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
                <a href="<?= BASE_URL ?>/employer/jobs.php"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php" class="active"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <div class="dash-content">
            <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 class="dash-title">Application Review</h1>
                    <p class="dash-subtitle">For: <strong><?= e($application['job_title']) ?></strong></p>
                </div>
                <a href="<?= BASE_URL ?>/employer/applicants.php?job_id=<?= $application['job_id'] ?>" class="btn btn-ghost btn-sm">← Back to Applicants</a>
            </div>

            <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

                <!-- Applicant Details -->
                <div>
                    <!-- Header card -->
                    <div class="form-card" style="margin-bottom:20px;">
                        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                            <div style="width:64px;height:64px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;font-size:1.5rem;flex-shrink:0;">
                                <?= strtoupper(substr($application['applicant_name'], 0, 1)) ?>
                            </div>
                            <div style="flex:1;">
                                <h2 style="font-family:var(--font-display);font-size:1.3rem;font-weight:800;"><?= e($application['applicant_name']) ?></h2>
                                <div style="color:var(--mid);font-size:0.875rem;margin-top:4px;">
                                    📧 <a href="mailto:<?= e($application['applicant_email']) ?>"><?= e($application['applicant_email']) ?></a>
                                    <?php if ($application['phone']): ?> &nbsp;·&nbsp; 📞 <?= e($application['phone']) ?><?php endif; ?>
                                    <?php if ($application['city']): ?> &nbsp;·&nbsp; 📍 <?= e($application['city']) ?><?php endif; ?>
                                </div>
                                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                                    <?= statusBadge($application['status']) ?>
                                    <span style="font-size:0.78rem;color:var(--mid);">Applied <?= timeAgo($application['applied_at']) ?></span>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php if ($application['linkedin']): ?>
                                    <a href="<?= e($application['linkedin']) ?>" target="_blank" class="btn btn-outline btn-sm">LinkedIn →</a>
                                <?php endif; ?>
                                <?php if ($application['website']): ?>
                                    <a href="<?= e($application['website']) ?>" target="_blank" class="btn btn-ghost btn-sm">Portfolio →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cover Letter -->
                    <?php if ($application['cover_letter']): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">📝 Cover Letter</h3>
                        <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.9rem;background:var(--bg);padding:16px;border-radius:var(--radius-sm);">
                            <?= nl2br(e($application['cover_letter'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- CV -->
                    <?php $cvFile = $application['cv_file'] ?: $application['profile_cv']; ?>
                    <?php if ($cvFile): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">📄 CV / Resume</h3>
                        <div style="display:flex;justify-content:space-between;align-items:center;background:var(--primary-light);padding:14px 16px;border-radius:var(--radius-sm);">
                            <div>
                                <strong style="font-size:0.875rem;">📎 <?= e($cvFile) ?></strong>
                                <p style="font-size:0.78rem;color:var(--mid);margin-top:2px;">Application CV</p>
                            </div>
                            <a href="<?= BASE_URL ?>/employer/download-cv.php?app_id=<?= $appId ?>" class="btn btn-primary btn-sm">⬇ Download CV</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Skills -->
                    <?php if ($application['skills']): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">🛠 Skills</h3>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <?php foreach (explode(',', $application['skills']) as $skill): ?>
                                <span class="tag"><?= e(trim($skill)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Experience -->
                    <?php if ($application['experience']): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">💼 Work Experience</h3>
                        <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.875rem;"><?= nl2br(e($application['experience'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Education -->
                    <?php if ($application['education']): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">🎓 Education</h3>
                        <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.875rem;"><?= nl2br(e($application['education'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Bio -->
                    <?php if ($application['bio']): ?>
                    <div class="form-card" style="margin-bottom:20px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">👤 About</h3>
                        <p style="color:var(--dark-3);line-height:1.8;font-size:0.875rem;"><?= nl2br(e($application['bio'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Status Update Sidebar -->
                <div>
                    <div class="form-card" style="position:sticky;top:88px;">
                        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);">⚡ Update Status</h3>

                        <form method="POST">
                            <?= csrfInput() ?>

                            <div class="form-group">
                                <label class="form-label">Application Status</label>
                                <select name="status" class="form-control form-select">
                                    <?php foreach (['pending'=>'Pending','reviewed'=>'Reviewed','shortlisted'=>'Shortlisted','interviewed'=>'Interviewed','hired'=>'Hired','rejected'=>'Rejected'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $application['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Note to Applicant</label>
                                <textarea name="employer_note" class="form-control" rows="4"
                                          placeholder="Optional feedback or note..."><?= e($application['employer_note'] ?? '') ?></textarea>
                                <span class="form-hint">This may be visible to the applicant.</span>
                            </div>

                            <button type="submit" name="update_status" class="btn btn-primary btn-block">Update Status</button>
                        </form>

                        <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">

                        <div>
                            <div class="detail-info-row">
                                <span class="detail-info-label">Applied</span>
                                <span class="detail-info-value"><?= formatDate($application['applied_at'], 'd M Y') ?></span>
                            </div>
                            <div class="detail-info-row">
                                <span class="detail-info-label">Last Updated</span>
                                <span class="detail-info-value"><?= formatDate($application['updated_at'], 'd M Y') ?></span>
                            </div>
                            <div class="detail-info-row">
                                <span class="detail-info-label">Member Since</span>
                                <span class="detail-info-value"><?= formatDate($application['member_since'], 'M Y') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
