<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$current_admin = get_current_admin();

// Get statistics
$total_articles = db_fetch("SELECT COUNT(*) as count FROM articles")['count'];
$published_articles = db_fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'];
$draft_articles = db_fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'];
$total_categories = db_fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'];
$total_comments = db_fetch("SELECT COUNT(*) as count FROM comments")['count'];
$pending_comments = db_fetch("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count'];
$total_subscribers = db_fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'")['count'];
$total_users = db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];

// Get recent articles
$recent_articles = db_fetch_all("
    SELECT a.*, c.name as category_name, ad.full_name as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins ad ON a.author_id = ad.id
    ORDER BY a.created_at DESC
    LIMIT 5
");

// Get recent comments
$recent_comments = db_fetch_all("
    SELECT cm.*, a.title as article_title, a.slug as article_slug
    FROM comments cm
    LEFT JOIN articles a ON cm.article_id = a.id
    ORDER BY cm.created_at DESC
    LIMIT 5
");

// Get popular articles
$popular_articles = db_fetch_all("
    SELECT a.*, c.name as category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published'
    ORDER BY a.views DESC
    LIMIT 5
");

// Get recent activity logs
$recent_activity = db_fetch_all("
    SELECT al.*, ad.full_name as admin_name
    FROM activity_logs al
    LEFT JOIN admins ad ON al.admin_id = ad.id
    ORDER BY al.created_at DESC
    LIMIT 10
");

$page_title = 'Dashboard';
include APP_ROOT . '/templates/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <div class="breadcrumb">
            <span>Home</span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-newspaper"></i>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Articles</div>
            <div class="stat-value"><?php echo number_format($total_articles); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-details">
            <div class="stat-label">Published</div>
            <div class="stat-value"><?php echo number_format($published_articles); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-details">
            <div class="stat-label">Pending Comments</div>
            <div class="stat-value"><?php echo number_format($pending_comments); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-details">
            <div class="stat-label">Subscribers</div>
            <div class="stat-value"><?php echo number_format($total_subscribers); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Articles -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Articles</span>
                <a href="<?php echo ADMIN_URL; ?>/articles/" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> New Article
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_articles as $article): ?>
                        <tr>
                            <td>
                                <strong><?php echo truncate($article['title'], 50); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>
                                <?php 
                                $badge_class = [
                                    'published' => 'badge-success',
                                    'draft' => 'badge-warning',
                                    'scheduled' => 'badge-info'
                                ];
                                ?>
                                <span class="badge <?php echo $badge_class[$article['status']] ?? 'badge-secondary'; ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($article['views']); ?></td>
                            <td><?php echo time_ago($article['created_at']); ?></td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/articles/edit.php?id=<?php echo $article['id']; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Popular Articles -->
        <div class="card mt-4">
            <div class="card-header">Most Popular Articles</div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Views</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_articles as $article): ?>
                        <tr>
                            <td><strong><?php echo truncate($article['title'], 50); ?></strong></td>
                            <td><?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo number_format($article['views']); ?></td>
                            <td><?php echo time_ago($article['published_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Recent Comments -->
        <div class="card">
            <div class="card-header">Recent Comments</div>
            
            <div class="p-3">
                <?php foreach ($recent_comments as $comment): ?>
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong style="font-size: 0.875rem;"><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                        <span class="badge <?php echo $comment['status'] === 'approved' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo ucfirst($comment['status']); ?>
                        </span>
                    </div>
                    <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">
                        <?php echo truncate($comment['content'], 80); ?>
                    </p>
                    <small class="text-muted">
                        On: <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo $comment['article_slug']; ?>">
                            <?php echo truncate($comment['article_title'], 40); ?>
                        </a>
                    </small>
                </div>
                <?php endforeach; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/comments/" class="btn btn-sm btn-outline-primary w-100">
                    View All Comments
                </a>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card mt-4">
            <div class="card-header">Quick Stats</div>
            
            <div class="p-3">
                <div class="d-flex justify-content-between mb-3">
                    <span>Draft Articles</span>
                    <strong class="text-warning"><?php echo $draft_articles; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Categories</span>
                    <strong class="text-primary"><?php echo $total_categories; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Total Comments</span>
                    <strong class="text-info"><?php echo $total_comments; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Registered Users</span>
                    <strong class="text-success"><?php echo $total_users; ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Activity Log -->
        <div class="card mt-4">
            <div class="card-header">Recent Activity</div>
            
            <div class="p-3">
                <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                    <div style="font-size: 0.875rem;">
                        <strong><?php echo htmlspecialchars($activity['admin_name'] ?? 'System'); ?></strong>
                        <span class="text-muted"><?php echo htmlspecialchars($activity['action']); ?></span>
                    </div>
                    <?php if ($activity['description']): ?>
                    <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted"><?php echo time_ago($activity['created_at']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>