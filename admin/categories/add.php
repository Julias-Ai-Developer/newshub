<?php
// admin/categories/add.php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

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
        // Check if category exists
        $exists = db_fetch("SELECT id FROM categories WHERE name = ?", [$name]);
        if ($exists) {
            $error = 'Category with this name already exists';
        } else {
            $slug = generate_unique_slug($name, 'categories');
            
            $result = db_execute("
                INSERT INTO categories (name, slug, description, status) 
                VALUES (?, ?, ?, ?)
            ", [$name, $slug, $description, $status]);
            
            if ($result) {
                log_activity($_SESSION['admin_id'], 'create_category', "Created category: {$name}");
                $_SESSION['success'] = 'Category created successfully';
                redirect(ADMIN_URL . '/categories/');
            } else {
                $error = 'Failed to create category';
            }
        }
    }
}

$page_title = 'Add Category';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add Category</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/categories/">Categories</a> / 
            <span>Add</span>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               placeholder="e.g., Technology, Sports, Business">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Brief description of this category..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <small class="form-help">This will appear on the category page</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <small class="form-help">Inactive categories won't appear on the website</small>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Category
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/categories/" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Tips</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Choose a clear, descriptive name</li>
                    <li>Keep the name short (1-3 words)</li>
                    <li>Add a description for SEO</li>
                    <li>URL slug is auto-generated</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>


