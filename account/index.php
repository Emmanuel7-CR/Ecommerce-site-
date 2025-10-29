<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(); // ensure user is logged in

require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Account</h1>

<div class="row g-3 mt-4">
  <!-- Profile -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>account/profile.php" class="text-decoration-none">
      <div class="card h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Profile</h5>
          <p class="card-text">View and update your personal information.</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Orders -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>account/orders.php" class="text-decoration-none">
      <div class="card h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Orders</h5>
          <p class="card-text">View your order history and details.</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Change Password -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>account/change_password.php" class="text-decoration-none">
      <div class="card h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Change Password</h5>
          <p class="card-text">Update your account password securely.</p>
        </div>
      </div>
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
