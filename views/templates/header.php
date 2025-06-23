<?php
// D:\php\htdocs\Barcode\views\templates\header.php

// 1) Start session if not already active + protect pages (except login.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 3) Grab user info
$username = $_SESSION['username'] ?? 'Guest';

// 4) Navigation items (no logout here)
$navItems = [
    'dashboard.php'       => ['bi-speedometer2', 'Dashboard'],
    'category.php'        => ['bi-tags',        'Category'],
    'product.php'         => ['bi-box-seam',    'Product'],
    'pos.php'             => ['bi-cart4',       'POS'],
    'orderlist.php'       => ['bi-list',        'OrderList'],
    'sales_report.php'    => ['bi-bar-chart',   'Sales Report'],
    'tax.php'             => ['bi-percent',     'Tax'],
    'registration.php'    => ['bi-person-plus', 'Registration'],
    'change_password.php' => ['bi-lock',        'Change Password'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'POS Barcode') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #10b981;
            --background-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-color: #1f2937;
            --border-radius: 8px;
        }

        html, body {
            height: 100%;
            margin: 0;
            background: var(--background-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-color);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            z-index: 1001;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-240px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header i {
            font-size: 1.8rem;
            margin-right: 0.75rem;
        }

        .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .user {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            margin: 0.5rem;
            border-radius: var(--border-radius);
        }

        .user img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 0.75rem;
            border: 2px solid #ffffff;
        }

        .user p {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
        }

        .bar {
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .bar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .bar .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .bar .nav-link:hover,
        .bar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            text-decoration: none;
            border-left: 4px solid var(--accent-color);
        }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            height: 64px;
            background: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.5rem;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        @media (max-width: 991.98px) {
            .topbar {
                left: 0;
            }
        }

        .topbar .search-box .input-group {
            width: 300px;
            background: #f1f5f9;
            border-radius: var(--border-radius);
        }

        .topbar .search-box .form-control {
            border: none;
            background: transparent;
            font-size: 0.9rem;
        }

        .topbar .search-box .btn {
            color: var(--text-color);
        }

        .right .btn {
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .right .btn:hover {
            background: var(--secondary-color);
            color: #ffffff;
        }

        .logout-btn a {
            color: var(--text-color);
            text-decoration: none;
        }

        .logout-btn a:hover {
            color: var(--accent-color);
        }

        /* Main content */
        .main-content {
            margin-top: 64px;
            margin-left: 240px;
            padding: 2rem;
            min-height: calc(100% - 64px);
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Overlay for mobile sidebar */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        @media (max-width: 991.98px) {
            .overlay.active {
                display: block;
            }
        }

        /* Form Styling */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: var(--card-bg);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
        }

        .card-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 1rem 1.5rem;
        }

        .card-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-primary, .btn-warning {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover, .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: #6b7280;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: var(--border-radius);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: none;
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
            color: #991b1b;
        }

        .alert-success {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
            color: #065f46;
        }

        /* Toast Notification */
        .toast-container {
            z-index: 1050;
        }

        .toast {
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .toast-body {
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
            }
            .btn-primary, .btn-warning, .btn-secondary {
                padding: 0.6rem 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="action-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <i class="bi-upc-scan"></i>
            <h4>POS BARCODE</h4>
        </div>
        <div class="user">
            <img src="https://i.pinimg.com/564x/66/18/13/661813c9ec1f4ca8dc5adf72add50caf.jpg" alt="Avatar">
            <p><?= htmlspecialchars($username) ?></p>
        </div>
        <div class="bar">
            <ul class="nav flex-column">
                <?php foreach ($navItems as $file => [$icon, $label]):
                    $active = ($self === $file) ? 'active' : '';
                ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/views/<?= $file ?>"
                           class="nav-link <?= $active ?>">
                            <i class="<?= $icon ?>"></i>
                            <span><?= $label ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="sidebar-footer text-center" style="font-size:0.85rem; padding: 1rem;">
            © <?= date('Y') ?> One-Night Destruction
        </div>
    </nav>

    <!-- OVERLAY FOR MOBILE -->
    <div class="overlay" id="overlay"></div>

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="left d-flex align-items-center">
            <button class="btn btn-outline-light d-lg-none me-2" id="sidebarToggle">
                <i class="bi-list"></i>
            </button>
            <div class="search-box">
                <div class="input-group input-group-sm">
                    <input type="search" class="form-control" placeholder="Search…">
                    <button class="btn btn-outline-secondary"><i class="bi-search"></i></button>
                </div>
            </div>
        </div>
        <div class="right d-flex align-items-center">
            <div class="Messages me-2">
                <button class="btn btn-sm btn-outline-light" title="Messages">
                    <i class="bi-envelope"></i>
                    <span class="badge bg-danger">3</span>
                </button>
            </div>
            <div class="Notifications me-2">
                <button class="btn btn-sm btn-outline-light" title="Notifications">
                    <i class="bi-bell"></i>
                    <span class="badge bg-danger">3</span>
                </button>
            </div>
            <div class="logout-btn">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT START -->
    <div class="main-content">

    <script>
        // Toast notification function
        function showToast(message, isError = false) {
            let toast = $('#action-toast');
            toast.find('.toast-body').text(message).addClass(isError ? 'text-danger' : 'text-success');
            toast.toast({ delay: 3000 }).toast('show');
        }

        // Sidebar toggle for mobile
        $('#sidebarToggle').click(function() {
            $('.sidebar').toggleClass('active');
            $('#overlay').toggleClass('active');
        });
        $('#overlay').click(function() {
            $('.sidebar').removeClass('active');
            $(this).removeClass('active');
        });
    </script>