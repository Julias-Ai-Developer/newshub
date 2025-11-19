<?php
// ============================================
// api/newsletter.php
// ============================================

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$email = sanitize($_POST['email'] ?? '');

// Validation
if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

if (!is_valid_email($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Check if already subscribed
$existing = db_fetch("SELECT id, status FROM newsletter_subscribers WHERE email = ?", [$email]);

if ($existing) {
    if ($existing['status'] === 'active') {
        echo json_encode(['success' => false, 'error' => 'Email already subscribed']);
        exit;
    } else {
        // Reactivate subscription
        db_execute("UPDATE newsletter_subscribers SET status = 'active', subscribed_at = NOW() WHERE email = ?", [$email]);
        echo json_encode(['success' => true, 'message' => 'Subscription reactivated']);
        exit;
    }
}

// Generate token
$token = generate_token();

// Insert subscriber
$result = db_execute("
    INSERT INTO newsletter_subscribers (email, status, token, subscribed_at) 
    VALUES (?, 'active', ?, NOW())
", [$email, $token]);

if ($result) {
    // Send confirmation email
    send_newsletter_confirmation($email);
    
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
} else {
    echo json_encode(['success' => false, 'error' => 'Subscription failed']);
}
?>