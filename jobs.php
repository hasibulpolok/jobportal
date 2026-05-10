<?php
// ============================================================
// TalentBridge - Browse Jobs Page
// Developer: Hasibul Polok
// ============================================================
require_once 'config/db.php';
require_once 'includes/functions.php';

startSecureSession();
$pdo = getDB();

// Filters
$q         = trim($_GET['q'] ?? '');
$category  = intval($_GET['category'] ?? 0);
$location  = trim($_GET['location'] ?? '');
$type      = $_GET['type'] ?? '';
$exp_level = $_GET['exp_level'] ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));
$perPage   = 10;

// Build query
$where = ["j.status = 'approved'", "j.deadline >= CURDATE()"];
$params = [];

if ($q) { $where[] = "(j.title LIKE ? OR j.description LIKE ? OR j.skills_required LIKE ?)"; $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]); }
if ($category) { $where[] = "j.category_id = ?"; $params[] = $category; }
if ($location) { $where[] = "(j.city LIKE ? OR j.location LIKE ?)"; $params[] = "%$location%"; $params[] = "%$location%"; }
if ($type) { $where[] = "j.type = ?"; $params[] = $type; }
if ($exp_level) { $where[] = "j.experience_level = ?"; $params[] = $exp_level; }

$whereClause = implode(' AND ', $where);
$baseSql = "
    SELECT j.*, c.name AS company_name, c.logo AS company_logo, cat.name AS category_name
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    JOIN categories cat ON j.category_id = cat.id
    WHERE $whereClause
    ORDER BY j.is_featured DESC, j.created_at DESC
";

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j JOIN companies c ON j.company_id = c.id JOIN categories cat ON j.category_id = cat.id WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("$baseSql LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Saved jobs for logged-in seeker
$savedJobIds = [];
if (isLoggedIn() && userRole() === 'jobseeker') {
    $stmt2 = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $savedJobIds = array_column($stmt2->fetchAll(), 'job_id');
}

$pageTitle = 'Browse Jobs';
$baseFilterUrl = BASE_URL . '/jobs.php?' . http_build_query(array_filter(['q' => $q, 'category' => $category ?: '', 'location' => $location, 'type' => $type, 'exp_level' => $exp_level]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | TalentBridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>window.BASE_URL='<?= BASE_URL ?>';window.CSRF_TOKEN='<?= generateCSRF() ?>';</script>
</head>
<body>
<?php require_once 'includes/header.php'; ?>

<div style="background:var(--dark);color:white;padding:40px 0;">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:1.8rem;font-weight:800;margin-bottom:6px;">Browse Jobs</h1>
        <p style="color:#94a3b8;"><?= number_format($total) ?> job<?= $total != 1 ? 's' : '' ?> found<?= $q ? ' for "' . e($q) . '"' : '' ?></p>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:60px;">

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/jobs.php">
            <div class="filter-grid">
                <div class="form-group" style="margin:0;">
                    <input type="text" name="q" class="form-control" placeholder="🔍 Search jobs, keywords, skills..." value="<?= e($q) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <select name="category" class="form-control form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <input type="text" name="location" class="form-control" placeholder="📍 Location" value="<?= e($location) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <select name="type" class="form-control form-select">
                        <option value="">All Types</option>
                        <option value="full-time" <?= $type == 'full-time' ? 'selected' : '' ?>>Full Time</option>
                        <option value="part-time" <?= $type == 'part-time' ? 'selected' : '' ?>>Part Time</option>
                        <option value="remote" <?= $type == 'remote' ? 'selected' : '' ?>>Remote</option>
                        <option value="contract" <?= $type == 'contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="internship" <?= $type == 'internship' ? 'selected' : '' ?>>Internship</option>
                        <option value="freelance" <?= $type == 'freelance' ? 'selected' : '' ?>>Freelance</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Filter</button>
                    <?php if ($q || $category || $location || $type): ?>
                        <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-ghost">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($jobs): ?>
        <div class="jobs-grid">
            <?php foreach ($jobs as $job):
                $isSaved = in_array($job['id'], $savedJobIds);
            ?>
            <div class="job-card <?= $job['is_featured'] ? 'featured' : '' ?>">
                <div class="job-card-top">
                    <div class="company-logo">
                        <?php if ($job['company_logo']): ?>
                            <img src="<?= BASE_URL ?>/uploads/logos/<?= e($job['company_logo']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($job['company_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="job-card-info">
                        <h3 class="job-title">
                            <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>"><?= e($job['title']) ?></a>
                        </h3>
                        <div class="company-name"><?= e($job['company_name']) ?></div>
                    </div>
                    <?php if (isLoggedIn() && userRole() === 'jobseeker'): ?>
                    <button class="save-job-btn" data-job-id="<?= $job['id'] ?>" data-saved="<?= $isSaved ? '1' : '0' ?>"
                            style="background:none;border:none;font-size:1.2rem;cursor:pointer;margin-left:auto;padding:4px;"
                            title="<?= $isSaved ? 'Remove from saved' : 'Save this job' ?>">
                        <?= $isSaved ? '❤️' : '🤍' ?>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="job-meta">
                    <span class="badge badge-type"><?= jobTypeLabel($job['type']) ?></span>
                    <span class="job-meta-item">📍 <?= e($job['city']) ?></span>
                    <span class="job-meta-item">📂 <?= e($job['category_name']) ?></span>
                    <span class="job-meta-item">⭐ <?= ucfirst($job['experience_level']) ?></span>
                </div>
                <p style="font-size:0.85rem;color:var(--mid);line-height:1.6;margin-bottom:14px;"><?= truncate($job['description'], 120) ?></p>
                <div class="job-card-footer">
                    <div class="job-salary"><?= formatSalary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?></div>
                    <a href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">View Job</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php echo paginationLinks($baseFilterUrl, $page, $totalPages); ?>

    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3 class="empty-title">No jobs found</h3>
            <p class="empty-desc">Try adjusting your search filters or check back later for new opportunities.</p>
            <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
