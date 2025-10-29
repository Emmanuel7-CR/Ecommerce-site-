<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) session_start();

$activeTab = $_POST['mode'] ?? 'email';       // email or phone
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    // Registration with EMAIL
    if ($activeTab === 'email') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone IS NOT NULL LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = 'You already have an account. Please log in.';
        $stmt->close();

        if ($name === '' || $email === '' || $password === '' || $confirm === '') {
            $errors[] = 'Please fill in all required fields.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Z]/',$password) || !preg_match('/[\W_]/',$password)) {
            $errors[] = 'Password must be at least 8 chars and contain one uppercase and one symbol.';
        }

        if (empty($errors)) {
            $code    = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 300);
            $hash    = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name,email,password_hash,status,verification_code,verification_expires) 
                 VALUES (?,?,?,?,?,?)"
            );
            $status='unverified';
            $stmt->bind_param('ssssss',$name,$email,$hash,$status,$code,$expires);
            $stmt->execute();
            $stmt->close();

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'electromart0002@gmail.com';
            $mail->Password   = 'hhtk vyuq qyps twxm';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            $mail->setFrom('electromart0002@gmail.com', 'ElectroMart');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification Code';
            $mail->Body    = 'Your verification code is: <strong>'.$code.'</strong>';
            $mail->send();

            $_SESSION['verify_email'] = $email;
            header('Location: verify.php');
            exit;
        }
    }

    // Registration with PHONE
    if ($activeTab === 'phone') {
        $name     = trim($_POST['name_p'] ?? '');
        $phone    = trim($_POST['phone']  ?? '');
        $password = $_POST['password_p'] ?? '';
        $confirm  = $_POST['password_confirm_p'] ?? '';

        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR email IS NOT NULL LIMIT 1");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = 'You already have an account. Please log in.';
        $stmt->close();

        if ($name === '' || $phone === '' || $password === '' || $confirm === '') {
            $errors[] = 'Please fill in all required fields.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Z]/',$password) || !preg_match('/[\W_]/',$password)) {
            $errors[] = 'Password must be at least 8 chars and contain one uppercase and one symbol.';
        }

        if (empty($errors)) {
            $code    = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 300);
            $hash    = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name,phone,password_hash,status,verification_code,verification_expires) 
                 VALUES (?,?,?,?,?,?)"
            );
            $status='unverified';
            $stmt->bind_param('ssssss',$name,$phone,$hash,$status,$code,$expires);
            $stmt->execute();
            $stmt->close();

            // For testing, store code in session
            $_SESSION['verify_email'] = 'PHONE-'.$phone;
            $_SESSION['phone_verification_code'] = $code;
            header('Location: verify.php');
            exit;
        }
    }

    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data']   = $_POST;
    header("Location: register.php");
    exit;
}

$form_errors = $_SESSION['form_errors'] ?? [];
$form_data   = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Include Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container mt-4">
    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link <?= ($activeTab==='email')?'active':'' ?>" href="#" onclick="switchTab('email')">Register with Email</a></li>
        <li class="nav-item"><a class="nav-link <?= ($activeTab==='phone')?'active':'' ?>" href="#" onclick="switchTab('phone')">Register with Phone</a></li>
    </ul>

    <?php if (!empty($form_errors)): ?>
      <div class="alert alert-danger mt-3">
          <ul><?php foreach ($form_errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- EMAIL FORM -->
    <form method="post" action="register.php" class="mt-3" id="formEmail" style="<?= ($activeTab==='email')?'':'display:none' ?>">
        <input type="hidden" name="mode" value="email">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

        <div class="mb-2"><label>Full name</label><input type="text" name="name" value="<?= e($form_data['name'] ?? '') ?>" class="form-control" required></div>
        <div class="mb-2"><label>Email</label><input type="email" name="email" value="<?= e($form_data['email'] ?? '') ?>" class="form-control" required></div>

        <div class="mb-2 position-relative">
            <label>Password</label>
            <input type="password" name="password" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>

        <div class="mb-2 position-relative">
            <label>Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>

        <button class="btn btn-primary">Create Account</button>
    </form>

    <!-- PHONE FORM -->
    <form method="post" action="register.php" class="mt-3" id="formPhone" style="<?= ($activeTab==='phone')?'':'display:none' ?>">
        <input type="hidden" name="mode" value="phone">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

        <div class="mb-2"><label>Full name</label><input type="text" name="name_p" value="<?= e($form_data['name_p'] ?? '') ?>" class="form-control" required></div>
        <div class="mb-2"><label>Phone Number</label><input type="text" name="phone" value="<?= e($form_data['phone'] ?? '+234') ?>" class="form-control" required></div>

        <div class="mb-2 position-relative">
            <label>Password</label>
            <input type="password" name="password_p" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>

        <div class="mb-2 position-relative">
            <label>Confirm Password</label>
            <input type="password" name="password_confirm_p" class="form-control password-field" required>
            <i class="bi bi-eye toggle-password" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
        </div>

        <button class="btn btn-primary">Create Account</button>
    </form>
</div>

<style>
.password-field {
    padding-right: 2.5rem;
}
.toggle-password {
    font-size: 1.2rem;
}
@media (max-width: 480px) {
    .toggle-password {
        font-size: 1rem;
        right: 5px;
    }
}
</style>

<script>
function switchTab(mode){
    document.querySelector('.nav-link.active').classList.remove('active');
    document.querySelector('[onclick="switchTab(\''+mode+'\')"]').classList.add('active');
    document.getElementById('formEmail').style.display  = (mode==='email') ? '' : 'none';
    document.getElementById('formPhone').style.display  = (mode==='phone') ? '' : 'none';
}

document.querySelectorAll('.toggle-password').forEach(function(icon){
    icon.addEventListener('click', function(){
        const input = this.previousElementSibling;
        if(input.type === 'password'){
            input.type = 'text';
            this.classList.remove('bi-eye');
            this.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('bi-eye-slash');
            this.classList.add('bi-eye');
        }
    });
});
</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>
