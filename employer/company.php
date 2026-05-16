<?php
// ============================================================
// TalentBridge - Employer Company Profile (with Logo Upload)
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

    $name         = trim($_POST['name']         ?? '');
    $website      = trim($_POST['website']      ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $industry     = trim($_POST['industry']     ?? '');
    $size         = $_POST['size']              ?? '1-10';
    $founded_year = intval($_POST['founded_year'] ?? 0);
    $address      = trim($_POST['address']      ?? '');
    $city         = trim($_POST['city']         ?? '');
    $country      = trim($_POST['country']      ?? 'Bangladesh');
    $description  = trim($_POST['description']  ?? '');

    if (empty($name)) $errors['name'] = 'Company name is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) $errors['website'] = 'Invalid website URL.';
    if ($founded_year && ($founded_year < 1900 || $founded_year > intval(date('Y'))))
        $errors['founded_year'] = 'Please enter a valid year.';

    // Handle logo upload
    $logoFile = $company['logo'] ?? null;
    if (!empty($_FILES['logo']['name'])) {
        try {
            $logoFile = uploadLogo($_FILES['logo'], $company['logo'] ?? null);
        } catch (Exception $e) {
            $errors['logo'] = $e->getMessage();
        }
    }

    // Handle logo delete
    if (isset($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
        if ($logoFile && file_exists(LOGO_UPLOAD_PATH . basename($logoFile))) {
            unlink(LOGO_UPLOAD_PATH . basename($logoFile));
        }
        $logoFile = null;
    }

    if (empty($errors)) {
        if ($company) {
            $pdo->prepare("
                UPDATE companies SET
                    name=?, website=?, email=?, phone=?, industry=?, size=?,
                    founded_year=?, address=?, city=?, country=?,
                    description=?, logo=?, updated_at=NOW()
                WHERE user_id=?
            ")->execute([
                $name, $website, $email, $phone, $industry, $size,
                $founded_year ?: null, $address, $city, $country,
                $description, $logoFile, $userId
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO companies
                    (user_id, name, website, email, phone, industry, size,
                     founded_year, address, city, country, description, logo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $userId, $name, $website, $email, $phone, $industry, $size,
                $founded_year ?: null, $address, $city, $country,
                $description, $logoFile
            ]);
        }
        setFlash('success', 'Company profile saved successfully!');
        redirect(BASE_URL . '/employer/company.php');
    }

    // Repopulate on error
    $company = array_merge($company ?? [], [
        'name' => $name, 'website' => $website, 'email' => $email,
        'phone' => $phone, 'industry' => $industry, 'size' => $size,
        'founded_year' => $founded_year, 'address' => $address,
        'city' => $city, 'country' => $country, 'description' => $description,
        'logo' => $logoFile,
    ]);
}

$currentLogoUrl = getLogoUrl($company['logo'] ?? null);
$sizes = ['1-10', '11-50', '51-200', '201-500', '500+'];
$pageTitle = 'Company Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .logo-wrap { position:relative; width:100px; height:100px; flex-shrink:0; cursor:pointer; }
        .logo-wrap .logo-preview {
            width:100px; height:100px; border-radius:14px;
            object-fit:cover; border:2px solid var(--border); display:block;
        }
        .logo-wrap .logo-placeholder {
            width:100px; height:100px; border-radius:14px;
            background:var(--primary-light); border:2px dashed var(--border);
            display:flex; align-items:center; justify-content:center;
            font-family:var(--font-display); font-weight:800;
            font-size:2rem; color:var(--primary);
        }
        .logo-overlay {
            position:absolute; inset:0; border-radius:14px;
            background:rgba(0,0,0,0.45);
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            gap:3px; opacity:0; transition:opacity 0.2s;
        }
        .logo-wrap:hover .logo-overlay { opacity:1; }
        .logo-overlay .ico { font-size:1.4rem; }
        .logo-overlay span { color:white; font-size:0.62rem; font-weight:700; letter-spacing:0.5px; }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div style="width:48px;height:48px;background:var(--secondary);color:white;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            font-family:var(--font-display);font-weight:800;font-size:1.2rem;margin-bottom:10px;">
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
                <p class="dash-subtitle">
                    <?= $company ? 'Update your company information' : 'Set up your company profile to start posting jobs' ?>
                </p>
            </div>

            <form method="POST" enctype="multipart/form-data" data-validate>
                <?= csrfInput() ?>
                <input type="hidden" name="delete_logo" id="deleteLogoFlag" value="0">

                <!-- COMPANY LOGO -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;
                               padding-bottom:12px;border-bottom:1px solid var(--border);">
                        🖼 Company Logo
                    </h3>
                    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">

                        <div class="logo-wrap" id="logoWrap"
                             onclick="document.getElementById('logo').click()"
                             title="Click to upload logo">
                            <?php if ($currentLogoUrl): ?>
                                <img src="<?= e($currentLogoUrl) ?>" id="logoPreviewImg"
                                     class="logo-preview" alt="Company Logo">
                            <?php else: ?>
                                <div class="logo-placeholder" id="logoPlaceholder">
                                    <?= strtoupper(substr($company['name'] ?? $_SESSION['user']['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="logo-overlay">
                                <span class="ico">🖼</span>
                                <span>UPLOAD</span>
                            </div>
                        </div>

                        <div style="flex:1;min-width:180px;">
                            <p style="font-size:0.875rem;font-weight:600;color:var(--dark);margin-bottom:6px;">
                                Upload your company logo
                            </p>
                            <p style="font-size:0.8rem;color:var(--mid);line-height:1.7;margin-bottom:14px;">
                                JPG, PNG, WEBP or GIF &nbsp;·&nbsp; Max 3MB<br>
                                Square logos look best on job listings.
                            </p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline btn-sm"
                                        onclick="document.getElementById('logo').click()">
                                    🖼 Choose Logo
                                </button>
                                <?php if ($currentLogoUrl): ?>
                                <button type="button" id="removeLogoBtn"
                                        class="btn btn-ghost btn-sm" style="color:var(--danger);"
                                        onclick="removeLogo()">
                                    🗑 Remove
                                </button>
                                <?php endif; ?>
                            </div>
                            <p id="logoName" style="display:none;font-size:0.78rem;color:var(--mid);margin-top:8px;"></p>
                            <input type="file" id="logo" name="logo"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   style="display:none;" onchange="previewLogo(this)">
                            <?php if (isset($errors['logo'])): ?>
                                <span class="form-error" style="display:block;margin-top:6px;">
                                    <?= e($errors['logo']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- BASIC INFO -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;
                               padding-bottom:12px;border-bottom:1px solid var(--border);">
                        🏢 Basic Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Name <span class="required">*</span></label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['name'] ?? '') ?>"
                                   required placeholder="Your company name">
                            <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" class="form-control"
                                   value="<?= e($company['industry'] ?? '') ?>"
                                   placeholder="e.g. Software Development">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Size</label>
                            <select name="size" class="form-control form-select">
                                <?php foreach ($sizes as $s): ?>
                                    <option value="<?= $s ?>" <?= ($company['size'] ?? '') === $s ? 'selected' : '' ?>>
                                        <?= $s ?> employees
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Founded Year</label>
                            <input type="number" name="founded_year"
                                   class="form-control <?= isset($errors['founded_year']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['founded_year'] ?? '') ?>"
                                   placeholder="e.g. 2015" min="1900" max="<?= date('Y') ?>">
                            <?php if (isset($errors['founded_year'])): ?><span class="form-error"><?= e($errors['founded_year']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Description</label>
                        <textarea name="description" class="form-control" rows="5" maxlength="2000"
                                  placeholder="Tell job seekers about your company culture and mission..."><?= e($company['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- CONTACT & LOCATION -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;
                               padding-bottom:12px;border-bottom:1px solid var(--border);">
                        📞 Contact &amp; Location
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company Email</label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['email'] ?? '') ?>"
                                   placeholder="company@example.com">
                            <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= e($company['phone'] ?? '') ?>"
                                   placeholder="+880 17XXXXXXXX">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="url" name="website"
                                   class="form-control <?= isset($errors['website']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($company['website'] ?? '') ?>"
                                   placeholder="https://yourcompany.com">
                            <?php if (isset($errors['website'])): ?><span class="form-error"><?= e($errors['website']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= e($company['city'] ?? '') ?>"
                                   placeholder="e.g. Dhaka">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= e($company['country'] ?? 'Bangladesh') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= e($company['address'] ?? '') ?>"
                                   placeholder="Street, area, city">
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Save Company Profile</button>
                    <a href="<?= BASE_URL ?>/employer/dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 3 * 1024 * 1024) { alert('Logo too large. Max 3MB.'); input.value = ''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('logoWrap');
        const old  = wrap.querySelector('img, .logo-placeholder');
        if (old) old.remove();
        const img = document.createElement('img');
        img.src       = e.target.result;
        img.className = 'logo-preview';
        img.id        = 'logoPreviewImg';
        img.alt       = 'Logo Preview';
        wrap.insertBefore(img, wrap.querySelector('.logo-overlay'));
    };
    reader.readAsDataURL(file);
    const nm = document.getElementById('logoName');
    nm.textContent = '📎 ' + file.name + ' (' + (file.size/1024/1024).toFixed(2) + ' MB)';
    nm.style.display = 'block';
    document.getElementById('deleteLogoFlag').value = '0';
}

function removeLogo() {
    if (!confirm('Remove the company logo?')) return;
    document.getElementById('deleteLogoFlag').value = '1';
    const wrap = document.getElementById('logoWrap');
    const old  = wrap.querySelector('img, .logo-placeholder');
    if (old) old.remove();
    const ph = document.createElement('div');
    ph.className = 'logo-placeholder';
    ph.textContent = '<?= strtoupper(substr($company['name'] ?? $_SESSION['user']['name'], 0, 1)) ?>';
    wrap.insertBefore(ph, wrap.querySelector('.logo-overlay'));
    const btn = document.getElementById('removeLogoBtn');
    if (btn) btn.remove();
    document.getElementById('logo').value = '';
    const nm = document.getElementById('logoName');
    if (nm) nm.style.display = 'none';
}
</script>
</body>
</html>
