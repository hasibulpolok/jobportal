<?php
// ============================================================
// TalentBridge - Edit Job
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];
$jobId  = intval($_GET['id'] ?? 0);

if (!$jobId) { setFlash('error', 'Invalid job.'); redirect(BASE_URL . '/employer/jobs.php'); }

// Fetch job and verify ownership
$stmt = $pdo->prepare("SELECT j.*, c.id AS comp_id FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.id = ? AND j.employer_id = ?");
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch();

if (!$job) { setFlash('error', 'Job not found.'); redirect(BASE_URL . '/employer/jobs.php'); }

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $form = [
        'title'           => trim($_POST['title'] ?? ''),
        'category_id'     => intval($_POST['category_id'] ?? 0),
        'type'            => $_POST['type'] ?? 'full-time',
        'location'        => trim($_POST['location'] ?? ''),
        'city'            => trim($_POST['city'] ?? ''),
        'salary_min'      => trim($_POST['salary_min'] ?? ''),
        'salary_max'      => trim($_POST['salary_max'] ?? ''),
        'salary_type'     => $_POST['salary_type'] ?? 'monthly',
        'experience_level'=> $_POST['experience_level'] ?? 'entry',
        'education_level' => trim($_POST['education_level'] ?? ''),
        'skills_required' => trim($_POST['skills_required'] ?? ''),
        'description'     => trim($_POST['description'] ?? ''),
        'requirements'    => trim($_POST['requirements'] ?? ''),
        'responsibilities'=> trim($_POST['responsibilities'] ?? ''),
        'deadline'        => trim($_POST['deadline'] ?? ''),
    ];

    if (empty($form['title']))       $errors['title']       = 'Job title is required.';
    if (!$form['category_id'])       $errors['category_id'] = 'Please select a category.';
    if (empty($form['description'])) $errors['description'] = 'Job description is required.';
    if (empty($form['city']))        $errors['city']        = 'City is required.';
    if (empty($form['deadline']))    $errors['deadline']    = 'Application deadline is required.';

    if (empty($errors)) {
        $slug = uniqueSlug($pdo, 'jobs', $form['title'] . ' ' . $job['comp_id'], $jobId);

        $pdo->prepare("
            UPDATE jobs SET
                title=?, slug=?, category_id=?, type=?, location=?, city=?,
                salary_min=?, salary_max=?, salary_type=?, experience_level=?,
                education_level=?, skills_required=?, description=?,
                requirements=?, responsibilities=?, deadline=?, status='pending', updated_at=NOW()
            WHERE id=? AND employer_id=?
        ")->execute([
            $form['title'], $slug, $form['category_id'], $form['type'],
            $form['location'], $form['city'],
            $form['salary_min'] ?: null, $form['salary_max'] ?: null,
            $form['salary_type'], $form['experience_level'],
            $form['education_level'], $form['skills_required'],
            $form['description'], $form['requirements'], $form['responsibilities'],
            $form['deadline'], $jobId, $userId
        ]);

        setFlash('success', 'Job updated successfully! It will need re-approval.');
        redirect(BASE_URL . '/employer/jobs.php');
    }

    // Merge form data back for display
    $job = array_merge($job, $form);
}

$pageTitle = 'Edit Job';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job | TalentBridge</title>
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
                <a href="<?= BASE_URL ?>/employer/jobs.php" class="active"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <div class="dash-content">
            <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 class="dash-title">Edit Job</h1>
                    <p class="dash-subtitle">Editing: <strong><?= e($job['title']) ?></strong></p>
                </div>
                <a href="<?= BASE_URL ?>/employer/jobs.php" class="btn btn-ghost btn-sm">← Back to Jobs</a>
            </div>

            <form method="POST" data-validate>
                <?= csrfInput() ?>

                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📌 Job Information</h3>

                    <div class="form-group">
                        <label class="form-label">Job Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               value="<?= e($job['title']) ?>" required>
                        <?php if (isset($errors['title'])): ?><span class="form-error"><?= e($errors['title']) ?></span><?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category <span class="required">*</span></label>
                            <select name="category_id" class="form-control form-select" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $job['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><span class="form-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Job Type</label>
                            <select name="type" class="form-control form-select">
                                <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','remote'=>'Remote','contract'=>'Contract','internship'=>'Internship','freelance'=>'Freelance'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $job['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City <span class="required">*</span></label>
                            <input type="text" name="city" class="form-control" value="<?= e($job['city']) ?>" required>
                            <?php if (isset($errors['city'])): ?><span class="form-error"><?= e($errors['city']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Location</label>
                            <input type="text" name="location" class="form-control" value="<?= e($job['location']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Experience Level</label>
                            <select name="experience_level" class="form-control form-select">
                                <?php foreach (['entry'=>'Entry Level','mid'=>'Mid Level','senior'=>'Senior Level','lead'=>'Lead/Manager','executive'=>'Executive'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $job['experience_level'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Education Requirement</label>
                            <input type="text" name="education_level" class="form-control" value="<?= e($job['education_level']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required Skills</label>
                        <input type="text" name="skills_required" class="form-control" value="<?= e($job['skills_required']) ?>" placeholder="PHP, Laravel, MySQL (comma separated)">
                    </div>
                </div>

                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">💰 Salary</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Salary Type</label>
                            <select name="salary_type" id="salary_type" class="form-control form-select">
                                <?php foreach (['monthly'=>'Monthly','yearly'=>'Yearly','hourly'=>'Hourly','negotiable'=>'Negotiable'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $job['salary_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="salaryFields" class="form-row" style="<?= $job['salary_type'] === 'negotiable' ? 'display:none;' : '' ?>">
                        <div class="form-group">
                            <label class="form-label">Minimum Salary (৳)</label>
                            <input type="number" name="salary_min" class="form-control" value="<?= e($job['salary_min']) ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Maximum Salary (৳)</label>
                            <input type="number" name="salary_max" class="form-control" value="<?= e($job['salary_max']) ?>" min="0">
                        </div>
                    </div>
                </div>

                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📝 Job Details</h3>

                    <div class="form-group">
                        <label class="form-label">Job Description <span class="required">*</span></label>
                        <textarea name="description" class="form-control" rows="7" required><?= e($job['description']) ?></textarea>
                        <?php if (isset($errors['description'])): ?><span class="form-error"><?= e($errors['description']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="5"><?= e($job['requirements']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Key Responsibilities</label>
                        <textarea name="responsibilities" class="form-control" rows="5"><?= e($job['responsibilities']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Application Deadline <span class="required">*</span></label>
                        <input type="date" name="deadline" class="form-control" value="<?= e($job['deadline']) ?>" required>
                        <?php if (isset($errors['deadline'])): ?><span class="form-error"><?= e($errors['deadline']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Update Job</button>
                    <a href="<?= BASE_URL ?>/employer/jobs.php" class="btn btn-ghost btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
