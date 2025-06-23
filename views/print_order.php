<?php
// C:\xampp\htdocs\Barcode\views\print_order.php

// 1) Auth-guard & DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

// 2) Get invoice ID
if (empty($_GET['invoice_id']) || !is_numeric($_GET['invoice_id'])) {
    die('Invalid invoice ID');
}
$invoice_id = (int)$_GET['invoice_id'];

// 3) Fetch invoice header
$stmt = $conn->prepare("
  SELECT Invoice_id, Order_date, Sub_total, Discount, sgst, cgst, Total, Payment_type, Due, Paid
  FROM Tbl_invoice
  WHERE Invoice_id = ?
  LIMIT 1
");
$stmt->bind_param('i', $invoice_id);
$stmt->execute();
$invRes = $stmt->get_result();
if (!$invoice = $invRes->fetch_assoc()) {
    die('Invoice not found');
}

// 4) Fetch invoice details
$stmt2 = $conn->prepare("
  SELECT Category, Barcode, Product_name, Qty, Purchase_Price, Rate
  FROM Tbl_Invoice_Detail
  WHERE invoice_id = ?
");
$stmt2->bind_param('i', $invoice_id);
$stmt2->execute();
$detRes = $stmt2->get_result();
$items  = $detRes->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Invoice #' . $invoice['Invoice_id'];
include __DIR__ . '/templates/header.php';
?>

<!-- Print-only CSS override -->
<style>
  @media print {
    /* hide on-screen controls */
    .no-print { display: none !important; }

    @page { size: A4 portrait; margin: 0.5cm; }

    body * { visibility: hidden; }
    .invoice, .invoice * { visibility: visible; }
    .invoice {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      margin: 0;
      box-shadow: none;
      page-break-after: auto;
      page-break-inside: avoid;
    }
    /* hide footer in print */
    .invoice-footer { display: none !important; }
    /* avoid page-breaks inside sections */
    .invoice-header,
    .invoice-body,
    .invoice-table,
    .invoice-summary { page-break-inside: avoid; }
  }

  .invoice {
    max-width: 900px;
    margin: 1.5rem auto;
    border: 1px solid #ddd;
    border-radius: .25rem;
    box-shadow: 0 0 10px rgba(0,0,0,.05);
  }
  .invoice-header {
    /* aqua-like header color */
    background: #17a2b8;
    color: #fff;
    padding: 1.5rem;
    /* ensure print preserves background */
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .invoice-header h2 {
    margin: 0;
    font-weight: 600;
  }
  .invoice-header .company-details {
    font-size: .9rem;
  }
  .invoice-body {
    padding: 1.5rem;
    background: #f9f9f9;
  }
  .invoice-body .bill-to {
    font-size: .95rem;
  }
  .invoice-table th {
    background: #e9ecef;
    font-weight: 500;
  }
  .invoice-summary {
    margin-top: 1rem;
  }
  .invoice-summary .summary-label {
    font-weight: 500;
  }
  .invoice-footer {
    background: #f1f1f1;
    padding: .75rem 1.5rem;
    font-size: .85rem;
    text-align: center;
  }
</style>

<div class="invoice">

  <div class="invoice-header d-flex justify-content-between align-items-center">
    <div>
      <h2>CYBARG INC</h2>
      <p class="company-details mb-0">
        Phone: (0755) 245998<br>
        www.cybarg.com | info@cybarg.com
      </p>
    </div>
    <div>
      <!-- Cancel button, hidden in print -->
      <button
        class="btn btn-danger no-print me-2"
        onclick="window.location.href='orderlist.php'">
        <i class="bi-x"></i> Cancel
      </button>
      <!-- Print button, hidden in print output -->
      <button class="btn btn-light no-print" onclick="window.print()">
        <i class="bi-printer"></i> Print
      </button>
    </div>
  </div>

  <div class="invoice-body">
    <div class="row mb-4">
      <div class="col-sm-6 bill-to">
        <h6 class="mb-1">Bill To:</h6>
        <p class="mb-1"><?= htmlspecialchars($_SESSION['username'] ?? 'Valued Customer') ?></p>
        <small>Email: <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></small>
      </div>
      <div class="col-sm-6 text-sm-end">
        <h6 class="mb-1">Invoice #<?= $invoice['Invoice_id'] ?></h6>
        <small>Order Date: <?= date('Y-m-d H:i', strtotime($invoice['Order_date'])) ?></small><br>
        <small>Payment: <?= htmlspecialchars($invoice['Payment_type']) ?></small>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered invoice-table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Category</th>
            <th>Product</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Unit Price (Rs)</th>
            <th class="text-end">Amount (Rs)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $it): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($it['Category']) ?></td>
            <td><?= htmlspecialchars($it['Product_name']) ?></td>
            <td class="text-end"><?= (int)$it['Qty'] ?></td>
            <td class="text-end"><?= number_format($it['Rate'], 2) ?></td>
            <td class="text-end"><?= number_format($it['Rate'] * $it['Qty'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="row invoice-summary">
      <div class="col-sm-6"></div>
      <div class="col-sm-6">
        <table class="table table-borderless">
          <tr>
            <td class="summary-label">Subtotal:</td>
            <td class="text-end">Rs <?= number_format($invoice['Sub_total'], 2) ?></td>
          </tr>
          <tr>
            <td class="summary-label">Discount:</td>
            <td class="text-end">Rs <?= number_format($invoice['Discount'], 2) ?></td>
          </tr>
          <tr>
            <td class="summary-label">SGST (<?= number_format($invoice['sgst'], 2) ?>%):</td>
            <td class="text-end">
              Rs <?= number_format(
                        ($invoice['Sub_total'] - $invoice['Discount']) *
                        $invoice['sgst'] / 100,
                        2
                      ) ?>
            </td>
          </tr>
          <tr>
            <td class="summary-label">CGST (<?= number_format($invoice['cgst'], 2) ?>%):</td>
            <td class="text-end">
              Rs <?= number_format(
                        ($invoice['Sub_total'] - $invoice['Discount']) *
                        $invoice['cgst'] / 100,
                        2
                      ) ?>
            </td>
          </tr>
          <tr class="fw-bold">
            <td class="summary-label">Grand Total:</td>
            <td class="text-end">Rs <?= number_format($invoice['Total'], 2) ?></td>
          </tr>
          <tr>
            <td class="summary-label">Paid:</td>
            <td class="text-end">Rs <?= number_format($invoice['Paid'], 2) ?></td>
          </tr>
          <tr>
            <td class="summary-label">Balance Due:</td>
            <td class="text-end">Rs <?= number_format($invoice['Due'], 2) ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="invoice-footer">
    Thank you for your business!<br>
    <em>No returns or refunds without original invoice within 2 days of purchase.</em>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
