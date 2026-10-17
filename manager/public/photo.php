<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/domain.php';
$key = field($_GET, 'key');
if (!isset(CAR_IMAGES[$key])) { http_response_code(404); exit; }
$path = dirname(__DIR__, 2) . '/assets/images/' . CAR_IMAGES[$key][1];
if (!is_file($path)) { http_response_code(404); exit; }
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
readfile($path);
