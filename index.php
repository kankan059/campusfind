<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$recentLostItems = [];
$recentFoundItems = [];

try {
    foreach (['lost' => 'recentLostItems', 'found' => 'recentFoundItems'] as $type => $resultKey) {
        $stmt = $pdo->prepare(
            'SELECT i.*, c.category_name
             FROM items i
             JOIN categories c ON i.category_id = c.category_id
             WHERE i.approval_status = \'approved\' AND i.item_type = ?
             ORDER BY i.created_at DESC
             LIMIT 3'
        );
        $stmt->execute([$type]);
        $$resultKey = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('CampusFind homepage query failed: ' . $e->getMessage());
}
?>

<section class="home-hero text-center">
    <div class="hero-inner">
        <p class="hero-kicker">A little more found. A little less lost.</p>
        <h1 class="hero-title">Good things find their way back.</h1>
        <p class="hero-subtitle">
            CampusFind brings our campus community together to report, search for, and return lost belongings.
        </p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>user/browse_items.php" class="btn btn-primary-custom">Browse items</a>
            <a href="<?php echo BASE_URL; ?>user/report_lost.php" class="btn btn-outline-primary">Report an item</a>
        </div>

        <form action="<?php echo BASE_URL; ?>user/browse_items.php" method="GET" class="hero-search">
            <div class="row g-2 align-items-center">
                <div class="col-md">
                    <label class="visually-hidden" for="home-keyword">Search item names</label>
                    <input id="home-keyword" type="search" name="keyword" class="form-control" placeholder="What are you looking for?">
                </div>
                <div class="col-md-auto">
                    <label class="visually-hidden" for="home-type">Item type</label>
                    <select id="home-type" name="type" class="form-select">
                        <option value="">Lost &amp; found</option>
                        <option value="lost">Lost items</option>
                        <option value="found">Found items</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-navy">Search items</button>
                </div>
            </div>
        </form>
    </div>
</section>

<?php
$collections = [
    ['title' => 'Recently lost', 'items' => $recentLostItems, 'type' => 'lost'],
    ['title' => 'Recently found', 'items' => $recentFoundItems, 'type' => 'found']
];
?>
<?php foreach ($collections as $collection): ?>
    <section class="mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="eyebrow mb-2"><?php echo $collection['type'] === 'lost' ? 'Looking for' : 'Waiting to go home'; ?></p>
                <h2 class="mb-1"><?php echo e($collection['title']); ?></h2>
                <p class="text-muted mb-0">The latest approved reports from your campus.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>user/browse_items.php?type=<?php echo e($collection['type']); ?>" class="btn btn-outline-primary">View all <?php echo e($collection['type']); ?> items</a>
        </div>

        <?php if (!$collection['items']): ?>
            <p class="text-muted py-3">There are no recent <?php echo e($collection['type']); ?> reports to show.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($collection['items'] as $item): ?>
                    <?php
                    $imageName = !empty($item['image']) ? basename($item['image']) : '';
                    $imagePath = __DIR__ . '/uploads/items/' . $imageName;
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <article class="card card-custom h-100">
                            <div class="item-img-container">
                                <?php if ($imageName !== '' && is_file($imagePath)): ?>
                                    <img src="<?php echo e(BASE_URL . 'uploads/items/' . rawurlencode($imageName)); ?>" alt="<?php echo e($item['item_name']); ?>">
                                <?php else: ?>
                                    <div class="item-img-placeholder" aria-hidden="true"></div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <span class="badge bg-secondary"><?php echo e($item['category_name']); ?></span>
                                    <?php echo get_status_badge($item['item_status']); ?>
                                </div>
                                <h3 class="h4 card-title mb-2"><?php echo e($item['item_name']); ?></h3>
                                <p class="text-muted flex-grow-1"><?php echo e(mb_strimwidth($item['description'], 0, 115, '…')); ?></p>
                                <p class="small text-muted mb-3"><?php echo e($item['location']); ?> · <?php echo e(format_date($item['item_date'])); ?></p>
                                <a href="<?php echo BASE_URL; ?>user/item_details.php?id=<?php echo (int)$item['item_id']; ?>" class="btn btn-outline-primary mt-auto">View details</a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<section class="how-it-works my-5" id="how-it-works">
    <div class="mb-4">
        <p class="eyebrow mb-2">A simple way to help</p>
        <h2 class="mb-2">How CampusFind works</h2>
        <p class="text-muted mb-0">A thoughtful process keeps reports useful and returns safe.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4 how-step">
            <h3 class="h4">Share a report</h3>
            <p>Describe the item and where it was last seen or found. Add a photograph if you have one.</p>
        </div>
        <div class="col-md-4 how-step">
            <h3 class="h4">Find a match</h3>
            <p>Browse verified reports or narrow the list by item name, type, category, and campus location.</p>
        </div>
        <div class="col-md-4 how-step">
            <h3 class="h4">Reconnect safely</h3>
            <p>For a found item, submit an ownership explanation so the campus team can review the claim.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
