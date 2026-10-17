<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/domain.php';

$count = 0;
function check(bool $result, string $message): void
{
    global $count;
    if (!$result) { fwrite(STDERR, 'FAIL: ' . $message . "\n"); exit(1); }
    $count++;
}
$valid = ['name' => 'Example sports car', 'category' => 'performance', 'model_year' => '2024', 'price_usd' => '150000.50', 'description' => 'Demonstration car for a portfolio project.', 'image_key' => 'gt3rs', 'is_published' => '1'];
[$car, $errors] = validate_car($valid);
check($errors === [] && $car['is_published'] === 1, 'valid car accepted');
foreach (['-1', '12.345', '1e4', '99999999999', '12 USD'] as $price) {
    [, $errors] = validate_car(array_replace($valid, ['price_usd' => $price]));
    check((bool) $errors, 'invalid price rejected: ' . $price);
}
[$car, $errors] = validate_car(array_replace($valid, ['price_usd' => '', 'is_published' => '0']));
check($car['price_usd'] === null && $car['is_published'] === 0 && !$errors, 'blank price and draft accepted');
foreach (['name' => '', 'category' => 'unknown', 'model_year' => '1900', 'image_key' => '../../config/local.php', 'description' => 'short'] as $key => $value) {
    [, $errors] = validate_car(array_replace($valid, [$key => $value]));
    check((bool) $errors, 'invalid ' . $key . ' rejected');
}
[, $errors] = validate_enquiry(['name' => 'Demo Person', 'email' => 'demo@example.com', 'message' => 'A fictional test enquiry.']);
check($errors === [], 'valid enquiry accepted');
foreach (['name' => '', 'email' => 'not-an-email', 'message' => 'short'] as $key => $value) {
    [, $errors] = validate_enquiry(array_replace(['name' => 'Demo', 'email' => 'demo@example.com', 'message' => 'A fictional test message.'], [$key => $value]));
    check((bool) $errors, 'invalid enquiry ' . $key . ' rejected');
}
check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML output escaped');
check(field(['name' => ['bad']], 'name') === '', 'array input is not treated as text');
check(car_image(['image_key' => 'gt3rs']) === 'photo.php?key=gt3rs', 'only allowlisted image endpoint used');
$password = ' Test password with spaces ';
check(password_verify($password, password_hash($password, PASSWORD_DEFAULT)), 'password hashing round trip');
echo "Passed $count validation and output checks.\n";
