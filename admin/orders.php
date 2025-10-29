<?php
require_once __DIR__.'/header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$offset = ($page-1)*$per;

$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if ($statusFilter !== '') {
    $where = "WHERE status = ?";
    $params[] = $statusFilter;
}

$totalSql = $where === '' ? "SELECT COUNT(*) AS cnt FROM orders" : "SELECT COUNT(*) AS cnt FROM orders $where";
if ($where === '') {
    $total = $conn->query($totalSql)->fetch_assoc()['cnt'];
} else {
    $stmt = $conn->prepare($totalSql);
    $stmt->bind_param('s', $params[0]);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}

$sql = $where === '' ?
    "SELECT id, user_id, total_amount, reference, status, created_at FROM orders ORDER BY created_at DESC LIMIT ?,?" :
    "SELECT id, user_id, total_amount, reference, status, created_at FROM orders $where ORDER BY created_at DESC LIMIT ?,?";

if ($where === '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $offset, $per);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $params[0], $offset, $per);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pages = max(1, ceil($total / $per));
?>

<h1>Orders</h1>
<div class="mb-3">
  <a class="btn btn-outline-secondary" href="?">All</a>
  <a class="btn btn-outline-primary" href="?status=pending">Pending</a>
  <a class="btn btn-outline-success" href="?status=completed">Completed</a>
  <a class="btn btn-outline-warning" href="?status=processing">Processing</a>
  <a class="btn btn-outline-danger" href="?status=cancelled">Cancelled</a>
  <a class="btn btn-outline-info" href="export_orders.php">Export CSV</a>
</div>

<table class="table table-sm">
  <thead><tr><th>ID</th><th>Reference</th><th>Total</th><th>Status</th><th>Created</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= (int)$o['id'] ?></td>
        <td><?= e($o['reference']) ?></td>
        <td>₦<?= number_format($o['total_amount'], 2) ?></td>
        <td><?= e($o['status']) ?></td>
        <td><?= e($o['created_at']) ?></td>
        <td><a class="btn btn-sm btn-outline-primary" href="order_view.php?id=<?= (int)$o['id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<nav><ul class="pagination">
<?php for($i=1;$i<=$pages;$i++): ?>
  <li class="page-item <?= $i===$page ? 'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul></nav>

<?php require_once __DIR__.'/footer.php'; ?>
