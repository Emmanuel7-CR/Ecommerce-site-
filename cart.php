<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        flash('Invalid request.');
        header('Location: ' . BASE_URL . 'cart.php');
        exit;
    }

    // --------  ADD  --------
    if (($_POST['action'] ?? '') === 'add') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty        = max(1, (int)($_POST['quantity'] ?? 1));

        if ($product_id > 0) {
            $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($product) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $qty;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'product_id' => $product['id'],
                        'name'       => $product['name'],
                        'price'      => (float)$product['price'],
                        'image'      => $product['image'],
                        'quantity'   => $qty,
                    ];
                }
                flash('Item added to cart.');
            } else {
                flash('Product not found.');
            }
        } else {
            flash('Invalid product.');
        }
        header('Location: ' . BASE_URL . 'cart.php');
        exit;
    }
    // --------  END ADD  --------

    // -------- REMOVE --------
    if (isset($_POST['action']) && str_starts_with($_POST['action'], 'remove_')) {
        $pid = (int)str_replace('remove_', '', $_POST['action']);
        unset($_SESSION['cart'][$pid]);
        flash('Item removed from cart.');
        header('Location: ' . BASE_URL . 'cart.php');
        exit;
    }
    // -------- END REMOVE --------

    // Update quantities
    if (($_POST['action'] ?? '') === 'update') {
        $quantities = $_POST['quantities'] ?? [];
        foreach ($quantities as $pid => $qty) {
            $pid = (int)$pid;
            $qty = max(0, (int)$qty);
            if ($qty === 0) {
                unset($_SESSION['cart'][$pid]);
            } elseif (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid]['quantity'] = $qty;
            }
        }
        flash('Cart updated.');
        header('Location: ' . BASE_URL . 'cart.php');
        exit;
    }
}

// Compute subtotal
$subtotal = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>
<h1>Shopping Cart</h1>

<?php if ($msg = get_flash()): ?>
    <div class="alert alert-info"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (empty($_SESSION['cart'])): ?>
    <div class="alert alert-secondary">
        Your cart is empty. <a href="<?= BASE_URL ?>index.php">Continue shopping</a>
    </div>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>cart.php" id="cart-form">
    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
    <input type="hidden" name="action" value="update">

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Line</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($_SESSION['cart'] as $pid => $item): ?>
                <tr>
                    <td class="align-middle">
                        <img src="<?= BASE_URL ?>assets/images/<?= e($item['image'] ?: 'placeholder.png') ?>"
                             style="width:64px;height:64px;object-fit:cover"
                             alt="<?= e($item['name']) ?>">
                        <?= e($item['name']) ?>
                    </td>
                    <td class="align-middle"><?= format_currency($item['price']) ?></td>
                    <td class="align-middle" style="width:120px">
                        <input type="number" name="quantities[<?= (int)$pid ?>]" value="<?= (int)$item['quantity'] ?>" min="0" class="form-control">
                    </td>
                    <td class="align-middle"><?= format_currency($item['price'] * $item['quantity']) ?></td>
                    <td class="align-middle">
                        <!-- Remove Button triggers modal -->
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#removeModal"
                                data-product-id="<?= (int)$pid ?>">
                            Remove
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="<?= BASE_URL ?>product.php" class="btn btn-outline-secondary">Continue shopping</a>
        <div class="text-end">
            <div class="mb-2">Subtotal: <strong><?= format_currency($subtotal) ?></strong></div>
            <button class="btn btn-primary">Update cart</button>
            <a href="<?= BASE_URL ?>checkout.php" class="btn btn-success ms-2">Proceed to checkout</a>
        </div>
    </div>
</form>

<!-- Remove Confirmation Modal -->
<div class="modal fade" id="removeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= BASE_URL ?>cart.php">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
        <input type="hidden" name="action" id="remove-action" value="">
        <div class="modal-header">
          <h5 class="modal-title">Remove Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to remove this item from your cart?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Remove</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Pass product ID into hidden input when modal opens
const removeModal = document.getElementById('removeModal');
removeModal.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget;
    let productId = button.getAttribute('data-product-id');
    let input = removeModal.querySelector('#remove-action');
    input.value = 'remove_' + productId;
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
