<!-- ============================================ -->
<!-- admin/articles/delete.php -->
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
    $_SESSION['error'] = 'Invalid article ID';
    redirect(ADMIN_URL . '/articles/');
}

// Get article
$article = db_fetch("SELECT * FROM articles WHERE id = ?", [$id]);

if (!$article) {
    $_SESSION['error'] = 'Article not found';
    redirect(ADMIN_URL . '/articles/');
}

// Confirm deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    csrf_check();
    
    // Delete featured image if exists
    if ($article['featured_image'] && file_exists(UPLOADS_DIR . $article['featured_image'])) {
        @unlink(UPLOADS_DIR . $article['featured_image']);
    }
    
    // Delete article (cascade will delete tags and comments)
    db_execute("DELETE FROM articles WHERE id = ?", [$id]);
    
    log_activity($_SESSION['admin_id'], 'delete_article', "Deleted article: {$article['title']}");
    
    $_SESSION['success'] = 'Article deleted successfully';
    redirect(ADMIN_URL . '/articles/');
}

$page_title = 'Delete Article';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Delete Article</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/articles/">Articles</a> / 
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
                
                <h4>Are you sure you want to delete this article?</h4>
                
                <div class="mt-4 p-4" style="background: #f8f9fa; border-radius: 8px;">
                    <h5><?php echo htmlspecialchars($article['title']); ?></h5>
                    <?php if ($article['featured_image']): ?>
                    <img src="<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>" 
                         style="max-width: 300px; border-radius: 8px; margin-top: 1rem;">
                    <?php endif; ?>
                    <p class="mt-3 text-muted">
                        Created: <?php echo format_date($article['created_at']); ?><br>
                        Views: <?php echo number_format($article['views']); ?>
                    </p>
                </div>
                
                <div class="mt-4">
                    <form method="post" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete Article
                        </button>
                    </form>
                    <a href="<?php echo ADMIN_URL; ?>/articles/" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>