<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'news_website');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
// BASE_URL is the project root (http://localhost/newshub)
define('BASE_URL', 'http://localhost/newshub');
// Public site lives under /public
define('SITE_URL', BASE_URL . '/public');
// Admin, uploads, assets are at project root level
define('ADMIN_URL', BASE_URL . '/admin');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('ASSETS_URL', BASE_URL . '/assets');

// Directory Paths
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('INCLUDES_DIR', dirname(__DIR__) . '/includes');
define('TEMPLATES_DIR', dirname(__DIR__) . '/templates');

// Security Settings
define('SESSION_LIFETIME', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Pagination
define('POSTS_PER_PAGE', 12);
define('ADMIN_POSTS_PER_PAGE', 20);

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// Email Settings (loaded from database)
$email_settings = [];

// Theme Colors
define('PRIMARY_COLOR', '#73AF6F');
define('SECONDARY_COLOR', '#73AF6F');
define('DARK_COLOR', '#1A3D64');

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}