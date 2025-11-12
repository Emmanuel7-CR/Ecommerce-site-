<?php
// includes/config.example.php
// Rename this to config.php on your server and fill in real values

// Base URL — adjust for local vs live
define('BASE_URL', 'http://localhost/ecommerce-site/'); // Local
// define('BASE_URL', 'https://yourdomain.com/');      // Live (cPanel)

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// Paystack (use test keys for dev, live keys for production)
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxx');
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxx');