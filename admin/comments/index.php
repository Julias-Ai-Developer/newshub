<!-- ============================================ -->
<!-- admin/comments/index.php -->
<?php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();
    $id = (int)$_POST['id'];
    
    switch ($_POST['action']) {
        case 'approve':
            db_execute("UPDATE comments SET status = 'approved' WHERE id = ?", [$id]);
            log_activity($_SESSION['admin_id'], 'approve_comment', "Approved comment ID: {$id}");
            $_SESSION['success'] = 'Comment approved';
            break;
        case 'spam':
            db_execute("UPDATE comments SET status = 'spam' WHERE id = ?", [$id]);
            log_activity($_SESSION['admin_id'], 'mark_spam', "Marked comment as spam ID: {$id}");
            $_SESSION['success'] = 'Comment marked as spam';
            break;
        case 'delete':
            db_execute("DELETE FROM comments WHERE id = ?", [$id]);
            log_activity($_SESSION['admin_id'], 'delete_comment', "Deleted comment ID: {$id}");
            $_SESSION['success'] = 'Comment deleted';
            break;
    }
    redirect(ADMIN_URL . '/comments/');
}

// Get comments
$status_filter = $_GET['status'] ?? '';
$where = !empty($status_filter) ? "WHERE c.status = '{$status_filter}'" : '';

$comments = db_fetch_all("
    SELECT c.*, a.title as article_title, a.slug as article_slug
    FROM comments c
    LEFT JOIN articles a ON c.article_id = a.id
    {$where}
    ORDER BY c.created_at DESC
    LIMIT 100
");

$page_title = 'Comments';
include APP_ROOT . '/templates/admin-header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Comments</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / <span>Comments</span>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <a href="?" class="btn btn-sm <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
        <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
        <a href="?status=approved" class="btn btn-sm <?php echo $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">Approved</a>
        <a href="?status=spam" class="btn btn-sm <?php echo $status_filter === 'spam' ? 'btn-danger' : 'btn-outline-danger'; ?>">Spam</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Article</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($comment['author_email']); ?></small>
                    </td>
                    <td><?php echo truncate($comment['content'], 100); ?></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo $comment['article_slug']; ?>" target="_blank">
                            <?php echo truncate($comment['article_title'], 40); ?>
                        </a>
                    </td>
                    <td>
                        <?php 
                        $badges = [
                            'pending' => 'badge-warning',
                            'approved' => 'badge-success',
                            'spam' => 'badge-danger',
                            'rejected' => 'badge-secondary'
                        ];
                        ?>
                        <span class="badge <?php echo $badges[$comment['status']] ?? ''; ?>">
                            <?php echo ucfirst($comment['status']); ?>
                        </span>
                    </td>
                    <td><?php echo time_ago($comment['created_at']); ?></td>
                    <td>
                        <?php if ($comment['status'] !== 'approved'): ?>
                        <form method="post" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="post" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                            <button type="submit" name="action" value="spam" class="btn btn-sm btn-warning">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;" onsubmit="return confirmDelete()">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">
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

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>