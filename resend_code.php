<?php
require_once __DIR__.'/includes/db_connect.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/vendor/autoload.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['verify_email'])) {
    echo json_encode(['success' => false]);
    exit;
}

$email = $_SESSION['verify_email'];
$code = rand(100000, 999999);
$expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

// Save code to DB
$stmt = $conn->prepare("UPDATE users SET verification_code=?, verification_expires=? WHERE email=?");
$stmt->bind_param('sss', $code, $expires, $email);
$stmt->execute();
$stmt->close();

// Send email
try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'electromart0002@gmail.com';
    $mail->Password   = 'hhtk vyuq qyps twxm'; // Gmail App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('electromart0002@gmail.com', 'ElectroMart');
    $mail->addReplyTo('no-reply@electromart.com', 'No Reply');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Email Verification Code';
    $mail->Body    = 'Your verification code is: <strong>'.$code.'</strong>';

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Resend code failed: '.$mail->ErrorInfo);
    echo json_encode(['success' => false]);
}
