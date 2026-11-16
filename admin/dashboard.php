<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$stats = [];
$stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$itemStats = $pdo->query(
    "SELECT SUM(item_type = 'lost') AS lost_items,
            SUM(item_type = 'found') AS found_items,
            SUM(approval_status = 'pending') AS pending_reports
     FROM items"
)->fetch();
$stats['lost'] = (int)($itemStats['lost_items'] ?? 0);
$stats['found'] = (int)($itemStats['found_items'] ?? 0);
$stats['pending_reports'] = (int)($itemStats['pending_reports'] ?? 0);
$claimStats = $pdo->query(
    "SELECT COUNT(*) AS total_claims, SUM(claim_status = 'pending') AS pending_claims
     FROM claims"
)->fetch();
$stats['claims'] = (int)$claimStats['total_claims'];
$stats['pending_claims'] = (int)($claimStats['pending_claims'] ?? 0);

$recentReports = $pdo->query(
    'SELECT i.item_name, i.item_type, i.location, i.approval_status, i.created_at, u.name AS reporter
     FROM items i JOIN users u ON u.user_id = i.user_id
     ORDER BY i.created_at DESC LIMIT 8'
)->fetchAll();
$recentClaims = $pdo->query(
    'SELECT cl.claim_status, cl.created_at, i.item_name, u.name AS claimant
     FROM claims cl
     JOIN items i ON i.item_id = cl.item_id
     JOIN users u ON u.user_id = cl.user_id
     ORDER BY cl.created_at DESC LIMIT 8'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 fw-bold text-navy mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">CampusFind system overview and recent activity.</p>
    </div>
    <span class="text-muted">Welcome, <?php echo e($_SESSION['name'] ?? 'Administrator'); ?></span>
</div>

<div class="row g-3 mb-4">
    <?php
    $overview = [
        ['Registered users', $stats['users']],
        ['Item reports', $stats['lost'] + $stats['found']],
        ['Awaiting review', $stats['pending_reports'] + $stats['pending_claims']]
    ];
    foreach ($overview as [$label, $value]):
    ?>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="small text-muted fw-semibold"><?php echo e($label); ?></div>
                <div class="stat-number"><?php echo (int)$value; ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <section class="card card-custom p-3 p-md-4 h-100">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold text-navy mb-0">Recently submitted reports</h2>
                <a href="<?php echo BASE_URL; ?>admin/manage_items.php" class="btn btn-sm btn-outline-primary">Manage items</a>
            </div>
            <?php if (!$recentReports): ?>
                <p class="text-muted mb-0">No reports have been submitted.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Item</th><th>Type</th><th>Reporter</th><th>Approval</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentReports as $report): ?>
                                <tr>
                                    <td><?php echo e($report['item_name']); ?><div class="small text-muted"><?php echo e($report['location']); ?></div></td>
                                    <td><?php echo get_type_badge($report['item_type']); ?></td>
                                    <td><?php echo e($report['reporter']); ?></td>
                                    <td><?php echo get_approval_badge($report['approval_status']); ?></td>
                                    <td><?php echo e(format_date($report['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-12 col-xl-5">
        <section class="card card-custom p-3 p-md-4 h-100">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold text-navy mb-0">Recent claim activity</h2>
                <a href="<?php echo BASE_URL; ?>admin/manage_claims.php" class="btn btn-sm btn-outline-primary">Manage claims</a>
            </div>
            <?php if (!$recentClaims): ?>
                <p class="text-muted mb-0">No claim requests have been submitted.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentClaims as $claim): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between gap-2">
                                <strong><?php echo e($claim['item_name']); ?></strong>
                                <?php echo get_approval_badge($claim['claim_status']); ?>
                            </div>
                            <div class="small text-muted"><?php echo e($claim['claimant']); ?> · <?php echo e(format_date($claim['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_content_end.php';
require_once __DIR__ . '/../includes/footer.php';
?>
