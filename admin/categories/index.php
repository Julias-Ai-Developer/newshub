<?php
// admin/categories/index.php
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/csrf.php';

require_admin();

$error = '';
$success = '';

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();
    
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        if ($_POST['action'] === 'create') {
            $slug = generate_unique_slug($name, 'categories');
            $result = db_execute("
                INSERT INTO categories (name, slug, description, status) 
                VALUES (?, ?, ?, ?)
            ", [$name, $slug, $description, $status]);
            
            if ($result) {
                log_activity($_SESSION['admin_id'], 'create_category', "Created category: {$name}");
                $success = 'Category created successfully';
            }
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $slug = generate_unique_slug($name, 'categories', 'slug', $id);
            $result = db_execute("
                UPDATE categories SET name = ?, slug = ?, description = ?, status = ? 
                WHERE id = ?
            ", [$name, $slug, $description, $status, $id]);
            
            if ($result) {
                log_activity($_SESSION['admin_id'], 'update_category', "Updated category: {$name}");
                $success = 'Category updated successfully';
            }
        }
    }
}

// Handle delete
if (isset($_POST['delete'])) {
    csrf_check();
    $id = (int)$_POST['id'];
    db_execute("DELETE FROM categories WHERE id = ?", [$id]);
    log_activity($_SESSION['admin_id'], 'delete_category', "Deleted category ID: {$id}");
    $success = 'Category deleted successfully';
}

// Get categories
$categories = db_fetch_all("
    SELECT c.*, COUNT(a.id) as article_count 
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id
    GROUP BY c.id
    ORDER BY c.name
");

$page_title = 'Categories';
include APP_ROOT . '/templates/admin-header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Categories</h1>
        <div class="breadcrumb">
            <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> / <span>Categories</span>
        </div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
        <i class="fas fa-plus"></i> New Category
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Articles</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                    <td><?php echo number_format($cat['article_count']); ?></td>
                    <td>
                        <span class="badge <?php echo $cat['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo ucfirst($cat['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick='editCategory(<?php echo json_encode($cat); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" style="display: inline;" onsubmit="return confirmDelete()">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
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

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="categoryDescription" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="categoryStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'New Category';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDescription').value = '';
    document.getElementById('categoryStatus').value = 'active';
}

function editCategory(cat) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.name;
    document.getElementById('categoryDescription').value = cat.description || '';
    document.getElementById('categoryStatus').value = cat.status;
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}
</script>

<?php include APP_ROOT . '/templates/admin-footer.php'; ?>

