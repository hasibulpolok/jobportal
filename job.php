<?php
// ============================================================
// TalentBridge - Job Detail Page
// Developer: Hasibul Polok
// ============================================================
require_once 'config/db.php';
require_once 'includes/functions.php';

startSecureSession();
$pdo = getDB();

$jobId = intval($_GET['id'] ?? 0);
if (!$jobId) { setFlash('error', 'Invalid job.'); redirect(BASE_URL . '/jobs.php'); }

$stmt = $pdo->prepare("
    SELECT j.*, c.name AS company_name, c.logo AS company_logo, c.website AS company_website,
           c.description AS company_desc, c.city AS company_city, c.size AS company_size,
           cat.name AS category_name, u.name AS employer_name
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    JOIN categories cat ON j.category_id = cat.id
    JOIN users u ON j.employer_id = u.id
    WHERE j.id = ? AND j.status = 'approved'
");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) { setFlash('error', 'Job not found or not available.'); redirect(BASE_URL . '/jobs.php'); }

// Increment view count
$pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$jobId]);

// Check if already applied
$alreadyApplied = false;
$isSaved = false;
if (isLoggedIn() && userRole() === 'jobseeker') {
    $stmt2 = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
    $stmt2->execute([$jobId, $_SESSION['user_id']]);
    $alreadyApplied = (bool)$stmt2->fetch();

    $stmt3 = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND user_id = ?");
    $stmt3->execute([$jobId, $_SESSION['user_id']]);
    $isSaved = (bool)$stmt3->fetch();
}

// Handle Apply
$applyError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    requireRole('jobseeker');
    verifyCSRF($_POST['csrf_token'] ?? '');

    if ($alreadyApplied) {
        $applyError = 'You have already applied for this job.';
    } elseif (isDeadlinePassed($job['deadline'])) {
        $applyError = 'Application deadline has passed.';
    } else {
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $cvFile = null;

        try {
            if (!empty($_FILES['cv_file']['name'])) {
                $cvFile = uploadCV($_FILES['cv_file']);
            } else {
                // Use profile CV if no new upload
                $profileStmt = $pdo->prepare("SELECT cv_file FROM user_profiles WHERE user_id = ?");
                $profileStmt->execute([$_SESSION['user_id']]);
                $profile = $profileStmt->fetch();
                $cvFile = $profile['cv_file'] ?? null;
            }

            $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, cover_letter, cv_file) VALUES (?, ?, ?, ?)");
            $stmt->execute([$jobId, $_SESSION['user_id'], $coverLetter, $cvFile]);
            setFlash('success', 'Application submitted successfully!');
            redirect(BASE_URL . '/job.php?id=' . $jobId);
        } catch (Exception $e) {
            $applyError = $e->getMessage();
        }
    }
}

// Similar jobs
$similar = $pdo->prepare("
    SELECT j.id, j.title, c.name AS company_name, j.city, j.type, j.salary_min, j.salary_max, j.salary_type
    FROM jobs j JOIN companies c ON j.company_id = c.id
    WHERE j.category_id = ? AND j.id != ? AND j.status = 'approved' AND j.deadline >= CURDATE()
    LIMIT 3
");
$similar->execute([$job['category_id'], $jobId]);
$similarJobs = $similar->fetchAll();

$pageTitle = $job['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($job['title']) ?> | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>window.BASE_URL='<?= BASE_URL ?>';window.CSRF_TOKEN='<?= generateCSRF() ?>';</script>
</head>
<body>
<?php require_once 'includes/header.php'; ?>

<!-- Job Header -->
<div class="job-detail-header">
    <div class="container">
        <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
            <div class="company-logo" style="width:70px;height:70px;border-radius:14px;font-size:1.5rem;background:rgba(255,255,255,0.1);color:white;">
                <?= strtoupper(substr($job['company_name'], 0, 1)) ?>
            </div>
            <div style="flex:1;">
                <h1 style="font-family:var(--font-display);font-size:clamp(1.3rem,3vw,1.8rem);font-weight:800;margin-bottom:6px;"><?= e($job['title']) ?></h1>
                <p style="color:#93c5fd;font-size:1rem;margin-bottom:14px;"><?= e($job['company_name']) ?> · <?= e($job['company_city']) ?></p>
                <div class="job-detail-meta">
                    <div class="detail-meta-item">💼 <?= jobTypeLabel($job['type']) ?></div>
                    <div class="detail-meta-item">📍 <?= e($job['location']) ?></div>
                    <div class="detail-meta-item">📂 <?= e($job['category_name']) ?></div>
                    <div class="detail-meta-item">⭐ <?= ucfirst($job['experience_level']) ?> level</div>
                    <div class="detail-meta-item">👁 <?= number_format($job['views']) ?> views</div>
                    <div class="detail-meta-item">⏰ Posted <?= timeAgo($job['created_at']) ?></div>
                </div>
            </div>
            <?php if (isLoggedIn() && userRole() === 'jobseeker'): ?>
            <button class="save-job-btn btn btn-ghost btn-sm"
                    data-job-id="<?= $job['id'] ?>" data-saved="<?= $isSaved ? '1' : '0' ?>"
                    style="background:rgba(255,255,255,0.1);color:white;border-color:rgba(255,255,255,0.2);">
                <?= $isSaved ? '❤️ Saved' : '🤍 Save' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="job-detail-body">

        <!-- Main Content -->
        <div class="job-detail-main">
            <?php if ($applyError): ?>
                <div class="flash flash-error"><span class="flash-icon">✕</span><?= e($applyError) ?></div>
            <?php endif; ?>

            <div class="detail-section">
                <h3>📋 Job Description</h3>
                <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.9rem;"><?= nl2br(e($job['description'])) ?></div>
            </div>

            <?php if ($job['requirements']): ?>
            <div class="detail-section">
                <h3>✅ Requirements</h3>
                <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.9rem;"><?= nl2br(e($job['requirements'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($job['responsibilities']): ?>
            <div class="detail-section">
                <h3>🎯 Responsibilities</h3>
                <div style="white-space:pre-line;color:var(--dark-3);line-height:1.8;font-size:0.9rem;"><?= nl2br(e($job['responsibilities'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($job['skills_required']): ?>
            <div class="detail-section">
                <h3>🛠 Required Skills</h3>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach (explode(',', $job['skills_required']) as $skill): ?>
                        <span class="tag"><?= e(trim($skill)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- About Company -->
            <div class="detail-section" style="background:var(--bg);border-radius:var(--radius-sm);padding:20px;margin-top:24px;">
                <h3 style="margin-bottom:12px;">🏢 About <?= e($job['company_name']) ?></h3>
                <p style="color:var(--dark-3);font-size:0.875rem;line-height:1.7;"><?= nl2br(e($job['company_desc'] ?? 'No description provided.')) ?></p>
                <?php if ($job['company_website']): ?>
                    <a href="<?= e($job['company_website']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="margin-top:12px;">Visit Website →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="job-detail-sidebar">

            <!-- Apply Card -->
            <div class="apply-card">
                <?php if (!isLoggedIn()): ?>
                    <h3>Apply for this Job</h3>
                    <p style="font-size:0.875rem;color:var(--mid);margin-bottom:16px;">Login to apply for this position</p>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block">Login to Apply</a>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline btn-block" style="margin-top:8px;">Create Account</a>

                <?php elseif (userRole() !== 'jobseeker'): ?>
                    <div style="text-align:center;padding:10px 0;color:var(--mid);font-size:0.875rem;">
                        Only job seekers can apply.
                    </div>

                <?php elseif ($alreadyApplied): ?>
                    <div style="text-align:center;padding:10px 0;">
                        <div style="font-size:2rem;margin-bottom:8px;">✅</div>
                        <h3>Application Submitted</h3>
                        <p style="font-size:0.875rem;color:var(--mid);margin-top:6px;">You've already applied for this job.</p>
                        <a href="<?= BASE_URL ?>/user/applications.php" class="btn btn-outline btn-sm" style="margin-top:12px;">View My Applications</a>
                    </div>

                <?php elseif (isDeadlinePassed($job['deadline'])): ?>
                    <div style="text-align:center;padding:10px 0;color:var(--danger);">
                        ⚠️ Application deadline has passed.
                    </div>

                <?php else: ?>
                    <h3>Apply for this Job</h3>
                    <p style="font-size:0.8rem;color:var(--mid);margin-bottom:16px;">Deadline: <strong><?= formatDate($job['deadline']) ?></strong></p>
                    <button class="btn btn-primary btn-block" data-modal="applyModal">Apply Now →</button>
                <?php endif; ?>
            </div>

            <!-- Job Overview -->
            <div class="apply-card">
                <h3>Job Overview</h3>
                <div class="detail-info-row">
                    <span class="detail-info-label">📅 Posted</span>
                    <span class="detail-info-value"><?= formatDate($job['created_at']) ?></span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-info-label">⏰ Deadline</span>
                    <span class="detail-info-value <?= isDeadlinePassed($job['deadline']) ? 'text-danger' : '' ?>"><?= formatDate($job['deadline']) ?></span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-info-label">💰 Salary</span>
                    <span class="detail-info-value text-success"><?= formatSalary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?></span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-info-label">📍 Location</span>
                    <span class="detail-info-value"><?= e($job['city']) ?></span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-info-label">💼 Type</span>
                    <span class="detail-info-value"><?= jobTypeLabel($job['type']) ?></span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-info-label">⭐ Experience</span>
                    <span class="detail-info-value"><?= ucfirst($job['experience_level']) ?></span>
                </div>
                <?php if ($job['education_level']): ?>
                <div class="detail-info-row">
                    <span class="detail-info-label">🎓 Education</span>
                    <span class="detail-info-value"><?= e($job['education_level']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Similar Jobs -->
            <?php if ($similarJobs): ?>
            <div class="apply-card">
                <h3>Similar Jobs</h3>
                <?php foreach ($similarJobs as $sj): ?>
                <a href="<?= BASE_URL ?>/job.php?id=<?= $sj['id'] ?>" style="display:block;padding:10px 0;border-bottom:1px solid var(--border);text-decoration:none;">
                    <div style="font-weight:600;font-size:0.875rem;color:var(--dark);"><?= e($sj['title']) ?></div>
                    <div style="font-size:0.78rem;color:var(--mid);"><?= e($sj['company_name']) ?> · <?= e($sj['city']) ?></div>
                    <div style="font-size:0.78rem;color:var(--success);margin-top:2px;"><?= formatSalary($sj['salary_min'], $sj['salary_max'], $sj['salary_type']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Apply Modal -->
<?php if (isLoggedIn() && userRole() === 'jobseeker' && !$alreadyApplied && !isDeadlinePassed($job['deadline'])): ?>
<div class="modal-overlay" id="applyModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Apply for <?= e($job['title']) ?></div>
            <button class="modal-close" onclick="document.getElementById('applyModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfInput() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Cover Letter</label>
                    <textarea name="cover_letter" class="form-control" rows="6" maxlength="2000"
                              placeholder="Tell the employer why you're a great fit for this role..."></textarea>
                    <span class="form-hint">A personalized cover letter greatly improves your chances!</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Upload CV (PDF or DOCX)</label>
                    <input type="file" name="cv_file" id="cv_file" accept=".pdf,.doc,.docx" class="form-control" style="padding:8px;">
                    <span id="cvLabel" class="form-hint">Max size: 5MB. Leave empty to use your profile CV.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('applyModal').classList.remove('open')">Cancel</button>
                <button type="submit" name="apply" class="btn btn-primary">Submit Application →</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
