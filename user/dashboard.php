<?php
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

$totalLost = 0;
$totalFound = 0;
$totalClaims = 0;
$recentReports = [];
$recentClaims = [];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND item_type = 'lost'");
    $stmt->execute([$user_id]);
    $totalLost = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND item_type = 'found'");
    $stmt->execute([$user_id]);
    $totalFound = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totalClaims = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name 
        FROM items i 
        JOIN categories c ON i.category_id = c.category_id 
        WHERE i.user_id = ? 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recentReports = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT cl.*, i.item_name, i.item_type, i.location 
        FROM claims cl 
        JOIN items i ON cl.item_id = i.item_id 
        WHERE cl.user_id = ? 
        ORDER BY cl.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recentClaims = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('CampusFind dashboard query failed: ' . $e->getMessage());
    set_flash('error', 'Your dashboard could not be loaded. Please try again.');
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-navy mb-1">
            <i class="bi bi-speedometer2 text-primary me-2"></i> Welcome, <?php echo e($_SESSION['name']); ?>!
        </h2>
        <p class="text-muted mb-0">Manage your lost &amp; found reports and track item ownership claims.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-md-content-end">
        <a href="<?php echo BASE_URL; ?>user/report_lost.php" class="btn btn-warning fw-bold flex-grow-1">
            <i class="bi bi-plus-circle me-1"></i> Report Lost
        </a>
        <a href="<?php echo BASE_URL; ?>user/report_found.php" class="btn btn-primary-custom fw-bold flex-grow-1">
            <i class="bi bi-plus-circle me-1"></i> Report Found
        </a>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="stat-card border-start border-danger border-5">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-semibold uppercase mb-1">My Lost Reports</h6>
                    <h2 class="stat-number text-danger"><?php echo $totalLost; ?></h2>
                </div>
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-search"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-start border-primary border-5">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-semibold uppercase mb-1">My Found Reports</h6>
                    <h2 class="stat-number text-primary"><?php echo $totalFound; ?></h2>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-start border-success border-5">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-semibold uppercase mb-1">My Claims Submitted</h6>
                    <h2 class="stat-number text-success"><?php echo $totalClaims; ?></h2>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-hand-index-thumb"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-text me-2"></i> My Recent Reports</h5>
                <a href="<?php echo BASE_URL; ?>user/my_reports.php" class="small fw-bold">View All</a>
            </div>

            <?php if (empty($recentReports)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                    <p class="mb-0">You haven't reported any items yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Approval</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReports as $item): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-navy"><?php echo e($item['item_name']); ?></span>
                                        <div class="small text-muted"><?php echo format_date($item['item_date']); ?></div>
                                    </td>
                                    <td><?php echo get_type_badge($item['item_type']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo e($item['category_name']); ?></span></td>
                                    <td><?php echo get_approval_badge($item['approval_status']); ?></td>
                                    <td><?php echo get_status_badge($item['item_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-navy mb-0"><i class="bi bi-hand-index-thumb me-2"></i> My Claims</h5>
                <a href="<?php echo BASE_URL; ?>user/my_claims.php" class="small fw-bold">View All</a>
            </div>

            <?php if (empty($recentClaims)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p class="mb-0">You haven't submitted any claim requests.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentClaims as $claim): ?>
                        <div class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold text-navy mb-0"><?php echo e($claim['item_name']); ?></h6>
                                <?php 
                                    if ($claim['claim_status'] === 'approved') {
                                        echo '<span class="badge bg-success">Approved</span>';
                                    } elseif ($claim['claim_status'] === 'rejected') {
                                        echo '<span class="badge bg-danger">Rejected</span>';
                                    } else {
                                        echo '<span class="badge bg-warning text-dark">Pending</span>';
                                    }
                                ?>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-geo-alt me-1"></i> <?php echo e($claim['location']); ?> | Claimed: <?php echo format_date($claim['created_at']); ?>
                            </div>
                            <p class="small text-dark mb-0 bg-light p-2 rounded">
                                "<?php echo e($claim['claim_message']); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
