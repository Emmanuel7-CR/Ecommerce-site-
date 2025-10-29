<?php
// admin/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// auto-login via remember_token (if header.php is used site-wide it may already handle this)
// but keep safe check
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $now   = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        SELECT users.id, users.name, users.role
        FROM user_remember_tokens
        JOIN users ON users.id = user_remember_tokens.user_id
        WHERE user_remember_tokens.token = ? AND user_remember_tokens.expires_at > ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['user_role'] = $row['role'];
    }
}

// Require login + admin role
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    flash('Admin area — please login with an admin account.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Admin page header (uses Bootstrap, keep consistent with your site)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — <?= e($_SESSION['user_name'] ?? 'Admin') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE_URL ?>admin/index.php">Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/orders.php">Orders</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/users.php">Users</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><span class="nav-link">Hi, <?= e($_SESSION['user_name'] ?? '') ?></span></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container mt-4">
