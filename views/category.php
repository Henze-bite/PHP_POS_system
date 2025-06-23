<?php
// C:\xampp\htdocs\Barcode\views\category.php

// 1) Load auth guard and DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

$error    = '';
$success  = '';
$editing  = false;
$category_name = '';

// Show deletion success if present
if (isset($_GET['deleted'])) {
    $success = 'Category deleted.';
}

// Handle create
if (isset($_POST['save'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['category_name']);
        if ($name === '') {
            $error = 'Category cannot be empty.';
        } else {
            $stmt = $conn->prepare("INSERT INTO Tbl_Category (Category_name) VALUES (?)");
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $success = 'Category added.';
            } else {
                $error   = 'Insert failed.';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Tbl_Category WHERE Category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    // Redirect with deleted flag
    header('Location: category.php?deleted=1');
    exit;
}

// Handle edit request
if (isset($_GET['edit'])) {
    $editing = true;
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM Tbl_Category WHERE Category_id=$edit_id LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $category_name = $row['Category_name'];
    }
}

// Handle update
if (isset($_POST['update'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['category_name']);
        if ($name === '') {
            $error = 'Category cannot be empty.';
        } else {
            $stmt = $conn->prepare("UPDATE Tbl_Category SET Category_name=? WHERE Category_id=?");
            $stmt->bind_param('si', $name, $id);
            if ($stmt->execute()) {
                $success = 'Category updated.';
                $editing = false;
                $category_name = '';
            } else {
                $error = 'Update failed.';
            }
        }
    }
}

// Fetch all categories
$catsRes   = $conn->query("SELECT * FROM Tbl_Category ORDER BY Category_id ASC");
$categories = $catsRes->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Category';
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
    <div class="card-header bg-white">
        <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    </div>
    <div class="card-body px-3 py-4">
        <div class="row">
            <!-- FORM COLUMN (never scrolls) -->
            <div class="col-12 col-md-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="post" action="category.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category</label>
                        <input
                            type="text"
                            id="category_name"
                            name="category_name"
                            class="form-control"
                            placeholder="Enter Category"
                            required
                            value="<?= htmlspecialchars($category_name) ?>">
                    </div>
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $edit_id ?>">
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                        <a href="category.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="save" class="btn btn-warning">Save</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABLE COLUMN (only this scrolls) -->
            <div class="col-12 col-md-8">
                <div class="table-responsive overflow-auto" style="max-height:400px;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th>Category</th>
                                <th style="width:15%">Edit</th>
                                <th style="width:15%">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $i => $cat): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($cat['Category_name']) ?></td>
                                    <td>
                                        <a href="category.php?edit=<?= $cat['Category_id'] ?>"
                                           class="btn btn-primary btn-sm w-100">Edit</a>
                                    </td>
                                    <td>
                                        <a href="category.php?delete=<?= $cat['Category_id'] ?>"
                                           onclick="return confirm('Delete this category?')"
                                           class="btn btn-info btn-sm w-100">Delete</a>
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