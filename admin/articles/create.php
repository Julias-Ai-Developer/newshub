<?php
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
    $content = $_POST['content'] ?? ''; // Don't sanitize content (WYSIWYG)
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');

    // Normalize scheduled_at: allow NULL or proper MySQL DATETIME, never empty string
    $raw_scheduled_at = $_POST['scheduled_at'] ?? '';
    $scheduled_at = null;
    if (!empty($raw_scheduled_at)) {
        // Convert HTML datetime-local (Y-m-dTH:i) to MySQL DATETIME (Y-m-d H:i:s)
        $scheduled_dt = str_replace('T', ' ', $raw_scheduled_at);
        // Append seconds if missing
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $scheduled_dt)) {
            $scheduled_dt .= ':00';
        }
        $scheduled_at = $scheduled_dt;
    }

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

$page_title = 'Create Article';
include APP_ROOT . '/templates/admin-header.php';
?>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/eof8tr0kcfiw2l035q2u2hru8rihobpsnoxf1og54v8jilbe/tinymce/6/tinymce.min.js"></script>
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
    content_style: 'body { font-family: Roboto, sans-serif; font-size: 16px; }'
});
</script>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Article</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/articles/">Articles</a> / 
            <span>Create</span>
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

<form method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                        <small class="form-help">Brief summary for article cards</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Content *</label>
                        <textarea name="content" id="content"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mb-4">
                <div class="card-header">SEO Settings</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>"
                               placeholder="keyword1, keyword2, keyword3">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Publish Settings -->
            <div class="card mb-4">
                <div class="card-header">Publish</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="scheduledDate" style="display: none;">
                        <label class="form-label">Schedule Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                        <label class="form-check-label" for="isFeatured">Featured Article</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_pinned" class="form-check-input" id="isPinned">
                        <label class="form-check-label" for="isPinned">Pin to Top</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Create Article
                    </button>
                </div>
            </div>
            
            <!-- Category -->
            <div class="card mb-4">
                <div class="card-header">Category</div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="card mb-4">
                <div class="card-header">Featured Image</div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <small class="form-help">Recommended: 1200x630px</small>
                </div>
            </div>
            
            <!-- Tags -->
            <div class="card mb-4">
                <div class="card-header">Tags</div>
                <div class="card-body">
                    <?php foreach ($all_tags as $tag): ?>
                    <div class="form-check">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" 
                               class="form-check-input" id="tag<?php echo $tag['id']; ?>">
                        <label class="form-check-label" for="tag<?php echo $tag['id']; ?>">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
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