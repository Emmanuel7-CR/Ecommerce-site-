<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$user_id = (int)$_SESSION['user_id'];

// Fetch all order items with product details
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.reference, o.status, o.created_at,
           p.id AS product_id, p.name AS product_name, p.image AS product_image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <h2 class="mb-4">My Orders</h2>

    <?php if (empty($order_items)): ?>
        <p>You have not placed any orders yet.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($order_items as $item): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="row g-0">
                            <!-- Product Image -->
                            <div class="col-4">
                                <img src="<?= BASE_URL . 'uploads/' . e($item['product_image']) ?>" 
                                     class="img-fluid rounded-start" 
                                     alt="<?= e($item['product_name']) ?>">
                            </div>

                            <!-- Product Info -->
                            <div class="col-8">
                                <div class="card-body">
                                    <h6 class="card-title mb-1"><?= e($item['product_name']) ?></h6>
                                    <p class="mb-1 text-muted">Order <?= (int)$item['order_id'] ?></p>
                                    <p class="mb-1">On <?= date("d-m", strtotime($item['created_at'])) ?></p>

                                    <!-- Status badge -->
                                    <p>
                                        <?php if ($item['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Delivered</span>
                                        <?php elseif ($item['status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </p>

                                    <div class="text-end">
                                       <a href="<?= BASE_URL ?>account/order_detail.php?order_id=<?= (int)$item['order_id'] ?>" 
   class="text-decoration-none text-primary fw-bold">
   See details →
</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
