<?php
// checkout.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

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

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1>Checkout</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo '<li>' . e($e) . '</li>'; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Shipping Form Card -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Shipping Details
                </div>
                <div class="card-body">
                    <form id="shippingForm" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="shipping_full_name" class="form-control"
                                   value="<?= e($_SESSION['user_name'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="shipping_email" class="form-control"
                                   value="<?= e($_SESSION['user_email'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Phone</label>
                            <input type="text" name="shipping_phone" class="form-control"
                                   value="<?= e($_SESSION['shipping_phone'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Street Address</label>
                            <textarea name="shipping_street" class="form-control" rows="2"
                                      required><?= e($_SESSION['shipping_street'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="shipping_city" class="form-control"
                                   value="<?= e($_SESSION['shipping_city'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="shipping_state" class="form-control"
                                   value="<?= e($_SESSION['shipping_state'] ?? '') ?>" required>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary Card -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    Order Summary
                </div>
                <div class="card-body">
                    <p>Subtotal: <strong><?= number_format($subtotal, 2) ?> NGN</strong></p>
                    <p>Delivery Fee: <strong><?= number_format($delivery_fee, 2) ?> NGN</strong></p>
                    <hr>
                    <p>Total: <strong><?= number_format($total, 2) ?> NGN</strong></p>

                    <!-- Payment Options -->
                    <form id="codForm" method="post" action="<?= BASE_URL ?>payment_cod.php">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <?php foreach (['shipping_full_name','shipping_email','shipping_phone','shipping_street','shipping_city','shipping_state'] as $field): ?>
                            <input type="hidden" name="<?= $field ?>" value="<?= e($_SESSION[$field] ?? '') ?>">
                        <?php endforeach; ?>
                        <div class="d-grid gap-2 mt-3">
                            <button id="paystackBtn" class="btn btn-success" type="button" disabled>
                                Pay with Paystack
                            </button>
                            <button class="btn btn-outline-primary" type="submit">
                                Pay on Delivery
                            </button>
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
    const email = document.querySelector('input[name="shipping_email"]').value.trim();
    const handler = PaystackPop.setup({
        key: "<?= PAYSTACK_PUBLIC_KEY ?>",
        email: email,
        amount: <?= (int)($total * 100) ?>,
        currency: "NGN",
        ref: "ECM-" + Math.floor(Math.random() * 10000000 + 1),
        callback: function(response) {
            // Send POST with shipping info to payment_success.php
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
