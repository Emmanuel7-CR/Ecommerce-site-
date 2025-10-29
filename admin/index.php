<?php
require_once __DIR__.'/header.php';

// Stats
// total users
$res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$usersCount = $res->fetch_assoc()['cnt'] ?? 0;

// total products
$res = $conn->query("SELECT COUNT(*) AS cnt FROM products");
$productsCount = $res->fetch_assoc()['cnt'] ?? 0;

// orders today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at)=?");
$stmt->bind_param('s', $today);
$stmt->execute();
$ordersToday = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// total sales last 30 days
$from = date('Y-m-d H:i:s', strtotime('-30 days'));
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS tot FROM orders WHERE created_at >= ? AND status='completed'");
$stmt->bind_param('s', $from);
$stmt->execute();
$sales30 = $stmt->get_result()->fetch_assoc()['tot'] ?? 0;
$stmt->close();
?>

<h1>Admin Dashboard</h1>

<div class="row gy-3">
  <div class="col-md-3">
    <div class="card p-3">
      <h5>Users</h5>
      <p class="h3"><?= (int)$usersCount ?></p>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <h5>Products</h5>
      <p class="h3"><?= (int)$productsCount ?></p>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <h5>Orders Today</h5>
      <p class="h3"><?= (int)$ordersToday ?></p>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <h5>Sales (30d)</h5>
      <p class="h3">₦<?= number_format((float)$sales30, 2) ?></p>
    </div>
  </div>
</div>

<div class="mt-4">
  <a class="btn btn-outline-primary" href="products.php">Manage Products</a>
  <a class="btn btn-outline-secondary" href="orders.php">Manage Orders</a>
  <a class="btn btn-outline-info" href="users.php">Manage Users</a>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
