<?php
require_once __DIR__ . '/functions.php';
$isAdminPage = strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' . SITE_NAME : SITE_NAME . ' | College Lost & Found Portal'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="site-main<?php echo $isAdminPage ? ' admin-main' : ''; ?>">
    <div class="container">
        <?php display_flash_messages(); ?>
    </div>
