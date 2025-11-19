<!-- ============================================ -->
<!-- admin/login.php - IMPROVED ADMIN LOGIN -->
<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    redirect(ADMIN_URL);
}

$error = '';
$success = '';

// Handle password reset message
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset successfully! You can now login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = login_admin($email, $password, $remember);
        
        if ($result['success']) {
            // Check for redirect
            $redirect = $_SESSION['admin_redirect'] ?? ADMIN_URL;
            unset($_SESSION['admin_redirect']);
            redirect($redirect);
        } else {
            $error = $result['error'];
        }
    }
}

$site_name = get_setting('site_name', 'NewsHub');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #73AF6F;
            --dark-color: #1A3D64;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Roboto', sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: var(--dark-color);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .login-header p {
            margin: 0.5rem 0 0;
            opacity: 0.8;
            font-size: 0.9375rem;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(115, 175, 111, 0.25);
        }
        
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 0.875rem;
            font-size: 1.0625rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #5d9459;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 175, 111, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: #fff5f5;
            color: #c53030;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #15803d;
        }
        
        .login-footer {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        
        .back-home a:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Admin Login</h2>
                <p>Welcome back! Please login to continue</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="email" 
                               placeholder="name@example.com" required autofocus
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control" id="password" 
                               placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me for 30 days
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>
                </form>
                
                <div class="login-footer">
                    <a href="<?php echo ADMIN_URL; ?>/forgot_password.php">
                        <i class="fas fa-key"></i> Forgot your password?
                    </a>
                </div>
            </div>
        </div>
        
        <div class="back-home">
            <a href="<?php echo SITE_URL; ?>">
                <i class="fas fa-arrow-left"></i> Back to Website
            </a>
        </div>
    </div>
</body>
</html>