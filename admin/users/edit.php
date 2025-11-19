<?php
// admin/users/edit.php - EDIT USER
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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'subscriber');
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email) || !is_valid_email($email)) {
        $errors[] = 'Valid email is required';
    }
    
    // Check if password is being changed
    if (!empty($password)) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // Check if email exists for another user
    $existing = db_fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
    if ($existing) {
        $errors[] = 'Email already in use by another user';
    }
    
    // Check if username exists for another user
    $existing = db_fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
    if ($existing) {
        $errors[] = 'Username already taken by another user';
    }
    
    if (empty($errors)) {
        // Prepare update query
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $result = db_execute("
                UPDATE users SET 
                    username = ?, email = ?, password = ?, full_name = ?, 
                    role = ?, status = ?
                WHERE id = ?
            ", [$username, $email, $password_hash, $full_name, $role, $status, $id]);
        } else {
            $result = db_execute("
                UPDATE users SET 
                    username = ?, email = ?, full_name = ?, 
                    role = ?, status = ?
                WHERE id = ?
            ", [$username, $email, $full_name, $role, $status, $id]);
        }
        
        if ($result !== false) {
            log_activity($_SESSION['admin_id'], 'update_user', "Updated user: {$username}");
            $_SESSION['success'] = 'User updated successfully';
            redirect(ADMIN_URL . '/users/edit.php?id=' . $id);
        } else {
            $errors[] = 'Failed to update user';
        }
    }
}

// Get user activity stats
$comment_count = db_fetch("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$id])['count'];

$page_title = 'Edit User';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit User</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/users/">Users</a> / 
            <span>Edit</span>
        </div>
    </div>
    <a href="<?php echo ADMIN_URL; ?>/users/delete.php?id=<?php echo $id; ?>" 
       class="btn btn-danger"
       onclick="return confirm('Are you sure you want to delete this user?')">
        <i class="fas fa-trash"></i> Delete User
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Leave password fields empty to keep current password
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control">
                                <small class="form-help">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="subscriber" <?php echo $user['role'] === 'subscriber' ? 'selected' : ''; ?>>Subscriber</option>
                                    <option value="author" <?php echo $user['role'] === 'author' ? 'selected' : ''; ?>>Author</option>
                                    <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/users/" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">User Statistics</div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Comments:</strong>
                    <span class="badge bg-primary"><?php echo number_format($comment_count); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Member Since:</strong><br>
                    <small class="text-muted"><?php echo format_date($user['created_at']); ?></small>
                </div>
                <div class="mb-3">
                    <strong>Last Login:</strong><br>
                    <small class="text-muted"><?php echo $user['last_login'] ? format_date($user['last_login']) : 'Never'; ?></small>
                </div>
                <?php if ($user['verification_token']): ?>
                <div class="alert alert-warning">
                    Email not verified
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <?php if ($user['status'] !== 'active'): ?>
                <form method="post" action="<?php echo ADMIN_URL; ?>/users/">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                    <button type="submit" name="action" value="activate" class="btn btn-success w-100 mb-2">
                        <i class="fas fa-check"></i> Activate User
                    </button>
                </form>
                <?php endif; ?>
                
                <?php if ($user['status'] !== 'suspended'): ?>
                <form method="post" action="<?php echo ADMIN_URL; ?>/users/">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                    <button type="submit" name="action" value="suspend" class="btn btn-warning w-100 mb-2"
                            onclick="return confirm('Suspend this user?')">
                        <i class="fas fa-ban"></i> Suspend User
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/users/delete.php?id=<?php echo $id; ?>" 
                   class="btn btn-danger w-100"
                   onclick="return confirm('Are you sure you want to delete this user?')">
                    <i class="fas fa-trash"></i> Delete User
                </a>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>

