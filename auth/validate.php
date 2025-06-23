<?php
// D:\php\htdocs\Barcode\auth\validate.php

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$self = basename($_SERVER['PHP_SELF']);
$adminPages = ['registration.php', 'tax.php'];

if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Role-based access control
if (in_array($self, $adminPages) && $_SESSION['role'] !== 'Admin') {
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit;
}