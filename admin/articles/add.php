<?php
// admin/articles/add.php - ADD NEW ARTICLE (Alternative to create.php)
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$errors = [];
$success = '';

// Get categories and tags
$categories = db_fetch_all("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$all_tags = db_fetch_all("SELECT * FROM tags ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize WYSIWYG content
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $scheduled_at = sanitize($_POST['scheduled_at'] ?? '');
    $selected_tags = $_POST['tags'] ?? [];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    
    if (empty($errors)) {
        // Generate slug
        $slug = generate_unique_slug($title, 'articles');
        
        // Handle image upload
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['featured_image']);
            if ($upload_result['success']) {
                $featured_image = $upload_result['filepath'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        if (empty($errors)) {
            // Calculate reading time
            $reading_time = calculate_reading_time($content);
            
            // Set published date
            $published_at = null;
            if ($status === 'published') {
                $published_at = date('Y-m-d H:i:s');
            } elseif ($status === 'scheduled' && !empty($scheduled_at)) {
                $published_at = $scheduled_at;
            }
            
            // Insert article
            $result = db_execute("
                INSERT INTO articles (
                    title, slug, subtitle, excerpt, content, featured_image, 
                    author_id, category_id, status, is_featured, is_pinned,
                    reading_time, published_at, scheduled_at,
                    meta_title, meta_description, meta_keywords
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $title, $slug, $subtitle, $excerpt, $content, $featured_image,
                $_SESSION['admin_id'], $category_id, $status, $is_featured, $is_pinned,
                $reading_time, $published_at, $scheduled_at,
                $meta_title, $meta_description, $meta_keywords
            ]);
            
            if ($result) {
                $article_id = $result['insert_id'];
                
                // Insert tags
                if (!empty($selected_tags)) {
                    foreach ($selected_tags as $tag_id) {
                        db_execute("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", 
                                 [$article_id, (int)$tag_id]);
                    }
                }
                
                log_activity($_SESSION['admin_id'], 'create_article', "Created article: {$title}");
                $_SESSION['success'] = 'Article created successfully';
                redirect(ADMIN_URL . '/articles/edit.php?id=' . $article_id);
            } else {
                $errors[] = 'Failed to create article';
            }
        }
    }
}

$page_title = 'Add Article';
include APP_ROOT . '/templates/admin-header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/eof8tr0kcfiw2l035q2u2hru8rihobpsnoxf1og54v8jilbe/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: 'textarea#content',
    height: 500,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | formatselect | bold italic backcolor | \
              alignleft aligncenter alignright alignjustify | \
              bullist numlist outdent indent | removeformat | help',
    content_style: 'body { font-family: Roboto, sans-serif; font-size: 16px; line-height: 1.6; }'
});
</script>

<div class="page-header">
    <div>
        <h1 class="page-title">Add New Article</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/articles/">Articles</a> / 
            <span>Add New</span>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" data-autosave="new-article">
    <?php echo csrf_field(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="Enter article title..."
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control"
                               placeholder="Optional subtitle or tagline"
                               value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="3"
                                  placeholder="Brief summary for article cards..."><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                        <small class="form-help">Short description shown in article listings</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Content *</label>
                        <textarea name="content" id="content"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        <small class="form-help">Use the rich text editor to format your article</small>
                    </div>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> SEO Settings
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" 
                               data-max-length="60"
                               placeholder="Custom title for search engines..."
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                        <small class="form-help">Leave blank to use article title (recommended max 60 characters)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2"
                                  data-max-length="160"
                                  placeholder="Brief description for search engines..."><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                        <small class="form-help">Recommended 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               placeholder="keyword1, keyword2, keyword3"
                               value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
                        <small class="form-help">Comma-separated keywords</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Publish Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-paper-plane"></i> Publish
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="draft">Save as Draft</option>
                            <option value="published">Publish Now</option>
                            <option value="scheduled">Schedule for Later</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="scheduledDate" style="display: none;">
                        <label class="form-label">Publish Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                        <label class="form-check-label" for="isFeatured">
                            <strong>Featured Article</strong><br>
                            <small class="text-muted">Show in hero slider</small>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_pinned" class="form-check-input" id="isPinned">
                        <label class="form-check-label" for="isPinned">
                            <strong>Pin to Top</strong><br>
                            <small class="text-muted">Keep at top of listings</small>
                        </label>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> Create Article
                    </button>
                    
                    <a href="<?php echo ADMIN_URL; ?>/articles/" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
            
            <!-- Category -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-folder"></i> Category
                </div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help mt-2 d-block">
                        <a href="<?php echo ADMIN_URL; ?>/categories/add.php" target="_blank">
                            <i class="fas fa-plus"></i> Add New Category
                        </a>
                    </small>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-image"></i> Featured Image
                </div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <small class="form-help">Recommended: 1200x630px (JPG, PNG, WebP)</small>
                </div>
            </div>
            
            <!-- Tags -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tags"></i> Tags
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($all_tags)): ?>
                    <p class="text-muted">No tags available</p>
                    <?php else: ?>
                    <?php foreach ($all_tags as $tag): ?>
                    <div class="form-check">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" 
                               class="form-check-input" id="tag<?php echo $tag['id']; ?>">
                        <label class="form-check-label" for="tag<?php echo $tag['id']; ?>">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('statusSelect').addEventListener('change', function() {
    document.getElementById('scheduledDate').style.display = 
        this.value === 'scheduled' ? 'block' : 'none';
});
</script>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>

