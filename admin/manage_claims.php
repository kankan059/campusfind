<?php
$pageTitle = 'Manage Claims';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$allowedStatuses = ['pending', 'approved', 'rejected', 'all'];
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'pending';
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'pending';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claimId = filter_var($_POST['claim_id'] ?? '', FILTER_VALIDATE_INT);
    $action = post_string('action');
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verify_csrf_token($token)) {
        set_flash('error', 'Your form session expired. Refresh and try again.');
    } elseif ($claimId === false || $claimId < 1 || !in_array($action, ['approve', 'reject'], true)) {
        set_flash('error', 'Invalid claim action.');
    } else {
        try {
            $pdo->beginTransaction();

            $referenceStmt = $pdo->prepare('SELECT item_id FROM claims WHERE claim_id = ?');
            $referenceStmt->execute([$claimId]);
            $itemId = $referenceStmt->fetchColumn();

            if (!$itemId) {
                $pdo->rollBack();
                set_flash('error', 'Claim not found.');
            } else {
                $itemStmt = $pdo->prepare(
                    'SELECT user_id, item_type, approval_status, item_status
                     FROM items WHERE item_id = ? FOR UPDATE'
                );
                $itemStmt->execute([$itemId]);
                $item = $itemStmt->fetch();

                $claimStmt = $pdo->prepare(
                    'SELECT user_id, claim_status FROM claims WHERE claim_id = ? FOR UPDATE'
                );
                $claimStmt->execute([$claimId]);
                $claim = $claimStmt->fetch();

                if (!$item || !$claim || $claim['claim_status'] !== 'pending') {
                    $pdo->rollBack();
                    set_flash('warning', 'This claim is no longer pending review.');
                } elseif ($action === 'approve' &&
                    ($item['item_type'] !== 'found' ||
                     $item['approval_status'] !== 'approved' ||
                     $item['item_status'] !== 'open' ||
                     (int)$item['user_id'] === (int)$claim['user_id'])) {
                    $pdo->rollBack();
                    set_flash('warning', 'This item is not eligible for claim approval.');
                } elseif ($action === 'reject') {
                    $updateClaim = $pdo->prepare(
                        "UPDATE claims SET claim_status = 'rejected'
                         WHERE claim_id = ? AND claim_status = 'pending'"
                    );
                    $updateClaim->execute([$claimId]);
                    if ($updateClaim->rowCount() !== 1) {
                        $pdo->rollBack();
                        set_flash('warning', 'This claim was already processed.');
                    } else {
                        $pdo->commit();
                        set_flash('success', 'Claim rejected.');
                    }
                } else {
                    $updateClaim = $pdo->prepare(
                        "UPDATE claims SET claim_status = 'approved'
                         WHERE claim_id = ? AND claim_status = 'pending'"
                    );
                    $updateClaim->execute([$claimId]);

                    $updateItem = $pdo->prepare(
                        "UPDATE items SET item_status = 'returned'
                         WHERE item_id = ? AND item_type = 'found'
                           AND approval_status = 'approved' AND item_status = 'open'"
                    );
                    $updateItem->execute([$itemId]);

                    if ($updateClaim->rowCount() !== 1 || $updateItem->rowCount() !== 1) {
                        $pdo->rollBack();
                        set_flash('warning', 'The claim or item changed while it was being reviewed. Refresh and try again.');
                    } else {
                        $rejectOthers = $pdo->prepare(
                            "UPDATE claims SET claim_status = 'rejected'
                             WHERE item_id = ? AND claim_id <> ? AND claim_status = 'pending'"
                        );
                        $rejectOthers->execute([$itemId, $claimId]);
                        $pdo->commit();
                        set_flash('success', 'Claim approved. The item was marked returned and other pending claims were rejected.');
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('CampusFind claim decision failed: ' . $e->getMessage());
            set_flash('error', 'The claim decision could not be saved. Please try again.');
        }
    }

    header('Location: ' . BASE_URL . 'admin/manage_claims.php?status=' . rawurlencode($status));
    exit;
}

$where = '';
if ($status !== 'all') {
    $where = 'WHERE cl.claim_status = ?';
}
$stmt = $pdo->prepare(
    'SELECT cl.claim_id, cl.claim_message, cl.claim_status, cl.created_at,
            i.item_id, i.item_name, i.image, i.item_status, i.item_type,
            u.name AS claimant_name, u.email AS claimant_email, u.phone AS claimant_phone
     FROM claims cl
     JOIN items i ON i.item_id = cl.item_id
     JOIN users u ON u.user_id = cl.user_id
     ' . $where . '
     ORDER BY cl.created_at DESC'
);
$stmt->execute($status === 'all' ? [] : [$status]);
$claims = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1"><i class="bi bi-hand-index-thumb text-primary me-2"></i>Manage Claims</h2>
        <p class="text-muted mb-0">Review ownership evidence and record claim decisions.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>admin/manage_items.php" class="btn btn-outline-primary">Review item reports</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All claims'] as $value => $label): ?>
        <a href="<?php echo BASE_URL; ?>admin/manage_claims.php?status=<?php echo e($value); ?>"
           class="btn <?php echo $status === $value ? 'btn-navy' : 'btn-outline-secondary'; ?>"><?php echo e($label); ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$claims): ?>
    <div class="alert alert-info">No <?php echo $status === 'all' ? '' : e($status); ?> claims found.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($claims as $claim): ?>
            <?php
            $imageName = !empty($claim['image']) ? basename($claim['image']) : '';
            $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
            ?>
            <div class="col-xl-6">
                <article class="card card-custom h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h5 class="fw-bold text-navy mb-1"><?php echo e($claim['item_name']); ?></h5>
                                <div class="d-flex gap-2"><?php echo get_type_badge($claim['item_type']); ?><?php echo get_approval_badge($claim['claim_status']); ?></div>
                            </div>
                            <?php if ($imageName !== '' && is_file($imagePath)): ?>
                                <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($claim['item_name']); ?>" class="rounded" style="width: 84px; height: 72px; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <p class="small text-muted mb-2">Item status: <?php echo e(ucfirst($claim['item_status'])); ?> · Claim submitted <?php echo e(format_date($claim['created_at'])); ?></p>
                        <div class="bg-light rounded p-3 mb-3">
                            <h6 class="fw-semibold">Claimant</h6>
                            <div><?php echo e($claim['claimant_name']); ?></div>
                            <div class="small"><?php echo e($claim['claimant_email']); ?> · <?php echo e($claim['claimant_phone']); ?></div>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold">Ownership explanation</h6>
                            <p class="mb-0"><?php echo nl2br(e($claim['claim_message'])); ?></p>
                        </div>
                        <?php if ($claim['claim_status'] === 'pending'): ?>
                            <div class="d-flex gap-2 mt-auto">
                                <?php foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $action => $label): ?>
                                    <form method="POST" onsubmit="return confirm('<?php echo $action === 'approve' ? 'Approve this claim? The item will be marked returned and other pending claims will be rejected.' : 'Reject this claim?'; ?>');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                        <input type="hidden" name="claim_id" value="<?php echo (int)$claim['claim_id']; ?>">
                                        <input type="hidden" name="action" value="<?php echo e($action); ?>">
                                        <button type="submit" class="btn <?php echo $action === 'approve' ? 'btn-success' : 'btn-outline-danger'; ?>"><?php echo e($label); ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
