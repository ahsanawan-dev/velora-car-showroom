<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';

echo "Create the first VELORA administrator\n";
if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    fwrite(STDERR, "An admin already exists. This first-admin setup is locked.\n");
    exit(1);
}
echo 'Username (letters, numbers, _ or -, 3–80 characters): ';
$username = trim((string) fgets(STDIN));
if (!preg_match('/^[A-Za-z0-9_-]{3,80}$/D', $username)) {
    fwrite(STDERR, "Invalid username.\n"); exit(1);
}
echo "Password (12–72 bytes; visible in this local terminal): ";
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Use a password between 12 and 72 bytes.\n"); exit(1);
}
$q = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
$q->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
echo "Admin created. Open manager/public/login.php in your browser.\n";
