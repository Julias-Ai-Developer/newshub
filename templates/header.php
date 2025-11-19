<?php
// ============================================
// templates/header.php - PUBLIC WEBSITE HEADER
// ============================================
if (!defined('APP_ROOT')) die('Direct access not permitted');

// Ensure authentication helper functions (is_logged_in, etc.) are available
require_once APP_ROOT . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(get_setting('site_description', 'Your trusted source for news')); ?>">
    <title><?php echo htmlspecialchars($page_title ?? get_setting('site_name', 'NewsHub')); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Favicon -->
    <?php if ($favicon = get_setting('site_favicon')): ?>
    <link rel="icon" href="<?php echo UPLOADS_URL . $favicon; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <?php if ($logo = get_setting('site_logo')): ?>
                <img src="<?php echo UPLOADS_URL . $logo; ?>" alt="Logo" style="height: 40px;">
                <?php else: ?>
                <?php echo htmlspecialchars(get_setting('site_name', 'NewsHub')); ?>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <?php
                    // Always prioritize Politics category in the nav if it exists
                    $nav_categories = [];
                    $politics_cat = db_fetch("SELECT * FROM categories WHERE slug = 'politics' AND status = 'active'");
                    if ($politics_cat) {
                        $nav_categories[] = $politics_cat;
                    }
                    $remaining = 6 - count($nav_categories);
                    if ($remaining > 0) {
                        $other_cats = db_fetch_all(
                            "SELECT * FROM categories WHERE status = 'active' AND slug != 'politics' LIMIT {$remaining}"
                        );
                        $nav_categories = array_merge($nav_categories, $other_cats);
                    }
                    foreach ($nav_categories as $cat):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $cat['slug']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex ms-3" action="<?php echo SITE_URL; ?>/search.php" method="get">
                    <input class="form-control" type="search" name="q" placeholder="Search..." style="border-radius: 20px;">
                </form>
            </div>
        </div>
    </nav>

