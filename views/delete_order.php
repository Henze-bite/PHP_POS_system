<?php
// C:\xampp\htdocs\Barcode\views\delete_order.php

// 1) Auth + DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

// Only allow AJAX POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['invoice_id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$invoice_id = (int)$_POST['invoice_id'];

// Wrap in transaction to keep detail + header in sync
$conn->begin_transaction();

try {
    // 1) Delete details
    $d = $conn->prepare("DELETE FROM Tbl_Invoice_Detail WHERE invoice_id = ?");
    $d->bind_param('i', $invoice_id);
    $d->execute();

    // 2) Delete invoice header
    $h = $conn->prepare("DELETE FROM Tbl_invoice WHERE Invoice_id = ?");
    $h->bind_param('i', $invoice_id);
    $h->execute();

    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}