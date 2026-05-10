<?php
// ============================================================
// TalentBridge - Login Page
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();

// Already logged in
if (isLoggedIn()) {
    $role = userRole();
    redirect(BASE_URL . ($role === 'admin' ? '/admin/dashboard.php' : ($role === 'employer' ? '/employer/dashboard.php' : '/user/dashboard.php')));
}

$errors = [];
$formEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $formEmail = $email;

    if (empty($email)) $errors['email'] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if (empty($password)) $errors['password'] = 'Password is required.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['user']    = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];

            setFlash('success', 'Welcome back, ' . $user['name'] . '!');

            $redirect = match($user['role']) {
                'admin'    => BASE_URL . '/admin/dashboard.php',
                'employer' => BASE_URL . '/employer/dashboard.php',
                default    => BASE_URL . '/user/dashboard.php',
            };
            redirect($redirect);
        } else {
            $errors['general'] = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon">⬡</div>
            <a href="<?= BASE_URL ?>/index.php" style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--dark);">
                Talent<strong style="color:var(--primary);">Bridge</strong>
            </a>
        </div>

        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Sign in to your account to continue</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error"><span class="flash-icon">✕</span><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="" data-validate>
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       placeholder="you@example.com" value="<?= e($formEmail) ?>" required autocomplete="email">
                <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <div style="position:relative;">
                    <input type="password" name="password" id="passwordField"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--mid);cursor:pointer;font-size:0.85rem;">Show</button>
                </div>
                <?php if (isset($errors['password'])): ?><span class="form-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Sign In</button>
        </form>

        <div class="auth-divider"><span>Don't have an account?</span></div>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline btn-block">Create Account</a>

        <p class="auth-footer">
            <a href="<?= BASE_URL ?>/index.php">← Back to Home</a>
        </p>

        <div style="margin-top:24px;padding:14px;background:var(--bg);border-radius:var(--radius-sm);font-size:0.78rem;color:var(--mid);">
            <strong>Demo Accounts:</strong><br>
            Admin: admin@talentbridge.com / password<br>
            Employer: employer@techinno.com / password<br>
            Job Seeker: rafiq@example.com / password
        </div>
    </div>
</div>

<script>
function togglePass() {
    const f = document.getElementById('passwordField');
    f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
