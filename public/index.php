<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/auth.php';

// Get hero articles (featured & pinned)
$hero_articles = db_fetch_all("
    SELECT a.*, c.name as category_name, c.slug as category_slug, 
           ad.full_name as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins ad ON a.author_id = ad.id
    WHERE a.status = 'published' 
    AND a.published_at <= NOW()
    AND (a.is_featured = 1 OR a.is_pinned = 1)
    ORDER BY a.is_pinned DESC, a.published_at DESC
    LIMIT 5
");

// Get trending articles
$trending_articles = db_fetch_all("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' 
    AND a.published_at <= NOW()
    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY a.views DESC
    LIMIT 6
");

// Get latest articles by category
// Always include Politics section first if it exists
$categories = [];
$politics_cat = db_fetch("SELECT * FROM categories WHERE slug = 'politics' AND status = 'active'");
if ($politics_cat) {
    $categories[] = $politics_cat;
}
$remaining = 4 - count($categories);
if ($remaining > 0) {
    $other_cats = db_fetch_all(
        "SELECT * FROM categories WHERE status = 'active' AND slug != 'politics' LIMIT {$remaining}"
    );
    $categories = array_merge($categories, $other_cats);
}
$category_articles = [];

foreach ($categories as $category) {
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
        LIMIT 3
    ", [$category['id']]);
    
    if (!empty($articles)) {
        $category_articles[$category['id']] = [
            'category' => $category,
            'articles' => $articles
        ];
    }
}

$page_title = get_setting('site_name', 'NewsHub');
include APP_ROOT . '/templates/header.php';
?>

<!-- Hero Slider -->
<?php if (!empty($hero_articles)): ?>
<section class="hero-section">
    <div class="container">
        <div id="heroSlider" class="hero-slider carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($hero_articles as $index => $article): ?>
                <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="<?php echo $index; ?>" 
                        class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <div class="carousel-inner">
                <?php foreach ($hero_articles as $index => $article): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="hero-slide" style="background-image: url('<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>');">
                        <div class="hero-overlay">
                            <div class="hero-content">
                                <?php if ($article['category_name']): ?>
                                <span class="hero-category"><?php echo htmlspecialchars($article['category_name']); ?></span>
                                <?php endif; ?>
                                
                                <h1 class="hero-title">
                                    <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" style="color: white;">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h1>
                                
                                <?php if ($article['subtitle']): ?>
                                <p class="hero-excerpt"><?php echo htmlspecialchars($article['subtitle']); ?></p>
                                <?php endif; ?>
                                
                                <div class="hero-meta">
                                    <?php if ($article['author_name']): ?>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-clock"></i> <?php echo time_ago($article['published_at']); ?></span>
                                    <?php if ($article['reading_time']): ?>
                                    <span><i class="fas fa-book-reader"></i> <?php echo $article['reading_time']; ?> min read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Trending News -->
<?php if (!empty($trending_articles)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">Trending Now</h2>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="row">
                    <?php foreach (array_slice($trending_articles, 0, 4) as $article): ?>
                    <div class="col-md-6 mb-4">
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
                                <p class="card-text"><?php echo truncate($article['excerpt'], 100); ?></p>
                                <?php endif; ?>
                                
                                <div class="card-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo time_ago($article['published_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <h4 class="mb-3">Top Stories</h4>
                <?php foreach (array_slice($trending_articles, 0, 5) as $index => $article): ?>
                <div class="trending-card">
                    <div class="trending-number"><?php echo sprintf('%02d', $index + 1); ?></div>
                    <div class="trending-content">
                        <h5>
                            <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h5>
                        <small class="text-muted"><?php echo time_ago($article['published_at']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Sections -->
<?php foreach ($category_articles as $data): ?>
<section class="section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0"><?php echo htmlspecialchars($data['category']['name']); ?></h2>
            <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo htmlspecialchars($data['category']['slug']); ?>" 
               class="btn btn-outline-primary">View All</a>
        </div>
        
        <div class="row">
            <?php foreach ($data['articles'] as $article): ?>
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
                        <p class="card-text"><?php echo truncate($article['excerpt'], 100); ?></p>
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
    </div>
</section>
<?php endforeach; ?>

<!-- Newsletter Section -->
<section class="section" style="background: linear-gradient(135deg, var(--dark-color), var(--primary-color)); color: white;">
    <div class="container text-center">
        <h2 class="mb-3" style="color: white;">Subscribe to Our Newsletter</h2>
        <p class="mb-4" style="font-size: 1.125rem; opacity: 0.9;">Get the latest news delivered directly to your inbox</p>
        
        <form id="newsletterForm" class="row g-3 justify-content-center">
            <div class="col-auto" style="min-width: 300px;">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </div>
        </form>
        <div id="newsletterMessage" style="margin-top: 1rem;"></div>
    </div>
</section>

<script>
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageEl = document.getElementById('newsletterMessage');
    
    fetch('<?php echo BASE_URL; ?>/api/newsletter.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageEl.innerHTML = '<div class="alert alert-success">Thank you for subscribing!</div>';
            this.reset();
        } else {
            messageEl.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(error => {
        messageEl.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    });
});
</script>

<?php include APP_ROOT . '/templates/footer.php'; ?>