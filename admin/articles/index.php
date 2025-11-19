
<?php
// admin/articles/index.php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

// Handle delete
if (isset($_POST['delete']) && isset($_POST['id'])) {
    csrf_check();
    $id = (int)$_POST['id'];
    db_execute("DELETE FROM articles WHERE id = ?", [$id]);
    log_activity($_SESSION['admin_id'], 'delete_article', "Deleted article ID: {$id}");
    $_SESSION['success'] = 'Article deleted successfully';
    redirect(ADMIN_URL . '/articles/');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ADMIN_POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';

$where = [];
$params = [];

if (!empty($status_filter)) {
    $where[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where[] = "a.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$total_articles = db_fetch("SELECT COUNT(*) as count FROM articles a {$where_sql}", $params)['count'];
$total_pages = ceil($total_articles / $per_page);

// Get articles
$params[] = $per_page;
$params[] = $offset;

$articles = db_fetch_all("
    SELECT a.*, c.name as category_name, ad.full_name as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins ad ON a.author_id = ad.id
    {$where_sql}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
", $params);

$categories = db_fetch_all("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Articles';
include APP_ROOT . '/templates/admin-header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Articles</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / <span>Articles</span>
        </div>
    </div>
    <a href="<?php echo ADMIN_URL; ?>/articles/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Article
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                <a href="<?php echo ADMIN_URL; ?>/articles/" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Articles Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <strong><?php echo truncate($article['title'], 50); ?></strong>
                        <?php if ($article['is_featured']): ?>
                        <span class="badge badge-warning ms-1">Featured</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></td>
                    <td>
                        <?php 
                        $badges = [
                            'published' => 'badge-success',
                            'draft' => 'badge-warning',
                            'scheduled' => 'badge-info'
                        ];
                        ?>
                        <span class="badge <?php echo $badges[$article['status']] ?? ''; ?>">
                            <?php echo ucfirst($article['status']); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($article['views']); ?></td>
                    <td><?php echo time_ago($article['created_at']); ?></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo $article['slug']; ?>" 
                           class="btn btn-sm btn-secondary" target="_blank" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo ADMIN_URL; ?>/articles/edit.php?id=<?php echo $article['id']; ?>" 
                           class="btn btn-sm btn-secondary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="post" style="display: inline;" onsubmit="return confirmDelete()">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>" 
       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
        <?php echo $i; ?>
    </a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>