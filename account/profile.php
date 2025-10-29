<?php
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = (int)$_SESSION['user_id'];

/* Get user details */
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Get default address */
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$address = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="container mt-4">
  <div class="row">
    <!-- ACCOUNT DETAILS -->
    <div class="col-md-6 mb-3">
      <div class="card">
        <div class="card-header">Account Details</div>
        <div class="card-body">
          <p><strong>Full Name:</strong> <?= e($user['name']) ?></p>
          <p><strong>Email:</strong> <?= e($user['email']) ?></p>
        </div>
      </div>
    </div>

    <!-- DEFAULT ADDRESS -->
    <div class="col-md-6 mb-3">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Your Default Shipping Address</span>
          <button class="btn btn-sm btn-link p-0" id="editAddressBtn">
            <i class="bi bi-pencil"></i>
          </button>
        </div>
        <div class="card-body" id="addressDisplay">
          <?php if ($address): ?>
            <p><?= e($address['full_name']) ?></p>
            <p><?= e($address['street']) ?></p>
            <p><?= e($address['city']) ?>, <?= e($address['state']) ?></p>
            <p><?= e($address['phone']) ?></p>
          <?php else: ?>
            <p>No default address saved.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADDRESS MODAL -->
<div class="modal fade" id="addressModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="addressForm">
      <div class="modal-header">
        <h5 class="modal-title">Edit Address</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" required
                 value="<?= e($address['full_name'] ?? '') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Street / Address</label>
          <input type="text" name="street" class="form-control" required
                 value="<?= e($address['street'] ?? '') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" required
                 value="<?= e($address['city'] ?? '') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">State</label>
          <input type="text" name="state" class="form-control" required
                 value="<?= e($address['state'] ?? '') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" required
                 value="<?= e($address['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
// Show modal
document.getElementById('editAddressBtn').addEventListener('click', function(){
    new bootstrap.Modal(document.getElementById('addressModal')).show();
});

// Handle AJAX submit
document.getElementById('addressForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('update_address.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if(data.success){
            // Replace the card body with updated HTML
            document.getElementById('addressDisplay').innerHTML = data.html;
            bootstrap.Modal.getInstance(document.getElementById('addressModal')).hide();
          } else {
            alert('Failed to update address.');
          }
        });
});
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
