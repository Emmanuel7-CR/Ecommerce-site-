<?php
// includes/admin_auth.php
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    flash('You must be an admin to access that page.');
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '/admin/index.php';
    header('Location: /public/login.php');
    exit;
}
