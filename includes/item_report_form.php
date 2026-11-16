<?php
require_once __DIR__ . '/functions.php';
require_login();

if (!isset($reportType) || !in_array($reportType, ['lost', 'found'], true)) {
    http_response_code(500);
    exit('Invalid report type.');
}

$pageTitle = 'Report ' . ucfirst($reportType) . ' Item';
$errors = [];
$itemName = '';
$categoryId = '';
$description = '';
$location = '';
$itemDate = date('Y-m-d');
$categories = get_all_categories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName = post_string('item_name');
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $description = post_string('description');
    $location = post_string('location');
    $itemDate = post_string('item_date');

    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Your form session expired. Please refresh and try again.';
    }
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

    $imageName = null;
    if (!$errors) {
        $upload = upload_item_image('image');
        if (!$upload['success']) {
            $errors[] = $upload['error'];
        } else {
            $imageName = $upload['filename'];
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO items
                    (user_id, category_id, item_name, description, item_type, location, item_date, image, approval_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
            );
            $stmt->execute([
                $_SESSION['user_id'],
                $categoryId,
                $itemName,
                $description,
                $reportType,
                $location,
                $itemDate,
                $imageName
            ]);

            set_flash('success', ucfirst($reportType) . ' item report submitted for administrator approval.');
            header('Location: ' . BASE_URL . 'user/my_reports.php');
            exit;
        } catch (PDOException $e) {
            if ($imageName !== null) {
                @unlink(__DIR__ . '/../uploads/items/' . $imageName);
            }
            error_log('CampusFind item report insert failed: ' . $e->getMessage());
            $errors[] = 'Your report could not be saved. Please try again.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-custom p-4 p-md-5">
            <div class="mb-4">
                <h2 class="fw-bold text-navy mb-1">
                    <i class="bi bi-<?php echo $reportType === 'lost' ? 'search' : 'box-seam'; ?> text-primary me-2"></i>
                    Report a <?php echo ucfirst($reportType); ?> Item
                </h2>
                <p class="text-muted mb-0">Reports are reviewed by an administrator before appearing publicly.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                <div class="mb-3">
                    <label for="item_name" class="form-label fw-semibold">Item name</label>
                    <input id="item_name" name="item_name" class="form-control" maxlength="150" required value="<?php echo e($itemName); ?>">
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label fw-semibold">Category</label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['category_id']; ?>" <?php echo (string)$categoryId === (string)$category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo e($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" maxlength="5000" required><?php echo e($description); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label for="location" class="form-label fw-semibold">Campus location</label>
                        <input id="location" name="location" class="form-control" maxlength="150" required value="<?php echo e($location); ?>" placeholder="Building, room, or area">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="item_date" class="form-label fw-semibold">Date <?php echo $reportType === 'lost' ? 'lost' : 'found'; ?></label>
                        <input id="item_date" type="date" name="item_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required value="<?php echo e($itemDate); ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="imageInput" class="form-label fw-semibold">Photograph <span class="text-muted fw-normal">(optional, JPG/PNG/GIF/WEBP up to 5 MB)</span></label>
                    <input id="imageInput" type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                    <img id="imagePreview" class="img-thumbnail mt-3 d-none" alt="Selected image preview" style="max-height: 220px;">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-navy fw-bold">
                        <i class="bi bi-send me-1"></i> Submit report
                    </button>
                    <a href="<?php echo BASE_URL; ?>user/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
