<?php
$pageTitle = 'Manage Item Reports';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$allowedStatuses = ['pending', 'approved', 'rejected', 'all'];
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'pending';
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'pending';
}
$type = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, ['', 'lost', 'found'], true)) {
    $type = '';
}
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$filterParams = ['status' => $status, 'type' => $type, 'q' => $search];
$filterQuery = http_build_query($filterParams);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = filter_var($_POST['item_id'] ?? '', FILTER_VALIDATE_INT);
    $action = post_string('action');
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verify_csrf_token($token)) {
        set_flash('error', 'Your form session expired. Refresh and try again.');
    } elseif ($itemId === false || $itemId < 1 || !in_array($action, ['approve', 'reject', 'edit', 'delete'], true)) {
        set_flash('error', 'Invalid report action.');
    } elseif (in_array($action, ['approve', 'reject'], true)) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare(
            "UPDATE items SET approval_status = ?
             WHERE item_id = ? AND approval_status = 'pending'"
        );
        $stmt->execute([$newStatus, $itemId]);
        set_flash($stmt->rowCount() === 1 ? 'success' : 'warning',
            $stmt->rowCount() === 1 ? 'Report ' . $newStatus . '.' : 'This report is no longer pending review.');
    } elseif ($action === 'edit') {
        $itemName = post_string('item_name');
        $description = post_string('description');
        $location = post_string('location');
        $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
        $itemDate = post_string('item_date');
        $dateParts = explode('-', $itemDate);
        $validDate = count($dateParts) === 3 &&
            checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]);

        if ($itemName === '' || mb_strlen($itemName) > 150 ||
            $description === '' || mb_strlen($description) > 20000 ||
            $location === '' || mb_strlen($location) > 150 ||
            $categoryId === false || $categoryId < 1 || !$validDate) {
            set_flash('error', 'Enter a valid name, category, description, location, and date.');
        } else {
            $categoryStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE category_id = ?');
            $categoryStmt->execute([$categoryId]);
            if ((int)$categoryStmt->fetchColumn() !== 1) {
                set_flash('error', 'Choose a valid category.');
            } else {
                $update = $pdo->prepare(
                    'UPDATE items
                     SET item_name = ?, category_id = ?, description = ?, location = ?, item_date = ?
                     WHERE item_id = ?'
                );
                $update->execute([$itemName, $categoryId, $description, $location, $itemDate, $itemId]);
                set_flash($update->rowCount() > 0 ? 'success' : 'info',
                    $update->rowCount() > 0 ? 'Report details updated.' : 'No report changes were made.');
            }
        }
    } else {
        $imageName = null;
        try {
            $pdo->beginTransaction();
            $lockItem = $pdo->prepare('SELECT image FROM items WHERE item_id = ? FOR UPDATE');
            $lockItem->execute([$itemId]);
            $imageName = $lockItem->fetchColumn();
            if ($imageName === false) {
                $pdo->rollBack();
                set_flash('error', 'Report not found.');
            } else {
                $delete = $pdo->prepare('DELETE FROM items WHERE item_id = ?');
                $delete->execute([$itemId]);
                $pdo->commit();
                if (is_string($imageName) && $imageName !== '') {
                    $imagePath = __DIR__ . '/../uploads/items/' . basename($imageName);
                    if (is_file($imagePath) && !unlink($imagePath)) {
                        error_log('CampusFind could not remove deleted item image: ' . $imagePath);
                    }
                }
                set_flash('success', 'Report deleted. Any claims connected to it were also removed.');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('CampusFind item deletion failed: ' . $e->getMessage());
            set_flash('error', 'The report could not be deleted.');
        }
    }

    header('Location: ' . BASE_URL . 'admin/manage_items.php?' . $filterQuery);
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId !== false && $editId > 0) {
        $editStmt = $pdo->prepare(
            'SELECT item_id, item_name, category_id, description, location, item_date
             FROM items WHERE item_id = ?'
        );
        $editStmt->execute([$editId]);
        $editItem = $editStmt->fetch();
    }
}
$categories = get_all_categories($pdo);

$conditions = [];
$params = [];
if ($status !== 'all') {
    $conditions[] = 'i.approval_status = ?';
    $params[] = $status;
}
if ($type !== '') {
    $conditions[] = 'i.item_type = ?';
    $params[] = $type;
}
if ($search !== '') {
    $conditions[] = '(i.item_name LIKE ? OR i.location LIKE ? OR u.name LIKE ?)';
    $params = array_merge($params, ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$stmt = $pdo->prepare(
    'SELECT i.item_id, i.item_name, i.description, i.item_type, i.location, i.item_date,
            i.image, i.approval_status, i.item_status, i.created_at, c.category_name,
            u.name AS reporter_name, u.email AS reporter_email, u.phone AS reporter_phone
     FROM items i
     JOIN categories c ON c.category_id = i.category_id
     JOIN users u ON u.user_id = i.user_id
     ' . $where . '
     ORDER BY i.created_at DESC'
);
$stmt->execute($params);
$items = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 fw-bold text-navy mb-1">Manage Item Reports</h1>
        <p class="text-muted mb-0">Review, correct, and remove submitted lost and found reports.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-outline-primary">View public items</a>
</div>

<?php if (isset($_GET['edit']) && !$editItem): ?>
    <div class="alert alert-warning">The requested report was not found.</div>
<?php endif; ?>

<?php if ($editItem): ?>
    <form method="POST" class="card card-custom p-3 p-md-4 mb-4">
        <h2 class="h5 fw-bold text-navy">Edit report details</h2>
        <p class="small text-muted">Type, approval, item status, and owner are managed separately and cannot be changed here.</p>
        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
        <input type="hidden" name="item_id" value="<?php echo (int)$editItem['item_id']; ?>">
        <input type="hidden" name="action" value="edit">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="edit-name">Item name</label>
                <input id="edit-name" name="item_name" class="form-control" maxlength="150" required value="<?php echo e($editItem['item_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="edit-category">Category</label>
                <select id="edit-category" name="category_id" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['category_id']; ?>" <?php echo (int)$editItem['category_id'] === (int)$category['category_id'] ? 'selected' : ''; ?>><?php echo e($category['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="edit-description">Description</label>
                <textarea id="edit-description" name="description" class="form-control" rows="4" maxlength="20000" required><?php echo e($editItem['description']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="edit-location">Location</label>
                <input id="edit-location" name="location" class="form-control" maxlength="150" required value="<?php echo e($editItem['location']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="edit-date">Date</label>
                <input id="edit-date" type="date" name="item_date" class="form-control" required value="<?php echo e($editItem['item_date']); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-navy">Save changes</button>
                <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>admin/manage_items.php?<?php echo e($filterQuery); ?>">Cancel</a>
            </div>
        </div>
    </form>
<?php endif; ?>

<form method="GET" class="card card-custom p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-3">
            <label for="filter-status" class="form-label">Approval status</label>
            <select id="filter-status" name="status" class="form-select">
                <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All statuses'] as $value => $label): ?>
                    <option value="<?php echo e($value); ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="filter-type" class="form-label">Report type</label>
            <select id="filter-type" name="type" class="form-select">
                <option value="">All types</option>
                <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Lost</option>
                <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Found</option>
            </select>
        </div>
        <div class="col-sm-8 col-lg-4">
            <label for="filter-search" class="form-label">Search</label>
            <input id="filter-search" name="q" class="form-control" value="<?php echo e($search); ?>" maxlength="100" placeholder="Item, location, or reporter">
        </div>
        <div class="col-sm-4 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-navy flex-grow-1">Filter</button>
            <a href="<?php echo BASE_URL; ?>admin/manage_items.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<?php if (!$items): ?>
    <div class="alert alert-info">No item reports match these filters.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($items as $item): ?>
            <?php
            $imageName = !empty($item['image']) ? basename($item['image']) : '';
            $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
            ?>
            <div class="col-12 col-xl-6">
                <article class="card card-custom h-100">
                    <div class="row g-0 h-100">
                        <div class="col-md-4">
                            <div class="item-img-container h-100">
                                <?php if ($imageName !== '' && is_file($imagePath)): ?>
                                    <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($item['item_name']); ?>">
                                <?php else: ?>
                                    <div class="item-img-placeholder" aria-hidden="true"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <?php echo get_type_badge($item['item_type']); ?>
                                    <?php echo get_approval_badge($item['approval_status']); ?>
                                    <?php echo get_status_badge($item['item_status']); ?>
                                </div>
                                <h2 class="h5 card-title fw-bold text-navy"><?php echo e($item['item_name']); ?></h2>
                                <p class="small text-muted mb-2"><?php echo e($item['category_name']); ?> · <?php echo e($item['location']); ?> · <?php echo e(format_date($item['item_date'])); ?></p>
                                <p class="mb-2"><?php echo nl2br(e($item['description'])); ?></p>
                                <p class="small text-muted mb-3">
                                    Reported by <?php echo e($item['reporter_name']); ?> (<?php echo e($item['reporter_email']); ?>, <?php echo e($item['reporter_phone']); ?>)
                                    on <?php echo e(format_date($item['created_at'])); ?>
                                </p>
                                <div class="d-flex flex-wrap gap-2 mt-auto">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>admin/manage_items.php?<?php echo e($filterQuery); ?>&edit=<?php echo (int)$item['item_id']; ?>">Edit</a>
                                    <?php if ($item['approval_status'] === 'pending'): ?>
                                        <?php foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $action => $label): ?>
                                            <form method="POST" onsubmit="return confirm('<?php echo $action === 'approve' ? 'Approve this report?' : 'Reject this report?'; ?>');">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                                <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo e($action); ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $action === 'approve' ? 'btn-success' : 'btn-outline-danger'; ?>"><?php echo e($label); ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Delete this report? Any claims connected to it will also be deleted. This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin_content_end.php';
require_once __DIR__ . '/../includes/footer.php';
?>
