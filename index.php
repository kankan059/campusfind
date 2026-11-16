<?php
// index.php - Public Homepage
$pageTitle = "Home - Lost & Found Management System";
require_once __DIR__ . '/includes/header.php';

// Fetch stats & recent approved items dynamically from DB
$totalLost = 0;
$totalFound = 0;
$totalReturned = 0;
$recentItems = [];
$categories = [];

try {
    // Stats
    $totalLost = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE item_type = 'lost' AND approval_status = 'approved'")->fetchColumn();
    $totalFound = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE item_type = 'found' AND approval_status = 'approved'")->fetchColumn();
    $totalReturned = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE item_status = 'returned'")->fetchColumn();

    // Categories
    $categories = get_all_categories($pdo);

    // Recent 6 approved items
    $stmt = $pdo->query("
        SELECT i.*, c.category_name 
        FROM items i 
        JOIN categories c ON i.category_id = c.category_id 
        WHERE i.approval_status = 'approved' 
        ORDER BY i.created_at DESC 
        LIMIT 6
    ");
    $recentItems = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful fallback if database tables aren't created yet
}
?>

<!-- Hero Section -->
<section class="hero-section text-center mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="hero-title">Campus Lost & Found Portal</h1>
                <p class="hero-subtitle">
                    Lost something on campus or found an item that belongs to someone else? 
                    CampusFind helps connect students and staff to return belongings safely.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?php echo BASE_URL; ?>user/report_lost.php" class="btn btn-warning btn-lg fw-bold px-4">
                        <i class="bi bi-search me-2"></i> Report Lost Item
                    </a>
                    <a href="<?php echo BASE_URL; ?>user/report_found.php" class="btn btn-light btn-lg fw-bold px-4 text-navy">
                        <i class="bi bi-box-seam me-2"></i> Report Found Item
                    </a>
                    <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-outline-light btn-lg fw-bold px-4">
                        <i class="bi bi-grid me-2"></i> Browse Items
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Stats Bar -->
<section class="mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-search"></i>
                </div>
                <div>
                    <h3 class="stat-number"><?php echo $totalLost; ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Reported Lost Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center gap-3" style="border-left-color: var(--accent-blue);">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <h3 class="stat-number"><?php echo $totalFound; ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Reported Found Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center gap-3" style="border-left-color: #10b981;">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <h3 class="stat-number"><?php echo $totalReturned; ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Reunited with Owners</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Search Bar -->
<section class="card card-custom p-4 mb-5 shadow-sm">
    <h5 class="fw-bold mb-3 text-navy"><i class="bi bi-search me-2 text-primary"></i> Search Lost &amp; Found Items</h5>
    <form action="<?php echo BASE_URL; ?>user/browse_items.php" method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" name="keyword" class="form-control" placeholder="Search by item name..." value="">
        </div>
        <div class="col-md-3">
            <input type="text" name="location" class="form-control" placeholder="Filter by campus location...">
        </div>
        <div class="col-md-2">
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                        <?php echo e($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="lost">Lost</option>
                <option value="found">Found</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-navy w-100 fw-bold">
                <i class="bi bi-search me-1"></i> Search
            </button>
        </div>
    </form>
</section>

<!-- Recent Approved Reports -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-navy">Recently Approved Items</h3>
            <p class="text-muted mb-0">Browse latest verified item reports submitted on campus</p>
        </div>
        <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-outline-primary btn-sm fw-semibold">
            View All Items <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($recentItems)): ?>
        <div class="alert alert-info text-center p-4">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            <h5>No items reported yet</h5>
            <p class="mb-0 text-muted">Be the first to report a lost or found item on campus!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($recentItems as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card card-custom h-100">
                        <div class="item-img-container">
                            <?php if (!empty($item['image']) && file_exists(__DIR__ . '/uploads/items/' . $item['image'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/items/' . e($item['image']); ?>" alt="<?php echo e($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="item-img-placeholder text-center">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="position-absolute top-0 start-0 m-2">
                                <?php echo get_type_badge($item['item_type']); ?>
                            </div>
                            <div class="position-absolute top-0 end-0 m-2">
                                <?php echo get_status_badge($item['item_status']); ?>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-secondary mb-1"><?php echo e($item['category_name']); ?></span>
                            </div>
                            <h5 class="card-title fw-bold text-navy"><?php echo e($item['item_name']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo e(mb_strimwidth($item['description'], 0, 90, "...")); ?>
                            </p>
                            <ul class="list-unstyled small text-muted mb-3">
                                <li><i class="bi bi-geo-alt text-danger me-1"></i> <strong>Location:</strong> <?php echo e($item['location']); ?></li>
                                <li><i class="bi bi-calendar-event text-primary me-1"></i> <strong>Date:</strong> <?php echo format_date($item['item_date']); ?></li>
                            </ul>
                            <a href="<?php echo BASE_URL; ?>user/item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline-primary w-100 fw-semibold mt-auto">
                                <i class="bi bi-eye me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- How It Works Section -->
<section class="my-5 p-5 bg-white rounded-4 shadow-sm border">
    <div class="text-center mb-5">
        <h3 class="fw-bold text-navy">How CampusFind Works</h3>
        <p class="text-muted">Simple 4-step process to recover lost belongings</p>
    </div>
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="p-3">
                <div class="stat-icon mx-auto mb-3" style="width:60px; height:60px; font-size:1.8rem;">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <h5 class="fw-bold">1. Submit Report</h5>
                <p class="small text-muted">Fill out a simple form detailing the item name, location, date, and photo.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3">
                <div class="stat-icon mx-auto mb-3" style="width:60px; height:60px; font-size:1.8rem; background-color:#fef3c7; color:#d97706;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="fw-bold">2. Admin Verification</h5>
                <p class="small text-muted">College administrators verify and approve legitimate item reports.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3">
                <div class="stat-icon mx-auto mb-3" style="width:60px; height:60px; font-size:1.8rem; background-color:#dcfce7; color:#15803d;">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold">3. Browse &amp; Search</h5>
                <p class="small text-muted">Search through approved items by category, location, or keyword.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3">
                <div class="stat-icon mx-auto mb-3" style="width:60px; height:60px; font-size:1.8rem; background-color:#f3e8ff; color:#7e22ce;">
                    <i class="bi bi-hand-index-thumb"></i>
                </div>
                <h5 class="fw-bold">4. Claim Item</h5>
                <p class="small text-muted">Submit ownership proof. Upon admin approval, meet to reclaim your item.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
