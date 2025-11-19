<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';

$query = sanitize($_GET['q'] ?? '');
$articles = [];
$total_results = 0;

if (!empty($query)) {
    // Search articles using FULLTEXT or LIKE
    $search_term = '%' . $query . '%';
    
    $articles = db_fetch_all("
        SELECT a.*, c.name as category_name, c.slug as category_slug,
               ad.full_name as author_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admins ad ON a.author_id = ad.id
        WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
        AND a.status = 'published'
        AND a.published_at <= NOW()
        ORDER BY a.published_at DESC
        LIMIT 50
    ", [$search_term, $search_term, $search_term]);
    
    $total_results = count($articles);
}

$page_title = 'Search Results - ' . get_setting('site_name', 'NewsHub');
include APP_ROOT . '/templates/header.php';
?>

<div class="section">
    <div class="container">
        <!-- Search Header -->
        <div class="text-center mb-5">
            <h1 class="display-4">Search Results</h1>
            
            <!-- Search Form -->
            <form action="<?php echo SITE_URL; ?>/search.php" method="get" class="mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="input-group input-group-lg">
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Search articles..." 
                                   value="<?php echo htmlspecialchars($query); ?>"
                                   style="border-radius: 50px 0 0 50px;">
                            <button type="submit" class="btn btn-primary" style="border-radius: 0 50px 50px 0;">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($query)): ?>
            <p class="mt-3 text-muted">
                Found <strong><?php echo number_format($total_results); ?></strong> result(s) for 
                "<strong><?php echo htmlspecialchars($query); ?></strong>"
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Search Results -->
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
                        <?php if ($article['category_name']): ?>
                        <span class="card-category"><?php echo htmlspecialchars($article['category_name']); ?></span>
                        <?php endif; ?>
                        
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
        
        <?php elseif (!empty($query)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3>No results found</h3>
            <p class="text-muted">Try different keywords or browse our categories.</p>
            
            <div class="mt-4">
                <h5>Popular Categories</h5>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <?php
                    $categories = db_fetch_all("SELECT * FROM categories WHERE status = 'active' LIMIT 6");
                    foreach ($categories as $cat):
                    ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $cat['slug']; ?>" 
                       class="btn btn-outline-primary">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-search" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3>Start Searching</h3>
            <p class="text-muted">Enter keywords above to find articles.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include APP_ROOT . '/templates/footer.php'; ?>