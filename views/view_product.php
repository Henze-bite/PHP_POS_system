<?php
// C:\xampp\htdocs\Barcode\views\view_product.php

// 1) Auth guard & DB connection
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) Get barcode from querystring
$barcode = $_GET['barcode'] ?? '';
if (!$barcode) {
    header('Location: product.php');
    exit;
}

// 3) Fetch the product + category
$stmt = $conn->prepare("\n    SELECT \n      p.Barcode,\n      p.Product_name,\n      c.Category_name,\n      p.Description,\n      p.Stock,\n      p.Purchase_price,\n      p.Sale_price,\n      p.Image\n    FROM Tbl_Product AS p\n    LEFT JOIN Tbl_Category AS c\n      ON p.Category_id = c.Category_id\n    WHERE p.Barcode = ?\n    LIMIT 1\n");
$stmt->bind_param('s', $barcode);
$stmt->execute();
$res     = $stmt->get_result();
$product = $res->fetch_assoc();

if (!$product) {
    header('Location: product.php');
    exit;
}

// 4) Compute profit
$profit = $product['Sale_price'] - $product['Purchase_price'];

$pageTitle = 'View Product';
include __DIR__ . '/templates/header.php';
?>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white d-flex align-items-center">
    <a href="product.php" class="btn btn-sm btn-secondary me-3">&larr; Back</a>
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
  </div>
  <div class="card-body p-3">
    <div class="row gx-4">

      <!-- PRODUCT DETAILS -->
      <div class="col-12 col-md-6 mb-4 mb-md-0">
        <div class="bg-info text-white text-center fw-semibold py-2 rounded-top">
          PRODUCT DETAILS
        </div>
        <ul class="list-group list-group-flush border">
          <li class="list-group-item d-flex justify-content-between">
            <span>Barcode</span>
            <span class="badge bg-secondary"><?= htmlspecialchars($product['Barcode']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Product Name</span>
            <span class="badge bg-warning text-dark"><?= htmlspecialchars($product['Product_name']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Category</span>
            <span class="badge bg-success"><?= htmlspecialchars($product['Category_name']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Description</span>
            <span class="badge bg-primary"><?= htmlspecialchars($product['Description']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Stock</span>
            <span class="badge bg-danger"><?= htmlspecialchars($product['Stock']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Purchase Price</span>
            <span class="badge bg-secondary"><?= number_format($product['Purchase_price'],2) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Sale Price</span>
            <span class="badge bg-dark"><?= number_format($product['Sale_price'],2) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Product Profit</span>
            <span class="badge bg-success"><?= number_format($profit,2) ?></span>
          </li>
        </ul>
      </div>

      <!-- PRODUCT IMAGE -->
      <div class="col-12 col-md-6">
        <div class="bg-info text-white text-center fw-semibold py-2 rounded-top">
          PRODUCT IMAGE
        </div>
        <div class="border h-100 d-flex align-items-center justify-content-center overflow-hidden">
          <?php if (!empty($product['Image'])): ?>
            <img
              src="<?= BASE_URL . '/' . htmlspecialchars($product['Image']) ?>"
              alt="Product Image"
              class="img-fluid"
              style="max-width:100%; max-height:100%; object-fit:contain;">
          <?php else: ?>
            <span class="text-muted">No image available</span>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
