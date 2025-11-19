<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require user login
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = current_url();
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Require admin login
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        $_SESSION['admin_redirect'] = current_url();
        redirect(ADMIN_URL . '/login.php');
    }
}

/**
 * Login user
 */
function login_user($email, $password, $remember = false) {
    $user = db_fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    
    // Update last login
    db_execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Set remember me cookie
    if ($remember) {
        $token = generate_token();
        setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
    }
    
    return ['success' => true, 'user' => $user];
}

/**
 * Login admin
 */
function login_admin($email, $password, $remember = false) {
    $admin = db_fetch("SELECT * FROM admins WHERE email = ? AND status = 'active'", [$email]);
    
    if (!$admin) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    if (!password_verify($password, $admin['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    
    // Update last login
    db_execute("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
    
    // Log activity
    log_activity($admin['id'], 'login', 'Admin logged in');
    
    return ['success' => true, 'admin' => $admin];
}

/**
 * Logout user
 */
function logout_user() {
    // Clear session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_role']);
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    session_regenerate_id(true);
}

/**
 * Logout admin
 */
function logout_admin() {
    if (isset($_SESSION['admin_id'])) {
        log_activity($_SESSION['admin_id'], 'logout', 'Admin logged out');
    }
    
    // Clear session variables
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    
    session_regenerate_id(true);
}

/**
 * Get current user
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    static $current_user = null;
    
    if ($current_user === null) {
        $current_user = db_fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    return $current_user;
}

/**
 * Get current admin
 */
function get_current_admin() {
    if (!is_admin_logged_in()) {
        return null;
    }
    
    static $current_admin = null;
    
    if ($current_admin === null) {
        $current_admin = db_fetch("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
    }
    
    return $current_admin;
}

/**
 * Check if super admin
 */
function is_super_admin() {
    if (!is_admin_logged_in()) {
        return false;
    }
    
    $admin = get_current_admin();
    return $admin && $admin['role'] === 'super_admin';
}

/**
 * Require super admin
 */
function require_super_admin() {
    require_admin();
    
    if (!is_super_admin()) {
        http_response_code(403);
        die('Access denied. Super admin privileges required.');
    }
}

/**
 * Register user
 */
function register_user($data) {
    // Validate input
    $errors = [];
    
    if (empty($data['username'])) {
        $errors[] = 'Username is required';
    }
    
    if (empty($data['email']) || !is_valid_email($data['email'])) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email exists
    $existing = db_fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
    if ($existing) {
        $errors[] = 'Email already registered';
    }
    
    // Check if username exists
    $existing = db_fetch("SELECT id FROM users WHERE username = ?", [$data['username']]);
    if ($existing) {
        $errors[] = 'Username already taken';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = generate_token();
    
    // Insert user
    $result = db_execute(
        "INSERT INTO users (username, email, password, full_name, verification_token, status) VALUES (?, ?, ?, ?, ?, 'active')",
        [$data['username'], $data['email'], $password_hash, $data['full_name'], $verification_token]
    );
    
    if (!$result) {
        return ['success' => false, 'error' => 'Registration failed'];
    }
    
    return ['success' => true, 'user_id' => $result['insert_id']];
}

/**
 * Log activity
 */
function log_activity($admin_id, $action, $description = '') {
    db_execute(
        "INSERT INTO activity_logs (admin_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
        [$admin_id, $action, $description, get_client_ip(), get_user_agent()]
    );
}