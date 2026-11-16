<?php
$pageTitle = 'Browse Items';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$keyword = isset($_GET['keyword']) && is_string($_GET['keyword']) ? trim($_GET['keyword']) : '';
$location = isset($_GET['location']) && is_string($_GET['location']) ? trim($_GET['location']) : '';
$type = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';
$rawCategoryId = $_GET['category_id'] ?? '';
$categoryId = '';

if (mb_strlen($keyword) > 100) {
    $keyword = mb_substr($keyword, 0, 100);
    $errors[] = 'Search text is limited to 100 characters.';
}
if (mb_strlen($location) > 150) {
    $location = mb_substr($location, 0, 150);
    $errors[] = 'Location filter is limited to 150 characters.';
}
if ($type !== '' && !in_array($type, ['lost', 'found'], true)) {
    $errors[] = 'Choose a valid item type.';
    $type = '';
}
if ($rawCategoryId !== '') {
    $validatedCategoryId = is_string($rawCategoryId) ? filter_var($rawCategoryId, FILTER_VALIDATE_INT) : false;
    if ($validatedCategoryId === false || $validatedCategoryId < 1) {
        $errors[] = 'Choose a valid category.';
    } else {
        $categoryId = (int)$validatedCategoryId;
    }
}

$categories = get_all_categories($pdo);
if ($categoryId !== '') {
    $categoryExists = false;
    foreach ($categories as $category) {
        if ((int)$category['category_id'] === $categoryId) {
            $categoryExists = true;
            break;
        }
    }
    if (!$categoryExists) {
        $errors[] = 'Choose a valid category.';
        $categoryId = '';
    }
}

$conditions = ["i.approval_status = 'approved'"];
$parameters = [];
if ($keyword !== '') {
    $conditions[] = 'i.item_name LIKE :keyword';
    $parameters['keyword'] = '%' . $keyword . '%';
}
if ($type !== '') {
    $conditions[] = 'i.item_type = :type';
    $parameters['type'] = $type;
}
if ($categoryId !== '') {
    $conditions[] = 'i.category_id = :category_id';
    $parameters['category_id'] = $categoryId;
}
if ($location !== '') {
    $conditions[] = 'i.location LIKE :location';
    $parameters['location'] = '%' . $location . '%';
}

$stmt = $pdo->prepare(
    'SELECT i.item_id, i.user_id, i.item_name, i.description, i.item_type, i.location,
            i.item_date, i.image, i.item_status, c.category_name
     FROM items i
     JOIN categories c ON c.category_id = i.category_id
     WHERE ' . implode(' AND ', $conditions) . '
     ORDER BY i.created_at DESC'
);
$stmt->execute($parameters);
$items = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1"><i class="bi bi-grid text-primary me-2"></i>Browse Items</h2>
        <p class="text-muted mb-0">Approved lost and found reports from your campus.</p>
    </div>
    <span class="badge bg-primary fs-6"><?php echo count($items); ?> item<?php echo count($items) === 1 ? '' : 's'; ?></span>
</div>

<?php if ($errors): ?>
    <div class="alert alert-warning" role="alert">
        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="GET" action="<?php echo BASE_URL; ?>user/browse_items.php" class="card card-custom p-3 p-md-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label for="keyword" class="form-label fw-semibold">Item name</label>
            <input id="keyword" name="keyword" class="form-control" maxlength="100" value="<?php echo e($keyword); ?>" placeholder="Search item names">
        </div>
        <div class="col-lg-3 col-md-6">
            <label for="location" class="form-label fw-semibold">Location</label>
            <input id="location" name="location" class="form-control" maxlength="150" value="<?php echo e($location); ?>" placeholder="Search campus locations">
        </div>
        <div class="col-lg-2 col-md-4">
            <label for="type" class="form-label fw-semibold">Type</label>
            <select id="type" name="type" class="form-select">
                <option value="">Lost &amp; Found</option>
                <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Lost</option>
                <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Found</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label for="category_id" class="form-label fw-semibold">Category</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int)$category['category_id']; ?>" <?php echo (string)$categoryId === (string)$category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo e($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-navy flex-grow-1"><i class="bi bi-search me-1"></i>Search</button>
            <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-outline-secondary" aria-label="Clear all filters" title="Clear filters"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<?php if (!$items): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-search fs-2 d-block mb-2"></i>
        <h5 class="fw-bold">No matching approved items</h5>
        <p class="mb-3">Try changing or clearing your search filters.</p>
        <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-outline-primary">Clear filters</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($items as $item): ?>
            <?php
            $imageName = !empty($item['image']) ? basename($item['image']) : '';
            $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
            ?>
            <div class="col-xl-4 col-md-6">
                <article class="card card-custom h-100">
                    <div class="item-img-container">
                        <?php if ($imageName !== '' && is_file($imagePath)): ?>
                            <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="item-img-placeholder" aria-hidden="true"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                        <div class="position-absolute top-0 start-0 m-2"><?php echo get_type_badge($item['item_type']); ?></div>
                        <div class="position-absolute top-0 end-0 m-2"><?php echo get_status_badge($item['item_status']); ?></div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary align-self-start mb-2"><?php echo e($item['category_name']); ?></span>
                        <h5 class="card-title fw-bold text-navy"><?php echo e($item['item_name']); ?></h5>
                        <p class="text-muted small flex-grow-1"><?php echo e(mb_strimwidth($item['description'], 0, 130, '...')); ?></p>
                        <ul class="list-unstyled small text-muted mb-3">
                            <li><i class="bi bi-geo-alt text-danger me-1"></i><?php echo e($item['location']); ?></li>
                            <li><i class="bi bi-calendar-event text-primary me-1"></i><?php echo e(format_date($item['item_date'])); ?></li>
                        </ul>
                        <a class="btn btn-outline-primary w-100 mt-auto" href="<?php echo BASE_URL; ?>user/item_details.php?id=<?php echo (int)$item['item_id']; ?>">
                            <i class="bi bi-eye me-1"></i>View details
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
