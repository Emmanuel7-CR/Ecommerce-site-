<?php
// payment_success.php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require a reference from Paystack
if (!isset($_GET['ref'])) {
    flash('Invalid payment reference.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$reference = $_GET['ref'];

// Verify Paystack transaction
if (!verify_paystack_transaction($reference)) {
    error_log("payment_success.php: Paystack verification returned FALSE for ref={$reference}");
    flash('Payment verification failed. Please contact support.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// If an order already exists for this reference, mark completed and return
$stmtCheck = $conn->prepare("SELECT id FROM orders WHERE reference = ?");
if ($stmtCheck === false) {
    error_log("payment_success.php: prepare stmtCheck failed: " . $conn->error);
    flash('Server error.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$stmtCheck->bind_param('s', $reference);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();

    $upd = $conn->prepare("UPDATE orders SET status = 'completed' WHERE reference = ?");
    if ($upd) {
        $upd->bind_param('s', $reference);
        $upd->execute();
        $upd->close();
    } else {
        error_log("payment_success.php: failed to prepare update: " . $conn->error);
    }

    // Clear cart and redirect
    unset($_SESSION['cart']);
    flash('Payment successful and order completed!');
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

$stmtCheck->close();

// Read shipping info from POST (preferred) or fallback to session
$shipping_full_name = trim($_POST['shipping_full_name'] ?? ($_SESSION['shipping_full_name'] ?? ''));
$shipping_email     = trim($_POST['shipping_email'] ?? ($_SESSION['shipping_email'] ?? ''));
$shipping_street    = trim($_POST['shipping_street'] ?? ($_SESSION['shipping_street'] ?? ''));
$shipping_city      = trim($_POST['shipping_city'] ?? ($_SESSION['shipping_city'] ?? ''));
$shipping_state     = trim($_POST['shipping_state'] ?? ($_SESSION['shipping_state'] ?? ''));
$shipping_phone     = trim($_POST['shipping_phone'] ?? ($_SESSION['shipping_phone'] ?? ''));

// Validate shipping presence
if (!$shipping_full_name || !$shipping_email || !$shipping_street || !$shipping_city || !$shipping_state || !$shipping_phone) {
    error_log("payment_success.php: missing shipping info for ref={$reference}");
    flash('Missing shipping information. Please contact support.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Ensure cart exists
if (empty($_SESSION['cart'])) {
    error_log("payment_success.php: cart empty for ref={$reference}");
    flash('Your cart is empty.');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// Compute total (include delivery fee)
$delivery_fee = 1000.00;
$subtotal = 0.00;
foreach ($_SESSION['cart'] as $item) {
    $price = (float)$item['price'];
    $qty = (int)$item['quantity'];
    $subtotal += $price * $qty;
}
$total = $subtotal + $delivery_fee;

// Build INSERT SQL (must match your orders table columns)
$sql = "
    INSERT INTO orders
        (user_id, total_amount, reference, status, created_at,
         shipping_full_name, shipping_email, shipping_street, shipping_city, shipping_state, shipping_phone)
    VALUES (?, ?, ?, 'completed', NOW(), ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("payment_success.php prepare failed: " . $conn->error);
    flash('Server error. Please try again later.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Prepare parameters in correct order
$user_id = (int)($_SESSION['user_id'] ?? 0);
$params = [
    $user_id,
    $total,
    $reference,
    $shipping_full_name,
    $shipping_email,
    $shipping_street,
    $shipping_city,
    $shipping_state,
    $shipping_phone
];

// Build types string: i (user_id), d (total), then s x (rest)
$types = 'i' . 'd' . str_repeat('s', count($params) - 2);

// call_user_func_array requires references
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = & $params[$i];
}

$bindResult = call_user_func_array([$stmt, 'bind_param'], $bind_names);
if ($bindResult === false) {
    error_log("payment_success.php bind_param failed: " . $stmt->error);
    flash('Server error. Please try again later.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

if (!$stmt->execute()) {
    error_log("payment_success.php execute failed: " . $stmt->error);
    flash('Payment verified, but failed to create order. Contact support.');
    $stmt->close();
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$order_id = $stmt->insert_id;
$stmt->close();

// Insert order_items
$stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
if ($stmtItem === false) {
    error_log("payment_success.php stmtItem prepare failed: " . $conn->error);
    flash('Server error inserting items. Contact support.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

foreach ($_SESSION['cart'] as $pid => $item) {
    $pid_int = (int)$pid;
    $qty = (int)$item['quantity'];
    $price = (float)$item['price'];

    if (!$stmtItem->bind_param('iiid', $order_id, $pid_int, $qty, $price)) {
        error_log("payment_success.php stmtItem bind_param failed: " . $stmtItem->error);
        continue;
    }
    if (!$stmtItem->execute()) {
        error_log("payment_success.php stmtItem execute failed for pid={$pid_int}: " . $stmtItem->error);
    }
}
$stmtItem->close();

// Clear cart and persist shipping details in session (useful for order view)
unset($_SESSION['cart']);
$_SESSION['shipping_full_name'] = $shipping_full_name;
$_SESSION['shipping_email'] = $shipping_email;
$_SESSION['shipping_street'] = $shipping_street;
$_SESSION['shipping_city'] = $shipping_city;
$_SESSION['shipping_state'] = $shipping_state;
$_SESSION['shipping_phone'] = $shipping_phone;

flash('Payment successful! Your order has been created.');
header('Location: ' . BASE_URL . 'account/orders.php');
exit;
