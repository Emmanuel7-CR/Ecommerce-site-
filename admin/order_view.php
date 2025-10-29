<?php
require_once __DIR__.'/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: orders.php'); exit;
}

// handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['status'] ?? '';
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param('si', $new, $id);
    $stmt->execute();
    $stmt->close();
    flash('Order status updated.');
    header('Location: order_view.php?id=' . $id);
    exit;
}

// fetch order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// items
$stmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h1>Order #<?= (int)$order['id'] ?> — <?= e($order['reference']) ?></h1>

<div class="mb-3">
  <strong>Status:</strong> <?= e($order['status']) ?>
</div>

<table class="table">
  <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Line</th></tr></thead>
  <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= e($it['name']) ?></td>
        <td><?= (int)$it['quantity'] ?></td>
        <td>₦<?= number_format($it['price'],2) ?></td>
        <td>₦<?= number_format($it['price'] * $it['quantity'],2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="mb-3">
  <form method="post">
    <select name="status" class="form-select d-inline w-auto">
      <?php foreach(['pending','processing','completed','cancelled','refunded'] as $s): ?>
        <option value="<?= $s ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Update</button>
  </form>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
