<?php
// admin/users/index.php - USER LISTING
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();
    $user_id = (int)$_POST['user_id'];
    
    switch ($_POST['action']) {
        case 'activate':
            db_execute("UPDATE users SET status = 'active' WHERE id = ?", [$user_id]);
            log_activity($_SESSION['admin_id'], 'activate_user', "Activated user ID: {$user_id}");
            $_SESSION['success'] = 'User activated successfully';
            break;
        case 'suspend':
            db_execute("UPDATE users SET status = 'suspended' WHERE id = ?", [$user_id]);
            log_activity($_SESSION['admin_id'], 'suspend_user', "Suspended user ID: {$user_id}");
            $_SESSION['success'] = 'User suspended successfully';
            break;
    }
    redirect(ADMIN_URL . '/users/');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ADMIN_POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where = [];
$params = [];

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$total_users = db_fetch("SELECT COUNT(*) as count FROM users {$where_sql}", $params)['count'];
$total_pages = ceil($total_users / $per_page);

// Get users
$params[] = $per_page;
$params[] = $offset;

$users = db_fetch_all("
    SELECT * FROM users
    {$where_sql}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", $params);

$page_title = 'Users';
include APP_ROOT . '/templates/admin-header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / <span>Users</span>
        </div>
    </div>
    <a href="<?php echo ADMIN_URL; ?>/users/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add User
    </a>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Total Users</h6>
                <h3><?php echo number_format(db_fetch("SELECT COUNT(*) as count FROM users")['count']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Active</h6>
                <h3 class="text-success"><?php echo number_format(db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Suspended</h6>
                <h3 class="text-danger"><?php echo number_format(db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'suspended'")['count']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Pending</h6>
                <h3 class="text-warning"><?php echo number_format(db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count']); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="subscriber" <?php echo $role_filter === 'subscriber' ? 'selected' : ''; ?>>Subscriber</option>
                    <option value="author" <?php echo $role_filter === 'author' ? 'selected' : ''; ?>>Author</option>
                    <option value="editor" <?php echo $role_filter === 'editor' ? 'selected' : ''; ?>>Editor</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                <a href="<?php echo ADMIN_URL; ?>/users/" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></strong><br>
                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td>
                        <?php 
                        $badges = [
                            'active' => 'badge-success',
                            'suspended' => 'badge-danger',
                            'pending' => 'badge-warning'
                        ];
                        ?>
                        <span class="badge <?php echo $badges[$user['status']] ?? ''; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><?php echo time_ago($user['created_at']); ?></td>
                    <td><?php echo $user['last_login'] ? time_ago($user['last_login']) : 'Never'; ?></td>
                    <td>
                        <a href="<?php echo ADMIN_URL; ?>/users/edit.php?id=<?php echo $user['id']; ?>" 
                           class="btn btn-sm btn-secondary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <?php if ($user['status'] !== 'active'): ?>
                        <form method="post" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success" title="Activate">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($user['status'] !== 'suspended'): ?>
                        <form method="post" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="action" value="suspend" class="btn btn-sm btn-warning" title="Suspend">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="<?php echo ADMIN_URL; ?>/users/delete.php?id=<?php echo $user['id']; ?>" 
                           class="btn btn-sm btn-danger" title="Delete"
                           onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&role=<?php echo $role_filter; ?>" 
       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
        <?php echo $i; ?>
    </a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>

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