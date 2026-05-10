<?php
// ============================================================
// TalentBridge - Post a Job
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Employer must have a company first
$compStmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
$compStmt->execute([$userId]);
$company = $compStmt->fetch();

if (!$company) {
    setFlash('warning', 'Please set up your company profile before posting jobs.');
    redirect(BASE_URL . '/employer/company.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];
$form   = [
    'title'=>'','category_id'=>'','type'=>'full-time','location'=>'','city'=>'',
    'salary_min'=>'','salary_max'=>'','salary_type'=>'monthly','experience_level'=>'entry',
    'education_level'=>'','skills_required'=>'','description'=>'','requirements'=>'',
    'responsibilities'=>'','deadline'=>''
];

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

    // Validate
    if (empty($form['title']))        $errors['title']       = 'Job title is required.';
    if (!$form['category_id'])        $errors['category_id'] = 'Please select a category.';
    if (empty($form['description']))  $errors['description'] = 'Job description is required.';
    if (empty($form['city']))         $errors['city']        = 'City is required.';
    if (empty($form['deadline']))     $errors['deadline']    = 'Application deadline is required.';
    elseif (strtotime($form['deadline']) <= time()) $errors['deadline'] = 'Deadline must be a future date.';
    if (!in_array($form['type'], ['full-time','part-time','remote','contract','internship','freelance']))
        $errors['type'] = 'Invalid job type.';

    if (empty($errors)) {
        $slug = uniqueSlug($pdo, 'jobs', $form['title'] . ' ' . $company['name']);

        $stmt = $pdo->prepare("
            INSERT INTO jobs (employer_id, company_id, category_id, title, slug, description,
                requirements, responsibilities, type, location, city, salary_min, salary_max,
                salary_type, experience_level, education_level, skills_required, deadline, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')
        ");
        $stmt->execute([
            $userId, $company['id'], $form['category_id'], $form['title'], $slug,
            $form['description'], $form['requirements'], $form['responsibilities'],
            $form['type'], $form['location'], $form['city'],
            $form['salary_min'] ?: null, $form['salary_max'] ?: null,
            $form['salary_type'], $form['experience_level'],
            $form['education_level'], $form['skills_required'], $form['deadline']
        ]);

        setFlash('success', 'Job posted successfully! It will be visible after admin approval.');
        redirect(BASE_URL . '/employer/jobs.php');
    }
}

$pageTitle = 'Post a Job';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job | TalentBridge</title>
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
                <div class="sidebar-user-name"><?= e($company['name']) ?></div>
                <div class="sidebar-user-role">Employer</div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/employer/dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/employer/company.php"><span class="nav-icon">🏢</span> Company Profile</a>
                <a href="<?= BASE_URL ?>/employer/post-job.php" class="active"><span class="nav-icon">➕</span> Post a Job</a>
                <a href="<?= BASE_URL ?>/employer/jobs.php"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">Post a New Job</h1>
                <p class="dash-subtitle">Fill in the details to attract the best candidates</p>
            </div>

            <form method="POST" data-validate>
                <?= csrfInput() ?>

                <!-- Basic Info -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📌 Job Information</h3>

                    <div class="form-group">
                        <label class="form-label">Job Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               value="<?= e($form['title']) ?>" placeholder="e.g. Senior PHP Developer" required>
                        <?php if (isset($errors['title'])): ?><span class="form-error"><?= e($errors['title']) ?></span><?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category <span class="required">*</span></label>
                            <select name="category_id" class="form-control form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $form['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><span class="form-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Job Type <span class="required">*</span></label>
                            <select name="type" class="form-control form-select" required>
                                <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','remote'=>'Remote','contract'=>'Contract','internship'=>'Internship','freelance'=>'Freelance'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $form['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City <span class="required">*</span></label>
                            <input type="text" name="city" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['city']) ?>" placeholder="e.g. Dhaka" required>
                            <?php if (isset($errors['city'])): ?><span class="form-error"><?= e($errors['city']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?= e($form['location']) ?>" placeholder="e.g. Banani, Dhaka, Bangladesh">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Experience Level</label>
                            <select name="experience_level" class="form-control form-select">
                                <?php foreach (['entry'=>'Entry Level','mid'=>'Mid Level','senior'=>'Senior Level','lead'=>'Lead/Manager','executive'=>'Executive'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $form['experience_level'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Education Requirement</label>
                            <input type="text" name="education_level" class="form-control"
                                   value="<?= e($form['education_level']) ?>" placeholder="e.g. BSc in Computer Science">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required Skills</label>
                        <input type="text" name="skills_required" class="form-control"
                               value="<?= e($form['skills_required']) ?>" placeholder="PHP, Laravel, MySQL, JavaScript (comma separated)">
                        <span class="form-hint">Separate skills with commas.</span>
                    </div>
                </div>

                <!-- Salary -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">💰 Salary Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Salary Type</label>
                            <select name="salary_type" id="salary_type" class="form-control form-select">
                                <?php foreach (['monthly'=>'Monthly','yearly'=>'Yearly','hourly'=>'Hourly','negotiable'=>'Negotiable'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $form['salary_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="salaryFields" class="form-row" style="<?= $form['salary_type'] === 'negotiable' ? 'display:none;' : '' ?>">
                        <div class="form-group">
                            <label class="form-label">Minimum Salary (৳)</label>
                            <input type="number" name="salary_min" class="form-control"
                                   value="<?= e($form['salary_min']) ?>" placeholder="e.g. 30000" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Maximum Salary (৳)</label>
                            <input type="number" name="salary_max" class="form-control"
                                   value="<?= e($form['salary_max']) ?>" placeholder="e.g. 60000" min="0">
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📝 Job Details</h3>

                    <div class="form-group">
                        <label class="form-label">Job Description <span class="required">*</span></label>
                        <textarea name="description" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  rows="7" required placeholder="Describe the role, team, and what the job involves..."><?= e($form['description']) ?></textarea>
                        <?php if (isset($errors['description'])): ?><span class="form-error"><?= e($errors['description']) ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="5"
                                  placeholder="List the qualifications and requirements for this role..."><?= e($form['requirements']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Key Responsibilities</label>
                        <textarea name="responsibilities" class="form-control" rows="5"
                                  placeholder="List the main responsibilities of this role..."><?= e($form['responsibilities']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Application Deadline <span class="required">*</span></label>
                        <input type="date" name="deadline" class="form-control <?= isset($errors['deadline']) ? 'is-invalid' : '' ?>"
                               value="<?= e($form['deadline']) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        <?php if (isset($errors['deadline'])): ?><span class="form-error"><?= e($errors['deadline']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div style="background:var(--primary-light);border:1px solid rgba(26,86,219,0.15);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:20px;">
                    <strong style="font-size:0.875rem;">ℹ️ Note:</strong>
                    <span style="font-size:0.875rem;color:var(--mid);"> Your job posting will be reviewed by our admin team before going live. This usually takes a few hours.</span>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary btn-lg">🚀 Post Job</button>
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
