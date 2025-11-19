
<?php
// ============================================
// templates/article-card.php - REUSABLE ARTICLE CARD
// ============================================
if (!defined('APP_ROOT')) die('Direct access not permitted');

// Usage: include this file with $article variable set
if (!isset($article)) return;
?>
<div class="card h-100">
    <?php if (!empty($article['featured_image'])): ?>
    <img src="<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>" 
         class="card-img-top" 
         alt="<?php echo htmlspecialchars($article['title']); ?>"
         loading="lazy">
    <?php endif; ?>
    
    <div class="card-body">
        <?php if (!empty($article['category_name'])): ?>
        <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo htmlspecialchars($article['category_slug']); ?>" 
           class="card-category">
            <?php echo htmlspecialchars($article['category_name']); ?>
        </a>
        <?php endif; ?>
        
        <h3 class="card-title">
            <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                <?php echo htmlspecialchars($article['title']); ?>
            </a>
        </h3>
        
        <?php if (!empty($article['excerpt'])): ?>
        <p class="card-text"><?php echo truncate($article['excerpt'], 120); ?></p>
        <?php endif; ?>
        
        <div class="card-meta">
            <?php if (!empty($article['author_name'])): ?>
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
            <?php endif; ?>
            <span><i class="fas fa-clock"></i> <?php echo time_ago($article['published_at']); ?></span>
            <?php if (!empty($article['reading_time'])): ?>
            <span><i class="fas fa-book-reader"></i> <?php echo $article['reading_time']; ?> min</span>
            <?php endif; ?>
        </div>
    </div>
</div>