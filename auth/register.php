<?php
// ============================================================
// TalentBridge - Register Page
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];
$form = ['name' => '', 'email' => '', 'role' => $_GET['role'] ?? 'jobseeker'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $form['name']  = trim($_POST['name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['role']  = $_POST['role'] ?? 'jobseeker';
    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($form['name'])) $errors['name'] = 'Full name is required.';
    elseif (strlen($form['name']) < 2) $errors['name'] = 'Name must be at least 2 characters.';

    if (empty($form['email'])) $errors['email'] = 'Email is required.';
    elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';

    if (empty($password)) $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';

    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (!in_array($form['role'], ['jobseeker', 'employer'])) $errors['role'] = 'Invalid role selected.';

    if (empty($errors)) {
        $pdo = getDB();
        // Check email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$form['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$form['name'], $form['email'], $hashed, $form['role']]);
            $userId = $pdo->lastInsertId();

            // Create profile row for jobseeker
            if ($form['role'] === 'jobseeker') {
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            }

            setFlash('success', 'Account created successfully! Please login.');
            redirect(BASE_URL . '/auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo">
            <div class="brand-icon">⬡</div>
            <a href="<?= BASE_URL ?>/index.php" style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--dark);">
                Talent<strong style="color:var(--primary);">Bridge</strong>
            </a>
        </div>

        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Join thousands of professionals on TalentBridge</p>

        <form method="POST" action="" data-validate>
            <?= csrfInput() ?>

            <!-- Role Selection -->
            <div class="form-group">
                <label class="form-label">I am a <span class="required">*</span></label>
                <div class="role-select">
                    <label class="role-option">
                        <input type="radio" name="role" value="jobseeker" <?= $form['role'] === 'jobseeker' ? 'checked' : '' ?>>
                        <div class="role-box">
                            <div class="role-emoji">🧑‍💼</div>
                            <div class="role-name">Job Seeker</div>
                            <div style="font-size:0.72rem;color:var(--mid);margin-top:4px;">Looking for work</div>
                        </div>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="employer" <?= $form['role'] === 'employer' ? 'checked' : '' ?>>
                        <div class="role-box">
                            <div class="role-emoji">🏢</div>
                            <div class="role-name">Employer</div>
                            <div style="font-size:0.72rem;color:var(--mid);margin-top:4px;">Hiring talent</div>
                        </div>
                    </label>
                </div>
                <?php if (isset($errors['role'])): ?><span class="form-error"><?= e($errors['role']) ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       placeholder="Your full name" value="<?= e($form['name']) ?>" required>
                <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       placeholder="you@example.com" value="<?= e($form['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Min. 8 characters" required minlength="8">
                    <?php if (isset($errors['password'])): ?><span class="form-error"><?= e($errors['password']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           placeholder="Repeat password" required>
                    <?php if (isset($errors['confirm_password'])): ?><span class="form-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <div class="auth-divider"><span>Already have an account?</span></div>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline btn-block">Sign In</a>

        <p class="auth-footer"><a href="<?= BASE_URL ?>/index.php">← Back to Home</a></p>
    </div>
</div>

<script>
// Highlight role option dynamically
document.querySelectorAll('.role-option input').forEach(input => {
    input.addEventListener('change', () => {
        document.querySelectorAll('.role-box').forEach(b => b.style.borderColor = '');
    });
});
</script>
</body>
</html>
