<!-- ============================================ -->
<!-- admin/categories/edit.php -->
<?php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = 'Invalid category ID';
    redirect(ADMIN_URL . '/categories/');
}

// Get category
$category = db_fetch("SELECT * FROM categories WHERE id = ?", [$id]);

if (!$category) {
    $_SESSION['error'] = 'Category not found';
    redirect(ADMIN_URL . '/categories/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        // Check if name exists for another category
        $exists = db_fetch("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $id]);
        if ($exists) {
            $error = 'Category with this name already exists';
        } else {
            // Update slug if name changed
            if ($name !== $category['name']) {
                $slug = generate_unique_slug($name, 'categories', 'slug', $id);
            } else {
                $slug = $category['slug'];
            }
            
            $result = db_execute("
                UPDATE categories 
                SET name = ?, slug = ?, description = ?, status = ?
                WHERE id = ?
            ", [$name, $slug, $description, $status, $id]);
            
            if ($result !== false) {
                log_activity($_SESSION['admin_id'], 'update_category', "Updated category: {$name}");
                $_SESSION['success'] = 'Category updated successfully';
                redirect(ADMIN_URL . '/categories/edit.php?id=' . $id);
            } else {
                $error = 'Failed to update category';
            }
        }
    }
}

$page_title = 'Edit Category';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Category</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/categories/">Categories</a> / 
            <span>Edit</span>
        </div>
    </div>
    <a href="<?php echo ADMIN_URL; ?>/categories/delete.php?id=<?php echo $id; ?>" 
       class="btn btn-danger"
       onclick="return confirm('Are you sure you want to delete this category?')">
        <i class="fas fa-trash"></i> Delete
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" readonly
                               value="<?php echo htmlspecialchars($category['slug']); ?>">
                        <small class="form-help">Auto-generated from category name</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo $category['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $category['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Category
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/categories/" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Statistics</div>
            <div class="card-body">
                <?php
                $article_count = db_fetch("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$id])['count'];
                ?>
                <div class="mb-3">
                    <strong>Total Articles:</strong>
                    <span class="badge bg-primary"><?php echo number_format($article_count); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Created:</strong><br>
                    <small class="text-muted"><?php echo format_date($category['created_at']); ?></small>
                </div>
                <hr>
                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $category['slug']; ?>" 
                   class="btn btn-sm btn-outline-primary w-100" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View on Website
                </a>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>
