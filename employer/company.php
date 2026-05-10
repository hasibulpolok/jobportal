<?php
// ============================================================
// TalentBridge - Company Profile
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->execute([$userId]);
$company = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $name         = trim($_POST['name'] ?? '');
    $website      = trim($_POST['website'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $industry     = trim($_POST['industry'] ?? '');
    $size         = $_POST['size'] ?? '1-10';
    $founded_year = intval($_POST['founded_year'] ?? 0);
    $address      = trim($_POST['address'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $country      = trim($_POST['country'] ?? 'Bangladesh');
    $description  = trim($_POST['description'] ?? '');

    if (empty($name)) $errors['name'] = 'Company name is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($founded_year && ($founded_year < 1900 || $founded_year > date('Y'))) $errors['founded_year'] = 'Invalid year.';

    if (empty($errors)) {
        if ($company) {
            $pdo->prepare("
                UPDATE companies SET name=?,website=?,email=?,phone=?,industry=?,size=?,
                founded_year=?,address=?,city=?,country=?,description=?,updated_at=NOW()
                WHERE user_id=?
            ")->execute([$name,$website,$email,$phone,$industry,$size,$founded_year?:null,$address,$city,$country,$description,$userId]);
        } else {
            $pdo->prepare("
                INSERT INTO companies (user_id,name,website,email,phone,industry,size,founded_year,address,city,country,description)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([$userId,$name,$website,$email,$phone,$industry,$size,$founded_year?:null,$address,$city,$country,$description]);
        }
        setFlash('success', 'Company profile saved successfully!');
        redirect(BASE_URL . '/employer/company.php');
    }

    // Repopulate
    $company = array_merge($company ?? [], [
        'name'=>$name,'website'=>$website,'email'=>$email,'phone'=>$phone,
        'industry'=>$industry,'size'=>$size,'founded_year'=>$founded_year,
        'address'=>$address,'city'=>$city,'country'=>$country,'description'=>$description
    ]);
}

$pageTitle = 'Company Profile';
$sizes = ['1-10','11-50','51-200','201-500','500+'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile | TalentBridge</title>
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
                <a href="<?= BASE_URL ?>/employer/company.php" class="active"><span class="nav-icon">🏢</span> Company Profile</a>
                <a href="<?= BASE_URL ?>/employer/post-job.php"><span class="nav-icon">➕</span> Post a Job</a>
                <a href="<?= BASE_URL ?>/employer/jobs.php"><span class="nav-icon">💼</span> My Jobs</a>
                <a href="<?= BASE_URL ?>/employer/applicants.php"><span class="nav-icon">👥</span> All Applicants</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">Company Profile</h1>
                <p class="dash-subtitle"><?= $company ? 'Update your company information' : 'Set up your company profile to start posting jobs' ?></p>
            </div>

            <form method="POST" data-validate>
                <?= csrfInput() ?>

                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">🏢 Basic Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['name'] ?? '') ?>" required placeholder="Your company name">
                            <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" class="form-control"
                                   value="<?= e($company['industry'] ?? '') ?>" placeholder="e.g. Software Development">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Size</label>
                            <select name="size" class="form-control form-select">
                                <?php foreach ($sizes as $s): ?>
                                    <option value="<?= $s ?>" <?= ($company['size'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?> employees</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Founded Year</label>
                            <input type="number" name="founded_year" class="form-control <?= isset($errors['founded_year']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['founded_year'] ?? '') ?>" placeholder="e.g. 2015" min="1900" max="<?= date('Y') ?>">
                            <?php if (isset($errors['founded_year'])): ?><span class="form-error"><?= e($errors['founded_year']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Company Description</label>
                        <textarea name="description" class="form-control" rows="5" maxlength="2000"
                                  placeholder="Tell job seekers about your company, culture, and mission..."><?= e($company['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📞 Contact & Location</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Email</label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['email'] ?? '') ?>" placeholder="company@example.com">
                            <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= e($company['phone'] ?? '') ?>" placeholder="+880 17XXXXXXXX">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" class="form-control"
                                   value="<?= e($company['website'] ?? '') ?>" placeholder="https://yourcompany.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= e($company['city'] ?? '') ?>" placeholder="e.g. Dhaka">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= e($company['country'] ?? 'Bangladesh') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= e($company['address'] ?? '') ?>" placeholder="Full address">
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Save Company Profile</button>
                    <a href="<?= BASE_URL ?>/employer/dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
