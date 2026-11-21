<?php
$pageTitle = 'Item Details';
require_once __DIR__ . '/../includes/functions.php';

$rawId = $_GET['id'] ?? null;
$itemId = is_string($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if ($itemId === false || $itemId < 1) {
    http_response_code(404);
    $item = null;
} else {
    $stmt = $pdo->prepare(
        "SELECT i.item_id, i.user_id, i.item_name, i.description, i.item_type,
                i.location, i.item_date, i.image, i.item_status, i.approval_status,
                i.created_at, c.category_name
         FROM items i
         JOIN categories c ON c.category_id = i.category_id
         WHERE i.item_id = ? AND i.approval_status = 'approved'
         LIMIT 1"
    );
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
    }
}

$canSubmitClaim = $item
    && $item['item_type'] === 'found'
    && $item['approval_status'] === 'approved'
    && $item['item_status'] === 'open'
    && is_logged_in()
    && !is_admin()
    && (int)$_SESSION['user_id'] !== (int)$item['user_id'];
$existingClaimStatus = null;
if ($canSubmitClaim) {
    $claimStmt = $pdo->prepare('SELECT claim_status FROM claims WHERE item_id = ? AND user_id = ? LIMIT 1');
    $claimStmt->execute([$item['item_id'], $_SESSION['user_id']]);
    $existingClaimStatus = $claimStmt->fetchColumn();
}
$claimEligible = $canSubmitClaim && $existingClaimStatus === false;

$canReply = $item && $item['item_type'] === 'lost' && $item['item_status'] === 'open'
    && is_logged_in() && !is_admin() && (int)$_SESSION['user_id'] !== (int)$item['user_id'];
$replyError = '';
$message = post_string('message');
if ($canReply && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(post_string('csrf_token'))) {
        $replyError = 'Your form session expired. Refresh the page and try again.';
    } elseif (mb_strlen($message) > 5000) {
        $replyError = 'Keep your message within 5,000 characters.';
    } else {
        $upload = upload_item_image('photo');
        if (!$upload['success']) {
            $replyError = $upload['error'];
        } elseif ($message === '' && !$upload['filename']) {
            $replyError = 'Add a message or choose a photo before sending.';
        } else {
            $photo = $upload['filename'];
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO finder_replies (item_id, sender_id, message, photo)
                     SELECT item_id, ?, ?, ? FROM items
                     WHERE item_id = ? AND item_type = 'lost' AND item_status = 'open'
                       AND approval_status = 'approved' AND user_id <> ?"
                );
                $stmt->execute([$_SESSION['user_id'], $message, $photo, $itemId, $_SESSION['user_id']]);
                if ($stmt->rowCount() === 1) {
                    set_flash('success', 'Your reply was sent to the person who reported this item.');
                    header('Location: ' . BASE_URL . 'user/item_details.php?id=' . $itemId);
                    exit;
                }
                $replyError = 'This report is no longer accepting replies.';
            } catch (PDOException $e) {
                error_log('CampusFind finder reply failed: ' . $e->getMessage());
                $replyError = 'Your reply could not be sent. Please try again.';
            }
            if ($photo !== null) {
                unlink(__DIR__ . '/../uploads/items/' . $photo);
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$item): ?>
    <div class="alert alert-warning text-center py-5">
        <i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>
        <h3 class="fw-bold">Item not found</h3>
        <p class="mb-3">This item does not exist or is not available for public viewing.</p>
        <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-navy">Browse approved items</a>
    </div>
<?php else: ?>
    <?php
    $imageName = !empty($item['image']) ? basename($item['image']) : '';
    $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
    ?>
    <div class="mb-3">
        <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to items</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card card-custom">
                <div class="item-img-container" style="height: min(60vh, 480px);">
                    <?php if ($imageName !== '' && is_file($imagePath)): ?>
                        <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($item['item_name']); ?>">
                    <?php else: ?>
                        <div class="item-img-placeholder" aria-hidden="true"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <section class="card card-custom p-4 p-md-5 h-100">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php echo get_type_badge($item['item_type']); ?>
                    <?php echo get_status_badge($item['item_status']); ?>
                    <span class="badge bg-secondary"><?php echo e($item['category_name']); ?></span>
                </div>
                <h1 class="h2 fw-bold text-navy mb-3"><?php echo e($item['item_name']); ?></h1>
                <p class="text-muted mb-4"><?php echo nl2br(e($item['description'])); ?></p>
                <dl class="row">
                    <dt class="col-sm-4 text-muted"><i class="bi bi-geo-alt me-1"></i>Location</dt>
                    <dd class="col-sm-8"><?php echo e($item['location']); ?></dd>
                    <dt class="col-sm-4 text-muted"><i class="bi bi-calendar-event me-1"></i>Date <?php echo $item['item_type'] === 'lost' ? 'lost' : 'found'; ?></dt>
                    <dd class="col-sm-8"><?php echo e(format_date($item['item_date'])); ?></dd>
                    <dt class="col-sm-4 text-muted"><i class="bi bi-clock me-1"></i>Reported</dt>
                    <dd class="col-sm-8"><?php echo e(format_date($item['created_at'])); ?></dd>
                </dl>

                <?php if ($canReply): ?>
                    <form method="post" enctype="multipart/form-data">
                        <h2 class="h5 fw-bold">Found this item?</h2>
                        <p>The owner and campus admins can see your reply. Contact an admin to arrange drop-off.</p>
                        <?php foreach (admin_contacts($pdo) as $admin): ?>
                            <p><a href="mailto:<?php echo e($admin['email']); ?>?subject=Lost%20item%20report%20%23<?php echo (int)$itemId; ?>">Email <?php echo e($admin['name']); ?> about report #<?php echo (int)$itemId; ?></a></p>
                        <?php endforeach; ?>
                        <?php if ($replyError !== ''): ?>
                            <div class="alert alert-danger" role="alert"><?php echo e($replyError); ?></div>
                        <?php endif; ?>
                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" name="message" class="form-control mb-3" maxlength="5000" rows="3"><?php echo e($message); ?></textarea>
                        <label for="photo" class="form-label">Photo (up to 5 MB)</label>
                        <input id="photo" type="file" name="photo" class="form-control mb-3" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <button class="btn btn-primary-custom fw-bold">Send to owner</button>
                    </form>
                <?php elseif ($item['item_type'] === 'lost' && $item['item_status'] === 'open' && !is_logged_in()): ?>
                    <p><a href="<?php echo BASE_URL; ?>login.php">Log in</a> to let the owner know you found this item.</p>
                <?php elseif ($claimEligible): ?>
                    <a href="<?php echo BASE_URL; ?>user/claim_item.php?id=<?php echo (int)$item['item_id']; ?>" class="btn btn-primary-custom fw-bold mt-auto">
                        <i class="bi bi-hand-index-thumb me-1"></i>Claim item
                    </a>
                <?php elseif ($item['item_type'] === 'found' && $item['item_status'] === 'open' && is_logged_in() && (int)$_SESSION['user_id'] === (int)$item['user_id']): ?>
                    <div class="alert alert-info mb-0">You cannot claim your own found-item report.</div>
                <?php elseif ($existingClaimStatus !== null && $existingClaimStatus !== false): ?>
                    <div class="alert alert-info mb-0">You already submitted a <?php echo e($existingClaimStatus); ?> claim for this item.</div>
                <?php elseif ($item['item_type'] === 'found' && $item['item_status'] === 'open' && is_admin()): ?>
                    <div class="alert alert-secondary mb-0">Claims are available to student accounts.</div>
                <?php elseif ($item['item_type'] === 'found'): ?>
                    <div class="alert alert-secondary mb-0">
                        <?php
                        if ($item['item_status'] !== 'open') {
                            echo 'This item is no longer available to claim.';
                        } elseif (!is_logged_in()) {
                            echo 'Log in to claim this item.';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
