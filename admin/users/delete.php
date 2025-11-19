<!-- ============================================ -->
<!-- admin/users/delete.php - DELETE USER -->
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
    $_SESSION['error'] = 'Invalid user ID';
    redirect(ADMIN_URL . '/users/');
}

// Get user
$user = db_fetch("SELECT * FROM users WHERE id = ?", [$id]);

if (!$user) {
    $_SESSION['error'] = 'User not found';
    redirect(ADMIN_URL . '/users/');
}

// Get user's content
$comment_count = db_fetch("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$id])['count'];

// Confirm deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    csrf_check();
    
    $delete_action = $_POST['delete_action'] ?? 'keep';
    
    if ($delete_action === 'delete_all') {
        // Delete all user's comments
        db_execute("DELETE FROM comments WHERE user_id = ?", [$id]);
    } else {
        // Just remove user association from comments
        db_execute("UPDATE comments SET user_id = NULL WHERE user_id = ?", [$id]);
    }
    
    // Delete user
    db_execute("DELETE FROM users WHERE id = ?", [$id]);
    
    log_activity($_SESSION['admin_id'], 'delete_user', "Deleted user: {$user['username']}");
    
    $_SESSION['success'] = 'User deleted successfully';
    redirect(ADMIN_URL . '/users/');
}

$page_title = 'Delete User';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Delete User</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/users/">Users</a> / 
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
                
                <h4>Delete User: <?php echo htmlspecialchars($user['username']); ?></h4>
                
                <div class="mt-4 p-4" style="background: #f8f9fa; border-radius: 8px;">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Full Name:</strong><br><?php echo htmlspecialchars($user['full_name'] ?: 'N/A'); ?></p>
                            <p><strong>Email:</strong><br><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Role:</strong><br><?php echo ucfirst($user['role']); ?></p>
                            <p><strong>Status:</strong><br><?php echo ucfirst($user['status']); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Registered:</strong> <?php echo format_date($user['created_at']); ?></p>
                        <p><strong>Comments:</strong> <?php echo number_format($comment_count); ?></p>
                    </div>
                </div>
                
                <?php if ($comment_count > 0): ?>
                <div class="alert alert-warning mt-4">
                    <strong>Note:</strong> This user has <?php echo $comment_count; ?> comment(s). 
                    What would you like to do with them?
                </div>
                
                <form method="post">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">Handle User Content:</label>
                        
                        <div class="form-check mb-2">
                            <input type="radio" name="delete_action" value="keep" 
                                   class="form-check-input" id="actionKeep" checked>
                            <label class="form-check-label" for="actionKeep">
                                <strong>Keep Comments</strong><br>
                                <small class="text-muted">Comments will remain but show as anonymous</small>
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="radio" name="delete_action" value="delete_all" 
                                   class="form-check-input" id="actionDelete">
                            <label class="form-check-label" for="actionDelete">
                                <strong>Delete All Comments</strong><br>
                                <small class="text-muted">Permanently delete all user's comments</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete User
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/users/" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
                
                <?php else: ?>
                <form method="post" class="mt-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="delete_action" value="keep">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete User
                    </button>
                    <a href="<?php echo ADMIN_URL; ?>/users/" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>