<?php
// includes/navbar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>index.php">
            <i class="bi bi-box-seam-fill text-warning fs-3"></i>
            <span>CampusFind</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'browse_items.php' || $current_page == 'item_details.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>user/browse_items.php">
                        <i class="bi bi-grid me-1"></i> Browse Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>about.php">
                        <i class="bi bi-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>contact.php">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <?php if (is_logged_in()): ?>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="btn btn-warning btn-sm font-weight-bold px-3" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-1" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-1"></i>
                                <span><?php echo e($_SESSION['name'] ?? 'Account'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/report_lost.php"><i class="bi bi-search me-2"></i> Report Lost Item</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/report_found.php"><i class="bi bi-box-seam me-2"></i> Report Found Item</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/my_reports.php"><i class="bi bi-file-earmark-text me-2"></i> My Reports</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/my_claims.php"><i class="bi bi-hand-index-thumb me-2"></i> My Claims</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="POST" action="<?php echo BASE_URL; ?>logout.php">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                            <button type="submit" class="btn btn-outline-light btn-sm px-3">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary-custom btn-sm px-3" href="<?php echo BASE_URL; ?>register.php">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
