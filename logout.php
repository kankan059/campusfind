<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['csrf_token']) ||
    !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(400);
    exit('Invalid logout request. Please submit the logout form again.');
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start();
set_flash('success', 'You have been successfully logged out.');

header("Location: " . BASE_URL . "login.php");
exit;
