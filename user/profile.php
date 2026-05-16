<?php
// ============================================================
// TalentBridge - Job Seeker Profile (Photo + CV Upload)
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

    $name       = trim($_POST['name']       ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $address    = trim($_POST['address']    ?? '');
    $city       = trim($_POST['city']       ?? '');
    $country    = trim($_POST['country']    ?? 'Bangladesh');
    $bio        = trim($_POST['bio']        ?? '');
    $skills     = trim($_POST['skills']     ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $education  = trim($_POST['education']  ?? '');
    $linkedin   = trim($_POST['linkedin']   ?? '');
    $website    = trim($_POST['website']    ?? '');

    if (empty($name)) $errors['name'] = 'Full name is required.';
    if ($phone && !preg_match('/^[\+\d\s\-]{7,20}$/', $phone)) $errors['phone'] = 'Invalid phone number.';
    if ($linkedin && !filter_var($linkedin, FILTER_VALIDATE_URL)) $errors['linkedin'] = 'Invalid LinkedIn URL.';
    if ($website  && !filter_var($website,  FILTER_VALIDATE_URL)) $errors['website']  = 'Invalid website URL.';

    // Handle photo delete flag
    $photoFile = $profile['profile_photo'] ?? null;
    if (isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1') {
        if ($photoFile && file_exists(PHOTO_UPLOAD_PATH . basename($photoFile))) {
            unlink(PHOTO_UPLOAD_PATH . basename($photoFile));
        }
        $photoFile = null;
    }

    // Handle new photo upload (overrides delete if both somehow sent)
    if (!empty($_FILES['profile_photo']['name'])) {
        try {
            $photoFile = uploadPhoto($_FILES['profile_photo'], $profile['profile_photo'] ?? null);
        } catch (Exception $e) {
            $errors['profile_photo'] = $e->getMessage();
        }
    }

    // Handle CV upload
    $cvFile = $profile['cv_file'] ?? null;
    if (!empty($_FILES['cv_file']['name'])) {
        try {
            if ($cvFile && file_exists(UPLOAD_PATH . basename($cvFile))) {
                unlink(UPLOAD_PATH . basename($cvFile));
            }
            $cvFile = uploadCV($_FILES['cv_file']);
        } catch (Exception $e) {
            $errors['cv_file'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$name, $userId]);

        $pdo->prepare("
            UPDATE user_profiles SET
                phone=?, address=?, city=?, country=?, bio=?,
                skills=?, experience=?, education=?,
                cv_file=?, profile_photo=?,
                linkedin=?, website=?, updated_at=NOW()
            WHERE user_id=?
        ")->execute([
            $phone, $address, $city, $country, $bio,
            $skills, $experience, $education,
            $cvFile, $photoFile,
            $linkedin, $website, $userId
        ]);

        $_SESSION['user']['name'] = $name;
        setFlash('success', 'Profile updated successfully!');
        redirect(BASE_URL . '/user/profile.php');
    }

    // Repopulate on error
    $user['name'] = $name;
    $profile = array_merge($profile, [
        'phone' => $phone, 'address' => $address, 'city' => $city,
        'country' => $country, 'bio' => $bio, 'skills' => $skills,
        'experience' => $experience, 'education' => $education,
        'linkedin' => $linkedin, 'website' => $website,
        'profile_photo' => $photoFile, 'cv_file' => $cvFile,
    ]);
}

// Completion score
$checks = [
    $profile['phone'] ?? '', $profile['bio'] ?? '', $profile['skills'] ?? '',
    $profile['cv_file'] ?? '', $profile['city'] ?? '', $profile['experience'] ?? '',
    $profile['education'] ?? '', $profile['profile_photo'] ?? '',
];
$completion = round(count(array_filter($checks)) / count($checks) * 100);
$currentPhotoUrl = getPhotoUrl($profile['profile_photo'] ?? null);

$palette = ['#1a56db','#7c3aed','#db2777','#059669','#d97706'];
$avatarBg = $palette[abs(crc32($user['name'])) % count($palette)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .photo-wrap { position:relative; width:100px; height:100px; flex-shrink:0; cursor:pointer; }
        .photo-wrap img,
        .photo-wrap .avatar-circle {
            width:100px; height:100px; border-radius:50%;
            border:3px solid var(--border); display:block;
        }
        .photo-wrap img { object-fit:cover; }
        .avatar-circle {
            display:flex; align-items:center; justify-content:center;
            font-family:var(--font-display); font-weight:800;
            font-size:2.2rem; color:white;
        }
        .photo-overlay {
            position:absolute; inset:0; border-radius:50%;
            background:rgba(0,0,0,0.5);
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            gap:3px; opacity:0; transition:opacity 0.2s;
        }
        .photo-wrap:hover .photo-overlay { opacity:1; }
        .photo-overlay .ico  { font-size:1.4rem; }
        .photo-overlay span  { color:white; font-size:0.62rem; font-weight:700; letter-spacing:0.5px; }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="dashboard">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <?= renderAvatar($user['name'], $profile['profile_photo'] ?? null, 48, 'margin-bottom:10px;') ?>
                <div class="sidebar-user-name"><?= e($user['name']) ?></div>
                <div class="sidebar-user-role">Job Seeker</div>
                <div style="margin-top:10px;">
                    <div style="font-size:0.72rem;color:var(--mid);margin-bottom:4px;">Profile <?= $completion ?>% complete</div>
                    <div style="background:var(--border);border-radius:50px;height:5px;overflow:hidden;">
                        <div style="width:<?= $completion ?>%;background:var(--primary);height:100%;border-radius:50px;"></div>
                    </div>
                </div>
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

        <!-- Main Content -->
        <div class="dash-content">
            <div class="dash-header">
                <h1 class="dash-title">My Profile</h1>
                <p class="dash-subtitle">Keep your profile updated to attract employers</p>
            </div>

            <form method="POST" enctype="multipart/form-data" data-validate>
                <?= csrfInput() ?>
                <input type="hidden" name="delete_photo" id="deletePhotoFlag" value="0">

                <!-- PROFILE PHOTO -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                        📸 Profile Photo
                    </h3>
                    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">

                        <!-- Preview circle -->
                        <div class="photo-wrap" id="photoWrap"
                             onclick="document.getElementById('profile_photo').click()"
                             title="Click to change photo">
                            <?php if ($currentPhotoUrl): ?>
                                <img src="<?= e($currentPhotoUrl) ?>" id="photoPreviewImg" alt="Profile photo">
                            <?php else: ?>
                                <div class="avatar-circle" id="photoInitials" style="background:<?= $avatarBg ?>;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="photo-overlay">
                                <span class="ico">📷</span>
                                <span>CHANGE</span>
                            </div>
                        </div>

                        <!-- Right side text + buttons -->
                        <div style="flex:1;min-width:180px;">
                            <p style="font-size:0.875rem;font-weight:600;color:var(--dark);margin-bottom:6px;">
                                Upload a professional headshot
                            </p>
                            <p style="font-size:0.8rem;color:var(--mid);line-height:1.7;margin-bottom:14px;">
                                JPG, PNG, WEBP or GIF &nbsp;·&nbsp; Max 3MB<br>
                                Square images look best. Min 200×200 px.
                            </p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline btn-sm"
                                        onclick="document.getElementById('profile_photo').click()">
                                    📷 Choose Photo
                                </button>
                                <?php if ($currentPhotoUrl): ?>
                                <button type="button" id="removePhoBtn"
                                        class="btn btn-ghost btn-sm" style="color:var(--danger);"
                                        onclick="removePhoto()">
                                    🗑 Remove
                                </button>
                                <?php endif; ?>
                            </div>
                            <p id="photoName" style="display:none;font-size:0.78rem;color:var(--mid);margin-top:8px;"></p>
                            <input type="file" id="profile_photo" name="profile_photo"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   style="display:none;" onchange="previewPhoto(this)">
                            <?php if (isset($errors['profile_photo'])): ?>
                                <span class="form-error" style="display:block;margin-top:6px;">
                                    <?= e($errors['profile_photo']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- PERSONAL INFO -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                        👤 Personal Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($user['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>"
                                   disabled style="background:var(--bg);cursor:not-allowed;">
                            <span class="form-hint">Email cannot be changed.</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone"
                                   class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   placeholder="+880 17XXXXXXXX"
                                   value="<?= e($profile['phone'] ?? '') ?>">
                            <?php if (isset($errors['phone'])): ?><span class="form-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   placeholder="e.g. Dhaka" value="<?= e($profile['city'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= e($profile['country'] ?? 'Bangladesh') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Address</label>
                            <input type="text" name="address" class="form-control"
                                   placeholder="Your full address"
                                   value="<?= e($profile['address'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Professional Bio</label>
                        <textarea name="bio" class="form-control" rows="4" maxlength="1000"
                                  placeholder="A brief professional summary about yourself..."><?= e($profile['bio'] ?? '') ?></textarea>
                        <span class="form-hint">Shown to employers viewing your application.</span>
                    </div>
                </div>

                <!-- SKILLS & EXPERIENCE -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                        💼 Skills &amp; Experience
                    </h3>
                    <div class="form-group">
                        <label class="form-label">Skills</label>
                        <input type="text" name="skills" class="form-control"
                               placeholder="PHP, JavaScript, MySQL, Laravel (comma separated)"
                               value="<?= e($profile['skills'] ?? '') ?>">
                        <span class="form-hint">Separate each skill with a comma.</span>
                    </div>
                    <?php if (!empty($profile['skills'])): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin:-6px 0 16px;">
                        <?php foreach (explode(',', $profile['skills']) as $sk): ?>
                            <?php if (trim($sk)): ?><span class="tag"><?= e(trim($sk)) ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Work Experience</label>
                        <textarea name="experience" class="form-control" rows="5" maxlength="2000"
                                  placeholder="e.g. Web Developer at XYZ (2022–2024)&#10;• Built REST APIs using Laravel&#10;• Managed MySQL databases"><?= e($profile['experience'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Education</label>
                        <textarea name="education" class="form-control" rows="4" maxlength="1000"
                                  placeholder="e.g. BSc Computer Science, University of Dhaka (2018–2022)"><?= e($profile['education'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- CV UPLOAD -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                        📄 CV / Resume
                    </h3>
                    <?php if (!empty($profile['cv_file'])): ?>
                    <div style="background:var(--primary-light);border:1px solid rgba(26,86,219,0.2);
                                border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:16px;
                                display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                        <div>
                            <strong style="font-size:0.875rem;">📎 Current CV: <?= e($profile['cv_file']) ?></strong>
                            <p style="font-size:0.78rem;color:var(--mid);margin-top:2px;">Upload below to replace.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/uploads/cvs/<?= e($profile['cv_file']) ?>"
                           target="_blank" class="btn btn-outline btn-sm">View CV ↗</a>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label"><?= !empty($profile['cv_file']) ? 'Replace CV' : 'Upload CV' ?></label>
                        <div id="cvDropZone"
                             style="border:2px dashed var(--border);border-radius:var(--radius-sm);
                                    padding:28px;text-align:center;cursor:pointer;transition:var(--transition);"
                             onclick="document.getElementById('cv_file').click()">
                            <div style="font-size:2rem;margin-bottom:8px;">📄</div>
                            <div id="cvLabel" style="font-weight:600;color:var(--dark);margin-bottom:4px;">
                                Click or drag &amp; drop your CV here
                            </div>
                            <div style="font-size:0.78rem;color:var(--mid);">PDF or DOCX &nbsp;·&nbsp; Max 5MB</div>
                        </div>
                        <input type="file" id="cv_file" name="cv_file"
                               accept=".pdf,.doc,.docx" style="display:none;">
                        <?php if (isset($errors['cv_file'])): ?>
                            <span class="form-error"><?= e($errors['cv_file']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ONLINE PRESENCE -->
                <div class="form-card" style="margin-bottom:24px;">
                    <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                        🌐 Online Presence
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">LinkedIn Profile</label>
                            <input type="url" name="linkedin"
                                   class="form-control <?= isset($errors['linkedin']) ? 'is-invalid' : '' ?>"
                                   placeholder="https://linkedin.com/in/yourname"
                                   value="<?= e($profile['linkedin'] ?? '') ?>">
                            <?php if (isset($errors['linkedin'])): ?><span class="form-error"><?= e($errors['linkedin']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Portfolio / Website</label>
                            <input type="url" name="website"
                                   class="form-control <?= isset($errors['website']) ? 'is-invalid' : '' ?>"
                                   placeholder="https://yourwebsite.com"
                                   value="<?= e($profile['website'] ?? '') ?>">
                            <?php if (isset($errors['website'])): ?><span class="form-error"><?= e($errors['website']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Save Profile</button>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Photo preview
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 3 * 1024 * 1024) {
        alert('Photo is too large. Maximum allowed is 3MB.');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('photoWrap');
        // Remove old element (img or initials div)
        const old = wrap.querySelector('img, .avatar-circle');
        if (old) old.remove();
        const img = document.createElement('img');
        img.src = e.target.result;
        img.id  = 'photoPreviewImg';
        img.alt = 'Preview';
        img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:block;';
        wrap.insertBefore(img, wrap.querySelector('.photo-overlay'));
    };
    reader.readAsDataURL(file);
    const nm = document.getElementById('photoName');
    nm.textContent = '📎 ' + file.name + ' (' + (file.size/1024/1024).toFixed(2) + ' MB)';
    nm.style.display = 'block';
    document.getElementById('deletePhotoFlag').value = '0';
}

// Remove photo
function removePhoto() {
    if (!confirm('Remove your profile photo?')) return;
    document.getElementById('deletePhotoFlag').value = '1';
    const wrap = document.getElementById('photoWrap');
    const old  = wrap.querySelector('img, .avatar-circle');
    if (old) old.remove();
    const init = document.createElement('div');
    init.className = 'avatar-circle';
    init.style.background = '<?= $avatarBg ?>';
    init.textContent = '<?= strtoupper(substr($user['name'], 0, 1)) ?>';
    wrap.insertBefore(init, wrap.querySelector('.photo-overlay'));
    const btn = document.getElementById('removePhoBtn');
    if (btn) btn.remove();
    const nm = document.getElementById('photoName');
    if (nm) nm.style.display = 'none';
    // Clear file input
    document.getElementById('profile_photo').value = '';
}

// CV drop zone
const cvDrop  = document.getElementById('cvDropZone');
const cvInput = document.getElementById('cv_file');
cvDrop.addEventListener('dragover',  e => { e.preventDefault(); cvDrop.style.borderColor='var(--primary)'; cvDrop.style.background='var(--primary-light)'; });
cvDrop.addEventListener('dragleave', () => { cvDrop.style.borderColor=''; cvDrop.style.background=''; });
cvDrop.addEventListener('drop', e => {
    e.preventDefault(); cvDrop.style.borderColor=''; cvDrop.style.background='';
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const ok = ['application/pdf','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!ok.includes(file.type)) { alert('Only PDF or DOCX allowed.'); return; }
    const dt = new DataTransfer(); dt.items.add(file); cvInput.files = dt.files;
    document.getElementById('cvLabel').textContent = '📎 ' + file.name + ' (' + (file.size/1024/1024).toFixed(2) + ' MB)';
});
cvInput.addEventListener('change', () => {
    const f = cvInput.files[0];
    if (f) document.getElementById('cvLabel').textContent = '📎 ' + f.name + ' (' + (f.size/1024/1024).toFixed(2) + ' MB)';
});
</script>
</body>
</html>
