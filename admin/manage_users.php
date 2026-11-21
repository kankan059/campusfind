<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    $role = post_string('role');
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verify_csrf_token($token)) {
        set_flash('error', 'Your form session expired. Refresh and try again.');
    } elseif ($userId === false || $userId < 1 || !in_array($role, ['admin', 'user'], true)) {
        set_flash('error', 'Invalid user role change.');
    } elseif ((int)$userId === (int)$_SESSION['user_id']) {
        set_flash('error', 'You cannot change your own administrator access.');
    } else {
        try {
            $pdo->beginTransaction();
            $admins = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            $targetStmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ? FOR UPDATE');
            $targetStmt->execute([$userId]);
            $currentRole = $targetStmt->fetchColumn();

            if ($currentRole === false) {
                $pdo->rollBack();
                set_flash('error', 'User not found.');
            } elseif ($currentRole === 'admin' && $role === 'user' && count($admins) <= 1) {
                $pdo->rollBack();
                set_flash('error', 'The last administrator cannot be demoted.');
            } else {
                $update = $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?');
                $update->execute([$role, $userId]);
                $pdo->commit();
                set_flash('success', 'User role updated.');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('CampusFind user role update failed: ' . $e->getMessage());
            set_flash('error', 'The user role could not be updated.');
        }
    }

    $redirect = BASE_URL . 'admin/manage_users.php';
    if (isset($_GET['search']) && is_string($_GET['search'])) {
        $redirect .= '?search=' . rawurlencode($_GET['search']);
    }
    header('Location: ' . $redirect);
    exit;
}

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$usersQuery = 'SELECT user_id, name, email, phone, role, created_at FROM users';
$params = [];
if ($search !== '') {
    $usersQuery .= ' WHERE name LIKE ? OR email LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$usersQuery .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($usersQuery);
$stmt->execute($params);
$users = $stmt->fetchAll();

$selectedUser = null;
if (isset($_GET['view'])) {
    $viewId = filter_var($_GET['view'], FILTER_VALIDATE_INT);
    if ($viewId !== false && $viewId > 0) {
        $detailStmt = $pdo->prepare(
            'SELECT u.user_id, u.name, u.email, u.phone, u.role, u.created_at,
                    (SELECT COUNT(*) FROM items i WHERE i.user_id = u.user_id) AS report_count,
                    (SELECT COUNT(*) FROM claims c WHERE c.user_id = u.user_id) AS claim_count
             FROM users u WHERE u.user_id = ?'
        );
        $detailStmt->execute([$viewId]);
        $selectedUser = $detailStmt->fetch();
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="mb-4">
    <h1 class="h2 fw-bold text-navy mb-1">Manage Users</h1>
    <p class="text-muted mb-0">Search accounts, view details, and manage administrator roles.</p>
</div>



<?php if ($selectedUser): ?>
    <section class="card card-custom p-3 p-md-4 mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <h2 class="h5 fw-bold text-navy">User details</h2>
            <a class="btn-close" href="<?php echo BASE_URL; ?>admin/manage_users.php<?php echo $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>" aria-label="Close details"></a>
        </div>
        <div class="row g-3">
            <div class="col-sm-6"><strong>Name:</strong> <?php echo e($selectedUser['name']); ?></div>
            <div class="col-sm-6"><strong>Email:</strong> <?php echo e($selectedUser['email']); ?></div>
            <div class="col-sm-6"><strong>Phone:</strong> <?php echo e($selectedUser['phone']); ?></div>
            <div class="col-sm-6"><strong>Role:</strong> <?php echo e(ucfirst($selectedUser['role'])); ?></div>
            <div class="col-sm-6"><strong>Joined:</strong> <?php echo e(format_date($selectedUser['created_at'])); ?></div>
            <div class="col-sm-6"><strong>Reports:</strong> <?php echo (int)$selectedUser['report_count']; ?> · <strong>Claims:</strong> <?php echo (int)$selectedUser['claim_count']; ?></div>
        </div>
    </section>
<?php elseif (isset($_GET['view'])): ?>
    <div class="alert alert-warning">The requested user was not found.</div>
<?php endif; ?>

<form method="GET" class="card card-custom p-3 mb-4">
    <label for="user-search" class="form-label fw-semibold">Search by name or email</label>
    <div class="input-group">
        <input id="user-search" type="search" name="search" class="form-control" value="<?php echo e($search); ?>" maxlength="100" placeholder="Enter a name or email">
        <button class="btn btn-navy" type="submit"><i class="bi bi-search"></i> Search</button>
        <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>admin/manage_users.php">Clear</a><?php endif; ?>
    </div>
</form>

<div class="card card-custom p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['name']); ?></td>
                        <td><?php echo e($user['email']); ?></td>
                        <td><?php echo e($user['phone']); ?></td>
                        <td><span class="badge <?php echo $user['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?>"><?php echo e(ucfirst($user['role'])); ?></span></td>
                        <td><?php echo e(format_date($user['created_at'])); ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>admin/manage_users.php?view=<?php echo (int)$user['user_id']; ?><?php echo $search !== '' ? '&search=' . rawurlencode($search) : ''; ?>">Details</a>
                                <?php if ((int)$user['user_id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="POST" class="d-flex gap-1" onsubmit="return confirm('Change this user role?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                        <select name="role" class="form-select form-select-sm" aria-label="Role for <?php echo e($user['name']); ?>">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Save</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted align-self-center">Current account</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="6" class="text-center text-muted py-4">No users matched your search.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_content_end.php';
require_once __DIR__ . '/../includes/footer.php';
?>
