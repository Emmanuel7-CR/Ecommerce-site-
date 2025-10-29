<?php
require_once __DIR__.'/header.php';

if (isset($_POST['change_role'])) {
    $uid = (int)$_POST['user_id'];
    $role = $_POST['role'] ?? 'user';
    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param('si', $role, $uid);
    $stmt->execute();
    $stmt->close();
    flash('Role updated.');
    header('Location: users.php'); exit;
}

// fetch users
$stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h1>Users</h1>
<div class="mb-3">
  <a class="btn btn-outline-info" href="export_users.php">Export CSV</a>
</div>

<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created</th><th></th></tr></thead>
  <tbody>
  <?php foreach($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><?= e($u['name']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($u['phone']) ?></td>
      <td><?= e($u['role']) ?></td>
      <td><?= e($u['created_at']) ?></td>
      <td>
        <form method="post" class="d-inline">
          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
          <select name="role" class="form-select form-select-sm d-inline" style="width:auto;display:inline-block">
            <option value="user" <?= $u['role']==='user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $u['role']==='admin' ? 'selected' : '' ?>>Admin</option>
          </select>
          <button name="change_role" class="btn btn-sm btn-outline-primary">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__.'/footer.php'; ?>
