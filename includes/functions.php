<?php
// includes/functions.php - Common Helper Functions

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

// Escape HTML output safely
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function post_string($key, $default = '') {
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Set flash message
function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

// Display flash messages
function display_flash_messages() {
    $types = ['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'];
    foreach ($types as $key => $bsClass) {
        if (isset($_SESSION['flash_' . $key])) {
            echo '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">';
            echo e($_SESSION['flash_' . $key]);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_' . $key]);
        }
    }
}

// Format date nicely
function format_date($dateString) {
    if (!$dateString) return 'N/A';
    $date = date_create($dateString);
    return date_format($date, 'M d, Y');
}

// Get HTML badge for item status
function get_status_badge($status) {
    switch ($status) {
        case 'open':
            return '<span class="badge bg-primary">Open</span>';
        case 'claimed':
            return '<span class="badge bg-warning text-dark">Claimed</span>';
        case 'returned':
            return '<span class="badge bg-success">Returned</span>';
        default:
            return '<span class="badge bg-secondary">' . e(ucfirst($status)) . '</span>';
    }
}

// Get HTML badge for approval status
function get_approval_badge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pending</span>';
        case 'approved':
            return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>';
        case 'rejected':
            return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>';
        default:
            return '<span class="badge bg-secondary">' . e(ucfirst($status)) . '</span>';
    }
}

// Get HTML badge for item type
function get_type_badge($type) {
    if ($type === 'lost') {
        return '<span class="badge bg-danger"><i class="bi bi-search"></i> Lost</span>';
    } else {
        return '<span class="badge bg-info text-dark"><i class="bi bi-box-seam"></i> Found</span>';
    }
}

// Fetch all categories
function get_all_categories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
    return $stmt->fetchAll();
}

// Upload item image safely
function upload_item_image($fileInput) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null];
    }

    $file = $_FILES[$fileInput];

    if (!isset($file['error'], $file['size'], $file['tmp_name'], $file['name']) ||
        !is_string($file['tmp_name']) || !is_string($file['name']) ||
        !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'The uploaded file is invalid.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error occurred code: ' . $file['error']];
    }

    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] <= 0 || $file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Image size must be less than 5MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return ['success' => false, 'error' => 'Image validation is unavailable.'];
    }
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mimeType, $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP.'];
    }

    $extension = $allowedMimeTypes[$mimeType];
    $uploadedExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($uploadedExtension === 'jpeg') {
        $uploadedExtension = 'jpg';
    }
    if ($uploadedExtension !== $extension || @getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'error' => 'The image content and file extension must match.'];
    }

    $newFilename = 'item_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetDir = __DIR__ . '/../uploads/items/';
    $targetPath = $targetDir . $newFilename;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        return ['success' => false, 'error' => 'The image upload directory is unavailable.'];
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newFilename];
    }

    return ['success' => false, 'error' => 'Failed to save uploaded image file.'];
}
