<?php
// Enable error logging to a file instead of showing to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_reporting(E_ALL);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "ecommerce_store";

// Create connection using MySQLi (Object-oriented)
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    // Show generic error to users
    http_response_code(500);
    die("An internal error occurred. Please try again later.");
}
?>
