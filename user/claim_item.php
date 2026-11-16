<?php
$pageTitle = 'Claim Found Item';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_admin()) {
    header('Location: ' . BASE_URL . 'admin/manage_claims.php');
    exit;
}

$rawId = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['item_id'] ?? null) : ($_GET['id'] ?? null);
$itemId = is_string($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
$item = null;
$existingClaim = null;
$errors = [];
$claimMessage = post_string('claim_message');

if ($itemId === false || $itemId < 1) {
    http_response_code(404);
} else {
    $itemStmt = $pdo->prepare(
        "SELECT i.item_id, i.user_id, i.item_name, i.description, i.location, i.item_date,
                i.image, i.item_status, i.approval_status, c.category_name
         FROM items i
         JOIN categories c ON c.category_id = i.category_id
         WHERE i.item_id = ? AND i.item_type = 'found' AND i.approval_status = 'approved'
         LIMIT 1"
    );
    $itemStmt->execute([$itemId]);
    $item = $itemStmt->fetch();
    if (!$item) {
        http_response_code(404);
    }
}

if ($item && (int)$item['user_id'] === (int)$_SESSION['user_id']) {
    $errors[] = 'You cannot submit a claim for your own report.';
}

if ($item && !$errors && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $duplicateStmt = $pdo->prepare('SELECT claim_status FROM claims WHERE item_id = ? AND user_id = ? LIMIT 1');
    $duplicateStmt->execute([$itemId, $_SESSION['user_id']]);
    $existingClaim = $duplicateStmt->fetchColumn();
}

if ($item && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Your form session expired. Refresh and try again.';
    }
    if (mb_strlen($claimMessage) < 10 || mb_strlen($claimMessage) > 2000) {
        $errors[] = 'Explain your ownership in 10 to 2,000 characters.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockedItemStmt = $pdo->prepare(
                'SELECT user_id, item_type, approval_status, item_status
                 FROM items WHERE item_id = ? FOR UPDATE'
            );
            $lockedItemStmt->execute([$itemId]);
            $lockedItem = $lockedItemStmt->fetch();

            if (!$lockedItem ||
                $lockedItem['item_type'] !== 'found' ||
                $lockedItem['approval_status'] !== 'approved' ||
                $lockedItem['item_status'] !== 'open') {
                $pdo->rollBack();
                $errors[] = 'This item is no longer available to claim.';
            } elseif ((int)$lockedItem['user_id'] === (int)$_SESSION['user_id']) {
                $pdo->rollBack();
                $errors[] = 'You cannot submit a claim for your own report.';
            } else {
                $duplicateStmt = $pdo->prepare('SELECT claim_id FROM claims WHERE item_id = ? AND user_id = ? LIMIT 1');
                $duplicateStmt->execute([$itemId, $_SESSION['user_id']]);
                if ($duplicateStmt->fetchColumn()) {
                    $pdo->rollBack();
                    $existingClaim = true;
                    $errors[] = 'You have already submitted a claim for this item.';
                } else {
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO claims (item_id, user_id, claim_message, claim_status)
                         VALUES (?, ?, ?, 'pending')"
                    );
                    $insertStmt->execute([$itemId, $_SESSION['user_id'], $claimMessage]);
                    $pdo->commit();

                    set_flash('success', 'Your claim was submitted for administrator review.');
                    header('Location: ' . BASE_URL . 'user/my_claims.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('CampusFind claim submission failed: ' . $e->getMessage());
            $errors[] = 'Your claim could not be submitted. Please try again.';
        }
    }
}

if ($item && !$existingClaim) {
    $duplicateStmt = $pdo->prepare('SELECT claim_status FROM claims WHERE item_id = ? AND user_id = ? LIMIT 1');
    $duplicateStmt->execute([$itemId, $_SESSION['user_id']]);
    $existingClaim = $duplicateStmt->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$item): ?>
    <div class="alert alert-warning text-center py-5">
        <h2 class="fw-bold">Found item unavailable</h2>
        <p class="mb-3">Only approved found-item reports can be claimed.</p>
        <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-navy">Browse items</a>
    </div>
<?php else: ?>
    <?php
    $imageName = !empty($item['image']) ? basename($item['image']) : '';
    $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-custom p-4 p-md-5">
                <h2 class="fw-bold text-navy mb-4"><i class="bi bi-hand-index-thumb text-primary me-2"></i>Claim Found Item</h2>
                <div class="row g-4 mb-4">
                    <?php if ($imageName !== '' && is_file($imagePath)): ?>
                        <div class="col-md-4">
                            <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($item['item_name']); ?>" class="img-fluid rounded">
                        </div>
                    <?php endif; ?>
                    <div class="col">
                        <h4 class="fw-bold"><?php echo e($item['item_name']); ?></h4>
                        <p class="text-muted mb-1"><?php echo e($item['category_name']); ?> · <?php echo e($item['location']); ?> · <?php echo e(format_date($item['item_date'])); ?></p>
                        <p class="mb-0"><?php echo e($item['description']); ?></p>
                    </div>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <?php if ((int)$item['user_id'] === (int)$_SESSION['user_id']): ?>
                    <div class="alert alert-warning mb-0">You cannot claim your own item.</div>
                <?php elseif ($item['item_status'] !== 'open'): ?>
                    <div class="alert alert-secondary mb-0">This item has already been claimed or returned and is no longer available.</div>
                <?php elseif ($existingClaim): ?>
                    <div class="alert alert-info mb-0">
                        You already submitted a claim for this item (<?php echo e(ucfirst($existingClaim === true ? 'pending' : $existingClaim)); ?>).
                        <a href="<?php echo BASE_URL; ?>user/my_claims.php" class="alert-link">View your claims</a>.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                        <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">
                        <div class="mb-3">
                            <label for="claim_message" class="form-label fw-semibold">Why do you believe this item belongs to you?</label>
                            <textarea id="claim_message" name="claim_message" class="form-control" rows="5" minlength="10" maxlength="2000" required><?php echo e($claimMessage); ?></textarea>
                            <div class="form-text">Describe identifying details that help the administrator verify ownership.</div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom fw-bold">Submit claim</button>
                        <a href="<?php echo BASE_URL; ?>user/item_details.php?id=<?php echo (int)$item['item_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
