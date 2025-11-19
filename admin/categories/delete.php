<!-- ============================================ -->
<!-- admin/categories/delete.php -->
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

// Get article count
$article_count = db_fetch("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$id])['count'];

// Confirm deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    csrf_check();
    
    $action = $_POST['delete_action'] ?? 'nullify';
    
    if ($action === 'nullify') {
        // Set articles category to NULL
        db_execute("UPDATE articles SET category_id = NULL WHERE category_id = ?", [$id]);
    } elseif ($action === 'reassign' && !empty($_POST['new_category_id'])) {
        // Reassign to another category
        $new_category_id = (int)$_POST['new_category_id'];
        db_execute("UPDATE articles SET category_id = ? WHERE category_id = ?", [$new_category_id, $id]);
    }
    
    // Delete category
    db_execute("DELETE FROM categories WHERE id = ?", [$id]);
    
    log_activity($_SESSION['admin_id'], 'delete_category', "Deleted category: {$category['name']}");
    
    $_SESSION['success'] = 'Category deleted successfully';
    redirect(ADMIN_URL . '/categories/');
}

// Get other categories for reassignment
$other_categories = db_fetch_all("SELECT * FROM categories WHERE id != ? ORDER BY name", [$id]);

$page_title = 'Delete Category';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Delete Category</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/categories/">Categories</a> / 
            <span>Delete</span>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                
                <h4>Delete Category: <?php echo htmlspecialchars($category['name']); ?></h4>
                
                <div class="mt-4 p-4" style="background: #f8f9fa; border-radius: 8px;">
                    <p><strong>Slug:</strong> <?php echo htmlspecialchars($category['slug']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($category['status']); ?></p>
                    <p><strong>Articles in this category:</strong> 
                        <span class="badge bg-primary"><?php echo number_format($article_count); ?></span>
                    </p>
                </div>
                
                <?php if ($article_count > 0): ?>
                <div class="alert alert-warning mt-4">
                    <strong>Note:</strong> This category has <?php echo $article_count; ?> article(s). 
                    Please choose what to do with them:
                </div>
                
                <form method="post">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">What should we do with the articles?</label>
                        
                        <div class="form-check mb-2">
                            <input type="radio" name="delete_action" value="nullify" 
                                   class="form-check-input" id="actionNullify" checked>
                            <label class="form-check-label" for="actionNullify">
                                Remove category (set to uncategorized)
                            </label>
                        </div>
                        
                        <?php if (!empty($other_categories)): ?>
                        <div class="form-check">
                            <input type="radio" name="delete_action" value="reassign" 
                                   class="form-check-input" id="actionReassign">
                            <label class="form-check-label" for="actionReassign">
                                Move to another category
                            </label>
                        </div>
                        
                        <div class="mt-2 ms-4" id="reassignSelect" style="display: none;">
                            <select name="new_category_id" class="form-select">
                                <option value="">Select category...</option>
                                <?php foreach ($other_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete Category
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/categories/" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
                
                <script>
                document.getElementById('actionReassign').addEventListener('change', function() {
                    document.getElementById('reassignSelect').style.display = 
                        this.checked ? 'block' : 'none';
                });
                document.getElementById('actionNullify').addEventListener('change', function() {
                    document.getElementById('reassignSelect').style.display = 'none';
                });
                </script>
                
                <?php else: ?>
                <form method="post" class="mt-4">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete Category
                    </button>
                    <a href="<?php echo ADMIN_URL; ?>/categories/" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>