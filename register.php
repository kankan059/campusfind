<?php
// register.php - User Registration Page
$pageTitle = "Register";
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . BASE_URL . (is_admin() ? "admin/dashboard.php" : "user/dashboard.php"));
    exit;
}

$name = '';
$email = '';
$phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Invalid session token. Please refresh and try again.";
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters long.";
    }

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Please enter a valid 10-15 digit phone number.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check duplicate email
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists.";
            }
        } catch (PDOException $e) {
            error_log('CampusFind registration email lookup failed: ' . $e->getMessage());
            $errors[] = "Unable to validate your account details. Please try again.";
        }
    }

    // Insert user into database
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, role) 
                VALUES (?, ?, ?, ?, 'user')
            ");
            $stmt->execute([$name, $email, $phone, $hashed_password]);

            set_flash('success', 'Registration successful! You can now log in with your email and password.');
            header("Location: " . BASE_URL . "login.php");
            exit;
        } catch (PDOException $e) {
            error_log('CampusFind registration failed: ' . $e->getMessage());
            $errors[] = $e->getCode() === '23000'
                ? "An account with this email address already exists."
                : "Registration could not be completed. Please try again.";
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-5">
        <div class="card card-custom p-4 p-md-5 shadow-sm">
            <div class="text-center mb-4">
                <div class="stat-icon mx-auto mb-2" style="width:60px; height:60px; font-size:1.8rem;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h3 class="fw-bold text-navy">Create Account</h3>
                <p class="text-muted small">Register to report or claim lost belongings on campus</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" class="form-control" value="<?php echo e($name); ?>" placeholder="e.g. Rahul Sharma" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">College / Personal Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?php echo e($email); ?>" placeholder="student@college.edu" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?php echo e($phone); ?>" placeholder="9876543210" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-navy w-100 fw-bold py-2 mb-3">
                    <i class="bi bi-person-check me-1"></i> Register Account
                </button>

                <div class="text-center small">
                    Already have an account? <a href="<?php echo BASE_URL; ?>login.php" class="fw-bold text-primary">Login here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
