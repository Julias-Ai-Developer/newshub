<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Send email using PHP mail() or SMTP
 */
function send_email($to, $subject, $message, $from_name = null, $from_email = null) {
    // Get email settings
    $smtp_enabled = get_setting('smtp_enabled', '0');
    $from_email = $from_email ?? get_setting('smtp_from_email', 'noreply@localhost');
    $from_name = $from_name ?? get_setting('smtp_from_name', get_setting('site_name', 'NewsHub'));
    
    if ($smtp_enabled === '1') {
        return send_smtp_email($to, $subject, $message, $from_name, $from_email);
    } else {
        return send_simple_email($to, $subject, $message, $from_name, $from_email);
    }
}

/**
 * Send email using PHP mail()
 */
function send_simple_email($to, $subject, $message, $from_name, $from_email) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $email_body = get_email_template($message);
    
    $result = mail($to, $subject, $email_body, implode("\r\n", $headers));
    
    return [
        'success' => $result,
        'error' => $result ? null : 'Failed to send email'
    ];
}

/**
 * Send email using SMTP (requires PHPMailer or similar)
 * For production, integrate PHPMailer library
 */
function send_smtp_email($to, $subject, $message, $from_name, $from_email) {
    // This is a placeholder for SMTP integration
    // In production, use PHPMailer or similar library
    
    $smtp_host = get_setting('smtp_host');
    $smtp_port = get_setting('smtp_port', '587');
    $smtp_user = get_setting('smtp_user');
    $smtp_pass = get_setting('smtp_password');
    
    // For now, fallback to simple email
    return send_simple_email($to, $subject, $message, $from_name, $from_email);
}

/**
 * Get email template
 */
function get_email_template($content) {
    $site_name = get_setting('site_name', 'NewsHub');
    $site_url = SITE_URL;
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: #1A3D64;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #73AF6F;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$site_name}</h1>
        </div>
        <div class="content">
            {$content}
        </div>
        <div class="footer">
            <p>&copy; 2025 {$site_name}. All rights reserved.</p>
            <p><a href="{$site_url}" style="color: #73AF6F;">Visit our website</a></p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Send verification email
 */
function send_verification_email($email, $token, $name) {
    $verify_url = SITE_URL . '/verify.php?token=' . $token;
    
    $message = <<<HTML
<h2>Welcome to our community!</h2>
<p>Hi {$name},</p>
<p>Thank you for registering. Please verify your email address by clicking the button below:</p>
<p style="text-align: center;">
    <a href="{$verify_url}" class="button">Verify Email</a>
</p>
<p>Or copy this link to your browser:<br>{$verify_url}</p>
<p>This link will expire in 24 hours.</p>
HTML;
    
    return send_email($email, 'Verify Your Email Address', $message);
}

/**
 * Send password reset email
 */
function send_password_reset_email($email, $token, $name) {
    $reset_url = SITE_URL . '/reset_password.php?token=' . $token;
    
    $message = <<<HTML
<h2>Password Reset Request</h2>
<p>Hi {$name},</p>
<p>We received a request to reset your password. Click the button below to create a new password:</p>
<p style="text-align: center;">
    <a href="{$reset_url}" class="button">Reset Password</a>
</p>
<p>Or copy this link to your browser:<br>{$reset_url}</p>
<p>If you didn't request this, please ignore this email. This link will expire in 1 hour.</p>
HTML;
    
    return send_email($email, 'Reset Your Password', $message);
}

/**
 * Send welcome email
 */
function send_welcome_email($email, $name) {
    $message = <<<HTML
<h2>Welcome to our community!</h2>
<p>Hi {$name},</p>
<p>Thank you for joining us! We're excited to have you as part of our community.</p>
<p>Stay updated with the latest news and stories.</p>
<p style="text-align: center;">
    <a href="{SITE_URL}" class="button">Explore Now</a>
</p>
HTML;
    
    return send_email($email, 'Welcome!', $message);
}

/**
 * Send newsletter confirmation
 */
function send_newsletter_confirmation($email) {
    $message = <<<HTML
<h2>Newsletter Subscription Confirmed</h2>
<p>Thank you for subscribing to our newsletter!</p>
<p>You'll now receive the latest news and updates directly in your inbox.</p>
<p>If you wish to unsubscribe at any time, click the unsubscribe link in any of our emails.</p>
HTML;
    
    return send_email($email, 'Newsletter Subscription Confirmed', $message);
}