<?php
// admin/settings/index.php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $settings = [
        'site_name' => sanitize($_POST['site_name'] ?? ''),
        'site_description' => sanitize($_POST['site_description'] ?? ''),
        'posts_per_page' => (int)($_POST['posts_per_page'] ?? 12),
        'smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
        'smtp_port' => sanitize($_POST['smtp_port'] ?? ''),
        'smtp_user' => sanitize($_POST['smtp_user'] ?? ''),
        'smtp_from_email' => sanitize($_POST['smtp_from_email'] ?? ''),
        'smtp_from_name' => sanitize($_POST['smtp_from_name'] ?? ''),
        'facebook_url' => sanitize($_POST['facebook_url'] ?? ''),
        'twitter_url' => sanitize($_POST['twitter_url'] ?? ''),
        'instagram_url' => sanitize($_POST['instagram_url'] ?? ''),
        'linkedin_url' => sanitize($_POST['linkedin_url'] ?? ''),
        'footer_text' => sanitize($_POST['footer_text'] ?? '')
    ];
    
    // Update SMTP password only if provided
    if (!empty($_POST['smtp_password'])) {
        $settings['smtp_password'] = $_POST['smtp_password'];
    }
    
    // Handle logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $upload = upload_image($_FILES['site_logo']);
        if ($upload['success']) {
            $settings['site_logo'] = $upload['filepath'];
        }
    }
    
    // Handle favicon upload
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
        $upload = upload_image($_FILES['site_favicon']);
        if ($upload['success']) {
            $settings['site_favicon'] = $upload['filepath'];
        }
    }
    
    // Update all settings
    foreach ($settings as $key => $value) {
        update_setting($key, $value);
    }
    
    log_activity($_SESSION['admin_id'], 'update_settings', 'Updated website settings');
    $success = 'Settings saved successfully';
}

$page_title = 'Settings';
include APP_ROOT . '/templates/admin-header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Settings</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / <span>Settings</span>
        </div>
    </div>
</div>

<form method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- General Settings -->
            <div class="card mb-4">
                <div class="card-header">General Settings</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('site_name', 'NewsHub')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Description</label>
                        <textarea name="site_description" class="form-control" rows="2"><?php echo htmlspecialchars(get_setting('site_description', '')); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Posts Per Page</label>
                        <input type="number" name="posts_per_page" class="form-control" 
                               value="<?php echo get_setting('posts_per_page', 12); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Logo</label>
                        <input type="file" name="site_logo" class="form-control" accept="image/*">
                        <?php if ($logo = get_setting('site_logo')): ?>
                        <small class="form-help">Current: <img src="<?php echo UPLOADS_URL . $logo; ?>" style="height: 40px; margin-top: 10px;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Favicon</label>
                        <input type="file" name="site_favicon" class="form-control" accept="image/*">
                        <?php if ($favicon = get_setting('site_favicon')): ?>
                        <small class="form-help">Current: <img src="<?php echo UPLOADS_URL . $favicon; ?>" style="height: 20px; margin-top: 10px;"></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- SMTP Settings -->
            <div class="card mb-4">
                <div class="card-header">Email (SMTP) Settings</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('smtp_host', '')); ?>"
                               placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">SMTP Port</label>
                                <input type="text" name="smtp_port" class="form-control" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_port', '587')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">SMTP User</label>
                                <input type="text" name="smtp_user" class="form-control" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_user', '')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" 
                               placeholder="Leave blank to keep current">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">From Email</label>
                                <input type="email" name="smtp_from_email" class="form-control" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_from_email', '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">From Name</label>
                                <input type="text" name="smtp_from_name" class="form-control" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_from_name', '')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="card mb-4">
                <div class="card-header">Social Media Links</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('facebook_url', '')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter URL</label>
                        <input type="url" name="twitter_url" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('twitter_url', '')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('instagram_url', '')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('linkedin_url', '')); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="card mb-4">
                <div class="card-header">Footer</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Footer Text</label>
                        <input type="text" name="footer_text" class="form-control" 
                               value="<?php echo htmlspecialchars(get_setting('footer_text', '')); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    
                    <hr>
                    
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" target="_blank">View Website</a></li>
                        <li><a href="<?php echo ADMIN_URL; ?>/articles/">Manage Articles</a></li>
                        <li><a href="<?php echo ADMIN_URL; ?>/categories/">Manage Categories</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>

