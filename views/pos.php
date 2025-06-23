<?php
// C:\xampp\htdocs\Barcode\views\pos.php

// 1) Auth + DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

//////////////////////////////////////////
// 2) Detect editing mode and load header/details
//////////////////////////////////////////
$editing     = isset($_GET['invoice_id']) && is_numeric($_GET['invoice_id']);
$invoice_id  = $editing ? (int)$_GET['invoice_id'] : null;
$invoice_hdr = null;
$line_items  = [];

if ($editing) {
    // load invoice header
    $h = $conn->prepare("
      SELECT Sub_total,
             Discount       AS disc_pct,
             sgst           AS sgst_pct,
             cgst           AS cgst_pct,
             Total          AS grand_total,
             Payment_type,
             Paid           AS paid_amt,
             Due            AS due_amt
      FROM Tbl_invoice
      WHERE Invoice_id = ?
      LIMIT 1
    ");
    $h->bind_param('i', $invoice_id);
    $h->execute();
    $invoice_hdr = $h->get_result()->fetch_assoc() ?: null;

    // load invoice details
    $d = $conn->prepare("
      SELECT Product_id    AS pid,
             Category,
             Barcode,
             Product_name  AS name,
             Qty           AS qty,
             Purchase_Price AS purchase,
             Rate          AS sale
      FROM Tbl_Invoice_Detail
      WHERE invoice_id = ?
    ");
    $d->bind_param('i', $invoice_id);
    $d->execute();
    $line_items = $d->get_result()->fetch_all(MYSQLI_ASSOC);
}

//////////////////////////////////////////
// 3) Fetch products (+ category)
//////////////////////////////////////////
$prodRes  = $conn->query("
  SELECT p.Product_id,
         p.Barcode,
         p.Product_name,
         p.Stock,
         p.Purchase_price,
         p.Sale_price,
         c.Category_name
    FROM Tbl_Product p
    JOIN Tbl_Category c USING(Category_id)
   ORDER BY p.Product_name
");
$products = $prodRes->fetch_all(MYSQLI_ASSOC);

//////////////////////////////////////////
// 4) Handle insert/update sale
//////////////////////////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_sale'])) {
    // decode line items JSON
    $items     = json_decode($_POST['items_json'], true);
    $subTotal  = (float)$_POST['sub_total'];
    $discPct   = (float)$_POST['disc_amt'];
    $sgstPct   = (float)$_POST['sgst_pct'];
    $cgstPct   = (float)$_POST['cgst_pct'];
    $sgstAmt   = (float)$_POST['sgst_amt'];
    $cgstAmt   = (float)$_POST['cgst_amt'];
    $grandTot  = (float)$_POST['grand_total'];
    $payment   = $_POST['payment_type'];
    $paid      = (int)$_POST['paid_amt'];
    $due       = (int)$_POST['due_amt'];

    if (!empty($_POST['invoice_id'])) {
        // ---- UPDATE existing invoice ----
        $invId = (int)$_POST['invoice_id'];

        // restore stock from old details
        $rst = $conn->prepare("
          SELECT Product_id, Qty
            FROM Tbl_Invoice_Detail
           WHERE invoice_id = ?
        ");
        $rst->bind_param('i', $invId);
        $rst->execute();
        $oldDetails = $rst->get_result()->fetch_all(MYSQLI_ASSOC);
        $restoreStock = $conn->prepare("
          UPDATE Tbl_Product
             SET Stock = Stock + ?
           WHERE Product_id = ?
        ");
        foreach ($oldDetails as $od) {
            $restoreStock->bind_param('ii', $od['Qty'], $od['Product_id']);
            $restoreStock->execute();
        }

        // update header (five d, one s, three i)
        $uh = $conn->prepare("
          UPDATE Tbl_invoice
             SET Sub_total=?, Discount=?, sgst=?, cgst=?, Total=?,
                 Payment_type=?, Paid=?, Due=?
           WHERE Invoice_id=?
        ");
        $uh->bind_param(
          'dddddsiii',
          $subTotal,
          $discPct,
          $sgstPct,
          $cgstPct,
          $grandTot,
          $payment,
          $paid,
          $due,
          $invId
        );
        $uh->execute();

        // delete old details
        $conn->query("DELETE FROM Tbl_Invoice_Detail WHERE invoice_id = $invId");

        // insert new details + subtract stock
        $dstmt       = $conn->prepare("
          INSERT INTO Tbl_Invoice_Detail
            (invoice_id, Category, Barcode, Product_id,
             Product_name, Qty, Purchase_Price, Rate)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $updateStock = $conn->prepare("
          UPDATE Tbl_Product
             SET Stock = Stock - ?
           WHERE Product_id = ?
        ");
        foreach ($items as $it) {
            $dstmt->bind_param(
              'issisidd',
              $invId,
              $it['category'],
              $it['barcode'],
              $it['pid'],
              $it['name'],
              $it['qty'],
              $it['purchase'],
              $it['sale']
            );
            $dstmt->execute();
            $updateStock->bind_param('ii', $it['qty'], $it['pid']);
            $updateStock->execute();
        }

    } else {
        // ---- NEW SALE INSERT ----
        // bind: five d, one s, two i
        $stmt = $conn->prepare("
          INSERT INTO Tbl_invoice
            (Sub_total, Discount, sgst, cgst, Total, Payment_type, Paid, Due)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
          'dddddsii',
          $subTotal,
          $discPct,
          $sgstPct,
          $cgstPct,
          $grandTot,
          $payment,
          $paid,
          $due
        );
        $stmt->execute();
        $newId = $conn->insert_id;

        $dstmt       = $conn->prepare("
          INSERT INTO Tbl_Invoice_Detail
            (invoice_id, Category, Barcode, Product_id,
             Product_name, Qty, Purchase_Price, Rate)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $updateStock = $conn->prepare("
          UPDATE Tbl_Product
             SET Stock = Stock - ?
           WHERE Product_id = ?
        ");
        foreach ($items as $it) {
            $dstmt->bind_param(
              'issisidd',
              $newId,
              $it['category'],
              $it['barcode'],
              $it['pid'],
              $it['name'],
              $it['qty'],
              $it['purchase'],
              $it['sale']
            );
            $dstmt->execute();
            $updateStock->bind_param('ii', $it['qty'], $it['pid']);
            $updateStock->execute();
        }
    }

    header('Location: orderlist.php');
    exit;
}

$pageTitle = $editing ? "Edit Sale #{$invoice_id}" : 'New POS';
include __DIR__ . '/templates/header.php';
?>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white d-flex align-items-center">
    <a href="orderlist.php" class="btn btn-sm btn-secondary me-3">&larr; Back</a>
    <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
  </div>
  <div class="card-body p-3 small">

    <form method="post" id="pos-form">
      <input type="hidden" name="complete_sale" value="1">
      <?php if ($editing): ?>
        <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>">
      <?php endif; ?>

      <div class="row gx-4 gy-3">

        <!-- LEFT: Scanner, selector, line-items -->
        <div class="col-12 col-lg-8">
          <div class="bg-light rounded p-3 h-100 d-flex flex-column">

            <div class="mb-3">
              <div class="input-group input-group-sm small">
                <span class="input-group-text"><i class="bi-upc-scan"></i></span>
                <input
                  id="scan_barcode"
                  type="text"
                  class="form-control form-control-sm small"
                  placeholder="Scan or enter barcode"
                  autocomplete="off">
              </div>
            </div>

            <div class="mb-3">
              <select id="select_product" class="form-select form-select-sm small">
                <option value="">Select or search product…</option>
                <?php foreach ($products as $p): ?>
                  <option
                    value="<?= $p['Product_id'] ?>"
                    data-barcode="<?= htmlspecialchars($p['Barcode']) ?>"
                    data-category="<?= htmlspecialchars($p['Category_name']) ?>"
                    data-purchase="<?= $p['Purchase_price'] ?>"
                    data-sale="<?= $p['Sale_price'] ?>"
                    data-stock="<?= $p['Stock'] ?>">
                    <?= htmlspecialchars($p['Product_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="table-responsive overflow-auto flex-grow-1" style="max-height:400px;">
              <table class="table table-hover table-sm small mb-0">
                <thead class="table-light small">
                  <tr>
                    <th>Barcode</th>
                    <th>Product</th>
                    <th>Stock</th>
                    <th>Purchase</th>
                    <th>Sale</th>
                    <th>QTY</th>
                    <th>Total</th>
                    <th>Del</th>
                  </tr>
                </thead>
                <tbody id="pos-items">
                  <!-- JS injects rows here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- RIGHT: Summary + Payment -->
        <div class="col-12 col-lg-4">
          <div class="bg-light rounded p-3 h-100 d-flex flex-column small">

            <div class="flex-grow-1">
              <div class="d-flex mb-2 small">
                <div class="flex-grow-1">SUBTOTAL (Rs)</div>
                <div class="text-end">Rs <span id="subtotal">0.00</span></div>
              </div>
              <hr class="my-2">

              <div class="row mb-2 small">
                <div class="col-6">
                  <label class="form-label">DISCOUNT (%)</label>
                  <input type="number" id="disc_pct" class="form-control form-control-sm small" value="0">
                </div>
                <div class="col-6">
                  <label class="form-label">DISCOUNT (Rs)</label>
                  <input type="text" id="disc_amt" class="form-control form-control-sm small" readonly>
                </div>
              </div>

              <div class="row mb-2 small">
                <div class="col-6">
                  <label class="form-label">SGST (%)</label>
                  <input type="number" id="sgst_pct" class="form-control form-control-sm small" value="0">
                </div>
                <div class="col-6">
                  <label class="form-label">CGST (%)</label>
                  <input type="number" id="cgst_pct" class="form-control form-control-sm small" value="0">
                </div>
              </div>

              <div class="row mb-3 small">
                <div class="col-6">
                  <input type="text" id="sgst_amt" class="form-control form-control-sm small" readonly>
                </div>
                <div class="col-6">
                  <input type="text" id="cgst_amt" class="form-control form-control-sm small" readonly>
                </div>
              </div>

              <hr class="my-2">

              <div class="d-flex mb-3 small">
                <div class="flex-grow-1">TOTAL (Rs)</div>
                <div class="text-end">Rs <span id="grand_total">0.00</span></div>
              </div>
            </div>

            <hr>

            <!-- PAYMENT -->
            <div class="mb-3 small">
              <label class="form-label">Payment Type</label>
              <div class="d-flex gap-3 small">
                <div class="form-check form-check-sm">
                  <input class="form-check-input" type="radio" name="payment_type" id="pay_cash" value="Cash">
                  <label class="form-check-label small" for="pay_cash">Cash</label>
                </div>
                <div class="form-check form-check-sm">
                  <input class="form-check-input" type="radio" name="payment_type" id="pay_card" value="Card">
                  <label class="form-check-label small" for="pay_card">Card</label>
                </div>
                <div class="form-check form-check-sm">
                  <input class="form-check-input" type="radio" name="payment_type" id="pay_check" value="Check">
                  <label class="form-check-label small" for="pay_check">Check</label>
                </div>
              </div>
            </div>

            <div class="row mb-3 small">
              <div class="col-6">
                <label class="form-label">PAID (Rs)</label>
                <input type="number" id="paid_amt" class="form-control form-control-sm small" value="0">
              </div>
              <div class="col-6">
                <label class="form-label">DUE (Rs)</label>
                <input type="text" id="due_amt" class="form-control form-control-sm small" readonly>
              </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="items_json"   id="items_json">
            <input type="hidden" name="sub_total"    id="sub_total">
            <input type="hidden" name="disc_amt"     id="disc_amt_h">
            <input type="hidden" name="sgst_pct"     id="sgst_pct_h">
            <input type="hidden" name="cgst_pct"     id="cgst_pct_h">
            <input type="hidden" name="sgst_amt"     id="sgst_amt_h">
            <input type="hidden" name="cgst_amt"     id="cgst_amt_h">
            <input type="hidden" name="grand_total"  id="grand_total_h">
            <input type="hidden" name="paid_amt"     id="paid_amt_h">
            <input type="hidden" name="due_amt"      id="due_amt_h">

            <button type="button" id="complete_sale" class="btn btn-success btn-sm w-100 small">
              Complete Sale
            </button>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script>
  const products     = <?= json_encode($products) ?>;
  const initialItems = <?= json_encode($line_items) ?>;
  const invoiceHdr   = <?= json_encode($invoice_hdr) ?>;
  const tbody        = document.getElementById('pos-items');

  function formatNum(x) {
    return parseFloat(x||0).toFixed(2);
  }

  function addProduct(pid, qty = 1) {
    const p = products.find(r => r.Product_id == pid);
    if (!p) return;
    let tr = tbody.querySelector(`tr[data-pid="${pid}"]`);
    if (tr) {
      const qin = tr.querySelector('.qty-input');
      qin.value = parseInt(qin.value) + qty;
      updateSummary();
      return;
    }
    tr = document.createElement('tr');
    tr.dataset.pid      = p.Product_id;
    tr.dataset.category = p.Category_name;
    tr.dataset.barcode  = p.Barcode;
    tr.innerHTML = `
      <td><input class="form-control form-control-sm small" type="text" value="${p.Barcode}" readonly></td>
      <td><span class="badge bg-dark small">${p.Product_name}</span></td>
      <td><span class="badge bg-info small">${p.Stock}</span></td>
      <td><span class="badge bg-primary small">${formatNum(p.Purchase_price)}</span></td>
      <td><span class="badge bg-warning small">${formatNum(p.Sale_price)}</span></td>
      <td><input class="form-control form-control-sm small qty-input" type="number" min="1" value="${qty}"></td>
      <td><span class="badge bg-success small total-badge">${formatNum(p.Sale_price * qty)}</span></td>
      <td><button class="btn btn-sm btn-danger del-btn small"><i class="bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    updateSummary();
  }

  function updateSummary() {
    let sub = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
      const unit = parseFloat(tr.querySelector('.badge.bg-warning').textContent) || 0;
      const qty  = parseInt(tr.querySelector('.qty-input').value) || 0;
      const tot  = unit * qty;
      tr.querySelector('.total-badge').textContent = formatNum(tot);
      sub += tot;
    });
    document.getElementById('subtotal').textContent = formatNum(sub);

    const dPct = parseFloat(disc_pct.value) || invoiceHdr?.disc_pct || 0;
    disc_amt.value = formatNum(sub * dPct/100);

    const sPct = parseFloat(sgst_pct.value) || invoiceHdr?.sgst_pct || 0;
    const cPct = parseFloat(cgst_pct.value) || invoiceHdr?.cgst_pct || 0;
    const dAmt = sub * dPct/100;
    const sAmt = (sub - dAmt) * sPct/100;
    const cAmt = (sub - dAmt) * cPct/100;
    sgst_amt.value = formatNum(sAmt);
    cgst_amt.value = formatNum(cAmt);

    const gTot = sub - dAmt + sAmt + cAmt;
    document.getElementById('grand_total').textContent = formatNum(gTot);

    const paid = parseFloat(paid_amt.value) || invoiceHdr?.paid_amt || 0;
    const due  = gTot - paid;
    due_amt.value = formatNum(due < 0 ? 0 : due);

    sub_total.value     = sub;
    disc_amt_h.value    = sub * dPct/100;
    sgst_pct_h.value    = sPct;
    cgst_pct_h.value    = cPct;
    sgst_amt_h.value    = sAmt;
    cgst_amt_h.value    = cAmt;
    grand_total_h.value = gTot;
    paid_amt_h.value    = paid;
    due_amt_h.value     = parseFloat(due_amt.value);
  }

  // Bind events
  select_product.addEventListener('change', e => {
    if (e.target.value) { addProduct(e.target.value); e.target.value = ''; }
  });
  scan_barcode.addEventListener('keypress', e => {
    if (e.key === 'Enter') {
      const code = e.target.value.trim();
      const prod = products.find(p => p.Barcode === code);
      if (prod) addProduct(prod.Product_id);
      e.target.value = '';
    }
  });
  tbody.addEventListener('input', e => {
    if (e.target.classList.contains('qty-input')) updateSummary();
  });
  tbody.addEventListener('click', e => {
    if (e.target.closest('.del-btn')) {
      e.target.closest('tr').remove();
      updateSummary();
    }
  });
  [disc_pct, sgst_pct, cgst_pct, paid_amt].forEach(el =>
    el.addEventListener('input', updateSummary)
  );

  complete_sale.addEventListener('click', e => {
    e.preventDefault();
    const rows = [];
    tbody.querySelectorAll('tr').forEach(tr => {
      rows.push({
        pid:      +tr.dataset.pid,
        category: tr.dataset.category,
        barcode:  tr.dataset.barcode,
        name:     tr.querySelector('.badge.bg-dark').textContent,
        qty:      +tr.querySelector('.qty-input').value,
        purchase: +tr.querySelector('.badge.bg-primary').textContent,
        sale:     +tr.querySelector('.badge.bg-warning').textContent
      });
    });
    if (!rows.length) return alert('Add at least one product.');
    items_json.value = JSON.stringify(rows);
    updateSummary();
    document.getElementById('pos-form').submit();
  });

  // Initialize form
  initialItems.forEach(it => addProduct(it.pid, it.qty));
  if (invoiceHdr) {
    disc_pct.value    = invoiceHdr.disc_pct;
    sgst_pct.value    = invoiceHdr.sgst_pct;
    cgst_pct.value    = invoiceHdr.cgst_pct;
    paid_amt.value    = invoiceHdr.paid_amt;
    document.querySelector(`input[name="payment_type"][value="${invoiceHdr.Payment_type}"]`).checked = true;
  }
  updateSummary();
</script>
