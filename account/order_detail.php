<?php
// account/order_detail.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$user_id  = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// 1) Fetch order (with status) and make sure it belongs to current user
$stmt = $conn->prepare("
    SELECT o.id, o.reference, o.total_amount, o.created_at, o.status,
           ua.full_name, ua.street, ua.city, ua.state, ua.phone
    FROM orders AS o
    LEFT JOIN user_addresses AS ua ON ua.user_id = o.user_id AND ua.is_default = 1
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    flash('Order not found.');
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

// 2) Fetch items for this order (join with products)
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.id AS product_id, p.name, p.image
    FROM order_items AS oi
    JOIN products AS p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Order Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order Summary</h5>
                    <span class="badge 
                        <?= $order['status'] === 'completed' ? 'bg-success' : 
                            ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <p class="mb-1"><strong>Reference:</strong> <?= e($order['reference'] ?? '—') ?></p>
                            <p class="mb-1"><strong>Order Date:</strong> <?= e($order['created_at']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3 text-md-end">
                            <p class="fs-5 mb-1"><strong>Total:</strong> $<?= number_format((float)$order['total_amount'], 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Shipping Address</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($order['full_name'])): ?>
                        <p class="mb-0">
                            <?= e($order['full_name']) ?><br>
                            <?= e($order['street']) ?><br>
                            <?= e($order['city']) ?>, <?= e($order['state']) ?><br>
                            <small class="text-muted">Phone: <?= e($order['phone']) ?></small>
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No shipping address on file.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Items in Your Order</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-muted">No items found for this order.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= BASE_URL . 'uploads/' . e($item['image']) ?>" 
                                                 class="card-img-top" 
                                                 style="height:180px; object-fit:cover;" 
                                                 alt="<?= e($item['name']) ?>">
                                        <?php endif; ?>
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title mb-2"><?= e($item['name']) ?></h6>
                                            <p class="small text-muted mb-2">
                                                Qty: <?= (int)$item['quantity'] ?><br>
                                                Price: $<?= number_format((float)$item['price'], 2) ?>
                                            </p>
                                            <a href="<?= BASE_URL ?>product_detail.php?product_id=<?= (int)$item['product_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm mt-auto">See Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-end">
                <a href="<?= BASE_URL ?>account/orders.php" class="btn btn-secondary">Back to Orders</a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
