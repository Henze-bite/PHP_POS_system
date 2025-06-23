<?php
// C:\xampp\htdocs\Barcode\views\dashboard.php

// 1) Auth guard & DB
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) Fetch summary metrics with prepared statements
try {
    // Total users
    $userStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Tbl_user");
    $userStmt->execute();
    $userCnt = $userStmt->get_result()->fetch_assoc()['cnt'];
    $userStmt->close();

    // Total categories
    $catStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Tbl_Category");
    $catStmt->execute();
    $catCnt = $catStmt->get_result()->fetch_assoc()['cnt'];
    $catStmt->close();

    // Total products
    $prodStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Tbl_Product");
    $prodStmt->execute();
    $prodCnt = $prodStmt->get_result()->fetch_assoc()['cnt'];
    $prodStmt->close();

    // Total orders
    $orderStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Tbl_invoice");
    $orderStmt->execute();
    $orderCnt = $orderStmt->get_result()->fetch_assoc()['cnt'];
    $orderStmt->close();

    // Total revenue
    $revStmt = $conn->prepare("SELECT COALESCE(SUM(Total),0) AS revenue FROM Tbl_invoice");
    $revStmt->execute();
    $revenue = $revStmt->get_result()->fetch_assoc()['revenue'];
    $revStmt->close();

    // Selected date (from GET or default to today)
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $selectedDate = date('Y-m-d');
    }

    // Sales for selected date
    $todayStmt = $conn->prepare("
        SELECT COUNT(*) AS cnt, COALESCE(SUM(Total),0) AS sum
        FROM Tbl_invoice
        WHERE DATE(Order_date) = ?
    ");
    $todayStmt->bind_param('s', $selectedDate);
    $todayStmt->execute();
    $today = $todayStmt->get_result()->fetch_assoc();
    $todayStmt->close();

    // Last 7 days sales for chart
    $chartStmt = $conn->prepare("
        SELECT DATE(Order_date) AS order_date, COALESCE(SUM(Total),0) AS sum
        FROM Tbl_invoice
        WHERE DATE(Order_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
        GROUP BY DATE(Order_date)
        ORDER BY Order_date ASC
    ");
    $chartStmt->execute();
    $chartRes = $chartStmt->get_result();
    $chartData = array_fill(0, 8, 0);
    $chartLabels = [];
    $todayDate = new DateTime();
    for ($i = 7; $i >= 0; $i--) {
        $chartLabels[] = $i === 0 ? 'Today' : ($i === 1 ? 'Yesterday' : $i . 'd ago');
    }
    $chartLabels = array_reverse($chartLabels);
    while ($row = $chartRes->fetch_assoc()) {
        $diff = (new DateTime($row['order_date']))->diff($todayDate)->days;
        if ($diff <= 7) {
            $chartData[7 - $diff] = (float)$row['sum'];
        }
    }
    $chartStmt->close();

    // Days with sales for calendar indicators (last 30 days)
    $salesDaysStmt = $conn->prepare("
        SELECT DATE(Order_date) AS order_date, COUNT(*) AS order_count
        FROM Tbl_invoice
        WHERE DATE(Order_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
        GROUP BY DATE(Order_date)
    ");
    $salesDaysStmt->execute();
    $salesDaysRes = $salesDaysStmt->get_result();
    $salesDays = [];
    while ($row = $salesDaysRes->fetch_assoc()) {
        $salesDays[$row['order_date']] = $row['order_count'];
    }
    $salesDaysStmt->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching data.");
}

$pageTitle = 'Dashboard';
include __DIR__ . '/templates/header.php';
?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Custom CSS -->
<style>
/* Card Hover Effect */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12) !important;
}
.card-footer a:hover {
    text-decoration: underline !important;
}

/* Calendar Card */
.calendar-card {
    border: none !important;
    background: linear-gradient(135deg, #0d6efd 0%, #2dd4bf 100%);
    animation: fadeIn 0.6s ease-in;
}
.calendar-card .card-header {
    background: transparent;
    border: none;
    padding: 0.75rem 1.25rem;
}
.calendar-card .card-body {
    background: #fff;
    border-radius: 12px;
    margin: 0 10px 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@media (max-width: 576px) {
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<div class="container-fluid">
  <div class="row gx-3 gy-3 mt-3">
    <!-- Users Card -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-primary h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small">Users</div>
          <div class="h2 mb-0"><?= number_format($userCnt) ?></div>
        </div>
        <div class="card-footer bg-transparent">
          <a href="registration.php" class="text-white text-decoration-none small">
            Manage Users →
          </a>
        </div>
      </div>
    </div>
    <!-- Categories Card -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-success h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small">Categories</div>
          <div class="h2 mb-0"><?= number_format($catCnt) ?></div>
        </div>
        <div class="card-footer bg-transparent">
          <a href="category.php" class="text-white text-decoration-none small">
            Manage Categories →
          </a>
        </div>
      </div>
    </div>
    <!-- Products Card -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-warning h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small">Products</div>
          <div class="h2 mb-0"><?= number_format($prodCnt) ?></div>
        </div>
        <div class="card-footer bg-transparent">
          <a href="product.php" class="text-white text-decoration-none small">
            Manage Products →
          </a>
        </div>
      </div>
    </div>
    <!-- Orders Card -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-danger h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small">Total Orders</div>
          <div class="h2 mb-0"><?= number_format($orderCnt) ?></div>
        </div>
        <div class="card-footer bg-transparent">
          <a href="orderlist.php" class="text-white text-decoration-none small">
            View Orders →
          </a>
        </div>
      </div>
    </div>
    <!-- Total Revenue Card -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small text-secondary">Total Revenue</div>
          <div class="h3">Rs <?= number_format($revenue, 2) ?></div>
        </div>
      </div>
    </div>
    <!-- Selected Date Sales Card -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="card-title small text-secondary">
            Sales on <?= htmlspecialchars($selectedDate) ?>
          </div>
          <div class="h4"><?= number_format($today['cnt']) ?> orders</div>
          <div class="h5">Rs <?= number_format($today['sum'], 2) ?></div>
        </div>
      </div>
    </div>
    <!-- Live Clock Card -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column justify-content-center align-items-center">
          <div class="small text-secondary mb-2">Current Time</div>
          <div id="live-clock" class="h2 fw-bold">--:--:--</div>
        </div>
      </div>
    </div>
    <!-- Calendar Card -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 calendar-card">
        <div class="card-header">
          <div class="small text-white text-uppercase fw-bold mb-0">Select Date</div>
        </div>
        <div class="card-body d-flex justify-content-center align-items-center">
          <div id="dashboard-calendar"></div>
        </div>
      </div>
    </div>
    <!-- Sales Trend Chart Card -->
    <div class="col-12 col-lg-8">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="small text-secondary mb-2">Sales Trend (Last 8 Days)</div>
          <canvas id="salesChart" style="max-width:100%;height:250px"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Live Clock
  function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent =
      now.toLocaleTimeString('en-GB', { hour12: false });
  }
  setInterval(updateClock, 1000);
  updateClock();

  // Inline Calendar
  const calendar = flatpickr("#dashboard-calendar", {
    inline: true,
    defaultDate: '2024-10-01', // Set to October 2024 to match the image
    onChange: (_, d) => window.location.href = 'dashboard.php?date=' + d,
    maxDate: 'today',
    disableMobile: true,
    ariaDateFormat: 'F j, Y',
    prevArrow: '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>',
    nextArrow: '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>',
    onDayCreate: (dObj, dStr, fp, dayElem) => {
      const today = new Date();
      if (dayElem.dateObj.toDateString() === today.toDateString()) {
        dayElem.classList.add('today');
      }
      const dateStr = dayElem.dateObj.toISOString().split('T')[0];
      const salesDays = <?= json_encode($salesDays) ?>;
      if (salesDays[dateStr]) {
        dayElem.classList.add('has-sales');
        if (salesDays[dateStr] > 5) {
          dayElem.classList.add('has-sales-high');
        }
      }
    }
  });

  // Schedule midnight refresh for "today" highlight
  (function scheduleMidnightRefresh() {
    const now = new Date();
    const msUntilMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1) - now;
    setTimeout(() => {
      calendar.redraw();
      scheduleMidnightRefresh();
    }, msUntilMidnight + 1000);
  })();

  // Sales Trend Chart
  (function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Revenue',
          data: <?= json_encode($chartData) ?>,
          tension: 0.4,
          borderColor: '#4bc0c0',
          backgroundColor: 'rgba(75,192,192,0.2)',
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { callback: v => 'Rs ' + v.toLocaleString() }
          },
          x: { grid: { display: false } }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: { label: ctx => 'Rs ' + ctx.parsed.y.toLocaleString() }
          }
        }
      }
    });
  })();
</script>