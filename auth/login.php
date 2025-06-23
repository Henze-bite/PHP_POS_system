<?php
// D:\php\htdocs\Barcode\auth\login.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $conn = reconnect_if_needed($conn);
        // Use case-insensitive email matching
        $stmt = $conn->prepare("SELECT User_id, User_name, User_email, Password, Role FROM Tbl_user WHERE LOWER(User_email) = LOWER(?) LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            error_log("Login attempt for email: $email, stored password: {$row['Password']}");
            if ($password === $row['Password']) {
                $_SESSION['user_id']    = $row['User_id'];
                $_SESSION['username']   = $row['User_name'];
                $_SESSION['user_email'] = $row['User_email'];
                $_SESSION['role']       = $row['Role'];
                session_regenerate_id(true); // Regenerate session ID
                error_log("Login successful for user: $email");
                header('Location: ' . BASE_URL . '/views/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
                error_log("Password comparison failed for email: $email, input: $password");
            }
        } else {
            $error = 'Invalid email or password.';
            error_log("No user found for email: $email");
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | POS Barcode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.5s ease-in;
        }
        .login-container h1 {
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            border: none;
            padding: 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.9rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>POS Barcode Login</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>