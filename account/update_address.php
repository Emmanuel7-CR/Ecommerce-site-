<?php
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false]);
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$fullName  = trim($_POST['full_name'] ?? '');
$street    = trim($_POST['street']    ?? '');
$city      = trim($_POST['city']      ?? '');
$state     = trim($_POST['state']     ?? '');
$phone     = trim($_POST['phone']     ?? '');

if ($fullName === '' || $street === '' || $city === '' || $state === '' || $phone === '') {
    echo json_encode(['success'=>false]);
    exit;
}

// Check if a default address already exists
$stmt = $conn->prepare("SELECT id FROM user_addresses WHERE user_id=? AND is_default=1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res  = $stmt->get_result();
$addr = $res->fetch_assoc();
$stmt->close();

if ($addr) {
    // update existing
    $id   = $addr['id'];
    $stmt = $conn->prepare("UPDATE user_addresses SET full_name=?, street=?, city=?, state=?, phone=? WHERE id=?");
    $stmt->bind_param('sssssi', $fullName, $street, $city, $state, $phone, $id);
    $stmt->execute();
    $stmt->close();
} else {
    // insert as default
    $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, street, city, state, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param('isssss', $userId, $fullName, $street, $city, $state, $phone);
    $stmt->execute();
    $stmt->close();
}

// Build HTML to return
$html  = "<p>".e($fullName)."</p>";
$html .= "<p>".e($street)."</p>";
$html .= "<p>".e($city).', '.e($state)."</p>";
$html .= "<p>".e($phone)."</p>";

echo json_encode(['success'=>true,'html'=>$html]);
