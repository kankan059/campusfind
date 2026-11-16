<?php
// contact.php - Public Contact Page
$pageTitle = "Contact Us";
require_once __DIR__ . '/includes/header.php';

$messageSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process contact form (feedback message)
    $messageSent = true;
}
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        <div class="card card-custom p-4 p-md-5">
            <h2 class="fw-bold text-navy mb-2"><i class="bi bi-envelope text-primary me-2"></i>Contact Campus Support</h2>
            <p class="text-muted mb-4">Have questions regarding a lost or found item? Reach out to the Student Affairs Desk.</p>

            <?php if ($messageSent): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Thank you! Your message has been received. Our team will contact you shortly.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Full Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Rahul Sharma">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. student@college.edu">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control" required placeholder="e.g. Query about lost bag in library">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Message / Inquiry</label>
                    <textarea name="message" class="form-control" rows="5" required placeholder="Describe your inquiry in detail..."></textarea>
                </div>
                <button type="submit" class="btn btn-navy fw-bold px-4">
                    <i class="bi bi-send me-1"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
