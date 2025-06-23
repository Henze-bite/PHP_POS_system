<?php
// C:\xampp\htdocs\Barcode\views\change_password.php

// 1) Start session + protect page
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) Initialize
$error   = '';
$success = '';
$user_id = (int)$_SESSION['user_id'];

// 3) Handle form submission
if (isset($_POST['change'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $current = trim($_POST['current_password'] ?? '');
        $newp    = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        // Basic validation
        if ($current === '' || $newp === '' || $confirm === '') {
            $error = 'All fields are required.';
        } elseif ($newp !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            // Fetch stored password
            $stmt = $conn->prepare("
                SELECT Password
                  FROM Tbl_user
                 WHERE User_id = ?
                 LIMIT 1
            ");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $error = 'User not found.';
            } elseif (!password_verify($current, $row['Password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update with hashed password
                $hashed_newp = password_hash($newp, PASSWORD_DEFAULT);
                $upd = $conn->prepare("
                    UPDATE Tbl_user
                       SET Password = ?
                     WHERE User_id = ?
                ");
                $upd->bind_param('si', $hashed_newp, $user_id);
                if ($upd->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Update failed.';
                }
                $upd->close();
            }
        }
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
    <div class="card-header bg-white border-bottom border-primary px-3 py-2">
        <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    </div>
    <div class="card-body px-3 py-4">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="change_password.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="form-control"
                    placeholder="Enter current password"
                    required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="form-control"
                    placeholder="Enter new password"
                    required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Re-enter new password"
                    required>
            </div>
            <button type="submit" name="change" class="btn btn-primary">Change Password</button>
        </form>

    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>