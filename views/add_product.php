<?php
// C:\xampp\htdocs\Barcode\views\add_product.php

// 1) Load auth guard and DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

$error        = '';
$success      = '';
$barcode      = '';
$product_name = '';
$category_id  = '';
$description  = '';
$stock        = '';
$purchase     = '';
$sale         = '';
$image_path   = '';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $barcode      = trim($_POST['barcode']);
        $product_name = trim($_POST['product_name']);
        $category_id  = (int)$_POST['category_id'];
        $description  = trim($_POST['description']);
        $stock        = (int)$_POST['stock'];
        $purchase     = (float)$_POST['purchase_price'];
        $sale         = (float)$_POST['sale_price'];

        // Image upload
        if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['product_image']['tmp_name'];
            $ext  = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed)) {
                $dest = 'Uploads/' . uniqid('prod_') . '.' . $ext;
                if (move_uploaded_file($tmp, __DIR__ . '/../' . $dest)) {
                    $image_path = $dest;
                } else {
                    $error = 'Failed to move uploaded file.';
                }
            } else {
                $error = 'Invalid image file type.';
            }
        }

        // Validation
        if (!$error && ($barcode === '' || $product_name === '' || $category_id === 0 || $stock < 0 || $purchase < 0 || $sale < 0)) {
            $error = 'Please fill all required fields correctly.';
        }

        // Insert into DB
        if (!$error) {
            $stmt = $conn->prepare("
                INSERT INTO Tbl_Product
                  (Barcode, Product_name, Category_id, Description, Stock, Purchase_price, Sale_price, Image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'ssissdds',
                $barcode,
                $product_name,
                $category_id,
                $description,
                $stock,
                $purchase,
                $sale,
                $image_path
            );
            if ($stmt->execute()) {
                $success = 'Product added successfully.';
                // Clear inputs
                $barcode = $product_name = $description = $image_path = '';
                $category_id = $stock = $purchase = $sale = '';
            } else {
                $error = 'Insert failed.';
            }
        }
    }
}

// Fetch categories
$catRes = $conn->query("SELECT Category_id, Category_name FROM Tbl_Category ORDER BY Category_name");
$cats   = $catRes->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Add Product';
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
    <div class="card-header">
        <h1 class="h5 mb-0">
            <a href="product.php" class="btn btn-secondary me-3">
                <i class="bi-arrow-left"></i> Back
            </a>
            <?= htmlentities($pageTitle) ?>
        </h1>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form id="product-form" method="post" action="add_product.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="row gx-4 gy-3">
                <!-- LEFT COLUMN -->
                <div class="col-12 col-lg-6">
                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input
                            type="text"
                            id="barcode"
                            name="barcode"
                            class="form-control"
                            placeholder="Enter Barcode"
                            required
                            value="<?= htmlspecialchars($barcode) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name</label>
                        <input
                            type="text"
                            id="product_name"
                            name="product_name"
                            class="form-control"
                            placeholder="Enter Name"
                            required
                            value="<?= htmlspecialchars($product_name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($cats as $c): ?>
                                <option
                                    value="<?= $c['Category_id'] ?>"
                                    <?= $category_id == $c['Category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['Category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            placeholder="Enter Description"
                            rows="4"><?= htmlspecialchars($description) ?></textarea>
                    </div>
                </div>
                <!-- RIGHT COLUMN -->
                <div class="col-12 col-lg-6">
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock Quantity</label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            class="form-control"
                            placeholder="Enter Stock"
                            min="0"
                            value="<?= htmlspecialchars($stock) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="purchase_price" class="form-label">Purchase Price</label>
                        <input
                            type="number"
                            id="purchase_price"
                            name="purchase_price"
                            class="form-control"
                            placeholder="Enter Purchase Price"
                            step="0.01"
                            value="<?= htmlspecialchars($purchase) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sale_price" class="form-label">Sale Price</label>
                        <input
                            type="number"
                            id="sale_price"
                            name="sale_price"
                            class="form-control"
                            placeholder="Enter Sale Price"
                            step="0.01"
                            value="<?= htmlspecialchars($sale) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image</label>
                        <input
                            type="file"
                            id="product_image"
                            name="product_image"
                            class="form-control">
                        <small class="form-text text-muted">Upload image (jpg, jpeg, png, gif)</small>
                    </div>
                </div>
            </div>
            <div class="d-flex mt-3 gap-2">
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded');
} else {
    $(document).ready(function() {
        $('#product-form').on('submit', function(e) {
            let barcode = $('#barcode').val().trim();
            let name = $('#product_name').val().trim();
            let category = $('#category_id').val();
            if (!barcode || !name || !category) {
                e.preventDefault();
                showToast('Barcode, Product Name, and Category are required.', true);
            }
        });
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>