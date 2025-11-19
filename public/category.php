<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';

// Get category slug
$slug = sanitize($_GET['slug'] ?? '');

if (empty($slug)) {
    redirect(SITE_URL);
}

// Get category
$category = db_fetch("SELECT * FROM categories WHERE slug = ? AND status = 'active'", [$slug]);

if (!$category) {
    http_response_code(404);
    die('Category not found');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Get total articles count
$total_articles = db_fetch("
    SELECT COUNT(*) as count 
    FROM articles 
    WHERE category_id = ? AND status = 'published' AND published_at <= NOW()
", [$category['id']])['count'];

$total_pages = ceil($total_articles / $per_page);

// Get articles
$articles = db_fetch_all("
    SELECT a.*, c.name as category_name, c.slug as category_slug,
           ad.full_name as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins ad ON a.author_id = ad.id
    WHERE a.category_id = ? 
    AND a.status = 'published'
    AND a.published_at <= NOW()
    ORDER BY a.published_at DESC
    LIMIT ? OFFSET ?
", [$category['id'], $per_page, $offset], 'iii');

$page_title = $category['name'] . ' - ' . get_setting('site_name', 'NewsHub');
include APP_ROOT . '/templates/header.php';
?>

<div class="section">
    <div class="container">
        <!-- Category Header -->
        <div class="text-center mb-5">
            <h1 class="display-4"><?php echo htmlspecialchars($category['name']); ?></h1>
            <?php if ($category['description']): ?>
            <p class="lead text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
            <?php endif; ?>
            <p class="text-muted"><?php echo number_format($total_articles); ?> articles</p>
        </div>
        
        <!-- Articles Grid -->
        <?php if (!empty($articles)): ?>
        <div class="row">
            <?php foreach ($articles as $article): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <?php if ($article['featured_image']): ?>
                    <img src="<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <span class="card-category"><?php echo htmlspecialchars($article['category_name']); ?></span>
                        
                        <h3 class="card-title">
                            <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h3>
                        
                        <?php if ($article['excerpt']): ?>
                        <p class="card-text"><?php echo truncate($article['excerpt'], 120); ?></p>
                        <?php endif; ?>
                        
                        <div class="card-meta">
                            <?php if ($article['author_name']): ?>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-clock"></i> <?php echo time_ago($article['published_at']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
            <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page || $i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                <a href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>" 
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php elseif (abs($i - $page) == 3): ?>
                <span class="page-link">...</span>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" class="page-link">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-newspaper" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3>No articles found</h3>
            <p class="text-muted">Check back later for new content.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include APP_ROOT . '/templates/footer.php'; ?>