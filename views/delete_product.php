<?php
// C:\xampp\htdocs\Barcode\views\delete_product.php

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

// 3) Fetch existing product to get image path
$stmt = $conn->prepare(
    "SELECT Image FROM Tbl_Product WHERE Barcode = ? LIMIT 1"
);
$stmt->bind_param('s', $barcode);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();

// 4) Delete image file if it exists
if (!empty($product['Image'])) {
    $filePath = __DIR__ . '/../' . $product['Image'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// 5) Delete product row
$del = $conn->prepare(
    "DELETE FROM Tbl_Product WHERE Barcode = ?"
);
$del->bind_param('s', $barcode);
if ($del->execute()) {
    // Optionally, pass a success message via query string
    header('Location: product.php?success=Product+deleted');
} else {
    header('Location: product.php?error=Delete+failed');
}
exit;
?>
