<?php
require_once __DIR__.'/header.php';

// pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// search simple
$search = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE name LIKE ?";
    $params[] = '%' . $search . '%';
}

// count
if ($where === '') {
    $total = $conn->query("SELECT COUNT(*) AS cnt FROM products")->fetch_assoc()['cnt'];
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM products $where");
    $stmt->bind_param('s', $params[0]);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}

$stmt = null;
if ($where === '') {
    $stmt = $conn->prepare("SELECT id, name, price, image, created_at FROM products ORDER BY created_at DESC LIMIT ?,?");
    $stmt->bind_param('ii', $offset, $perPage);
} else {
    $stmt = $conn->prepare("SELECT id, name, price, image, created_at FROM products $where ORDER BY created_at DESC LIMIT ?,?");
    $stmt->bind_param('sii', $params[0], $offset, $perPage);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pages = max(1, ceil($total / $perPage));
?>

<h1>Products</h1>

<div class="d-flex justify-content-between mb-3">
    <form class="d-flex" method="get" action="products.php">
        <input class="form-control me-2" name="q" placeholder="Search product" value="<?= e($search) ?>">
        <button class="btn btn-outline-secondary">Search</button>
    </form>
    <a class="btn btn-primary" href="product_edit.php">Add Product</a>
</div>

<table class="table table-striped">
    <thead>
      <tr><th>ID</th><th>Name</th><th>Price</th><th>Image</th><th>Created</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= e($p['name']) ?></td>
        <td><?= '₦' . number_format($p['price'], 2) ?></td>
        <td><img src="<?= BASE_URL ?>assets/images/<?= e($p['image'] ?: 'placeholder.png') ?>" style="height:40px;object-fit:cover"></td>
        <td><?= e($p['created_at']) ?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="product_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
          <a class="btn btn-sm btn-outline-danger" href="product_delete.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('Delete product?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
</table>

<nav>
  <ul class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?= $i===$page ? 'active': '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

<?php require_once __DIR__.'/footer.php'; ?>
