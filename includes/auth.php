<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if running on HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        $_SESSION['flash_error'] = "Access denied. Administrator privileges required.";
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}
