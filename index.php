<?php
// ============================================================
// TalentBridge - Homepage
// Developer: Hasibul Polok
// ============================================================
require_once 'config/db.php';
require_once 'includes/functions.php';

startSecureSession();
$pdo = getDB();

// Featured/recent jobs
$stmt = $pdo->prepare("
    SELECT j.*, c.name AS company_name, c.logo AS company_logo, cat.name AS category_name
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    JOIN categories cat ON j.category_id = cat.id
    WHERE j.status = 'approved' AND j.deadline >= CURDATE()
    ORDER BY j.is_featured DESC, j.created_at DESC
    LIMIT 6
");
$stmt->execute();
$featuredJobs = $stmt->fetchAll();

// Categories with job count
$stmt = $pdo->query("
    SELECT cat.*, COUNT(j.id) AS job_count
    FROM categories cat
    LEFT JOIN jobs j ON j.category_id = cat.id AND j.status = 'approved' AND j.deadline >= CURDATE()
    GROUP BY cat.id
    ORDER BY job_count DESC
    LIMIT 8
");
$categories = $stmt->fetchAll();

// Stats
$totalJobs     = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='approved'")->fetchColumn();
$totalCompanies= $pdo->query("SELECT COUNT(*) FROM companies WHERE status='active'")->fetchColumn();
$totalSeekers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='jobseeker' AND status='active'")->fetchColumn();

$pageTitle = 'Find Your Dream Job';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TalentBridge - Find Your Dream Job in Bangladesh</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
<?php require_once 'includes/header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-label">🇧🇩 Bangladesh's #1 Job Portal</div>
            <h1>Find Your <span>Dream Job</span><br>Build Your Career</h1>
            <p>Connect with top employers across Bangladesh. Discover thousands of opportunities in IT, Marketing, Finance, Engineering, and more.</p>

            <!-- Search Box -->
            <form action="<?= BASE_URL ?>/jobs.php" method="GET">
                <div class="search-box">
                    <div class="search-field">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="q" placeholder="Job title, keyword, skill..." value="<?= e($_GET['q'] ?? '') ?>">
                    </div>
                    <div class="search-field">
                        <span class="search-icon">📂</span>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-field">
                        <span class="search-icon">📍</span>
                        <input type="text" name="location" placeholder="City or location..." value="<?= e($_GET['location'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Search Jobs</button>
                </div>
            </form>

            <div class="hero-stats">
                <div>
                    <div class="hero-stat-num" data-target="<?= $totalJobs ?>"><?= number_format($totalJobs) ?>+</div>
                    <div class="hero-stat-label">Active Jobs</div>
                </div>
                <div>
                    <div class="hero-stat-num" data-target="<?= $totalCompanies ?>"><?= number_format($totalCompanies) ?>+</div>
                    <div class="hero-stat-label">Companies</div>
                </div>
                <div>
                    <div class="hero-stat-num" data-target="<?= $totalSeekers ?>"><?= number_format($totalSeekers) ?>+</div>
                    <div class="hero-stat-label">Job Seekers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section-sm" style="background:white;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="section-header text-center">
            <div class="section-label">Explore by field</div>
            <h2 class="section-title">Browse Job Categories</h2>
            <p class="section-desc" style="margin:0 auto;">Find opportunities in the field that matches your expertise</p>
        </div>
        <div class="categories-grid">
            <?php
            $catIcons = ['💻','📈','🎨','💰','🏥','📚','⚙️','👥','⚖️','🎧'];
            foreach ($categories as $i => $cat):
            ?>
            <a href="<?= BASE_URL ?>/jobs.php?category=<?= $cat['id'] ?>" style="text-decoration:none;">
                <div class="category-card">
                    <div class="cat-icon"><?= $catIcons[$i % count($catIcons)] ?></div>
                    <div class="cat-name"><?= e($cat['name']) ?></div>
                    <div class="cat-count"><?= $cat['job_count'] ?> jobs</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED JOBS -->
<section class="section">
    <div class="container">
        <div class="section-header section-header-flex">
            <div>
                <div class="section-label">Latest opportunities</div>
                <h2 class="section-title">Featured Jobs</h2>
            </div>
            <a href="<?= BASE_URL ?>/jobs.php" class="btn btn-outline">View All Jobs →</a>
        </div>

        <?php if ($featuredJobs): ?>
        <div class="jobs-grid">
            <?php foreach ($featuredJobs as $job): ?>
            <div class="job-card <?= $job['is_featured'] ? 'featured' : '' ?>">
                <div class="job-card-top">
                    <div class="company-logo">
                        <?php $logoUrl = getLogoUrl($job['company_logo'] ?? null); ?>
                        <?php if ($logoUrl): ?>
                            <img src="<?= e($logoUrl) ?>" alt="<?= e($job['company_name']) ?>">
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
                </div>
                <div class="job-meta">
                    <span class="badge badge-type"><?= jobTypeLabel($job['type']) ?></span>
                    <span class="job-meta-item">📍 <?= e($job['city']) ?></span>
                    <span class="job-meta-item">📂 <?= e($job['category_name']) ?></span>
                </div>
                <div class="job-card-footer">
                    <div class="job-salary"><?= formatSalary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?></div>
                    <div class="job-deadline <?= isDeadlinePassed($job['deadline']) ? 'expired' : '' ?>">
                        <?= isDeadlinePassed($job['deadline']) ? 'Expired' : 'Deadline: ' . formatDate($job['deadline'], 'd M Y') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">💼</div>
            <h3 class="empty-title">No jobs yet</h3>
            <p class="empty-desc">Check back soon for exciting opportunities.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" style="background:var(--dark);color:white;">
    <div class="container">
        <div class="section-header text-center">
            <div class="section-label" style="background:rgba(26,86,219,0.3);color:#93c5fd;">Simple Process</div>
            <h2 class="section-title" style="color:white;">How TalentBridge Works</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:32px;margin-top:40px;">
            <?php
            $steps = [
                ['🎯', 'Create Profile', 'Sign up and build your professional profile to stand out to employers.'],
                ['🔍', 'Find Opportunities', 'Search thousands of jobs by keyword, location, category, and more.'],
                ['📝', 'Apply with Ease', 'Submit your CV and cover letter directly through our platform.'],
                ['🎉', 'Get Hired', 'Connect with employers and land your perfect job quickly.'],
            ];
            foreach ($steps as $i => $step):
            ?>
            <div style="text-align:center;padding:28px 20px;">
                <div style="font-size:2.5rem;margin-bottom:16px;"><?= $step[0] ?></div>
                <div style="background:var(--primary);color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.8rem;margin:0 auto 14px;font-family:var(--font-display);"><?= $i+1 ?></div>
                <h3 style="font-family:var(--font-display);font-size:1.05rem;font-weight:700;margin-bottom:10px;"><?= $step[1] ?></h3>
                <p style="color:#94a3b8;font-size:0.875rem;line-height:1.7;"><?= $step[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section-sm" style="background:var(--primary-light);border-top:1px solid rgba(26,86,219,0.1);">
    <div class="container text-center">
        <h2 class="section-title">Ready to Start Your Journey?</h2>
        <p style="color:var(--mid);max-width:480px;margin:0 auto 32px;">Join thousands of professionals and companies already using TalentBridge.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">Find Jobs</a>
            <a href="<?= BASE_URL ?>/auth/register.php?role=employer" class="btn btn-outline btn-lg">Post a Job</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
