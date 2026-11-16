<?php
// login.php - User Login Page
$pageTitle = "Login";
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . BASE_URL . (is_admin() ? "admin/dashboard.php" : "user/dashboard.php"));
    exit;
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Invalid session token. Please refresh and try again.";
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');

                if ($user['role'] === 'admin') {
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "user/dashboard.php");
                }
                exit;
            } else {
                $errors[] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log('CampusFind login query failed: ' . $e->getMessage());
            $errors[] = "Login is temporarily unavailable. Please try again.";
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-4">
        <div class="card card-custom p-4 shadow-sm">
            <div class="text-center mb-4">
                <div class="stat-icon mx-auto mb-2" style="width:60px; height:60px; font-size:1.8rem;">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <h3 class="fw-bold text-navy">Account Login</h3>
                <p class="text-muted small">Access your CampusFind dashboard</p>
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

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?php echo e($email); ?>" placeholder="student@college.edu" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-navy w-100 fw-bold py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                </button>

                <div class="text-center small">
                    Don't have an account? <a href="<?php echo BASE_URL; ?>register.php" class="fw-bold text-primary">Register here</a>
                </div>
            </form>
        </div>
        
        <div class="card card-custom mt-3 p-3 text-center bg-light border-0">
            <small class="text-muted">
                <strong>Demo Credentials:</strong><br>
                Admin Email: <code>admin@campusfind.edu</code> | Password: <code>admin123</code>
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
