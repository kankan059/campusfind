</main>

<footer>
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="text-white mb-3"><i class="bi bi-box-seam-fill text-warning me-2"></i>CampusFind</h5>
                <p class="small text-light opacity-75">
                    CampusFind is a dedicated lost and found management system designed for college campuses. Report lost or found items quickly and reconnect with your belongings.
                </p>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>user/browse_items.php">Browse Items</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>about.php">About Project</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Item Categories</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1">Electronics & Gadgets</li>
                    <li class="mb-1">Documents & ID Cards</li>
                    <li class="mb-1">Books & Notebooks</li>
                    <li class="mb-1">Bags, Wallets & Accessories</li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">College Support</h6>
                <p class="small mb-1"><i class="bi bi-geo-alt text-warning me-2"></i> Student Affairs Office, Admin Block</p>
                <p class="small mb-1"><i class="bi bi-envelope text-warning me-2"></i> support@campusfind.edu</p>
                <p class="small"><i class="bi bi-telephone text-warning me-2"></i> +91 (022) 2345-6789</p>
            </div>
        </div>
        <hr class="my-4 border-secondary">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start small">
                &copy; <?php echo date('Y'); ?> <strong>CampusFind</strong> - College Major Project. All Rights Reserved.
            </div>
            <div class="col-md-6 text-center text-md-end small text-muted">
                Built with PHP 8 &amp; MySQL PDO
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>
