<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request (CSRF).';
    }

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $new)) {
        $errors[] = 'New password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[\W_]/', $new)) {
        $errors[] = 'New password must contain at least one symbol (e.g. !@#$%).';
    }

    // Verify current password
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_column();
        $stmt->close();

        if (!$hash || !password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    // Update password
    if (empty($errors)) {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $new_hash, $user_id);

        if ($stmt->execute()) {
            session_regenerate_id(true);
            flash('Password updated successfully.');
            $stmt->close();
            header('Location: ' . BASE_URL . 'account/change_password.php');
            exit;
        }

        $errors[] = 'Unable to change password.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Change Password</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($m = get_flash()): ?>
<div class="alert alert-info"><?= e($m) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>account/change_password.php" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

    <div class="col-md-6">
      <label class="form-label">Current password</label>
      <input type="password" name="current_password" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">New password</label>
      <input type="password" name="new_password" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Confirm new password</label>
      <input type="password" name="confirm_password" class="form-control" required>
    </div>

    <div class="col-12">
      <button class="btn btn-primary">Update password</button>
      <a href="<?= BASE_URL ?>account/index.php" class="btn btn-secondary ms-2">Back to Account</a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
