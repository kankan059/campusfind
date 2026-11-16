<?php
$adminPage = basename($_SERVER['PHP_SELF']);
?>
<div class="container-fluid px-0">
    <div class="row g-0">
        <aside class="col-12 col-lg-2">
            <div class="admin-sidebar h-100">
                <div class="px-3 pb-3 d-none d-lg-block">
                    <span class="text-uppercase small fw-bold text-white-50">Administration</span>
                </div>
                <nav class="nav flex-row flex-lg-column flex-nowrap overflow-auto" aria-label="Admin navigation">
                    <a class="nav-link <?php echo $adminPage === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo $adminPage === 'manage_users.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/manage_users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link <?php echo $adminPage === 'manage_items.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/manage_items.php">
                        <i class="bi bi-box-seam"></i> Item reports
                    </a>
                    <a class="nav-link <?php echo $adminPage === 'manage_claims.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/manage_claims.php">
                        <i class="bi bi-hand-index-thumb"></i> Claims
                    </a>
                    <a class="nav-link <?php echo $adminPage === 'categories.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/categories.php">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </nav>
            </div>
        </aside>
        <section class="col-12 col-lg-10 px-3 px-lg-4 py-4">
