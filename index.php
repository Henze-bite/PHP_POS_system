<?php
// D:\php\htdocs\Barcode\index.php

// Set session cookie parameters before starting the session
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    'secure' => true, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
header('Location: ' . BASE_URL . '/views/dashboard.php');
exit;