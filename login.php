<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$activeTab = $_POST['mode'] ?? 'email';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($activeTab === 'email') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || $password === '') {
            $errors[] = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM users WHERE email=? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: index.php');
                exit;
            }
        }
    } elseif ($activeTab === 'phone') {
        $phone = trim($_POST['phone'] ?? '');
        // Ensure +234 prefix
        if (!str_starts_with($phone, '+234')) $phone = '+234' . preg_replace('/^\+?234?/', '', $phone);

        if ($phone === '' || $password === '') {
            $errors[] = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM users WHERE phone=? LIMIT 1");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: index.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container mt-5" style="max-width:400px;">
    <h2 class="text-center mb-4">Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
          <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <a class="nav-link <?= $activeTab==='email'?'active':'' ?>" href="#" onclick="switchLoginTab('email')">Email</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab==='phone'?'active':'' ?>" href="#" onclick="switchLoginTab('phone')">Phone</a>
      </li>
    </ul>

    <!-- Email Form -->
    <form method="post" action="login.php" id="loginEmailForm" style="<?= $activeTab==='email'?'':'display:none' ?>">
        <input type="hidden" name="mode" value="email">
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3 position-relative">
            <label>Password</label>
            <input type="password" name="password" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>
        <button class="btn btn-primary w-100">Login</button>
    </form>

    <!-- Phone Form -->
    <form method="post" action="login.php" id="loginPhoneForm" style="<?= $activeTab==='phone'?'':'display:none' ?>">
        <input type="hidden" name="mode" value="phone">
        <div class="mb-3">
            <label>Phone Number</label>
            <input type="text" name="phone" id="phoneInput" class="form-control" value="<?= e($_POST['phone'] ?? '+234') ?>" required>
        </div>
        <div class="mb-3 position-relative">
            <label>Password</label>
            <input type="password" name="password" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>
        <button class="btn btn-primary w-100">Login</button>
    </form>
    <div class="text-center mt-3">
    <small>Don't have an account? <a href="register.php">Register here</a></small>
</div>

</div>

<style>
.password-field { padding-right: 2.5rem; }
.toggle-password { font-size: 1.2rem; cursor:pointer; }
@media (max-width:480px){
    .toggle-password { font-size:1rem; right:5px; }
}
</style>

<script>
function switchLoginTab(tab){
    document.getElementById('loginEmailForm').style.display = tab==='email' ? '' : 'none';
    document.getElementById('loginPhoneForm').style.display = tab==='phone' ? '' : 'none';
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');
}

// Eye toggle
document.querySelectorAll('.toggle-password').forEach(function(icon){
    icon.addEventListener('click', function(){
        const input = this.previousElementSibling;
        if(input.type==='password'){ input.type='text'; this.classList.replace('bi-eye','bi-eye-slash'); }
        else{ input.type='password'; this.classList.replace('bi-eye-slash','bi-eye'); }
    });
});

// Lock +234 and allow only digits after it
const phoneInput = document.getElementById('phoneInput');

phoneInput.addEventListener('keydown', function(e){
    const prefix = '+234';

    // Ensure prefix
    if (!this.value.startsWith(prefix)) this.value = prefix;

    // Prevent deleting the prefix
    if ((this.selectionStart <= prefix.length) && (e.key==='Backspace'||e.key==='Delete')) {
        e.preventDefault();
        return;
    }

    // Allow control keys (arrow, tab, etc.)
    const allowedKeys = ['ArrowLeft','ArrowRight','Tab','Home','End'];
    if (allowedKeys.includes(e.key)) return;

    // Allow digits only
    if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
    }
});

// Fix input if user pastes text with letters
phoneInput.addEventListener('input', function(){
    const prefix = '+234';
    let numbers = this.value.slice(prefix.length).replace(/\D/g,''); // remove non-digits
    this.value = prefix + numbers;
});

</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>
