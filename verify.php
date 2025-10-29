<?php
require_once __DIR__.'/includes/db_connect.php';
require_once __DIR__.'/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if no verification session set
if (empty($_SESSION['verify_email'])) {
    flash('No email or phone to verify.');
    header('Location: ' . BASE_URL . 'register.php');
    exit;
}

$verifyKey = $_SESSION['verify_email']; // e.g., 'PHONE-+2348012345678' or actual email
$errors = [];
$success = false;
$testing_code = $_SESSION['phone_verification_code'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === '') {
        $errors[] = 'Please enter the verification code.';
    } else {
        // Determine if email or phone registration
        if (str_starts_with($verifyKey, 'PHONE-')) {
            $phone = substr($verifyKey, 6);
            $stmt = $conn->prepare("SELECT id, name, role, verification_code, verification_expires FROM users WHERE phone=?");
            $stmt->bind_param('s', $phone);
        } else {
            $stmt = $conn->prepare("SELECT id, name, role, verification_code, verification_expires FROM users WHERE email=?");
            $stmt->bind_param('s', $verifyKey);
        }

        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = 'User not found.';
        } elseif ($user['verification_code'] !== $code) {
            $errors[] = 'Invalid verification code.';
        } elseif (strtotime($user['verification_expires']) < time()) {
            $errors[] = 'Code has expired. Please request a new one.';
        } else {
            // Verified! Update status and clear code
            $stmt = $conn->prepare("UPDATE users SET status='verified', verification_code=NULL, verification_expires=NULL WHERE id=?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();

            // Auto-login
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Clear session verification info
            unset($_SESSION['verify_email'], $_SESSION['phone_verification_code']);

            flash('Account verified successfully! Welcome.');
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
}

require_once __DIR__.'/includes/header.php';
?>

<h1>Account Verification</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul>
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($testing_code): ?>
<div class="alert alert-info">
    <strong>Test Verification Code:</strong> <?= e($testing_code) ?>
</div>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>verify.php" class="row g-3" id="verifyForm">
    <div class="col-md-6">
        <label class="form-label">Verification Code</label>
        <input type="text" name="code" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">Verify</button>
    </div>
</form>

<div class="mt-2">
    <span id="countdown">60</span> seconds remaining.
    <button id="resendBtn" class="btn btn-link" disabled>Resend Code</button>
</div>

<script>
let countdown = 60;
const countdownEl = document.getElementById('countdown');
const resendBtn = document.getElementById('resendBtn');

const timer = setInterval(() => {
    countdown--;
    countdownEl.textContent = countdown;
    if (countdown <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        countdownEl.textContent = '0';
    }
}, 1000);

resendBtn.addEventListener('click', () => {
    resendBtn.disabled = true;
    fetch('resend_code.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                countdown = 60;
                countdownEl.textContent = countdown;
                const newTimer = setInterval(() => {
                    countdown--;
                    countdownEl.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(newTimer);
                        resendBtn.disabled = false;
                    }
                }, 1000);
                alert('A new verification code has been sent!');
            } else {
                alert('Error sending code. Try again later.');
                resendBtn.disabled = false;
            }
        });
});
</script>
