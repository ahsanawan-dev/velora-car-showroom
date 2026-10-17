<?php
declare(strict_types=1);

// Copy this file to local.php. Never commit real database credentials.
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=velora_manager;charset=utf8mb4',
    'username' => 'root',
    'password' => '', // Local XAMPP only. Use a dedicated database user on hosting.
    'secure_cookies' => false, // Set true on HTTPS hosting.
];
