<?php
// includes/functions.php - Additional utility functions
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Display pagination
 */
function render_pagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($prev_params) . '" class="page-link">';
        $html .= '<i class="fas fa-chevron-left"></i> Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $first_params = array_merge($params, ['page' => 1]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($first_params) . '" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-link">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $active = $i == $current_page ? 'active' : '';
        $html .= '<a href="' . $base_url . '?' . http_build_query($page_params) . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<span class="page-link">...</span>';
        }
        $last_params = array_merge($params, ['page' => $total_pages]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($last_params) . '" class="page-link">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($next_params) . '" class="page-link">';
        $html .= 'Next <i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get breadcrumb for admin
 */
function admin_breadcrumb($items = []) {
    $html = '<div class="breadcrumb">';
    $html .= '<a href="' . ADMIN_URL . '">Dashboard</a>';
    
    foreach ($items as $label => $url) {
        if ($url) {
            $html .= ' / <a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a>';
        } else {
            $html .= ' / <span>' . htmlspecialchars($label) . '</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get status badge HTML
 */
function status_badge($status) {
    $badges = [
        'published' => 'badge-success',
        'draft' => 'badge-warning',
        'scheduled' => 'badge-info',
        'active' => 'badge-success',
        'inactive' => 'badge-secondary',
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'spam' => 'badge-danger',
        'rejected' => 'badge-secondary'
    ];
    
    $class = $badges[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
}

/**
 * Validate URL
 */
function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get avatar URL or initials
 */
function get_avatar($name, $size = 48) {
    $initial = strtoupper(substr($name, 0, 1));
    return '<div class="avatar" style="width: ' . $size . 'px; height: ' . $size . 'px; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold;">' . $initial . '</div>';
}

/**
 * Pluralize word
 */
function pluralize($count, $singular, $plural = null) {
    if ($plural === null) {
        $plural = $singular . 's';
    }
    return $count . ' ' . ($count == 1 ? $singular : $plural);
}

/**
 * Get excerpt with read more
 */
function get_excerpt($content, $length = 150, $more = '...') {
    $content = strip_tags($content);
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . $more;
}

/**
 * Check if URL is active
 */
function is_active_url($url) {
    return strpos($_SERVER['REQUEST_URI'], $url) !== false;
}

/**
 * Generate random color
 */
function random_color() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

/**
 * Convert string to title case
 */
function title_case($string) {
    return ucwords(str_replace(['-', '_'], ' ', $string));
}

/**
 * Check if string contains
 */
function str_contains_any($haystack, $needles) {
    foreach ($needles as $needle) {
        if (stripos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Get days ago
 */
function days_ago($date) {
    $diff = time() - strtotime($date);
    return floor($diff / (60 * 60 * 24));
}

/**
 * Is weekend
 */
function is_weekend($date = null) {
    $date = $date ?? date('Y-m-d');
    $day = date('N', strtotime($date));
    return $day >= 6;
}

/**
 * Clean HTML
 */
function clean_html($html) {
    $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6>');
    return $html;
}

/**
 * Array to CSV
 */
function array_to_csv($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Generate meta tags
 */
function generate_meta_tags($title, $description, $image = null, $url = null) {
    $url = $url ?? current_url();
    $site_name = get_setting('site_name', 'NewsHub');
    
    $html = '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    $html .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    $html .= '<meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    $html .= '<meta property="og:site_name" content="' . htmlspecialchars($site_name) . '">' . "\n";
    
    if ($image) {
        $html .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    
    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    
    return $html;
}

/**
 * Get popular articles
 */
function get_popular_articles($limit = 5) {
    return db_fetch_all("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published' AND a.published_at <= NOW()
        ORDER BY a.views DESC
        LIMIT ?
    ", [$limit], 'i');
}

/**
 * Get recent articles
 */
function get_recent_articles($limit = 5, $exclude_id = null) {
    $where = "WHERE a.status = 'published' AND a.published_at <= NOW()";
    $params = [];
    $types = '';
    
    if ($exclude_id) {
        $where .= " AND a.id != ?";
        $params[] = $exclude_id;
        $types .= 'i';
    }
    
    $params[] = $limit;
    $types .= 'i';
    
    return db_fetch_all("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        {$where}
        ORDER BY a.published_at DESC
        LIMIT ?
    ", $params, $types);
}

/**
 * Get related articles by category
 */
function get_related_articles($article_id, $category_id, $limit = 3) {
    return db_fetch_all("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.category_id = ? 
        AND a.id != ?
        AND a.status = 'published'
        AND a.published_at <= NOW()
        ORDER BY a.published_at DESC
        LIMIT ?
    ", [$category_id, $article_id, $limit], 'iii');
}

/**
 * Get category with article count
 */
function get_categories_with_count() {
    return db_fetch_all("
        SELECT c.*, COUNT(a.id) as article_count
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id 
            AND a.status = 'published' 
            AND a.published_at <= NOW()
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY c.name
    ");
}

/**
 * Search articles
 */
function search_articles($query, $limit = 50) {
    $search_term = '%' . $query . '%';
    return db_fetch_all("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
        AND a.status = 'published'
        AND a.published_at <= NOW()
        ORDER BY a.published_at DESC
        LIMIT ?
    ", [$search_term, $search_term, $search_term, $limit], 'sssi');
}

/**
 * Get comment count for article
 */
function get_comment_count($article_id) {
    $result = db_fetch("
        SELECT COUNT(*) as count 
        FROM comments 
        WHERE article_id = ? AND status = 'approved'
    ", [$article_id]);
    
    return $result['count'] ?? 0;
}

/**
 * Record page view
 */
function record_page_view($page_type, $page_id = null) {
    // Simple implementation - can be enhanced with analytics
    if ($page_type === 'article' && $page_id) {
        db_execute("UPDATE articles SET views = views + 1 WHERE id = ?", [$page_id]);
    }
}

/**
 * Get site statistics
 */
function get_site_stats() {
    return [
        'total_articles' => db_fetch("SELECT COUNT(*) as count FROM articles")['count'],
        'published_articles' => db_fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
        'total_categories' => db_fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
        'total_comments' => db_fetch("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")['count'],
        'total_users' => db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
        'total_subscribers' => db_fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'")['count']
    ];
}

/**
 * Check if article is new (published within last 7 days)
 */
function is_new_article($published_at) {
    $days_old = days_ago($published_at);
    return $days_old <= 7;
}

/**
 * Get tag cloud
 */
function get_tag_cloud($limit = 20) {
    return db_fetch_all("
        SELECT t.*, COUNT(at.article_id) as article_count
        FROM tags t
        LEFT JOIN article_tags at ON t.id = at.tag_id
        GROUP BY t.id
        HAVING article_count > 0
        ORDER BY article_count DESC
        LIMIT ?
    ", [$limit], 'i');
}

/**
 * Notify admins (email notification)
 */
function notify_admins($subject, $message) {
    $admins = db_fetch_all("SELECT email FROM admins WHERE status = 'active'");
    
    foreach ($admins as $admin) {
        send_email($admin['email'], $subject, $message);
    }
}

/**
 * Check if user can comment
 */
function can_comment($user_id = null) {
    if (!get_setting('enable_comments', '1')) {
        return false;
    }
    
    // Add rate limiting logic here if needed
    return true;
}

/**
 * Generate RSS feed
 */
function generate_rss_feed() {
    $articles = db_fetch_all("
        SELECT * FROM articles 
        WHERE status = 'published' 
        AND published_at <= NOW()
        ORDER BY published_at DESC 
        LIMIT 20
    ");
    
    header('Content-Type: application/rss+xml; charset=utf-8');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<rss version="2.0">';
    echo '<channel>';
    echo '<title>' . htmlspecialchars(get_setting('site_name', 'NewsHub')) . '</title>';
    echo '<link>' . SITE_URL . '</link>';
    echo '<description>' . htmlspecialchars(get_setting('site_description', '')) . '</description>';
    
    foreach ($articles as $article) {
        echo '<item>';
        echo '<title>' . htmlspecialchars($article['title']) . '</title>';
        echo '<link>' . SITE_URL . '/article.php?slug=' . $article['slug'] . '</link>';
        echo '<description>' . htmlspecialchars($article['excerpt'] ?? '') . '</description>';
        echo '<pubDate>' . date('r', strtotime($article['published_at'])) . '</pubDate>';
        echo '</item>';
    }
    
    echo '</channel>';
    echo '</rss>';
    exit;
}

/**
 * Clear cache (if caching is implemented)
 */
function clear_cache($type = 'all') {
    // Placeholder for cache clearing
    // Implement based on your caching strategy
    return true;
}