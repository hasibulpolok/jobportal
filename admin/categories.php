<?php
// ============================================================
// TalentBridge - Admin: Manage Categories
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('admin');

$pdo    = getDB();
$errors = [];
$editCategory = null;

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    verifyCSRF($_GET['csrf'] ?? '');
    $catId = intval($_GET['delete']);
    // Check if category has jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE category_id = ?");
    $stmt->execute([$catId]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('error', 'Cannot delete category — it has jobs assigned to it.');
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
        setFlash('success', 'Category deleted.');
    }
    redirect(BASE_URL . '/admin/categories.php');
}

// Load for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editCategory = $stmt->fetch();
}

// Handle save (add or edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'briefcase');
    $catId = intval($_POST['cat_id'] ?? 0);

    if (empty($name)) { $errors['name'] = 'Category name is required.'; }

    // Check duplicate name
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $catId]);
        if ($stmt->fetch()) { $errors['name'] = 'A category with this name already exists.'; }
    }

    if (empty($errors)) {
        $slug = uniqueSlug($pdo, 'categories', $name, $catId ?: null);
        if ($catId) {
            $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?")
                ->execute([$name, $slug, $icon, $catId]);
            setFlash('success', 'Category updated successfully.');
        } else {
            $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)")
                ->execute([$name, $slug, $icon]);
            setFlash('success', 'Category added successfully.');
        }
        redirect(BASE_URL . '/admin/categories.php');
    }
}

// Load all categories with job counts
$categories = $pdo->query("
    SELECT cat.*, COUNT(j.id) AS job_count
    FROM categories cat
    LEFT JOIN jobs j ON j.category_id = cat.id
    GROUP BY cat.id
    ORDER BY cat.name
")->fetchAll();

$csrf = generateCSRF();
$pageTitle = 'Manage Categories';

$categoryIcons = ['briefcase','laptop','trending-up','pen-tool','dollar-sign','heart','book-open','settings','users','shield','headphones','globe','camera','code','truck','home','coffee','music'];
?>
<?php require_once '../admin/header.php'; ?>

<div class="dash-header">
    <h1 class="dash-title">Manage Categories</h1>
    <p class="dash-subtitle"><?= count($categories) ?> categories</p>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

    <!-- Categories List -->
    <div class="table-wrap">
        <div class="table-header">
            <div class="table-title">📂 All Categories</div>
        </div>
        <?php if ($categories): ?>
        <table>
            <thead>
                <tr><th>Category Name</th><th>Slug</th><th>Jobs</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:1.2rem;">📂</span>
                            <strong><?= e($cat['name']) ?></strong>
                        </div>
                    </td>
                    <td style="font-size:0.8rem;color:var(--mid);font-family:monospace;"><?= e($cat['slug']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/jobs.php?category=<?= $cat['id'] ?>" target="_blank"
                           style="font-weight:700;color:var(--primary);"><?= $cat['job_count'] ?> jobs</a>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="<?= BASE_URL ?>/admin/categories.php?edit=<?= $cat['id'] ?>" class="action-btn edit">Edit</a>
                            <a href="<?= BASE_URL ?>/admin/categories.php?delete=<?= $cat['id'] ?>&csrf=<?= $csrf ?>"
                               class="action-btn delete"
                               data-confirm="Delete '<?= e($cat['name']) ?>'? This cannot be done if it has jobs.">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state" style="padding:40px;">
            <div class="empty-icon">📂</div>
            <h3 class="empty-title">No categories yet</h3>
            <p class="empty-desc">Add your first category using the form.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add / Edit Form -->
    <div class="form-card" style="position:sticky;top:72px;">
        <h3 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
            <?= $editCategory ? '✏️ Edit Category' : '➕ Add New Category' ?>
        </h3>

        <form method="POST" data-validate>
            <?= csrfInput() ?>
            <?php if ($editCategory): ?>
                <input type="hidden" name="cat_id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Category Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= e($editCategory['name'] ?? ($_POST['name'] ?? '')) ?>"
                       placeholder="e.g. Information Technology" required>
                <?php if (isset($errors['name'])): ?><span class="form-error"><?= e($errors['name']) ?></span><?php endif; ?>
                <span class="form-hint">The slug will be auto-generated from the name.</span>
            </div>

            <div class="form-group">
                <label class="form-label">Icon (name or emoji)</label>
                <input type="text" name="icon" class="form-control"
                       value="<?= e($editCategory['icon'] ?? ($_POST['icon'] ?? 'briefcase')) ?>"
                       placeholder="e.g. laptop, briefcase, 💻">
                <span class="form-hint">Use an icon name or emoji character.</span>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">
                    <?= $editCategory ? '💾 Update Category' : '➕ Add Category' ?>
                </button>
                <?php if ($editCategory): ?>
                    <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-ghost">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($editCategory): ?>
        <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:var(--radius-sm);font-size:0.82rem;">
            <strong>Editing:</strong> <?= e($editCategory['name']) ?><br>
            <span style="color:var(--mid);">Slug: <?= e($editCategory['slug']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../admin/footer.php'; ?>
