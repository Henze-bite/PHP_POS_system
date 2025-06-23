<?php
// D:\php\htdocs\Barcode\views\registration.php

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Handle create
$error   = '';
$success = '';
$name    = '';
$email   = '';
$role    = '';
if (isset($_POST['save'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
        error_log("CSRF validation failed for user creation: $email");
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $role === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $conn = reconnect_if_needed($conn);
            // Check for existing email
            $chk = $conn->prepare("SELECT 1 FROM Tbl_user WHERE LOWER(User_email) = LOWER(?) LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = 'That email is already registered.';
                error_log("Email already registered: $email");
            } else {
                // Insert user with plain-text password
                $stmt = $conn->prepare("
                    INSERT INTO Tbl_user (User_name, User_email, Password, Role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param('ssss', $name, $email, $password, $role);
                if ($stmt->execute()) {
                    $success = 'User registered.';
                    error_log("User registered successfully: $email");
                    $name = $email = $role = '';
                } else {
                    $error = 'Insert failed.';
                    error_log("User registration failed: $email, Error: " . $conn->error);
                }
            }
            $chk->close();
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn = reconnect_if_needed($conn);
    $stmt = $conn->prepare("DELETE FROM Tbl_user WHERE User_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: registration.php');
    exit;
}

// Fetch all users
$conn = reconnect_if_needed($conn);
$res   = $conn->query("SELECT * FROM Tbl_user ORDER BY User_id ASC");
$users = $res->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Registration';
include __DIR__ . '/templates/header.php';
?>
<div class="card mt-3 shadow-sm">
    <div class="card-header">
        <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    </div>
    <div class="card-body">
        <div class="row gx-4 gy-4">
            <!-- FORM COLUMN -->
            <div class="col-12 col-md-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <form method="post" action="registration.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input
                            type="text" id="name" name="name"
                            class="form-control" placeholder="Enter Name"
                            required
                            value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input
                            type="email" id="email" name="email"
                            class="form-control" placeholder="Enter email"
                            required
                            value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password" id="password" name="password"
                            class="form-control" placeholder="Password"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option <?= $role === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option <?= $role === 'User' ? 'selected' : '' ?>>User</option>
                        </select>
                    </div>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                </form>
            </div>
            <!-- TABLE COLUMN -->
            <div class="col-12 col-md-8">
                <div class="d-flex justify-content-end mb-2">
                    <input
                        type="text" id="user-search"
                        class="form-control form-control-sm w-auto"
                        placeholder="Search…">
                </div>
                <div class="table-responsive overflow-auto" style="max-height:400px;">
                    <table class="table table-hover table-sm mb-0" id="users-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:5%">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Role</th>
                                <th style="width:10%">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['User_id'] ?></td>
                                <td><?= htmlspecialchars($u['User_name']) ?></td>
                                <td><?= htmlspecialchars($u['User_email']) ?></td>
                                <td>********</td>
                                <td><?= htmlspecialchars($u['Role']) ?></td>
                                <td>
                                    <a
                                        href="registration.php?delete=<?= $u['User_id'] ?>"
                                        onclick="return confirm('Delete this user?')"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script>
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded');
} else {
    $(document).ready(function() {
        const tableRows = Array.from(document.querySelectorAll('#users-table tbody tr'));
        const search = document.getElementById('user-search');
        function render() {
            const q = search.value.trim().toLowerCase();
            tableRows.forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }
        search.addEventListener('input', render);
        render();
    });
}
</script>