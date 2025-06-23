<?php
// C:\xampp\htdocs\Barcode\views\edit_order.php

// 1) Auth + DB
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$error    = '';
$invoice  = null;
$items    = [];

// 2) Figure out the invoice_id (GET or POST)
if (!empty($_REQUEST['invoice_id']) && ctype_digit($_REQUEST['invoice_id'])) {
    $invoice_id = (int)$_REQUEST['invoice_id'];
} else {
    // nothing sensible was passed
    header('Location: orderlist.php');
    exit;
}

// 3) If this is a POST, handle the update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount     = (float)$_POST['Discount'];
    $sgst         = (float)$_POST['sgst'];
    $cgst         = (float)$_POST['cgst'];
    $payment_type = $_POST['Payment_type'];
    $paid         = (float)$_POST['Paid'];

    // fetch original subtotal
    $hdrQ = $conn->prepare("
      SELECT Sub_total
        FROM Tbl_invoice
       WHERE Invoice_id = ?
    ");
    $hdrQ->bind_param('i', $invoice_id);
    $hdrQ->execute();
    $hdrRes  = $hdrQ->get_result()->fetch_assoc();
    $subtotal = (float)$hdrRes['Sub_total'];

    // recalc
    $total = ($subtotal - $discount)
           + ($subtotal - $discount) * ($sgst + $cgst) / 100.0;
    $due   = $total - $paid;

    // perform update
    $upd = $conn->prepare("
      UPDATE Tbl_invoice
         SET Discount   = ?,
             sgst       = ?,
             cgst       = ?,
             Total      = ?,
             Payment_type = ?,
             Paid       = ?,
             Due        = ?
       WHERE Invoice_id = ?
    ");
    $upd->bind_param(
      'dddssdsi',
      $discount,
      $sgst,
      $cgst,
      $total,
      $payment_type,
      $paid,
      $due,
      $invoice_id
    );
    if ($upd->execute()) {
        header('Location: orderlist.php');
        exit;
    }
    $error = 'Update failed: ' . $conn->error;
}

// 4) Fetch invoice header
$hdr = $conn->prepare("
  SELECT Invoice_id, Order_date, Sub_total, Discount, sgst, cgst, Total, Payment_type, Paid, Due
    FROM Tbl_invoice
   WHERE Invoice_id = ?
");
$hdr->bind_param('i', $invoice_id);
$hdr->execute();
$invoice = $hdr->get_result()->fetch_assoc();
if (!$invoice) {
    die("Invoice #{$invoice_id} not found.");
}

// 5) Fetch line items
$dtl = $conn->prepare("
  SELECT Category, Barcode, Product_name, Qty, Rate
    FROM Tbl_Invoice_Detail
   WHERE invoice_id = ?
");
$dtl->bind_param('i', $invoice_id);
$dtl->execute();
$items = $dtl->get_result()->fetch_all(MYSQLI_ASSOC);

// 6) Render
$pageTitle = 'Edit Invoice #' . $invoice['Invoice_id'];
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    <a href="orderlist.php" class="btn btn-sm btn-secondary no-print">
      <i class="bi-arrow-left"></i> Back
    </a>
  </div>
  <div class="card-body p-3">

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row gx-4">
      <!-- keep invoice_id across POST -->
      <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>">

      <!-- Left: line items -->
      <div class="col-12 col-lg-8">
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-3">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Category</th>
                <th>Barcode</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Rate (Rs)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $i => $it): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($it['Category']) ?></td>
                <td><?= htmlspecialchars($it['Barcode']) ?></td>
                <td><?= htmlspecialchars($it['Product_name']) ?></td>
                <td><?= (int)$it['Qty'] ?></td>
                <td><?= number_format($it['Rate'],2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right: editable summary -->
      <div class="col-12 col-lg-4">
        <div class="mb-3">
          <label class="form-label small">Order Date</label>
          <input type="text" class="form-control form-control-sm" readonly
            value="<?= date('Y-m-d H:i',strtotime($invoice['Order_date'])) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">Subtotal (Rs)</label>
          <input type="text" class="form-control form-control-sm" readonly
            value="<?= number_format($invoice['Sub_total'],2) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">Discount (Rs)</label>
          <input name="Discount" type="number" step="0.01"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($invoice['Discount']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">SGST (%)</label>
          <input name="sgst" type="number" step="0.01"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($invoice['sgst']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">CGST (%)</label>
          <input name="cgst" type="number" step="0.01"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($invoice['cgst']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">Payment Type</label>
          <select name="Payment_type"
                  class="form-select form-select-sm">
            <?php foreach (['Cash','Card','Check'] as $pt): ?>
            <option value="<?= $pt ?>"
              <?= $invoice['Payment_type']===$pt?'selected':'' ?>>
              <?= $pt ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small">Paid (Rs)</label>
          <input name="Paid" type="number" step="0.01"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($invoice['Paid']) ?>">
        </div>
        <button type="submit" class="btn btn-success w-100">
          <i class="bi-save"></i> Update Invoice
        </button>
      </div>
    </form>

  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
