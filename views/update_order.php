<?php
// C:\xampp\htdocs\Barcode\views\update_order.php

// 1) Auth + DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// 2) Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_POST['invoice_id'], $_POST['paid_amt'], $_POST['payment_type'])
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid request'
    ]);
    exit;
}

$invoice_id   = (int) $_POST['invoice_id'];
$paid         = (float) $_POST['paid_amt'];
$payment_type = $_POST['payment_type'];

// 3) Fetch existing total
$sel = $conn->prepare("
    SELECT Total
      FROM Tbl_invoice
     WHERE Invoice_id = ?
     LIMIT 1
");
$sel->bind_param('i', $invoice_id);
$sel->execute();
$res = $sel->get_result();
if (!($row = $res->fetch_assoc())) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => 'Order not found'
    ]);
    exit;
}

$total = (float) $row['Total'];
$due   = $total - $paid;
if ($due < 0) $due = 0;

// 4) Update the invoice
$upd = $conn->prepare("
    UPDATE Tbl_invoice
       SET Paid = ?, Due = ?, Payment_type = ?
     WHERE Invoice_id = ?
");
$upd->bind_param('ddsi', $paid, $due, $payment_type, $invoice_id);
if (!$upd->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Update failed: ' . $conn->error
    ]);
    exit;
}

// 5) Return updated values
echo json_encode([
    'success'      => true,
    'paid'         => number_format($paid, 2),
    'due'          => number_format($due, 2),
    'payment_type' => $payment_type
]);
