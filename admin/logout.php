<?php
// admin/logout.php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/auth.php';

logout_admin();
redirect(ADMIN_URL . '/login.php');

// ============================================
