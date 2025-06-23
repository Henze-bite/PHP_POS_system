<?php
// C:\xampp\htdocs\Barcode\views\orderlist.php

// 1) Auth guard & DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

// 2) Fetch your orders
$res    = $conn->query("
  SELECT
    Invoice_id,
    Order_date,
    Total,
    Paid,
    Due,
    Payment_type
  FROM Tbl_invoice
  ORDER BY Invoice_id DESC
");
$orders = $res->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Order List';
include __DIR__ . '/templates/header.php';
?>

<!-- DataTables CSS -->
<link
  rel="stylesheet"
  href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"
/>

<div class="card mt-3 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    <a href="pos.php" class="btn btn-sm btn-primary no-print">
      <i class="bi-cart4"></i> New POS
    </a>
  </div>
  <div class="card-body px-3 py-4">

    <!-- Invoice ID filter -->
    <div class="mb-3">
      <input
        id="invoice-id-search"
        type="text"
        class="form-control form-control-sm"
        placeholder="Filter by Invoice ID…">
    </div>

    <!-- external global-search placeholder (optional) -->
    <div id="orders-filter" class="mb-3"></div>

    <!-- Scrollable body only -->
    <div class="table-responsive overflow-auto" style="max-height: 350px;">
      <table
        id="orders-table"
        class="table table-hover table-striped table-sm mb-0"
        style="width:100%">
        <thead class="table-light">
          <tr>
            <th>Invoice ID</th>
            <th>Order Date</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Payment Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr data-invoice-id="<?= $o['Invoice_id'] ?>">
            <td><?= htmlspecialchars($o['Invoice_id']) ?></td>
            <td><?= htmlspecialchars($o['Order_date']) ?></td>
            <td><?= number_format($o['Total'], 2) ?></td>
            <td><?= number_format($o['Paid'], 2) ?></td>
            <td><?= number_format($o['Due'], 2) ?></td>
            <td>
              <span class="badge
                <?= $o['Payment_type']==='Cash'  ? 'bg-warning' :
                   ($o['Payment_type']==='Card' ? 'bg-success' :
                                                  'bg-danger') ?>">
                <?= htmlspecialchars($o['Payment_type']) ?>
              </span>
            </td>
            <td class="text-nowrap">
              <a href="print_order.php?invoice_id=<?= $o['Invoice_id'] ?>"
                 class="btn btn-sm btn-warning me-1" title="Print">
                <i class="bi-printer"></i>
              </a>
              <a href="pos.php?invoice_id=<?= $o['Invoice_id'] ?>"
                 class="btn btn-sm btn-info me-1" title="Edit">
                <i class="bi-pencil"></i>
              </a>
              <button
                class="btn btn-sm btn-danger delete-order"
                title="Delete">
                <i class="bi-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  // initialize DataTable: newest first, no pagination, scrollable
  var dt = $('#orders-table').DataTable({
    order:         [[0, 'desc']],    // sort by Invoice ID desc
    paging:        false,            // disable pagination
    info:          false,
    lengthChange:  false,
    searching:     true,
    ordering:      true,
    scrollY:       '300px',
    scrollCollapse:true,
    dom:           'ft'              // global filter + table only
  });

  // move the global DataTables search into our custom container
  var f = $('#orders-table_filter');
  $('#orders-filter').append(f);
  f.find('label').hide();
  var input = f.find('input');
  input
    .addClass('form-control form-control-sm')
    .attr('placeholder','Search orders…')
    .wrap('<div class="input-group input-group-sm"></div>')
    .before('<span class="input-group-text"><i class="bi-search"></i></span>');

  // invoice-id column (0) search on keyup
  $('#invoice-id-search').on('keyup', function(){
    dt.column(0).search(this.value).draw();
  });

  // handle delete via AJAX
  $('#orders-table').on('click', '.delete-order', function(){
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var invoiceId = $row.data('invoice-id');
    if (!confirm('Delete order #' + invoiceId + '?')) return;
    $.ajax({
      url: 'delete_order.php',
      method: 'POST',
      data: { invoice_id: invoiceId },
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          dt.row($row).remove().draw(false);
        } else {
          alert('Delete failed: ' + (resp.error||'Unknown error'));
        }
      },
      error: function() {
        alert('Request failed. Could not delete.');
      }
    });
  });
});
</script>
