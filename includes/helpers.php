<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate slug from string
 */
function generate_slug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Generate unique slug
 */
function generate_unique_slug($string, $table, $field = 'slug', $id = null) {
    $slug = generate_slug($string);
    $original_slug = $slug;
    $counter = 1;
    
    while (true) {
        $query = "SELECT id FROM $table WHERE $field = ?";
        $params = [$slug];
        
        if ($id !== null) {
            $query .= " AND id != ?";
            $params[] = $id;
        }
        
        $existing = db_fetch($query, $params);
        
        if (!$existing) {
            return $slug;
        }
        
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
}

/**
 * Redirect
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently");
    }
    header("Location: " . $url);
    exit;
}

/**
 * Get current URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Format date
 */
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Time ago
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Calculate reading time
 */
function calculate_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = ceil($word_count / 200); // Average reading speed: 200 words/min
    return $minutes;
}

/**
 * Upload image
 */
function upload_image($file, $subfolder = '') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $ext = array_search($mime, [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ], true);
    
    // Create upload directory
    $upload_dir = UPLOADS_DIR . '/' . date('Y') . '/' . date('m');
    if (!empty($subfolder)) {
        $upload_dir .= '/' . $subfolder;
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $ext;
    $filepath = $upload_dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    // Create thumbnail
    $thumbnail_path = create_thumbnail($filepath, $upload_dir);
    
    $relative_path = str_replace(UPLOADS_DIR, '', $filepath);
    $relative_thumb = $thumbnail_path ? str_replace(UPLOADS_DIR, '', $thumbnail_path) : null;
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $relative_path,
        'thumbnail' => $relative_thumb,
        'url' => UPLOADS_URL . $relative_path
    ];
}

/**
 * Create thumbnail
 */
function create_thumbnail($source, $dest_dir) {
    $image_info = getimagesize($source);
    if (!$image_info) {
        return null;
    }
    
    list($width, $height, $type) = $image_info;
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
        default:
            return null;
    }
    
    // Calculate thumbnail dimensions
    $thumb_width = THUMBNAIL_WIDTH;
    $thumb_height = THUMBNAIL_HEIGHT;
    
    $ratio = $width / $height;
    $thumb_ratio = $thumb_width / $thumb_height;
    
    if ($ratio > $thumb_ratio) {
        $new_width = $thumb_width;
        $new_height = $thumb_width / $ratio;
    } else {
        $new_height = $thumb_height;
        $new_width = $thumb_height * $ratio;
    }
    
    // Cast all geometry values to integers to avoid implicit float-to-int conversions
    $new_width = (int) round($new_width);
    $new_height = (int) round($new_height);
    $dst_x = (int) round(($thumb_width - $new_width) / 2);
    $dst_y = (int) round(($thumb_height - $new_height) / 2);
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // Preserve transparency
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    imagecopyresampled(
        $thumbnail, $image,
        $dst_x, $dst_y,
        0, 0,
        $new_width, $new_height,
        $width, $height
    );
    
    // Save thumbnail
    $thumb_filename = 'thumb_' . basename($source);
    $thumb_path = $dest_dir . '/' . $thumb_filename;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnail, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumbnail, $thumb_path, 85);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($thumbnail);
    
    return $thumb_path;
}

/**
 * Get setting from database
 */
function get_setting($key, $default = '') {
    static $settings_cache = [];
    
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    $setting = db_fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    $value = $setting ? $setting['setting_value'] : $default;
    
    $settings_cache[$key] = $value;
    return $value;
}

/**
 * Update setting in database
 */
function update_setting($key, $value) {
    $existing = db_fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($existing) {
        return db_execute(
            "UPDATE settings SET setting_value = ? WHERE setting_key = ?",
            [$value, $key]
        );
    } else {
        return db_execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)",
            [$key, $value]
        );
    }
}

/**
 * Generate random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get client IP
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Get user agent
 */
function get_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}