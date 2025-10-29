<?php
require_once __DIR__.'/includes/db_connect.php';
require_once __DIR__.'/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If a remember_token cookie exists, remove it from DB and delete cookie
if (!empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    // remove from DB
    $stmt = $conn->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();

    // delete cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy full session
$_SESSION = [];
session_destroy();

flash('You have been logged out.');
header('Location: ' . BASE_URL . 'login.php');
exit;
