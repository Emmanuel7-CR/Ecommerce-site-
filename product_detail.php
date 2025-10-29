<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// validate product_id
$id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if ($id <= 0) {
    error_log("Invalid product ID requested: " . $id);
    flash('Invalid product requested.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// fetch product including quantity and specs
$stmt = $conn->prepare("SELECT id, name, description, price, image, quantity, specs FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    flash('Product not found.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// determine image path
if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])) {
    $imgSrc = BASE_URL . 'uploads/' . htmlspecialchars($product['image']);
} else {
    $imgSrc = BASE_URL . 'assets/images/placeholder.png';
}

// parse specs (assuming JSON stored in DB)
$specs = [];
if (!empty($product['specs'])) {
    $specs = json_decode($product['specs'], true) ?: explode(',', $product['specs']);
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Product Card -->
            <div class="card mb-3">
                <div class="row g-0">
                    <div class="col-md-6">
                        <img src="<?= $imgSrc ?>" class="img-fluid rounded-start" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="card-body">
                            <h2 class="card-title"><?= htmlspecialchars($product['name']) ?></h2>
                            <p class="fs-4 text-success"><?= format_currency($product['price']) ?></p>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            <p><strong>Quantity left:</strong> <?= (int)$product['quantity'] ?></p>

                            <!-- Fixed form action -->
                            <form method="post" action="<?= BASE_URL ?>cart.php" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="number" name="quantity" value="1" min="1" class="form-control" style="width:100px">
                                <button type="submit" class="btn btn-success">Add to cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specifications Card -->
            <?php if (!empty($specs)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    Specifications
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach ($specs as $spec): ?>
                            <li><?= htmlspecialchars($spec) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
