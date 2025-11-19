<!-- ============================================ -->
<!-- admin/users/add.php - ADD USER -->
<?php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

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
    
    if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email exists
    $existing = db_fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        $errors[] = 'Email already registered';
    }
    
    // Check if username exists
    $existing = db_fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        $errors[] = 'Username already taken';
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $result = db_execute("
            INSERT INTO users (username, email, password, full_name, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [$username, $email, $password_hash, $full_name, $role, $status]);
        
        if ($result) {
            log_activity($_SESSION['admin_id'], 'create_user', "Created user: {$username}");
            $_SESSION['success'] = 'User created successfully';
            redirect(ADMIN_URL . '/users/');
        } else {
            $errors[] = 'Failed to create user';
        }
    }
}

$page_title = 'Add User';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add User</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/users/">Users</a> / 
            <span>Add</span>
        </div>
    </div>
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
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                <small class="form-help">Unique username for login</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="form-help">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="subscriber">Subscriber</option>
                                    <option value="author">Author</option>
                                    <option value="editor">Editor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create User
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/users/" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">User Roles</div>
            <div class="card-body">
                <p><strong>Subscriber:</strong><br>Can view content and comment</p>
                <p><strong>Author:</strong><br>Can create and edit own articles</p>
                <p><strong>Editor:</strong><br>Can edit all articles</p>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>