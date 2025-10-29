<?php

/**
 * Require the current session to be logged in as admin.
 * If not, redirect to login and exit.
 */
function require_admin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Adjust flash()/BASE_URL as per your functions.php
    if (empty($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
        // Optionally log the unauthorized attempt
        error_log('Unauthorized admin access attempt. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        flash('Admin access required. Please log in with an admin account.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}


// Base URL for building links
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ecommerce-site/');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Return the existing CSRF token (or generate one if not set)
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST/headers
 */
function verify_csrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


/**
 * Require the user to be logged in. If not, redirect to login with return URL.
 */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}


/**
 * Flash message helper
 */
function flash(string $msg): void {
    $_SESSION['flash'] = $msg;
}

/**
 * Get and clear flash
 */
function get_flash(): ?string {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}

/**
 * Validate image file and move original to /uploads.
 * Also generates a 300x300 thumbnail in /uploads/thumbs.
 * Returns the filename (without path) or false on failure.
 */
function handle_uploaded_image(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return false;
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        return false;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = uniqid('img_', true) . '.' . $ext;

    $destOriginal = __DIR__ . '/../uploads/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $destOriginal)) {
        return false;
    }

    // ---- Create thumbnail (300x300px)  ----
    $thumbDest = __DIR__ . '/../uploads/thumbs/' . $name;

    // read original
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($destOriginal); break;
        case 'image/png':  $src = imagecreatefrompng($destOriginal); break;
        case 'image/webp': $src = imagecreatefromwebp($destOriginal); break;
        default: return $name; // no thumb
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    $thumbW = 300;
    $thumbH = 300;

    $thumb = imagecreatetruecolor($thumbW, $thumbH);
    imagecopyresampled($thumb, $src, 0,0, 0,0, $thumbW, $thumbH, $origW, $origH);

    // save the new image
    switch ($mime) {
        case 'image/jpeg': imagejpeg($thumb, $thumbDest); break;
        case 'image/png':  imagepng($thumb,  $thumbDest); break;
        case 'image/webp': imagewebp($thumb, $thumbDest); break;
    }
    imagedestroy($src);
    imagedestroy($thumb);

    return $name;
}

/**
 * Format a number as Naira currency
 */
function format_currency($amount) {
    return '₦' . number_format($amount, 2);
}


/**
 * Verify Paystack transaction
 * @param string $reference
 * @return bool
 */
function verify_paystack_transaction(string $reference): bool
{
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Cache-Control: no-cache',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        error_log("Paystack verification failed (no response) for ref: {$reference}");
        return false;
    }

    $data = json_decode($response, true);

    // Status must be true AND data.status must be 'success'
    return $data['status'] === true && $data['data']['status'] === 'success';
}

