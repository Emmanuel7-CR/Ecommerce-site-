<?php
// cod_success.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

// Expect order reference in session (set by payment_cod.php before redirect)
if (empty($_SESSION['cod_order_reference'])) {
    flash('Invalid request.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$reference = $_SESSION['cod_order_reference'];
unset($_SESSION['cod_order_reference']); // consume it

// Fetch order details to display
$stmt = $conn->prepare("
    SELECT total_amount, shipping_full_name, shipping_city, shipping_state, created_at 
    FROM orders 
    WHERE reference = ? AND user_id = ?
");
$stmt->bind_param('si', $reference, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    flash('Order not found.');
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="alert alert-success text-center">
        <h2>✅ Order Placed Successfully!</h2>
        <p>Your order is confirmed and will be delivered soon.</p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <strong>Order Details</strong>
        </div>
        <div class="card-body">
            <p><strong>Order Reference:</strong> <?= e($reference) ?></p>
            <p><strong>Total Amount:</strong> <?= number_format($order['total_amount'], 2) ?> NGN</p>
            <p><strong>Delivery Address:</strong> 
                <?= e($order['shipping_full_name']) ?>, 
                <?= e($order['shipping_city']) ?>, 
                <?= e($order['shipping_state']) ?>
            </p>
            <p><strong>Order Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
            <p class="text-muted">You’ll pay on delivery.</p>
        </div>
    </div>

    <div class="text-center">
        <a href="<?= BASE_URL ?>account/orders.php" class="btn btn-primary">View All Orders</a>
        <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">Continue Shopping</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>