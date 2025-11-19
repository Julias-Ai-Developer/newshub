<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!is_valid_email($email)) {
        $error = 'Invalid email address';
    } else {
        // Send email to admin
        $admin_email = get_setting('smtp_from_email', 'admin@localhost');
        $email_body = "
            <h3>New Contact Form Submission</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong></p>
            <p>{$message}</p>
        ";
        
        $result = send_email($admin_email, "Contact Form: {$subject}", $email_body);
        
        if ($result['success']) {
            $success = 'Thank you for contacting us! We will get back to you soon.';
        } else {
            $error = 'Failed to send message. Please try again later.';
        }
    }
}

$page_title = 'Contact Us';
include APP_ROOT . '/templates/header.php';
?>

<div class="section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="text-center mb-5">
                    <h1 class="display-4">Contact Us</h1>
                    <p class="lead text-muted">Have a question? We'd love to hear from you.</p>
                </div>
                
                <div class="card">
                    <div class="card-body p-5">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" name="name" class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Email</label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Additional contact details removed to keep a single shared footer across the site -->
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/footer.php'; ?>