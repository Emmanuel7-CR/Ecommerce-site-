<?php
// debug-create-admin.php  (run once, then delete)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db_connect.php';

$name = 'Site Admin';
$email = 'electromart@admin.com';
$password = 'admin'; // change after login
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';

try {
    // check if an admin with this email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "User with email {$email} already exists.\\n";
        $stmt->close();
        exit;
    }
    $stmt->close();

    // insert admin
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password_hash, role, status, created_at) 
         VALUES (?, ?, ?, ?, 'verified', NOW())"
    );
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ssss', $name, $email, $hash, $role);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    echo "Inserted admin id: " . $conn->insert_id . "<br>\n";
    echo "Email: {$email}<br>\n";
    echo "Password (plain-text for first login): {$password}<br>\n";
    echo "IMPORTANT: delete this file immediately after confirming login.<br>\n";

    $stmt->close();
} catch (Exception $ex) {
    echo "Error: " . htmlspecialchars($ex->getMessage());
}
