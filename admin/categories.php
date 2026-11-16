<?php
$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_string('action');
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $categoryName = post_string('category_name');
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verify_csrf_token($token)) {
        set_flash('error', 'Your form session expired. Refresh and try again.');
    } elseif (!in_array($action, ['add', 'edit', 'delete'], true)) {
        set_flash('error', 'Invalid category action.');
    } elseif (in_array($action, ['add', 'edit'], true) &&
        ($categoryName === '' || mb_strlen($categoryName) > 50)) {
        set_flash('error', 'Category name is required and must be 50 characters or fewer.');
    } elseif (in_array($action, ['edit', 'delete'], true) && ($categoryId === false || $categoryId < 1)) {
        set_flash('error', 'Invalid category.');
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)');
                $stmt->execute([$categoryName]);
                set_flash('success', 'Category added.');
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare('UPDATE categories SET category_name = ? WHERE category_id = ?');
                $stmt->execute([$categoryName, $categoryId]);
                set_flash($stmt->rowCount() > 0 ? 'success' : 'info', $stmt->rowCount() > 0 ? 'Category updated.' : 'No category changes were made.');
            } else {
                $pdo->beginTransaction();
                $lockCategory = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ? FOR UPDATE');
                $lockCategory->execute([$categoryId]);
                if (!$lockCategory->fetchColumn()) {
                    $pdo->rollBack();
                    set_flash('error', 'Category not found.');
                } else {
                    $countItems = $pdo->prepare('SELECT COUNT(*) FROM items WHERE category_id = ?');
                    $countItems->execute([$categoryId]);
                    if ((int)$countItems->fetchColumn() > 0) {
                        $pdo->rollBack();
                        set_flash('error', 'This category is in use. Reassign its reports before deleting it.');
                    } else {
                        $delete = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
                        $delete->execute([$categoryId]);
                        $pdo->commit();
                        set_flash('success', 'Category deleted.');
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                set_flash('error', 'That category name already exists.');
            } else {
                error_log('CampusFind category update failed: ' . $e->getMessage());
                set_flash('error', 'The category change could not be saved.');
            }
        }
    }

    header('Location: ' . BASE_URL . 'admin/categories.php');
    exit;
}

$categories = $pdo->query(
    'SELECT c.category_id, c.category_name, COUNT(i.item_id) AS item_count
     FROM categories c LEFT JOIN items i ON i.category_id = c.category_id
     GROUP BY c.category_id, c.category_name ORDER BY c.category_name'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="mb-4">
    <h1 class="h2 fw-bold text-navy mb-1">Manage Categories</h1>
    <p class="text-muted mb-0">Keep item categories clear and organized.</p>
</div>

<form method="POST" class="card card-custom p-3 p-md-4 mb-4">
    <h2 class="h5 fw-bold text-navy">Add category</h2>
    <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
    <input type="hidden" name="action" value="add">
    <div class="input-group">
        <input type="text" name="category_name" class="form-control" maxlength="50" required aria-label="New category name" placeholder="Category name">
        <button type="submit" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Add category</button>
    </div>
</form>

<div class="card card-custom p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Category</th><th>Reports using category</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="category_id" value="<?php echo (int)$category['category_id']; ?>">
                                <input type="text" name="category_name" class="form-control form-control-sm" maxlength="50" required value="<?php echo e($category['category_name']); ?>" aria-label="Category name">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                        <td><?php echo (int)$category['item_count']; ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this category? It can only be deleted if no reports use it.');">
                                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo (int)$category['category_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" <?php echo (int)$category['item_count'] > 0 ? 'disabled title="Reassign reports before deleting"' : ''; ?>>Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$categories): ?><tr><td colspan="3" class="text-center text-muted py-4">No categories found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_content_end.php';
require_once __DIR__ . '/../includes/footer.php';
?>
