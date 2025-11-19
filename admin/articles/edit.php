<?php
// admin/articles/edit.php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect(ADMIN_URL . '/articles/');
}

// Get article
$article = db_fetch("SELECT * FROM articles WHERE id = ?", [$id]);
if (!$article) {
    $_SESSION['error'] = 'Article not found';
    redirect(ADMIN_URL . '/articles/');
}

// Get article tags
$article_tags = db_fetch_all("SELECT tag_id FROM article_tags WHERE article_id = ?", [$id]);
$selected_tag_ids = array_column($article_tags, 'tag_id');

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
    $content = $_POST['content'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $scheduled_at = sanitize($_POST['scheduled_at'] ?? '');
    $selected_tags = $_POST['tags'] ?? [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    
    if (empty($errors)) {
        // Check if slug needs update
        if ($title !== $article['title']) {
            $slug = generate_unique_slug($title, 'articles', 'slug', $id);
        } else {
            $slug = $article['slug'];
        }
        
        // Handle image upload
        $featured_image = $article['featured_image'];
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['featured_image']);
            if ($upload_result['success']) {
                // Delete old image if exists
                if ($featured_image && file_exists(UPLOADS_DIR . $featured_image)) {
                    @unlink(UPLOADS_DIR . $featured_image);
                }
                $featured_image = $upload_result['filepath'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        if (empty($errors)) {
            $reading_time = calculate_reading_time($content);
            
            $published_at = $article['published_at'];
            if ($status === 'published' && empty($published_at)) {
                $published_at = date('Y-m-d H:i:s');
            } elseif ($status === 'scheduled' && !empty($scheduled_at)) {
                $published_at = $scheduled_at;
            }
            
            // Update article
            $result = db_execute("
                UPDATE articles SET 
                    title = ?, slug = ?, subtitle = ?, excerpt = ?, content = ?, 
                    featured_image = ?, category_id = ?, status = ?, 
                    is_featured = ?, is_pinned = ?, reading_time = ?,
                    published_at = ?, scheduled_at = ?,
                    meta_title = ?, meta_description = ?, meta_keywords = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $title, $slug, $subtitle, $excerpt, $content,
                $featured_image, $category_id, $status,
                $is_featured, $is_pinned, $reading_time,
                $published_at, $scheduled_at,
                $meta_title, $meta_description, $meta_keywords,
                $id
            ]);
            
            if ($result) {
                // Update tags
                db_execute("DELETE FROM article_tags WHERE article_id = ?", [$id]);
                if (!empty($selected_tags)) {
                    foreach ($selected_tags as $tag_id) {
                        db_execute("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", 
                                 [$id, (int)$tag_id]);
                    }
                }
                
                log_activity($_SESSION['admin_id'], 'update_article', "Updated article: {$title}");
                $_SESSION['success'] = 'Article updated successfully';
                redirect(ADMIN_URL . '/articles/edit.php?id=' . $id);
            } else {
                $errors[] = 'Failed to update article';
            }
        }
    }
}

$page_title = 'Edit Article';
include APP_ROOT . '/templates/admin-header.php';
?>

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
        <h1 class="page-title">Edit Article</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / 
            <a href="<?php echo ADMIN_URL; ?>/articles/">Articles</a> / 
            <span>Edit</span>
        </div>
    </div>
    <div>
        <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo $article['slug']; ?>" 
           class="btn btn-secondary" target="_blank">
            <i class="fas fa-eye"></i> View Article
        </a>
        <a href="<?php echo ADMIN_URL; ?>/articles/delete.php?id=<?php echo $id; ?>" 
           class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this article?')">
            <i class="fas fa-trash"></i> Delete
        </a>
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

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
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
                               value="<?php echo htmlspecialchars($article['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control"
                               value="<?php echo htmlspecialchars($article['subtitle'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Content *</label>
                        <textarea name="content" id="content"><?php echo htmlspecialchars($article['content']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">SEO Settings</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="<?php echo htmlspecialchars($article['meta_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($article['meta_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="<?php echo htmlspecialchars($article['meta_keywords'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Publish</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="draft" <?php echo $article['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $article['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="scheduled" <?php echo $article['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="scheduledDate" style="display: <?php echo $article['status'] === 'scheduled' ? 'block' : 'none'; ?>;">
                        <label class="form-label">Schedule Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control"
                               value="<?php echo $article['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($article['scheduled_at'])) : ''; ?>">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured"
                               <?php echo $article['is_featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isFeatured">Featured Article</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_pinned" class="form-check-input" id="isPinned"
                               <?php echo $article['is_pinned'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isPinned">Pin to Top</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Update Article
                    </button>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">Category</div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $article['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">Featured Image</div>
                <div class="card-body">
                    <?php if ($article['featured_image']): ?>
                    <img src="<?php echo UPLOADS_URL . htmlspecialchars($article['featured_image']); ?>" 
                         class="img-fluid mb-3" style="border-radius: 8px;">
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">Tags</div>
                <div class="card-body">
                    <?php foreach ($all_tags as $tag): ?>
                    <div class="form-check">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" 
                               class="form-check-input" id="tag<?php echo $tag['id']; ?>"
                               <?php echo in_array($tag['id'], $selected_tag_ids) ? 'checked' : ''; ?>>
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

