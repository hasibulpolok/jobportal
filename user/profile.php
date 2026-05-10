<?php
// ============================================================
// TalentBridge - Job Seeker Profile Page
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('jobseeker');

$pdo    = getDB();
$userId = $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$profStmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profStmt->execute([$userId]);
$profile = $profStmt->fetch();

if (!$profile) {
    $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)")->execute([$userId]);
    $profStmt->execute([$userId]);
    $profile = $profStmt->fetch();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $name       = trim($_POST['name'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $city       = trim($_POST['city'] ?? '');
    $country    = trim($_POST['country'] ?? 'Bangladesh');
    $address    = trim($_POST['address'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $education  = trim($_POST['education'] ?? '');
    $linkedin   = trim($_POST['linkedin'] ?? '');
    $website    = trim($_POST['website'] ?? '');

    if (empty($name))  $errors['name']  = 'Full name is required.';
    if (strlen($bio) > 1000) $errors['bio'] = 'Bio must be under 1000 characters.';

    $cvFile = $profile['cv_file'];
    if (!empty($_FILES['cv_file']['name'])) {
        try {
            $cvFile = uploadCV($_FILES['cv_file']);
            if ($profile['cv_file'] && file_exists(UPLOAD_PATH . $profile['cv_file'])) {
                unlink(UPLOAD_PATH . $profile['cv_file']);
            }
        } catch (Exception $e) {
            $errors['cv_file'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")
            ->execute([$name, $userId]);
        $pdo->prepare("UPDATE user_profiles SET phone=?,city=?,country=?,address=?,bio=?,skills=?,experience=?,education=?,cv_file=?,linkedin=?,website=? WHERE user_id=?")
            ->execute([$phone,$city,$country,$address,$bio,$skills,$experience,$education,$cvFile,$linkedin,$website,$userId]);
        $_SESSION['user']['name'] = $name;
        setFlash('success', 'Profile updated successfully!');
        redirect(BASE_URL . '/user/profile.php');
    }
}

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My Profile | TalentBridge</title>
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
                <a href="<?= BASE_URL ?>/user/profile.php" class="active"><span class="nav-icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/user/applications.php"><span class="nav-icon">📋</span> Applications</a>
                <a href="<?= BASE_URL ?>/user/saved-jobs.php"><span class="nav-icon">❤️</span> Saved Jobs</a>
                <a href="<?= BASE_URL ?>/jobs.php"><span class="nav-icon">🔍</span> Browse Jobs</a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="<?= BASE_URL ?>/auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
            </nav>
        </div>
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">My Profile</h1>
                <p class="dash-subtitle">Keep your profile updated to attract the best employers</p>
            </div>
            <form method="POST" enctype="multipart/form-data" data-validate>
                <?= csrfInput() ?>
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">👤 Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>" value="<?= e($user['name']) ?>" required>
                            <?php if(isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled style="background:var(--bg);color:var(--mid);">
                            <span class="form-hint">Email cannot be changed.</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>" placeholder="+880 1XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($profile['city'] ?? '') ?>" placeholder="Dhaka">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= e($profile['country'] ?? 'Bangladesh') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e($profile['address'] ?? '') ?>" placeholder="Street address">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Professional Bio</label>
                        <textarea name="bio" class="form-control" rows="4" maxlength="1000" placeholder="Write a short summary about yourself..."><?= e($profile['bio'] ?? '') ?></textarea>
                        <?php if(isset($errors['bio'])): ?><span class="form-error"><?= e($errors['bio']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">💼 Professional Details</h3>
                    <div class="form-group">
                        <label class="form-label">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= e($profile['skills'] ?? '') ?>" placeholder="PHP, JavaScript, MySQL, HTML, CSS (comma separated)">
                        <span class="form-hint">Separate skills with commas for better visibility.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Work Experience</label>
                        <textarea name="experience" class="form-control" rows="5" placeholder="Describe your work experience..."><?= e($profile['experience'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Education</label>
                        <textarea name="education" class="form-control" rows="4" placeholder="Your educational background..."><?= e($profile['education'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">📄 CV / Resume</h3>
                    <?php if ($profile['cv_file']): ?>
                    <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                        <div>
                            <div style="font-weight:600;font-size:0.875rem;">📎 <?= e($profile['cv_file']) ?></div>
                            <div style="font-size:0.78rem;color:var(--mid);">Current CV on file</div>
                        </div>
                        <a href="<?= BASE_URL ?>/uploads/cvs/<?= e($profile['cv_file']) ?>" target="_blank" class="btn btn-ghost btn-sm">View CV</a>
                    </div>
                    <?php endif; ?>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label"><?= $profile['cv_file'] ? 'Upload New CV (replaces current)' : 'Upload Your CV' ?></label>
                        <input type="file" name="cv_file" id="cv_file" accept=".pdf,.doc,.docx" class="form-control" style="padding:10px;">
                        <?php if(isset($errors['cv_file'])): ?><span class="form-error"><?= e($errors['cv_file']) ?></span><?php endif; ?>
                        <span id="cvLabel" class="form-hint">Accepted: PDF, DOC, DOCX. Max size: 5MB</span>
                    </div>
                </div>
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">🔗 Social Links</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">LinkedIn Profile</label>
                            <input type="url" name="linkedin" class="form-control" value="<?= e($profile['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/yourname">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Personal Website / Portfolio</label>
                            <input type="url" name="website" class="form-control" value="<?= e($profile['website'] ?? '') ?>" placeholder="https://yourportfolio.com">
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary">💾 Save Profile</button>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
