<?php
$pageTitle = 'My Claims';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_admin()) {
    header('Location: ' . BASE_URL . 'admin/manage_claims.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT cl.claim_id, cl.claim_message, cl.claim_status, cl.created_at,
            i.item_id, i.item_name, i.image, i.item_type, i.item_status, i.location
     FROM claims cl
     JOIN items i ON i.item_id = cl.item_id
     WHERE cl.user_id = ?
     ORDER BY cl.created_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$claims = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold text-navy mb-1"><i class="bi bi-hand-index-thumb text-primary me-2"></i>My Claims</h2>
    <p class="text-muted mb-0">Track the status of ownership claims you submitted.</p>
</div>

<?php if (!$claims): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        <h5 class="fw-bold">No claims yet</h5>
        <p class="mb-3">Find an approved found item and submit an ownership claim.</p>
        <a href="<?php echo BASE_URL; ?>user/browse_items.php?type=found" class="btn btn-navy">Browse found items</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($claims as $claim): ?>
            <?php
            $imageName = !empty($claim['image']) ? basename($claim['image']) : '';
            $imagePath = __DIR__ . '/../uploads/items/' . $imageName;
            ?>
            <div class="col-xl-6">
                <article class="card card-custom h-100">
                    <div class="row g-0 h-100">
                        <div class="col-sm-4">
                            <div class="item-img-container h-100">
                                <?php if ($imageName !== '' && is_file($imagePath)): ?>
                                    <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($claim['item_name']); ?>">
                                <?php else: ?>
                                    <div class="item-img-placeholder" aria-hidden="true"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="card-body d-flex flex-column h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h5 class="fw-bold text-navy mb-0"><?php echo e($claim['item_name']); ?></h5>
                                    <?php echo get_approval_badge($claim['claim_status']); ?>
                                </div>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>Submitted <?php echo e(format_date($claim['created_at'])); ?>
                                    <br><i class="bi bi-geo-alt me-1"></i><?php echo e($claim['location']); ?>
                                </p>
                                <p class="small mb-3"><?php echo e($claim['claim_message']); ?></p>
                                <a href="<?php echo BASE_URL; ?>user/item_details.php?id=<?php echo (int)$claim['item_id']; ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">View item</a>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
