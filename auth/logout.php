<?php
// C:\xampp\htdocs\Barcode\auth\logout.php

// 1) Load DB config so BASE_URL is defined
require_once __DIR__ . '/../config/db.php';

// 2) Clear out the session
session_start();
$_SESSION = [];
session_destroy();

// 3) Redirect to login
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
