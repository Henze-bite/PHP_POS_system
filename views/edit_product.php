<?php
// C:\xampp\htdocs\Barcode\views\edit_product.php

// 1) Auth guard & DB connection
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 2) Get barcode (or id) from querystring
$barcode = $_GET['barcode'] ?? '';
if (!$barcode) {
    header('Location: product.php');
    exit;
}

// 3) Fetch existing product
$stmt = $conn->prepare("
    SELECT 
      Product_id,
      Barcode,
      Product_name,
      Category_id,
      Description,
      Stock,
      Purchase_price,
      Sale_price,
      Image
    FROM Tbl_Product
    WHERE Barcode = ?
    LIMIT 1
");
$stmt->bind_param('s', $barcode);
$stmt->execute();
$res     = $stmt->get_result();
$product = $res->fetch_assoc();

if (!$product) {
    header('Location: product.php');
    exit;
}

// 4) Fetch categories
$catRes = $conn->query("SELECT Category_id, Category_name FROM Tbl_Category ORDER BY Category_name");
$cats = $catRes->fetch_all(MYSQLI_ASSOC);

// 5) Initialize variables for form
$error   = '';
$success = '';
$id            = $product['Product_id'];
$barcode_val   = $product['Barcode'];
$name_val      = $product['Product_name'];
$category_id   = $product['Category_id'];
$description   = $product['Description'];
$stock_val     = $product['Stock'];
$purchase_val  = $product['Purchase_price'];
$sale_val      = $product['Sale_price'];
$image_path    = $product['Image'];

// 6) Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode_val  = trim($_POST['barcode']);
    $name_val     = trim($_POST['product_name']);
    $category_id  = (int)$_POST['category_id'];
    $description  = trim($_POST['description']);
    $stock_val    = (int)$_POST['stock'];
    $purchase_val = trim($_POST['purchase_price']);
    $sale_val     = trim($_POST['sale_price']);

    // Optional image upload
    if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $tmp      = $_FILES['product_image']['tmp_name'];
        $ext      = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true) . '.' . $ext;
        $destRel  = 'uploads/' . $filename;
        $destAbs  = $uploadDir . $filename;
        if (move_uploaded_file($tmp, $destAbs)) {
            $image_path = $destRel;
        }
    }

    // Validate
    if ($barcode_val === '' || $name_val === '') {
        $error = 'Barcode and Product Name are required.';
    } else {
        $upd = $conn->prepare("
            UPDATE Tbl_Product
               SET Barcode = ?, Product_name = ?, Category_id = ?, Description = ?,
                   Stock = ?, Purchase_price = ?, Sale_price = ?, Image = ?
             WHERE Product_id = ?
        ");
        $upd->bind_param(
            'ssissddsi',
            $barcode_val,
            $name_val,
            $category_id,
            $description,
            $stock_val,
            $purchase_val,
            $sale_val,
            $image_path,
            $id
        );
        if ($upd->execute()) {
            $success = 'Product updated successfully.';
        } else {
            $error = 'Update failed: ' . $conn->error;
        }
    }
}

$pageTitle = 'Edit Product';
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
  <div class="card-header bg-white border-bottom border-primary px-3 py-2 d-flex align-items-center">
    <a href="product.php" class="btn btn-sm btn-secondary me-3">&larr; Back</a>
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
  </div>
  <div class="card-body px-3 py-4">
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_product.php?barcode=<?= urlencode($barcode) ?>" enctype="multipart/form-data">
      <div class="row">
        <!-- LEFT COLUMN -->
        <div class="col-12 col-lg-6">
          <div class="mb-3">
            <label for="barcode" class="form-label">Barcode</label>
            <input
              type="text"
              id="barcode"
              name="barcode"
              class="form-control"
              required
              value="<?= htmlspecialchars($barcode_val) ?>">
          </div>

          <div class="mb-3">
            <label for="product_name" class="form-label">Product Name</label>
            <input
              type="text"
              id="product_name"
              name="product_name"
              class="form-control"
              required
              value="<?= htmlspecialchars($name_val) ?>">
          </div>

          <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select id="category_id" name="category_id" class="form-select" required>
              <option value="">Select Category</option>
              <?php foreach ($cats as $c): ?>
                <option
                  value="<?= $c['Category_id'] ?>"
                  <?= $c['Category_id'] == $category_id ? 'selected' : '' ?>>
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
              rows="4"><?= htmlspecialchars($description) ?></textarea>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-12 col-lg-6">
          <?php if ($image_path): ?>
            <div class="mb-3">
              <label class="form-label">Current Image</label><br>
              <img src="<?= BASE_URL . '/' . htmlspecialchars($image_path) ?>"
                   class="img-thumbnail"
                   style="max-width:200px; height:auto;"
                   alt="Product image">
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="product_image" class="form-label">Product Image</label>
            <input
              type="file"
              id="product_image"
              name="product_image"
              class="form-control">
            <small class="form-text text-muted">
              <?= $image_path ? 'Current: ' . basename($image_path) : 'Upload new to replace' ?>
            </small>
          </div>

          <div class="mb-3">
            <label for="stock" class="form-label">Stock Quantity</label>
            <input
              type="number"
              id="stock"
              name="stock"
              class="form-control"
              value="<?= htmlspecialchars($stock_val) ?>">
          </div>

          <div class="mb-3">
            <label for="purchase_price" class="form-label">Purchase Price</label>
            <input
              type="text"
              id="purchase_price"
              name="purchase_price"
              class="form-control"
              value="<?= htmlspecialchars($purchase_val) ?>">
          </div>

          <div class="mb-3">
            <label for="sale_price" class="form-label">Sale Price</label>
            <input
              type="text"
              id="sale_price"
              name="sale_price"
              class="form-control"
              value="<?= htmlspecialchars($sale_val) ?>">
          </div>
        </div>
      </div>

      <div class="d-flex mt-3">
        <button type="submit" class="btn btn-primary">Update Product</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
