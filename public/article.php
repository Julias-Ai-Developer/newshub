<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

// Get article slug
$slug = sanitize($_GET['slug'] ?? '');

if (empty($slug)) {
    redirect(SITE_URL);
}

// Get article
$article = db_fetch("
    SELECT a.*, c.name as category_name, c.slug as category_slug,
           ad.full_name as author_name, ad.id as author_id
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins ad ON a.author_id = ad.id
    WHERE a.slug = ? AND a.status = 'published' AND a.published_at <= NOW()
", [$slug]);

if (!$article) {
    http_response_code(404);
    die('Article not found');
}

// Update view count
db_execute("UPDATE articles SET views = views + 1 WHERE id = ?", [$article['id']]);

// Get article tags
$tags = db_fetch_all("
    SELECT t.* FROM tags t
    INNER JOIN article_tags at ON t.id = at.tag_id
    WHERE at.article_id = ?
", [$article['id']]);

// Get related articles
$related_articles = db_fetch_all("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? 
    AND a.id != ?
    AND a.status = 'published'
    AND a.published_at <= NOW()
    ORDER BY a.published_at DESC
    LIMIT 3
", [$article['category_id'], $article['id']]);

// Get comments
$comments = db_fetch_all("
    SELECT c.*, u.full_name as user_name
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved' AND c.parent_id IS NULL
    ORDER BY c.created_at DESC
", [$article['id']]);

// Handle comment submission
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    csrf_check();
    
    $author_name = sanitize($_POST['author_name'] ?? '');
    $author_email = sanitize($_POST['author_email'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    
    if (empty($author_name) || empty($author_email) || empty($content)) {
        $comment_error = 'All fields are required';
    } elseif (!is_valid_email($author_email)) {
        $comment_error = 'Invalid email address';
    } else {
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
        
        $result = db_execute("
            INSERT INTO comments (article_id, user_id, author_name, author_email, content, status, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ", [
            $article['id'],
            $user_id,
            $author_name,
            $author_email,
            $content,
            get_client_ip(),
            get_user_agent()
        ]);
        
        if ($result) {
            $comment_success = 'Your comment has been submitted and is awaiting moderation.';
        } else {
            $comment_error = 'Failed to submit comment. Please try again.';
        }
    }
}

$page_title = $article['meta_title'] ?? $article['title'];
$page_description = $article['meta_description'] ?? $article['excerpt'];

include APP_ROOT . '/templates/header.php';
?>

<style>
.article-page {
    padding: 3rem 0;
}
</style>

<div class="article-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <article class="article-content-wrapper">
                    <div class="article-header">
                        <?php if ($article['category_name']): ?>
                        <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $article['category_slug']; ?>" 
                           class="article-category">
                            <?php echo htmlspecialchars($article['category_name']); ?>
                        </a>
                        <?php endif; ?>
                        
                        <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                        
                        <?php if ($article['subtitle']): ?>
                        <p class="article-subtitle"><?php echo htmlspecialchars($article['subtitle']); ?></p>
                        <?php endif; ?>
                        
                        <div class="article-meta">
                            <div class="article-author">
                                <div class="author-avatar" style="background: var(--primary-color); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    <?php echo strtoupper(substr($article['author_name'] ?? 'A', 0, 1)); ?>
                                </div>
                                <div class="author-info">
                                    <strong><?php echo htmlspecialchars($article['author_name'] ?? 'Admin'); ?></strong>
                                    <span><?php echo format_date($article['published_at'], 'F j, Y'); ?></span>
                                </div>
                            </div>
                            
                            <span><i class="fas fa-clock"></i> <?php echo time_ago($article['published_at']); ?></span>
                            
                            <?php if ($article['reading_time']): ?>
                            <span><i class="fas fa-book-reader"></i> <?php echo $article['reading_time']; ?> min read</span>
                            <?php endif; ?>
                            
                            <span><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?> views</span>
                        </div>
                    </div>
                    
                    <?php if ($article['featured_image']): ?>
                    <img src="<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>" 
                         class="article-featured-image">
                    <?php endif; ?>
                    
                    <div class="article-content">
                        <?php echo $article['content']; ?>
                    </div>
                    
                    <?php if (!empty($tags)): ?>
                    <div class="article-tags mt-4">
                        <strong>Tags:</strong>
                        <?php foreach ($tags as $tag): ?>
                        <a href="<?php echo SITE_URL; ?>/tag.php?slug=<?php echo $tag['slug']; ?>" 
                           class="badge bg-secondary me-2">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Social Share -->
                    <div class="social-share">
                        <strong>Share:</strong>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(current_url()); ?>" 
                           target="_blank" class="social-share-btn share-facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(current_url()); ?>&text=<?php echo urlencode($article['title']); ?>" 
                           target="_blank" class="social-share-btn share-twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(current_url()); ?>" 
                           target="_blank" class="social-share-btn share-linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' - ' . current_url()); ?>" 
                           target="_blank" class="social-share-btn share-whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </article>
                
                <!-- Comments Section -->
                <div class="comments-section">
                    <h3>Comments (<?php echo count($comments); ?>)</h3>
                    
                    <?php if ($comment_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($comment_error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($comment_success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($comment_success); ?></div>
                    <?php endif; ?>
                    
                    <!-- Comment Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Leave a Comment</h5>
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" name="author_name" class="form-control" 
                                               placeholder="Your Name" required
                                               value="<?php echo htmlspecialchars($_POST['author_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" name="author_email" class="form-control" 
                                               placeholder="Your Email" required
                                               value="<?php echo htmlspecialchars($_POST['author_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <textarea name="content" class="form-control" rows="4" 
                                              placeholder="Your comment..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="submit_comment" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Comment
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Display Comments -->
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <strong class="comment-author">
                                <?php echo htmlspecialchars($comment['author_name']); ?>
                            </strong>
                            <span class="comment-date"><?php echo time_ago($comment['created_at']); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Articles -->
                <?php if (!empty($related_articles)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Related Articles</h5>
                        <?php foreach ($related_articles as $related): ?>
                        <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                            <h6>
                                <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo $related['slug']; ?>">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            </h6>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo time_ago($related['published_at']); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter -->
                <div class="card" style="background: linear-gradient(135deg, var(--dark-color), var(--primary-color)); color: white;">
                    <div class="card-body">
                        <h5 style="color: white;">Subscribe to Newsletter</h5>
                        <p style="opacity: 0.9;">Get the latest articles delivered to your inbox</p>
                        <form id="sidebarNewsletter">
                            <input type="email" name="email" class="form-control mb-2" 
                                   placeholder="Your email" required>
                            <button type="submit" class="btn btn-light w-100">Subscribe</button>
                        </form>
                        <div id="sidebarNewsletterMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('sidebarNewsletter')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const msgEl = document.getElementById('sidebarNewsletterMsg');
    
    fetch('<?php echo SITE_URL; ?>/api/newsletter.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        msgEl.innerHTML = data.success ? 
            '<small class="text-success">✓ Subscribed!</small>' : 
            '<small class="text-danger">' + data.error + '</small>';
        if (data.success) this.reset();
    });
});
</script>

<?php include APP_ROOT . '/templates/footer.php'; ?>