<?php
// ============================================
// templates/admin-header.php - ADMIN PANEL HEADER
// ============================================
if (!defined('APP_ROOT')) die('Direct access not permitted');

// Ensure admin is logged in
if (!is_admin_logged_in()) {
    redirect(ADMIN_URL . '/login.php');
}

$current_admin = get_current_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin Panel'); ?> - <?php echo get_setting('site_name', 'NewsHub'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
    
    <?php if ($favicon = get_setting('site_favicon')): ?>
    <link rel="icon" href="<?php echo UPLOADS_URL . $favicon; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <span class="sidebar-logo"><?php echo htmlspecialchars(get_setting('site_name', 'NewsHub')); ?></span>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo ADMIN_URL; ?>">
                            <i class="nav-icon fas fa-home"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/articles/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo ADMIN_URL; ?>/articles/">
                            <i class="nav-icon fas fa-newspaper"></i>
                            <span class="nav-text">Articles</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo ADMIN_URL; ?>/categories/">
                            <i class="nav-icon fas fa-folder"></i>
                            <span class="nav-text">Categories</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/tags/">
                            <i class="nav-icon fas fa-tags"></i>
                            <span class="nav-text">Tags</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/media/">
                            <i class="nav-icon fas fa-images"></i>
                            <span class="nav-text">Media</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/comments/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo ADMIN_URL; ?>/comments/">
                            <i class="nav-icon fas fa-comments"></i>
                            <span class="nav-text">Comments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/users/">
                            <i class="nav-icon fas fa-users"></i>
                            <span class="nav-text">Users</span>
                        </a>
                    </li>
                    <?php if (is_super_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/admins/">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <span class="nav-text">Admins</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/subscribers/">
                            <i class="nav-icon fas fa-envelope"></i>
                            <span class="nav-text">Newsletter</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo ADMIN_URL; ?>/settings/">
                            <i class="nav-icon fas fa-cog"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/logs/">
                            <i class="nav-icon fas fa-history"></i>
                            <span class="nav-text">Activity Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/logout.php">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <span class="nav-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></h1>
                </div>
                
                <div class="header-right">
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-sm btn-outline-primary me-3" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Site
                    </a>
                    
                    <div class="header-user">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($current_admin['full_name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($current_admin['full_name'] ?? 'Admin'); ?></div>
                            <div class="user-role"><?php echo ucfirst($current_admin['role'] ?? 'admin'); ?></div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="admin-content">
