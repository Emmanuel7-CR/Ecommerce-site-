<?php
// public/payment_failed.php
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// No need to clear cart — user may retry payment
flash('Payment was cancelled or failed. Please try again.');

// Redirect back to cart or checkout
header('Location: ' . BASE_URL . 'cart.php');
exit;
