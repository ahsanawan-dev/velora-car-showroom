<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Use the sign-out button.'); }
require_csrf();
$_SESSION = [];
session_regenerate_id(true);
redirect('login.php');
