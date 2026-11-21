<?php
$pageTitle = "About Project";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-10">
        <div class="card card-custom p-4 p-md-5">
            <h2 class="fw-bold text-navy mb-3"><i class="bi bi-info-circle text-primary me-2"></i>About CampusFind</h2>
            <p class="lead text-muted">
                <strong>CampusFind</strong> is a web-based Lost &amp; Found Management System designed specifically for college campuses.
                It provides a centralized platform for students, faculty, and administrators to report, search, and reclaim lost items efficiently.
            </p>
            <hr class="my-4">
            
            <div class="row g-4">
                <div class="col-md-6">
                    <h4 class="fw-bold text-navy"><i class="bi bi-bullseye text-danger me-2"></i>Project Objectives</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i> Centralize lost and found reporting across college campus.</li>
                        <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i> Reduce item recovery turnaround time for students.</li>
                        <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i> Prevent fraudulent claims with admin verification.</li>
                        <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i> Provide real-time tracking of item reports and claim status.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4 class="fw-bold text-navy"><i class="bi bi-cpu text-primary me-2"></i>Tech Stack</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent"><strong>Backend:</strong>PHP </li>
                        <li class="list-group-item bg-transparent"><strong>Database:</strong> MySQL (Relational DB)</li>
                        <li class="list-group-item bg-transparent"><strong>Frontend:</strong> HTML5, CSS3, Bootstrap 5, JavaScript</li>
                        <li class="list-group-item bg-transparent"><strong>Server Environment:</strong> XAMPP</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
