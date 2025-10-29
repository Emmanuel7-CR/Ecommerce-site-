<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only logged-in users (admins or the buyer) can view order details
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID.");
}

$order_id = (int) $_GET['id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Fetch order items
$stmt_items = $conn->prepare("
    SELECT oi.*, p.name AS product_name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$order_items_result = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo htmlspecialchars($order['id']); ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">Order Details (#<?php echo htmlspecialchars($order['id']); ?>)</h2>

    <!-- Order Information -->
    <div class="card mb-4">
        <div class="card-header">Order Information</div>
        <div class="card-body">
            <p><strong>Reference:</strong> <?php echo htmlspecialchars($order['reference']); ?></p>
            <p><strong>Status:</strong> 
                <span class="badge bg-<?php 
                    echo $order['status'] === 'completed' ? 'success' : 
                        ($order['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </p>
            <p><strong>Total Amount:</strong> ₦<?php echo number_format($order['total_amount'], 2); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="card mb-4">
        <div class="card-header">Customer Information</div>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
        </div>
    </div>

    <!-- Shipping Information -->
    <div class="card mb-4">
        <div class="card-header">Shipping Information</div>
        <div class="card-body">
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($order['shipping_full_name']); ?></p>
            <p><strong>Street:</strong> <?php echo htmlspecialchars($order['shipping_street']); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
            <p><strong>State:</strong> <?php echo htmlspecialchars($order['shipping_state']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card">
        <div class="card-header">Items in this Order</div>
        <div class="card-body">
            <?php if ($order_items_result->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price (₦)</th>
                            <th>Total (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $order_items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo (int) $item['quantity']; ?></td>
                                <td><?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No items found for this order.</p>
            <?php endif; ?>
        </div>
    </div>

    <a href="orders.php" class="btn btn-secondary mt-3">Back to Orders</a>
</div>
</body>
</html>
