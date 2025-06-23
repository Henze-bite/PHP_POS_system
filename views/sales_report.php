<?php
// C:\xampp\htdocs\Barcode\views\sale_report.php

// 1) Auth guard & DB
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) Date range (defaults to last 30 days)
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end   = $_GET['end']   ?? date('Y-m-d');

// 3) Fetch each invoice in that range
$stmt = $conn->prepare("
  SELECT 
    Invoice_id,
    DATE(Order_date) AS order_date,
    Sub_total,
    Discount,
    sgst,
    cgst,
    Total
  FROM Tbl_invoice
  WHERE DATE(Order_date) BETWEEN ? AND ?
  ORDER BY Order_date ASC
");
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res   = $stmt->get_result();
$rows  = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4) Compute summary totals
$sumStmt = $conn->prepare("
  SELECT
    COALESCE(SUM(Sub_total),0) AS sum_sub,
    COALESCE(SUM(Discount),0)  AS sum_disc,
    COALESCE(SUM(sgst),0)      AS sum_sgst,
    COALESCE(SUM(cgst),0)      AS sum_cgst,
    COALESCE(SUM(Total),0)     AS sum_tot
  FROM Tbl_invoice
  WHERE DATE(Order_date) BETWEEN ? AND ?
");
$sumStmt->bind_param('ss', $start, $end);
$sumStmt->execute();
$sumRow = $sumStmt->get_result()->fetch_assoc();
$sumStmt->close();

$pageTitle = 'Sales Report';
include __DIR__ . '/templates/header.php';
?>

<div class="card mt-3 shadow-sm">
  <div class="card-header bg-white">
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
  </div>
  <div class="card-body p-3">

    <!-- 1) Date‐range filter alone -->
    <form class="row gx-3 gy-2 align-items-end mb-4"
          method="get"
          action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
      <div class="col-auto">
        <label for="start" class="form-label small mb-0">From</label>
        <input type="date" id="start" name="start"
               class="form-control form-control-sm"
               value="<?= htmlspecialchars($start) ?>">
      </div>
      <div class="col-auto">
        <label for="end" class="form-label small mb-0">To</label>
        <input type="date" id="end" name="end"
               class="form-control form-control-sm"
               value="<?= htmlspecialchars($end) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      </div>
    </form>

    <!-- 2) Summary cards in their own flex row -->
    <div class="d-flex justify-content-between align-items-start gap-2 mb-4">
      <div class="card bg-info text-white flex-fill">
        <div class="card-body py-2 px-3">
          <div class="small">Subtotal</div>
          <div class="h6 mb-0">Rs <?= number_format($sumRow['sum_sub'],2) ?></div>
        </div>
      </div>
      <div class="card bg-warning text-dark flex-fill">
        <div class="card-body py-2 px-3">
          <div class="small">Discount</div>
          <div class="h6 mb-0">Rs <?= number_format($sumRow['sum_disc'],2) ?></div>
        </div>
      </div>
      <div class="card bg-success text-white flex-fill">
        <div class="card-body py-2 px-3">
          <div class="small">SGST</div>
          <div class="h6 mb-0">Rs <?= number_format($sumRow['sum_sgst'],2) ?></div>
        </div>
      </div>
      <div class="card bg-secondary text-white flex-fill">
        <div class="card-body py-2 px-3">
          <div class="small">CGST</div>
          <div class="h6 mb-0">Rs <?= number_format($sumRow['sum_cgst'],2) ?></div>
        </div>
      </div>
      <div class="card bg-dark text-white flex-fill">
        <div class="card-body py-2 px-3">
          <div class="small">Total</div>
          <div class="h6 mb-0">Rs <?= number_format($sumRow['sum_tot'],2) ?></div>
        </div>
      </div>
    </div>

    <!-- Container for search bar -->
    <div id="tbl-filter" class="mb-2"></div>

    <!-- Scrollable table body -->
    <div class="table-responsive overflow-auto" style="max-height:400px;">
      <table id="report-table" class="table table-hover table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Invoice ID</th>
            <th>Subtotal</th>
            <th>Discount</th>
            <th>SGST</th>
            <th>CGST</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['order_date']) ?></td>
            <td><?= htmlspecialchars($r['Invoice_id']) ?></td>
            <td><?= number_format($r['Sub_total'],2) ?></td>
            <td><?= number_format($r['Discount'],2) ?></td>
            <td><?= number_format($r['sgst'],2) ?></td>
            <td><?= number_format($r['cgst'],2) ?></td>
            <td><?= number_format($r['Total'],2) ?></td>
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
    // initialize DataTable on our report table
    var dt = $('#report-table').DataTable({
      scrollY:        true,
      scrollCollapse: true,
      paging:         false,
      info:           false,
      ordering:       true,
      dom:            't'      // only the table itself
    });

    // pull its built-in search box up into #tbl-filter
    var filter = $('#report-table_filter');
    $('#tbl-filter').append(filter);
    filter.find('input')
      .addClass('form-control form-control-sm')
      .attr('placeholder','Search…');
  });
</script>
