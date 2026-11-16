<?php
$pageTitle = 'My Reports';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$userId = (int)$_SESSION['user_id'];
$errors = [];
$editItem = null;
$editId = filter_var($_POST['item_id'] ?? $_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$categories = get_all_categories($pdo);

function find_editable_report($pdo, $itemId, $userId) {
    $stmt = $pdo->prepare(
        "SELECT i.* FROM items i
         WHERE i.item_id = ? AND i.user_id = ?
           AND i.approval_status = 'pending' AND i.item_status = 'open'
           AND NOT EXISTS (SELECT 1 FROM claims c WHERE c.item_id = i.item_id)"
    );
    $stmt->execute([$itemId, $userId]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_string('action');
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Your form session expired. Please refresh and try again.';
    } elseif (!$editId || $editId < 1) {
        $errors[] = 'Select a valid report.';
    } else {
        $editItem = find_editable_report($pdo, $editId, $userId);
        if (!$editItem) {
            $errors[] = 'This report is no longer available for editing or deletion.';
        }
    }

    if (!$errors && $action === 'delete') {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM items
                 WHERE item_id = ? AND user_id = ?
                   AND approval_status = 'pending' AND item_status = 'open'
                   AND NOT EXISTS (SELECT 1 FROM claims c WHERE c.item_id = items.item_id)"
            );
            $stmt->execute([$editId, $userId]);
            if ($stmt->rowCount() !== 1) {
                $errors[] = 'This report is no longer available for deletion.';
            } else {
                if (!empty($editItem['image'])) {
                    $imagePath = __DIR__ . '/../uploads/items/' . basename($editItem['image']);
                    if (is_file($imagePath)) {
                        unlink($imagePath);
                    }
                }
                set_flash('success', 'Your report was deleted.');
                header('Location: ' . BASE_URL . 'user/my_reports.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('CampusFind report deletion failed: ' . $e->getMessage());
            $errors[] = 'Your report could not be deleted. Please try again.';
        }
    } elseif (!$errors && $action === 'update') {
        $itemName = post_string('item_name');
        $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
        $description = post_string('description');
        $location = post_string('location');
        $itemDate = post_string('item_date');

        if ($itemName === '' || mb_strlen($itemName) > 150) {
            $errors[] = 'Item name is required and must be 150 characters or fewer.';
        }
        if ($categoryId === false || $categoryId < 1) {
            $errors[] = 'Select a valid category.';
        } else {
            $categoryStmt = $pdo->prepare('SELECT 1 FROM categories WHERE category_id = ?');
            $categoryStmt->execute([$categoryId]);
            if (!$categoryStmt->fetchColumn()) {
                $errors[] = 'Select a valid category.';
            }
        }
        if ($description === '' || mb_strlen($description) > 5000) {
            $errors[] = 'Description is required and must be 5,000 characters or fewer.';
        }
        if ($location === '' || mb_strlen($location) > 150) {
            $errors[] = 'Location is required and must be 150 characters or fewer.';
        }
        $date = DateTime::createFromFormat('!Y-m-d', $itemDate);
        if (!$date || $date->format('Y-m-d') !== $itemDate || $itemDate > date('Y-m-d')) {
            $errors[] = 'Enter a valid item date that is not in the future.';
        }

        $newImage = null;
        if (!$errors) {
            $upload = upload_item_image('image');
            if (!$upload['success']) {
                $errors[] = $upload['error'];
            } else {
                $newImage = $upload['filename'];
            }
        }

        if (!$errors) {
            $imageName = $newImage ?: $editItem['image'];
            try {
                $stmt = $pdo->prepare(
                    "UPDATE items SET category_id = ?, item_name = ?, description = ?,
                        location = ?, item_date = ?, image = ?
                     WHERE item_id = ? AND user_id = ?
                       AND approval_status = 'pending' AND item_status = 'open'
                       AND NOT EXISTS (SELECT 1 FROM claims c WHERE c.item_id = items.item_id)"
                );
                $stmt->execute([
                    $categoryId,
                    $itemName,
                    $description,
                    $location,
                    $itemDate,
                    $imageName,
                    $editId,
                    $userId
                ]);
                if ($stmt->rowCount() !== 1 && !find_editable_report($pdo, $editId, $userId)) {
                    $errors[] = 'This report is no longer available for editing.';
                    if ($newImage !== null) {
                        @unlink(__DIR__ . '/../uploads/items/' . $newImage);
                    }
                } else {
                    if ($newImage !== null && !empty($editItem['image'])) {
                        $oldImagePath = __DIR__ . '/../uploads/items/' . basename($editItem['image']);
                        if (is_file($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    set_flash('success', 'Your report was updated and remains pending approval.');
                    header('Location: ' . BASE_URL . 'user/my_reports.php');
                    exit;
                }
            } catch (PDOException $e) {
                if ($newImage !== null) {
                    @unlink(__DIR__ . '/../uploads/items/' . $newImage);
                }
                error_log('CampusFind report update failed: ' . $e->getMessage());
                $errors[] = 'Your report could not be updated. Please try again.';
            }
        }
    } elseif (!$errors) {
        $errors[] = 'Invalid report action.';
    }
}

if (!$editItem && $editId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $editItem = find_editable_report($pdo, $editId, $userId);
    if (!$editItem) {
        set_flash('error', 'This report is no longer available for editing.');
        header('Location: ' . BASE_URL . 'user/my_reports.php');
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT i.*, c.category_name,
            (SELECT COUNT(*) FROM claims cl WHERE cl.item_id = i.item_id) AS claim_count
     FROM items i JOIN categories c ON c.category_id = i.category_id
     WHERE i.user_id = ? ORDER BY i.created_at DESC'
);
$stmt->execute([$userId]);
$reports = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>My Reports</h2>
        <p class="text-muted mb-0">Review your lost and found reports and their review status.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>user/report_lost.php" class="btn btn-warning fw-bold">Report Lost</a>
        <a href="<?php echo BASE_URL; ?>user/report_found.php" class="btn btn-primary-custom fw-bold">Report Found</a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($editItem): ?>
    <?php
    $formValues = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
        ? [
            'item_name' => post_string('item_name', $editItem['item_name']),
            'category_id' => $_POST['category_id'] ?? $editItem['category_id'],
            'description' => post_string('description', $editItem['description']),
            'location' => post_string('location', $editItem['location']),
            'item_date' => post_string('item_date', $editItem['item_date'])
        ]
        : $editItem;
    ?>
    <div class="card card-custom p-4 mb-4">
        <h4 class="fw-bold text-navy mb-3">Edit pending report</h4>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
            <input type="hidden" name="item_id" value="<?php echo (int)$editItem['item_id']; ?>">
            <input type="hidden" name="action" value="update">
            <div class="mb-3">
                <label for="item_name" class="form-label fw-semibold">Item name</label>
                <input id="item_name" name="item_name" class="form-control" maxlength="150" required value="<?php echo e($formValues['item_name']); ?>">
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label fw-semibold">Category</label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['category_id']; ?>" <?php echo (string)$formValues['category_id'] === (string)$category['category_id'] ? 'selected' : ''; ?>><?php echo e($category['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" maxlength="5000" required><?php echo e($formValues['description']); ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-7 mb-3">
                    <label for="location" class="form-label fw-semibold">Campus location</label>
                    <input id="location" name="location" class="form-control" maxlength="150" required value="<?php echo e($formValues['location']); ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label for="item_date" class="form-label fw-semibold">Date</label>
                    <input id="item_date" type="date" name="item_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required value="<?php echo e($formValues['item_date']); ?>">
                </div>
            </div>
            <?php if (!empty($editItem['image'])): ?>
                <p class="small text-muted">Upload a new image to replace the current photograph.</p>
                <?php
                $currentImage = basename($editItem['image']);
                $currentImagePath = __DIR__ . '/../uploads/items/' . $currentImage;
                ?>
                <?php if (is_file($currentImagePath)): ?>
                    <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($currentImage)); ?>" alt="Current item photograph" class="img-thumbnail mb-3" style="max-height: 180px;">
                <?php endif; ?>
            <?php endif; ?>
            <div class="mb-3">
                <label for="imageInput" class="form-label fw-semibold">Replace photograph <span class="text-muted fw-normal">(optional, up to 5 MB)</span></label>
                <input id="imageInput" type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                <img id="imagePreview" class="img-thumbnail mt-3 d-none" alt="Selected image preview" style="max-height: 220px;">
            </div>
            <button type="submit" class="btn btn-navy fw-bold">Save changes</button>
            <a href="<?php echo BASE_URL; ?>user/my_reports.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
<?php endif; ?>

<div class="card card-custom p-3 p-md-4">
    <?php if (!$reports): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
            <p class="mb-3">You have not submitted any reports yet.</p>
            <a href="<?php echo BASE_URL; ?>user/report_lost.php" class="btn btn-navy">Create your first report</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th><th>Type</th><th>Category</th><th>Date</th><th>Approval</th><th>Item status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <?php $canManage = $report['approval_status'] === 'pending' && $report['item_status'] === 'open' && (int)$report['claim_count'] === 0; ?>
                        <tr>
                            <td>
                                <?php
                                $reportImage = !empty($report['image']) ? basename($report['image']) : '';
                                $reportImagePath = __DIR__ . '/../uploads/items/' . $reportImage;
                                ?>
                                <?php if ($reportImage !== '' && is_file($reportImagePath)): ?>
                                    <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($reportImage)); ?>" alt="" class="rounded me-2" style="width: 48px; height: 48px; object-fit: cover;">
                                <?php endif; ?>
                                <span class="fw-semibold"><?php echo e($report['item_name']); ?></span>
                                <div class="small text-muted"><?php echo e($report['location']); ?></div>
                            </td>
                            <td><?php echo get_type_badge($report['item_type']); ?></td>
                            <td><?php echo e($report['category_name']); ?></td>
                            <td><?php echo format_date($report['item_date']); ?></td>
                            <td><?php echo get_approval_badge($report['approval_status']); ?></td>
                            <td><?php echo get_status_badge($report['item_status']); ?></td>
                            <td>
                                <?php if ($canManage): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>user/my_reports.php?edit=<?php echo (int)$report['item_id']; ?>">Edit</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this pending report?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo (int)$report['item_id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted">Locked during review or after approval</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
