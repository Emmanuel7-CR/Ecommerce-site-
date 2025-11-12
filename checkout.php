<?php
// checkout.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

// Helper to get flash message (in case your functions.php doesn't have it)
if (!function_exists('get_flash')) {
    function get_flash() {
        if (!empty($_SESSION['flash'])) {
            $msg = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $msg;
        }
        return '';
    }
}

// Ensure user email in session
if (empty($_SESSION['user_email'])) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $_SESSION['user_email'] = $email;
    $stmt->close();
}

if (empty($_SESSION['cart'])) {
    flash('Your cart is empty.');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$errors = [];
$user_id = (int)$_SESSION['user_id'];
$delivery_fee = 1000;

// Calculate subtotal
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $delivery_fee;

// Get any flash message (e.g., from payment_cod.php redirect)
$flash_message = get_flash();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1>Checkout</h1>

    <?php if (!empty($flash_message)): ?>
        <div class="alert alert-warning"><?= e($flash_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo '<li>' . e($e) . '</li>'; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Shipping & Payment
                </div>
                <div class="card-body">
                    <form id="checkoutForm" method="post" action="<?= BASE_URL ?>payment_cod.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                        <!-- Shipping Details (Left Side) -->
                        <div class="col-md-6">
                            <h5>Shipping Details</h5>

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="shipping_full_name" class="form-control"
                                       value="<?= e($_SESSION['user_name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="shipping_email" class="form-control"
                                       value="<?= e($_SESSION['user_email'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="shipping_phone" class="form-control"
                                       value="<?= e($_SESSION['shipping_phone'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Street Address</label>
                                <textarea name="shipping_street" class="form-control" rows="2" required><?= e($_SESSION['shipping_street'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" name="shipping_city" class="form-control"
                                           value="<?= e($_SESSION['shipping_city'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State</label>
                                    <input type="text" name="shipping_state" class="form-control"
                                           value="<?= e($_SESSION['shipping_state'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary & Payment (Right Side) -->
                        <div class="col-md-6">
                            <h5>Order Summary</h5>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <p>Subtotal: <strong><?= number_format($subtotal, 2) ?> NGN</strong></p>
                                    <p>Delivery Fee: <strong><?= number_format($delivery_fee, 2) ?> NGN</strong></p>
                                    <hr>
                                    <p>Total: <strong><?= number_format($total, 2) ?> NGN</strong></p>
                                </div>
                            </div>

                            <h5>Payment Method</h5>
                            <div class="d-grid gap-2">
                                <button id="paystackBtn" class="btn btn-success" type="button" disabled>
                                    Pay with Paystack
                                </button>
                                <button type="submit" class="btn btn-outline-primary">
                                    Pay on Delivery
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const paystackBtn = document.getElementById('paystackBtn');
const shippingFields = [
    'shipping_full_name',
    'shipping_email',
    'shipping_phone',
    'shipping_street',
    'shipping_city',
    'shipping_state'
];

function validateShipping() {
    for (let name of shippingFields) {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || !field.value.trim()) return false;
        if (name === 'shipping_email') {
            const email = field.value.trim();
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regex.test(email)) return false;
        }
    }
    return true;
}

function togglePaystackBtn() {
    paystackBtn.disabled = !validateShipping();
}

shippingFields.forEach(name => {
    const field = document.querySelector(`[name="${name}"]`);
    if (field) field.addEventListener('input', togglePaystackBtn);
});
togglePaystackBtn();

paystackBtn.addEventListener('click', function() {
    if (!validateShipping()) return;

    const email = document.querySelector('input[name="shipping_email"]').value.trim();
    const handler = PaystackPop.setup({
        key: "<?= PAYSTACK_PUBLIC_KEY ?>",
        email: email,
        amount: <?= (int)($total * 100) ?>,
        currency: "NGN",
        ref: "ECM-" + Math.floor(Math.random() * 10000000 + 1),
        callback: function(response) {
            // Submit shipping data to payment_success.php via POST
            const form = document.createElement('form');
            form.method = 'post';
            form.action = "<?= BASE_URL ?>payment_success.php?ref=" + response.reference;

            shippingFields.forEach(name => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = document.querySelector(`[name="${name}"]`).value.trim();
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        },
        onClose: function() {
            alert('Payment window closed.');
        }
    });
    handler.openIframe();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>