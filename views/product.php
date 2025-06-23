<?php
// C:\xampp\htdocs\Barcode\views\product.php

// 1) Auth guard & DB connection
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

// 2) Fetch products + category
$sql = <<<SQL
  SELECT 
    p.Barcode,
    p.Product_name      AS Product,
    c.Category_name     AS Category,
    p.Description,
    p.Stock,
    p.Purchase_price    AS PurchasePrice,
    p.Sale_price        AS SalePrice,
    p.Image
  FROM Tbl_Product p
  LEFT JOIN Tbl_Category c USING(Category_id)
  ORDER BY p.Product_name
SQL;
$res      = $conn->query($sql);
$products = $res->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Product List';
include __DIR__ . '/templates/header.php';
?>

<!-- DataTables + Bootstrap5 CSS -->
<link
  rel="stylesheet"
  href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"
/>


<div class="card shadow-sm mb-4">
  <div class="card-header bg-white">
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
  </div>
  <div class="card-body p-3">

    <!-- Top controls -->
    <div class="d-flex justify-content-between align-items-center mb-2">
      <!-- placeholder for search -->
      <div id="dt-search-container"></div>
      <a href="add_product.php" class="btn btn-primary btn-sm">
        <i class="bi-plus-lg"></i> Add Product
      </a>
    </div>

    <!-- Scrollable table -->
    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
      <table
        id="product-table"
        class="table table-hover table-striped table-sm mb-0"
        style="width:100%">
        <thead class="table-light position-sticky top-0">
          <tr>
            <th>Barcode</th>
            <th>Product</th>
            <th>Category</th>
            <th>Description</th>
            <th>Stock</th>
            <th>Purchase</th>
            <th>Sale</th>
            <th>Image</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['Barcode']) ?></td>
            <td><?= htmlspecialchars($row['Product']) ?></td>
            <td><?= htmlspecialchars($row['Category']) ?></td>
            <td><?= htmlspecialchars($row['Description']) ?></td>
            <td><?= htmlspecialchars($row['Stock']) ?></td>
            <td><?= number_format($row['PurchasePrice'],2) ?></td>
            <td><?= number_format($row['SalePrice'],2) ?></td>
            <td>
              <?php if ($row['Image']): ?>
                <img
                  src="<?= BASE_URL . '/' . htmlspecialchars($row['Image']) ?>"
                  alt="Product image"
                  style="height:30px; border-radius:4px;">
              <?php endif; ?>
            </td>
            <td class="text-nowrap">
              <a href="view_product.php?barcode=<?= urlencode($row['Barcode']) ?>"
                 class="btn btn-sm btn-dark" title="View">
                <i class="bi-eye"></i>
              </a>
              <a href="edit_product.php?barcode=<?= urlencode($row['Barcode']) ?>"
                 class="btn btn-sm btn-success ms-1" title="Edit">
                <i class="bi-pencil"></i>
              </a>
              <a href="delete_product.php?barcode=<?= urlencode($row['Barcode']) ?>"
                 onclick="return confirm('Delete this product?')"
                 class="btn btn-sm btn-danger ms-1" title="Delete">
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

<?php include __DIR__ . '/templates/footer.php'; ?>

<!-- jQuery & DataTables + Bootstrap5 JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(function(){
    // Initialize DataTable: no pagination, no info, search + table only
    var table = $('#product-table').DataTable({
      paging:       false,  // remove pagination
      lengthChange: false,  // remove "Show X entries"
      searching:    true,
      info:         false,  // remove "Showing..."
      ordering:     true,
      dom:          'ft'    // f=filter, t=table
    });

    // Move the generated filter into our custom container
    $('#dt-search-container').append( $('.dataTables_filter') );

    // Style the search input as a Bootstrap input-group
    var f = $('#dt-search-container .dataTables_filter');
    f.find('label').addClass('d-flex').contents().filter(function(){
      return this.nodeType === 3;
    }).remove();
    var inp = f.find('input');
    inp.addClass('form-control form-control-sm')
       .attr('placeholder','Search products…')
       .wrap('<div class="input-group input-group-sm"></div>')
       .before('<span class="input-group-text"><i class="bi-search"></i></span>');
  });
</script>
