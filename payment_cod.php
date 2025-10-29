<?php
// payment_cod.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// CSRF verification
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('Invalid request.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Ensure cart exists
if (empty($_SESSION['cart'])) {
    flash('Your cart is empty.');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

/*
 * Read shipping info from POST if provided (checkout now posts hidden inputs),
 * otherwise fall back to session values.
 */
$shipping_full_name = trim($_POST['shipping_full_name'] ?? ($_SESSION['shipping_full_name'] ?? ''));
$shipping_email     = trim($_POST['shipping_email'] ?? ($_SESSION['shipping_email'] ?? $_SESSION['user_email'] ?? ''));
$shipping_phone     = trim($_POST['shipping_phone'] ?? ($_SESSION['shipping_phone'] ?? ''));
$shipping_street    = trim($_POST['shipping_street'] ?? ($_SESSION['shipping_street'] ?? ''));
$shipping_city      = trim($_POST['shipping_city'] ?? ($_SESSION['shipping_city'] ?? ''));
$shipping_state     = trim($_POST['shipping_state'] ?? ($_SESSION['shipping_state'] ?? ''));

if (!$shipping_full_name || !$shipping_email || !$shipping_street || !$shipping_city || !$shipping_state || !$shipping_phone) {
    flash('Shipping information is incomplete.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Calculate total + delivery fee (delivery fee is NOT a separate DB column)
$total = 0.00;
foreach ($_SESSION['cart'] as $item) {
    // ensure numeric casting
    $price = (float)$item['price'];
    $qty = (int)$item['quantity'];
    $total += $price * $qty;
}
$delivery_fee = 1000.00;
$total += $delivery_fee;

// Generate unique reference
$reference = 'ECM-' . mt_rand(10000000, 99999999);

// Prepare columns and placeholders (must match orders table structure)
$sql = "
    INSERT INTO orders
        (user_id, total_amount, reference, status, created_at,
         shipping_full_name, shipping_email, shipping_street, shipping_city, shipping_state, shipping_phone)
    VALUES (?, ?, ?, 'pending', NOW(), ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("payment_cod.php prepare failed: " . $conn->error);
    flash('Server error. Please try again later.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Build params array in correct order
$user_id = (int)($_SESSION['user_id'] ?? 0);

// params: user_id (i), total (d), reference (s), shipping_full_name (s), shipping_email (s),
// shipping_street (s), shipping_city (s), shipping_state (s), shipping_phone (s)
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

// Build types string: 'i' + 'd' + remaining 's'
$types = 'i' . 'd' . str_repeat('s', count($params) - 2);

// Bind dynamically using call_user_func_array with references
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    // mysqli requires variables by reference
    $bind_names[] = & $params[$i];
}

$bindResult = call_user_func_array([$stmt, 'bind_param'], $bind_names);
if ($bindResult === false) {
    error_log("payment_cod.php bind_param failed: " . $stmt->error);
    flash('Server error. Please try again later.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Execute
if (!$stmt->execute()) {
    error_log("payment_cod.php execute failed: " . $stmt->error);
    flash('Failed to create order. Please try again.');
    $stmt->close();
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

$order_id = $stmt->insert_id;
$stmt->close();

// Insert order items
$stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
if ($stmtItem === false) {
    error_log("payment_cod.php stmtItem prepare failed: " . $conn->error);
    flash('Server error inserting order items. Contact support.');
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

foreach ($_SESSION['cart'] as $pid => $item) {
    $pid_int = (int)$pid;
    $qty = (int)$item['quantity'];
    $price = (float)$item['price'];
    if (!$stmtItem->bind_param('iiid', $order_id, $pid_int, $qty, $price)) {
        error_log("payment_cod.php stmtItem bind_param failed: " . $stmtItem->error);
        continue;
    }
    if (!$stmtItem->execute()) {
        error_log("payment_cod.php stmtItem execute failed for pid={$pid_int}: " . $stmtItem->error);
    }
}
$stmtItem->close();

// Clear cart and any temp reference
unset($_SESSION['cart']);
unset($_SESSION['current_order_reference']);

// Optionally persist shipping into session (so account/orders page can display)
$_SESSION['shipping_full_name'] = $shipping_full_name;
$_SESSION['shipping_email'] = $shipping_email;
$_SESSION['shipping_street'] = $shipping_street;
$_SESSION['shipping_city'] = $shipping_city;
$_SESSION['shipping_state'] = $shipping_state;
$_SESSION['shipping_phone'] = $shipping_phone;

flash('Order placed successfully. You can pay on delivery.');
header('Location: ' . BASE_URL . 'account/orders.php');
exit;
