<?php
// C:\xampp\htdocs\Barcode\views\tax.php

// 1) Auth & DB
include_once __DIR__ . '/../auth/validate.php';
include_once __DIR__ . '/../config/db.php';

$error     = '';
$success   = '';
$editing   = false;
$sgst      = '';
$cgst      = '';
$discount  = '';

// Handle “Save”
if (isset($_POST['save'])) {
    $sgst     = trim($_POST['sgst']);
    $cgst     = trim($_POST['cgst']);
    $discount = trim($_POST['discount']);
    if ($sgst === '' || $cgst === '' || $discount === '') {
        $error = 'All fields are required.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO Tbl_Taxdis (sgst, cgst, discount)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ddd', $sgst, $cgst, $discount);
        if ($stmt->execute()) {
            $success = 'Tax & discount added.';
            $sgst = $cgst = $discount = '';
        } else {
            $error = 'Insert failed: ' . $conn->error;
        }
    }
}

// Handle “Edit” request
if (isset($_GET['edit'])) {
    $editing  = true;
    $id       = (int)$_GET['edit'];
    $res      = $conn->query("SELECT * FROM Tbl_Taxdis WHERE Taxdis_id=$id LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $sgst     = $row['sgst'];
        $cgst     = $row['cgst'];
        $discount = $row['discount'];
    }
}

// Handle “Update”
if (isset($_POST['update'])) {
    $id       = (int)$_POST['id'];
    $sgst     = trim($_POST['sgst']);
    $cgst     = trim($_POST['cgst']);
    $discount = trim($_POST['discount']);
    if ($sgst === '' || $cgst === '' || $discount === '') {
        $error = 'All fields are required.';
    } else {
        $stmt = $conn->prepare("
            UPDATE Tbl_Taxdis
               SET sgst=?, cgst=?, discount=?
             WHERE Taxdis_id=?
        ");
        $stmt->bind_param('dddi', $sgst, $cgst, $discount, $id);
        if ($stmt->execute()) {
            $success = 'Tax & discount updated.';
            $editing = false;
            $sgst = $cgst = $discount = '';
        } else {
            $error = 'Update failed: ' . $conn->error;
        }
    }
}

// Fetch all records
$taxRes = $conn->query("SELECT * FROM Tbl_Taxdis ORDER BY Taxdis_id ASC");
$taxes  = $taxRes->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Tax And Discount Form';
include __DIR__ . '/templates/header.php';
?>
<!-- DataTables CSS -->
<link 
    rel="stylesheet" 
    href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"
    integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" 
    crossorigin="anonymous"
/>

<div class="card mt-3 shadow-sm">
    <div class="card-header">
        <h1 class="h5 mb-0"><?= htmlentities($pageTitle) ?></h1>
    </div>
    <div class="card-body">
        <div class="row gx-4 gy-3">
            <!-- FORM COLUMN -->
            <div class="col-12 col-md-4 mb-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <form method="post" action="tax.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label for="sgst" class="form-label">SGST (%)</label>
                        <input
                            type="number"
                            step="0.01"
                            id="sgst"
                            name="sgst"
                            class="form-control"
                            placeholder="Enter SGST"
                            required
                            value="<?= htmlspecialchars($sgst) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cgst" class="form-label">CGST (%)</label>
                        <input
                            type="number"
                            step="0.01"
                            id="cgst"
                            name="cgst"
                            class="form-control"
                            placeholder="Enter CGST"
                            required
                            value="<?= htmlspecialchars($cgst) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="discount" class="form-label">Discount (%)</label>
                        <input
                            type="number"
                            step="0.01"
                            id="discount"
                            name="discount"
                            class="form-control"
                            placeholder="Enter Discount"
                            required
                            value="<?= htmlspecialchars($discount) ?>">
                    </div>
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                            <a href="tax.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php endif; ?>
                </form>
            </div>
            <!-- TABLEc COLUMN -->
            <div class="col-12 col-md-8">
                <table
                    id="tax-table"
                    class="table table-hover table-striped table-sm mb-0"
                    style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>SGST (%)</th>
                            <th>CGST (%)</th>
                            <th>Discount (%)</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taxes as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($t['sgst']) ?></td>
                            <td><?= htmlspecialchars($t['cgst']) ?></td>
                            <td><?= htmlspecialchars($t['discount']) ?></td>
                            <td>
                                <a
                                    href="tax.php?edit=<?= $t['Taxdis_id'] ?>"
                                    class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" integrity="sha384-4fjK2ZAHF6jzwz3aH8cE3DJAHrfswnvivEus8mTBihTQM0RNpcB1mum+2Q2H7tD7" crossorigin="anonymous"></script>
<script>
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded');
} else {
    $(document).ready(function() {
        $('#tax-table').DataTable({
            scrollY:        '300px',
            scrollCollapse: true,
            paging:         false,
            info:           false,
            lengthChange:   false,
            searching:      false,
            ordering:       true,
            dom:            't'
        });
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>